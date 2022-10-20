<?php

namespace App\Controllers;

use Core\Controller;
use \Core\View;

class Index extends Controller
{

    public function indexAction()
    {
        View::renderTemplate('Pages/index.html');
    }
}
