<?php

namespace App\Controllers;

use App\Config;
use App\Utils\DocumentUtils;
use Core\Controller;
use \Core\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Index extends Controller {

    public function indexAction() {
        $fileOne = IOFactory::load(  Config::$APP_DIR . "\Resources\Documents\\variant_1.xlsx");
        $fileTwo = IOFactory::load(  Config::$APP_DIR . "\Resources\Documents\\variant_4.xlsx");

        $fileOne->setActiveSheetIndex(0);
        $fileTwo->setActiveSheetIndex(0);

         DocumentUtils::instance()->excelToMysql(
            $fileOne->getActiveSheet(), 'variant_1',
             [
                 'paddingLeft' => 3,
                 'columnsNameLine' => 1,
                 'primaryKey' => [
                   'name' => 'Код',
                   'increment' => false
                  ]
             ]
        );

        DocumentUtils::instance()->excelToMysql(
            $fileTwo->getActiveSheet(), 'variant_4',
            [
                'paddingLeft' => 0,
                'columnsNameLine' => 1,
                'primaryKey' => [
                    'name' => 'id',
                    'increment' => true
                ]
            ]
        );

        View::renderTemplate('Pages/index.html');
    }
}
