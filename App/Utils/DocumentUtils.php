<?php

namespace App\Utils;

use App\Models\Document;
use App\Traits\TSingleton;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DocumentUtils {

    use TSingleton;

    public function excelToMysql($worksheet, $table_name, $columnsNameLine = 0) {
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

            if (!$columnName || str_contains($columnName, 'NULL'))
                continue;

            $columns[] = $columnsNameLine == 1 ? $this->formatColumnName($columnName) : $columnName;
        }

        // Объединяем колонки в строку используя запятые для разделения
        $columnsStr = join(',', $columns);

        // Удаляем старую таблицу
        Document::dropTable($table_name);
        // Создаем таблицу для заполнения
        Document::createTable($table_name, $columnsStr);
    }

    // Преобразуем название колонок из документа
    public function formatColumnName($name) : string {
        $editedName = str_replace([' ', '.'], ['_', ''], StringUtils::translateWord($name));

        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $editedName));
    }
}