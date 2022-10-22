<?php

namespace App\Utils;

class ArrayUtils {

    public static function getAndRemoveDuplicate(array &$columns): array {
        $duplicated = array_diff_assoc($columns, array_unique($columns));

        foreach ($duplicated as $index => $duplicate) {
            unset($columns[$index]);
        }

        return $duplicated;
    }

    public static function areNoUniqueValues(array $array): bool {
        return count(array_unique($array)) === 1;
    }

}