<?php

namespace App\Models;

use App\Utils\DebugUtils;
use App\Utils\StringUtils;
use Core\Model;

class Document extends Model {

    public static function dropTable($name) {
        static::getDB()->query("DROP TABLE IF EXISTS $name");
    }

    public static function createTable($name, $columns) {

        $query = "CREATE TABLE $name" . " (" .
            str_replace(",", " TEXT NOT NULL,", StringUtils::translateWord($columns)) .
            " TEXT NOT NULL)";

        DebugUtils::debug(str_replace(',', ",\n", $columns));

        static::getDB()->query($query);
    }
}
