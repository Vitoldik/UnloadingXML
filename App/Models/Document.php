<?php

namespace App\Models;

use App\Utils\ArrayUtils;
use App\Utils\DebugUtils;
use Core\Model;

class Document extends Model {

    public static function dropTable($name) {
        static::getDB()->query("DROP TABLE IF EXISTS $name");
    }

    public static function createTable($name, $columns) {
        foreach ($columns as $key => &$column) {
            $column .= " TEXT NULL,";

            $arrayKeys = array_keys($columns);

            if (end($arrayKeys) == $key) // Проверяем, последний ли это ключ массива, чтобы убрать запятую в конце
                $column = substr($column, 0, -1);
        }

        $columnsStr = join('', $columns);
        $query = "CREATE TABLE $name ($columnsStr)";

        static::getDB()->query($query);
    }

    public static function addRow($tableName, $columns, $values) {
        // Исключаем строки, в которых все значения NULL
        if ($values[0] === null && count(array_unique($values)) === 1) { // TODO изменить проверку на нулл и сделать устанавливаемым нормальное null значение
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
    private static function generatePlaceholders($columns) {
        $arr = array_map(function () {
            return "?";
        }, $columns);

        return join(', ', $arr);
    }
}
