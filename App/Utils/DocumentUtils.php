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
    public function excelToMysql(Worksheet $worksheet, string $tableName, array $ignoreRowAddress, int $columnsNameLine = 0) {
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
                $value = $cell->getCalculatedValue();

                // Добавляем ячейку в массив, если ячейка пустая (#NULL!) то устанавливаем ей значения null
                $values[] =  !str_contains($value, '#NULL!') ? $value : null;
            }

            // Записываем строку в базу
            Document::addRow($tableName, $columns, $values);
        }
    }

    // Преобразуем название колонок из документа
    public function formatColumnName($name): string {
        $editedName = str_replace([' ', '.'], ['_', ''], StringUtils::translateWord($name));

        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $editedName));
    }
}