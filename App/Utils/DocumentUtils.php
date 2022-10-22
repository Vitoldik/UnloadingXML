<?php

namespace App\Utils;

use App\Configs\SQLVariableTypes;
use App\Models\Document;
use App\Traits\TSingleton;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DocumentUtils {

    use TSingleton;

    /**
     * @param Worksheet $worksheet - документ для работы
     * @param string $tableName - название таблицы
     * @param array $options - массив опций для настройки выгрузки
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function excelToMysql(Worksheet $worksheet, string $tableName, array $options = [
        'paddingLeft' => 0, // Количество колонок для отступа слева (так как в документе вариант 1 он есть)
        'columnsNameLine' => 0, // Номер колонки сверху, с которой начнется выгрузка (0 уровень - номера колонок A, B, C...)
        'primaryKey' => [ // Настройки первичного ключа таблицы
            'name' => 'id',
            'increment' => false
        ]
    ]): void {
        ['paddingLeft' => $paddingLeft, 'columnsNameLine' => $columnsNameLine, 'primaryKey' => $primaryKey] = $options;
        $columns = [];

        // Получаем количество колонок
        $columnsCount = Coordinate::columnIndexFromString($worksheet->getHighestColumn());

        // Заполняем массив columns названиями колонок
        for ($index = 1; $index <= $columnsCount; $index++) {
            if ($columnsNameLine == 0) {
                $columns[$index] = "column" . $index;
                continue;
            }

            $columnName = $worksheet->getCellByColumnAndRow($index, $columnsNameLine)->getCalculatedValue();

            if (!$columnName || str_contains($columnName, 'NULL')) {
                continue;
            }

            $columns[$index] = $columnsNameLine == 1 ? $this->formatColumnName($columnName) : $columnName;
        }

        // Удаляем старую таблицу
        Document::dropTable($tableName);
        // Удаляем дубликаты из названия колонок
        $duplicated = ArrayUtils::getAndRemoveDuplicate($columns);
        // Создаем таблицу для заполнения
        Document::createTable($tableName, $columns, $primaryKey);

        // Работа со строками
        // Получаем количество строк
        $rowsCount = $worksheet->getHighestRow();

        $columnTypes = [];
        $paddingLeft = max($paddingLeft, 0);

        // Перебираем строки листа Excel
        $rows = [];

        for ($rowIndex = $columnsNameLine + 1; $rowIndex <= $rowsCount; $rowIndex++) {
            // Строка со значениями всех столбцов в строке листа Excel
            $values = [];

            // Перебираем столбцы листа Excel
            $startIndex = $paddingLeft + 1;

            for ($columnIndex = $startIndex; $columnIndex <= $columnsCount; $columnIndex++) {

                if (array_key_exists($columnIndex, $duplicated)) {
                    continue;
                }

                // Ячейка листа Excel
                $cell = $worksheet->getCellByColumnAndRow($columnIndex, $rowIndex);

                // Получаем значение ячейки
                $value = $cell->getCalculatedValue();

                if ($value != null && $cell->getDataType() == DataType::TYPE_ERROR) {
                    $value = null;
                }

                // Добавляем ячейку в массив
                $values[] = $value == null ? 'NULL' : "'$value'";

                $indexWithStartPadding = ($startIndex == $columnIndex ? array_key_first($columns) : $columnIndex); // Расчет отступа слева
                $this->fillColumnTypes($value, $indexWithStartPadding, $columnTypes);
            }

            if ($values[0] == 'NULL' && ArrayUtils::areNoUniqueValues($values))
                continue;

            // Записываем строку в массив строк
            $rows[] = SQLUtils::formatInsertValues($values);
        }

        // Записываем строки в базу
        Document::addRows($tableName, $columns, $rows);
        // Устанавливаем типы для столбцов
        Document::setColumnTypes($tableName, $this->formatColumnName($primaryKey['name']), $columns, $columnTypes);
    }

    // Преобразуем название колонок из документа
    // Не знаю, нужен ли транслит :D, но не видел, чтобы кто-то называл столбцы на русском
    private function formatColumnName(string $name): string {
        // убираем текст в скобках, точки и заменяем пробелы на _
        $processedName = StringUtils::translateWord(
            trim(preg_replace(['/\([^)]*(\)?)|[()]|\./', '/\s+/i'], ['', '_'], trim($name)), '_')
        );

        return StringUtils::toSnakeCase($processedName);
    }

    /**
     * @param $value - текущее значение ячейки
     * @param $columnIndex - номер столбца
     * @param $columnTypes - массив с типами столбцов
     * @return void
     */
    private function fillColumnTypes(?string $value, int $columnIndex, array &$columnTypes): void {
        $newType = SQLUtils::getType($value);

        // Если индекса нет в массиве, то добавляем его
        if (!array_key_exists($columnIndex, $columnTypes)) {
            $columnTypes[$columnIndex] = $newType;
            return;
        }

        $type = $columnTypes[$columnIndex];

        // Если тип текущей ячейки null или тип такой же, как в массиве с типами, то выходим
        if ($value == null || $newType == $type || $newType == SQLVariableTypes::NULL)
            return;

        // Если тип в массиве null, а новый тип нет - обновляем
        if ($type == SQLVariableTypes::NULL) {
            $columnTypes[$columnIndex] = $newType;
            return;
        }

        if ($type != SQLVariableTypes::TEXT && preg_match("/[a-z]/i", $value)) {
            $columnTypes[$columnIndex] = SQLVariableTypes::TEXT;
            return;
        }

        if ($type === SQLVariableTypes::INT && $newType == SQLVariableTypes::FLOAT) {
            $columnTypes[$columnIndex] = $newType;
        }
    }
}