<?php

namespace App\Utils;

use App\Models\Document;
use App\Traits\TSingleton;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DocumentUtils {

    use TSingleton;

    /**
     * @param Worksheet $worksheet - документ для работы
     * @param string $tableName - название таблицы
     * @param array $options - ignoreRowAddress - буквенные адреса, которые будут игнорироваться (например: A, B, C),
     * columnsNameLine - номер колонки сверху, с которой начнется выгрузка (0 уровень - номера колонок A, B, C...),
     * primaryKey - первичный ключ таблицы
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function excelToMysql(Worksheet $worksheet, string $tableName, array $options = [
        'ignoreRowAddress' => [],
        'columnsNameLine' => 0,
        'primaryKey' => null
    ]): void {
        ['ignoreRowAddress' => $ignoreRowAddress, 'columnsNameLine' => $columnsNameLine, 'primaryKey' => $primaryKey] = $options;
        $columns = [];

        // Получаем количество колонок
        $columnsCount = Coordinate::columnIndexFromString($worksheet->getHighestColumn());

        // Заполняем массив columns названиями колонок
        for ($index = 1; $index <= $columnsCount; $index++) {
            if ($columnsNameLine == 0) {
                $columns[] = "column" . $index;
                continue;
            }

            $columnName = $worksheet->getCellByColumnAndRow($index, $columnsNameLine)->getCalculatedValue();

            if (!$columnName || str_contains($columnName, 'NULL')) {
                continue;
            }

            $columns[] = $columnsNameLine == 1 ? $this->formatColumnName($columnName) : $columnName;
        }

        // Удаляем старую таблицу
        Document::dropTable($tableName);
        // Создаем таблицу для заполнения
        Document::createTable($tableName, $columns);

        // Работа со строками
        $ignoreRowIndex = [];

        foreach ($ignoreRowAddress as $address) {
            $ignoreRowIndex[] = Coordinate::columnIndexFromString($address);
        }

        // Получаем количество строк
        $rowsCount = $worksheet->getHighestRow();

        $columnTypes = [];

        // Перебираем строки листа Excel
        for ($rowIndex = $columnsNameLine + 1; $rowIndex <= $rowsCount; $rowIndex++) {
            // Строка со значениями всех столбцов в строке листа Excel
            $values = [];

            // Перебираем столбцы листа Excel
            for ($columnIndex = 1; $columnIndex <= $columnsCount; $columnIndex++) {

                if (in_array($columnIndex, $ignoreRowIndex)) {
                    continue;
                }

                // Ячейка листа Excel
                $cell = $worksheet->getCellByColumnAndRow($columnIndex, $rowIndex);

                // Получаем значение ячейки
                $rawValue = $cell->getCalculatedValue();

                // Если ячейка пустая (#NULL!) то устанавливаем ей значения null
                $value = !str_contains($rawValue, '#NULL!') ? $rawValue : null;

                // Добавляем ячейку в массив
                $values[] = $value;

                // Заполняем массив с типами колонок
                $this->fillColumnTypes($value, $columnIndex, $columnTypes);
            }

            // Записываем строку в базу
            Document::addRow($tableName, $columns, $values);
        }

        // Устанавливаем типы для столбцов
        Document::setColumnTypes($tableName, $this->formatColumnName($primaryKey), $columns, $columnTypes);
    }

    // Преобразуем название колонок из документа
    private function formatColumnName($name): string {
        $editedName = str_replace([' ', '.'], ['_', ''], StringUtils::translateWord($name));

        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $editedName));
    }

    /**
     * @param $value - текущее значение ячейки
     * @param $columnIndex - номер столбца
     * @param $columnTypes - массив с типами столбцов
     * @return void
     */
    private function fillColumnTypes($value, $columnIndex, &$columnTypes): void {
        $newType = StringUtils::getType($value);

        // Если индекса нет в массиве, то добавляем его
        if (!array_key_exists($columnIndex, $columnTypes)) {
            $columnTypes[$columnIndex] = $newType;
            return;
        }

        $type = $columnTypes[$columnIndex];

        // Если тип текущей ячейки null или тип такой же, как в массиве с типами, то выходим
        if ($newType == 'NULL' || $newType == $type)
            return;

        // Если тип в массиве null, а новый тип нет - обновляем
        if ($type == 'NULL') {
            $columnTypes[$columnIndex] = $newType;
            return;
        }

        if ($type != 'string' && preg_match("/[a-z]/i", $value)) {
            $columnTypes[$columnIndex] = 'string';
            return;
        }

        if ($type === 'integer' && $newType == 'double') {
            $columnTypes[$columnIndex] = $newType;
        }
    }
}