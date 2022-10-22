<?php

namespace App\Utils;

class DebugUtils {

    public static function debug(array $arr): void {
        echo  '<pre>' . print_r($arr, true) . '</pre>';
    }

}