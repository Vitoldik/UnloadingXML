<?php

namespace App\Models;

use App\Configs\SQLVariableTypes;
use Core\Model;

class Document extends Model {

    public static function dropTable(string $name) : void {
        static::getDB()->query("DROP TABLE IF EXISTS $name");
    }

    public static function createTable(string $name, array $columns, array $primaryKey) : void {
        $primaryName = $primaryKey['name'];

        $columnsStr = ($primaryKey['increment']
                ? $primaryName . ' INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                : '') .
            implode(" TEXT NULL, ", $columns) . " TEXT NULL" . ($primaryKey['increment']
                ? ", PRIMARY KEY ($primaryName)"
                : ''
            );

        static::getDB()->query("CREATE TABLE $name ($columnsStr)");
    }

    public static function addRow(string $tableName, array $columns, array $values) : void {
        // Исключаем строки, в которых все значения NULL
        if ($values[0] === null && count(array_unique($values)) === 1) {
            return;
        }

        $countColumns = count($columns);
        $countValues = count($values);

        // Если колонок больше чем значений, то заполняем недостающие нулами
        if ($countColumns > $countValues) {
            $diff = $countColumns - $countValues;
            $array = [];

            for ($i = 0; $i < $diff; $i++)
                $array[] = null;

            $values = array_merge($values, $array);
        }

        $columnsStr = join(',', $columns);
        $placeholders = self::generatePlaceholders($columns);

        $query = "INSERT INTO $tableName ($columnsStr) VALUES ($placeholders)";

        $prepare = static::getDB()->prepare($query);

        $prepare->execute($values);
    }

    // Генерируем вопросительные знаки для подстановки их в query
    private static function generatePlaceholders(array $columns) : string {
        $arr = array_map(function () {
            return "?";
        }, $columns);

        return join(', ', $arr);
    }

    public static function setColumnTypes(string $table, string $primaryKey, array $columns, array $columnTypes): void {
        $query = "ALTER TABLE $table";

        foreach ($columns as $index => $column) {
            /**
             * @var SQLVariableTypes $type
             */
            $type = $columnTypes[$index];
            $isPrimaryKey = $column == $primaryKey;

            $query .= " MODIFY COLUMN $column " .
                ($type != SQLVariableTypes::NULL ? (
                $isPrimaryKey && $type == SQLVariableTypes::TEXT
                    ? SQLVariableTypes::VARCHAR : $type->name)
                    : SQLVariableTypes::NULL_REPLACEMENT
                ) .
                ($isPrimaryKey ? ' PRIMARY KEY' : ' NULL') .
                ($index != array_key_last($columns) ? ', ' : '');
        }

        static::getDB()->exec($query);
    }
}
