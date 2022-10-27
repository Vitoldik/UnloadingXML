<?php

namespace App\Models;

use App\Configs\SQLVariableTypes;
use Core\Model;
use PDO;

class Document extends Model {

    public static function dropTable(string $name): void {
        static::getDB()->query("DROP TABLE IF EXISTS $name");
    }

    public static function getPage(int $start, int $limit, string $params): bool|array {
        $stmt = static::getDB()->prepare("
            SELECT * FROM `variant_1` `t1`" . $params . " LIMIT ?,?
        ");

        $stmt->execute([$start, $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function calcPageAmount(string $params) {
        $stmt = static::getDB()->prepare("
            SELECT COUNT(*) FROM `variant_1` `t1`
        " . $params);

        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public static function createTable(string $name, array $columns, array $primaryKey): void {
        $primaryName = $primaryKey['name'];

        // Удаляем primaryKey, если он есть в списке колонок
        if (($key = array_search($primaryName, $columns)) !== false) {
            unset($columns[$key]);
        }

        $columnsStr = ($primaryKey['increment']
                ? $primaryName . ' INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                : $primaryName . ' ' . $primaryKey['type']) . ',' .
            implode(" TEXT NULL, ", $columns) . " TEXT NULL" . ", PRIMARY KEY ($primaryName)";

        static::getDB()->query("CREATE TABLE $name ($columnsStr)");
    }

    public static function createTableWithTypes(string $name, array $columns, array $primaryKey): void {
        $primaryName = $primaryKey['name'];
        $columnsStr = '';

        foreach ($columns as $key => $type) {
            $columnsStr .= ($key == $primaryName
                    ? $primaryName . ' ' . $primaryKey['type'] . ' PRIMARY KEY NOT NULL'
                    : $key . ' ' . ($type != SQLVariableTypes::NULL
                        ? $type->name
                        : SQLVariableTypes::NULL_REPLACEMENT) . ' NULL') .
                ($key != array_key_last($columns) ? ',' : '');
        }

        static::getDB()->query("CREATE TABLE $name ($columnsStr)");
    }

    public static function addRows(string $tableName, array $columns, string|array $rows): void {
        $columnsStr = join(',', $columns);

        $query = "INSERT INTO $tableName ($columnsStr) VALUES " .
            (is_array($rows) ? join(', ', $rows) : $rows);

        static::getDB()->prepare($query)->execute();
    }

    public static function setColumnTypes(string $table, array $columns, array $columnTypes, array $primaryKey): void {
        $query = "ALTER TABLE $table";

        foreach ($columns as $index => $column) {
            if ($column == $primaryKey['name'])
                continue;

            /**
             * @var SQLVariableTypes $type
             */
            $type = $columnTypes[$index];

            $query .= " MODIFY COLUMN $column " .
                ($type != SQLVariableTypes::NULL ? $type->name : SQLVariableTypes::NULL_REPLACEMENT) .
                ($index != array_key_last($columns) ? ', ' : '');
        }

        static::getDB()->exec($query);
    }
}
