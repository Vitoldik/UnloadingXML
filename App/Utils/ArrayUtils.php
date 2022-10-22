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

}