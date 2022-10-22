<?php

namespace App\Utils;

use App\Configs\SQLVariableTypes;

class SQLUtils {

    public static function getType(?string $string): SQLVariableTypes {
        if ($string == null)
            return SQLVariableTypes::NULL;

        $string = trim($string);

        if ($string === '0' || $string === '1') {
            return SQLVariableTypes::INT;
        }

        if (empty($string)) {
            return SQLVariableTypes::NULL;
        }

        if (!preg_match('/[^0-9.]+/', $string)) {
            return preg_match('/[.]+/', $string) ? SQLVariableTypes::FLOAT : SQLVariableTypes::INT;
        }

        return $string == 'true' || $string == 'false' ? SQLVariableTypes::BOOLEAN : SQLVariableTypes::TEXT;
    }

    public static function formatInsertValues(array $values) : string {
        return '(' . join(',', $values) . ')';
    }

}