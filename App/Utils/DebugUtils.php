<?php

namespace App\Utils;

class DebugUtils {

    public static function debug($arr): void {
        echo  '<pre>' . print_r($arr, true) . '</pre>';
    }

}