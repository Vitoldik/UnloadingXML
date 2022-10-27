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

    public static function formatInsertValueWithColumns(array $columns, array $values) : string {
        $insert = '';

        foreach ($values as $key => $arr) {
            $fill = array_fill(0, count($columns), 'NULL');

            foreach ($arr as $index => $value) {
                $search = array_search($index, $columns);

                $fill[$search] = $value;
            }

            $insert .= self::formatInsertValues($fill) .
                ($key != array_key_last($values) ? ',' : '');
        }

        return $insert;
    }

    public static function formatInsertValueForChildrenTable(&$childrenTableColumns, string $string, string $delimiter = ' '): array { // TODO рефакторинг
        $params = explode('<br>', strip_tags($string, '<br>'));

        $pairs = [];

        foreach ($params as $value) {
            $explode = explode($delimiter, $value);

            if (!isset($explode[0]) || !isset($explode[1]))
                continue;

            $column = DocumentUtils::instance()->formatColumnName(trim($explode[0]));

            if (!in_array($column, $childrenTableColumns))
                $childrenTableColumns[] = $column;

            $pairs[DocumentUtils::instance()->formatColumnName(trim($explode[0]))] = "'" . trim($explode[1]) . "'";
        }

        return $pairs;
    }

    public static function generateQuerySearch(array $searchParams, array $sortParams, array $priceFilterParams) : string {
        $searchParams = !empty($searchParams)
            ? ' WHERE ' . $searchParams['column'] . ' LIKE ' . "'" . $searchParams['text'] . "'"
            : '';

        $filterParams = !empty($priceFilterParams)
            ? ((!empty($searchParams) ? ' AND ' : ' WHERE ') .
            $priceFilterParams['column'] . ' BETWEEN ' . $priceFilterParams['min'] . ' AND ' . $priceFilterParams['max'])
            : '';

        $sortParams = !empty($sortParams)
            ? ' ORDER BY ' . $sortParams['column'] . ' ' . $sortParams['type']
            : '';


        return $searchParams . $filterParams . $sortParams;
    }

}