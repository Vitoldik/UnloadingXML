<?php

namespace App\Utils;

class DebugUtils {

    public static function debug($arr) {
        echo  '<pre>' . print_r($arr, true) . '</pre>';
    }

}