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
     * @param array $ignoreRowAddress - буквенные адреса, которые будут игнорироваться (например: A, B, C)
     * @param int $columnsNameLine - номер колонки сверху, с которой начнется выгрузка (0 уровень - номера колонок A, B, C...)
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function excelToMysql(Worksheet $worksheet, string $tableName, array $ignoreRowAddress, int $columnsNameLine = 0): void {
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
        $currentType = gettype($value);

        if (array_key_exists($columnIndex, $columnTypes)) {
            $type = $columnTypes[$columnIndex];

            if ($currentType != 'NULL') {
                if ($type == 'NULL') {
                    $columnTypes[$columnIndex] = $currentType;
                } elseif ($type === 'integer' && $currentType === 'double') {
                    $columnTypes[$columnIndex] = $currentType;
                } elseif ($currentType == 'string' && filter_var($value, FILTER_VALIDATE_INT)) {
                    $columnTypes[$columnIndex] = 'integer';
                }
            }
        } else {
            $columnTypes[$columnIndex] = $currentType;
        }
    }
}