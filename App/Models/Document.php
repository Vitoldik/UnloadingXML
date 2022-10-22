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

    public static function addRows(string $tableName, array $columns, array $rows) : void {
        $columnsStr = join(',', $columns);

        $query = "INSERT INTO $tableName ($columnsStr) VALUES " .
            join(', ', $rows);

        static::getDB()->prepare($query)->execute();
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
