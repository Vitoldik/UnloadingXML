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

        $fileOne->setActiveSheetIndex(0);

         DocumentUtils::instance()->excelToMysql(
            $fileOne->getActiveSheet(), 'variant_1',
             [
                 'ignoreRowAddress' => ['A', 'B', 'C'],
                 'columnsNameLine' => 1,
                 'primaryKey' => 'Код'
             ]
        );

        View::renderTemplate('Pages/index.html');
    }
}
