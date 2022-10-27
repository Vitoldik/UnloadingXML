<?php

namespace App\Controllers;

use App\Config;
use App\Lang\ColumnLocale;
use App\Models\Document;
use App\Utils\SQLUtils;
use Core\Controller;
use Core\View;

class Index extends Controller {

    public function indexAction() {
        // DocumentUtils::instance()->loadDocuments(); - выгрузка из документов

        $page = 0;

        if (isset($_GET['page'])) {
            $page = max($_GET['page'] - 1, 0);
        }

        $searchParams = [];

        if (isset($_GET['searchColumn']) && isset($_GET['searchText'])) {
            $translatedColumn = ColumnLocale::transtale($_GET['searchColumn']);

            if ($translatedColumn) {
                $searchParams = [
                    'column' => $translatedColumn,
                    'text' => $_GET['searchText']
                ];
            }
        }

        $sortParams = [];

        if (isset($_GET['sortColumn']) && isset($_GET['sortType'])) {
            $translatedColumn = ColumnLocale::transtale($_GET['sortColumn']);

            if ($translatedColumn) {
                $sortParams = [
                    'column' => $translatedColumn,
                    'type' => $_GET['sortType']
                ];
            }
        }

        $priceFilterParams = [];

        if (isset($_GET['minPrice']) && isset($_GET['maxPrice']) && isset($_GET['priceColumn'])) {
            $translatedColumn = ColumnLocale::transtale($_GET['priceColumn']);

            if ($translatedColumn) {
                $priceFilterParams = [
                    'column' => $translatedColumn,
                    'min' => $_GET['minPrice'],
                    'max' => $_GET['maxPrice']
                ];
            }
        }

        $limit = Config::PAGE_LIMIT;
        $start = $page * $limit;
        $params = SQLUtils::generateQuerySearch($searchParams, $sortParams, $priceFilterParams);

        $pageContent = Document::getPage($start, $limit, $params);

        if (empty($pageContent)) {
            View::renderTemplate('404.html');
            return;
        }

        $pageCount = ceil(Document::calcPageAmount($params) / $limit);
        $columns = ColumnLocale::handle(array_keys($pageContent[0]));

        View::renderTemplate('Pages/index.twig', ['columns' => $columns, 'content' => $pageContent, 'page' => [
            'current' => $page + 1,
            'count' => $pageCount,
        ]]);
    }
}
