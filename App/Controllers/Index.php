<?php

namespace App\Controllers;

use App\Config;
use App\Configs\SQLVariableTypes;
use App\Utils\DocumentUtils;
use Core\Controller;
use Core\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Index extends Controller {

    public function indexAction() {
        $variant1 = IOFactory::load(Config::$APP_DIR . "\Resources\Documents\\variant_1.xlsx");
        $variant2 = file_get_contents(Config::$APP_DIR . "\Resources\Documents\\variant_2.xml");;
        $variant4 = IOFactory::load(Config::$APP_DIR . "\Resources\Documents\\variant_4.xlsx");

        $variant1->setActiveSheetIndex(0);
        $variant4->setActiveSheetIndex(0);

         DocumentUtils::instance()->xlsxToMysql(
             $variant1->getActiveSheet(),
             'variant_1',
             [
                 'paddingLeft' => 3,
                 'columnsNameLine' => 1,
                 'primaryKey' => [
                     'name' => 'Код',
                     'type' => SQLVariableTypes::VARCHAR,
                     'increment' => false
                 ],
                 'moveColumnToSeparateTable' => ''
             ]
         );

        DocumentUtils::instance()->xlsxToMysql(
            $variant4->getActiveSheet(),
            'variant_4',
            [
                'paddingLeft' => 0,
                'columnsNameLine' => 1,
                'primaryKey' => [
                    'name' => 'ID элемента предложения',
                    'type' => SQLVariableTypes::INT->name,
                    'increment' => false
                ],
                'moveColumnToSeparateTable' => 'Характеристики'
            ]
        );

        DocumentUtils::instance()->xmlToMysql(
            $variant2,
            'variant_2',
            [
                'primaryKey' => [
                    'name' => 'Код',
                    'type' => SQLVariableTypes::VARCHAR
                ]
            ]);

        View::renderTemplate('Pages/index.html');
    }
}
