<?php

namespace App\Utils;

use App\Config;
use App\Configs\SQLVariableTypes;
use App\Models\Document;
use App\Traits\TSingleton;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use XMLReader;

class DocumentUtils {

    use TSingleton;

    public function loadDocuments() : void {
        $variant1 = IOFactory::load(Config::$APP_DIR . "\Resources\Documents\\variant_1.xlsx");
        $variant2 = file_get_contents(Config::$APP_DIR . "\Resources\Documents\\variant_2.xml");;
        $variant4 = IOFactory::load(Config::$APP_DIR . "\Resources\Documents\\variant_4.xlsx");

        $variant1->setActiveSheetIndex(0);
        $variant4->setActiveSheetIndex(0);

        DocumentUtils::instance()->xlsxToMysql(
            $variant1->getActiveSheet(),
            'variant_1',
            [
                'paddingLeft' => 3,
                'columnsNameLine' => 1,
                'primaryKey' => [
                    'name' => 'Код',
                    'type' => SQLVariableTypes::VARCHAR,
                    'increment' => false
                ],
                'moveColumnToSeparateTable' => ''
            ]
        );

        DocumentUtils::instance()->xlsxToMysql(
            $variant4->getActiveSheet(),
            'variant_4',
            [
                'paddingLeft' => 0,
                'columnsNameLine' => 1,
                'primaryKey' => [
                    'name' => 'ID элемента предложения',
                    'type' => SQLVariableTypes::INT->name,
                    'increment' => false
                ],
                'moveColumnToSeparateTable' => 'Характеристики'
            ]
        );

        DocumentUtils::instance()->xmlToMysql(
            $variant2,
            'variant_2',
            [
                'primaryKey' => [
                    'name' => 'Код',
                    'type' => SQLVariableTypes::VARCHAR
                ]
            ]);
    }

    /**
     * @param Worksheet $worksheet - документ для работы
     * @param string $tableName - название таблицы
     * @param array $options - массив опций для настройки выгрузки
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function xlsxToMysql(Worksheet $worksheet, string $tableName, array $options = [
        'paddingLeft' => 0, // Количество колонок для отступа слева (так как в документе вариант 1 он есть)
        'columnsNameLine' => 0, // Номер колонки сверху, с которой начнется выгрузка (0 уровень - номера колонок A, B, C...)
        'primaryKey' => [ // Настройки первичного ключа таблицы
            'name' => 'id',
            'type' => 'INT',
            'increment' => true
        ],
        'moveColumnToSeparateTable' => ''
    ]): void {
        [
            'paddingLeft' => $paddingLeft,
            'columnsNameLine' => $columnsNameLine,
            'primaryKey' => $primaryKey,
            'moveColumnToSeparateTable' => $moveColumnToSeparateTable
        ] = $options;

        $columns = [];

        // Преобразуем входные опции
        $primaryKey['name'] = $this->formatColumnName($primaryKey['name']);
        $moveColumnToSeparateTable = $this->formatColumnName($moveColumnToSeparateTable);
        $childrenTableName = $tableName . '_' . $moveColumnToSeparateTable;

        // Номер колонки с праймари ключем
        $primaryKeyColumnIndex = 0;

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

            $translatedColumnName = $this->formatColumnName($columnName);

            if ($translatedColumnName == $primaryKey['name']) {
                $primaryKeyColumnIndex = $index;
            }

            $columns[$index] = $columnsNameLine == 1 ? $translatedColumnName : $columnName;
        }

        // Удаляем старую таблицу
        Document::dropTable($tableName);
        // Удаляем дочернюю таблицу
        Document::dropTable($childrenTableName);
        // Удаляем дубликаты из названия колонок
        $duplicated = ArrayUtils::getAndRemoveDuplicate($columns);
        // Ищем индекс колонки, которую необходимо удалить из массива колонок
        $movedColumnIndex = array_search($moveColumnToSeparateTable, $columns);
        // Удаляем колонку, которую необходимо вынести в отдельную таблицу
        unset($columns[$movedColumnIndex]);

        // Создаем таблицу для заполнения
        Document::createTable($tableName, $columns, $primaryKey);

        // Работа со строками
        // Получаем количество строк
        $rowsCount = $worksheet->getHighestRow();

        $columnTypes = [];
        $paddingLeft = max($paddingLeft, 0);

        // Перебираем строки листа Excel
        $rows = [];
        $childrenTableColumns = [$primaryKey['name']];
        $childrenTableInfo = [];

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

                if ($columnIndex == $movedColumnIndex) {
                    $pairs = SQLUtils::formatInsertValueForChildrenTable($childrenTableColumns, $cell->getValue(), ':');
                    $primaryKeyValue = $worksheet->getCellByColumnAndRow($primaryKeyColumnIndex, $rowIndex)->getCalculatedValue();
                    $childrenTableInfo[] = array_merge([$primaryKey['name'] => $primaryKeyValue], $pairs);
                    continue;
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
        Document::setColumnTypes($tableName, $columns, $columnTypes, $primaryKey);
        // Работа с дочерней таблицей
        if (!empty($moveColumnToSeparateTable)) {
            Document::createTable($childrenTableName, $childrenTableColumns, $primaryKey);
            $childrenTableValues = SQLUtils::formatInsertValueWithColumns($childrenTableColumns, $childrenTableInfo);

            Document::addRows($childrenTableName, $childrenTableColumns, $childrenTableValues);
        }
    }

    public function xmlToMysql(string $fileContent, string $tableName, array $options = [
        'primaryKey' => [ // Настройки первичного ключа таблицы
            'name' => 'id',
            'type' => 'INT'
        ]
    ]): void {
        $reader = (new XMLReader());
        $reader->XML($fileContent);

        $primaryKey = $options['primaryKey'];
        $primaryKey['name'] = $this->formatColumnName($primaryKey['name']);
        $columns = [];
        $rows = [];

        while ($reader->read()) {
            if ($reader->name != 'Номенклатура')
                continue;

            $reader->moveToFirstAttribute();
            $value = $reader->value;
            $values = [];

            if (empty($columns)) {
                $columns[$this->formatColumnName($reader->name)] = SQLUtils::getType($value);
            }

            $values[] = $value ? "'$value'" : 'NULL';

            while ($reader->moveToNextAttribute()) {
                $column = $this->formatColumnName($reader->name);
                $value = $reader->value;

                $this->fillColumnTypes($reader->value, $column, $columns);
                $values[] = $value ? "'$value'" : 'NULL';
            }

            $rows[] = SQLUtils::formatInsertValues($values);
        }

        // Удаляем старую таблицу
        Document::dropTable($tableName);
        Document::createTableWithTypes($tableName, $columns, $primaryKey);
        Document::addRows($tableName, array_keys($columns), $rows);
    }

    // Преобразуем название колонок из документа
    // Не знаю, нужен ли транслит :D, но не видел, чтобы кто-то называл столбцы на русском
    public function formatColumnName(string $name): string {
        // убираем текст в скобках, точки и заменяем пробелы на _
        $processedName = StringUtils::translateWord(
            trim(preg_replace(['/\([^)]*(\)?)|[()]|\./', '/[\s-]+/i', '/\//i'], ['', '_', '_ili_'], trim($name)), '_')
        );

        return StringUtils::toSnakeCase($processedName);
    }

    /**
     * @param $value - текущее значение ячейки
     * @param $columnIndex - номер столбца
     * @param $columnTypes - массив с типами столбцов
     * @return void
     */
    private function fillColumnTypes(?string $value, int|string $columnIndex, array &$columnTypes): void {
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