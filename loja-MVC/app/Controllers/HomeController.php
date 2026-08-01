<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\ProductModel;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->render('home/index', [
            'produtos' => ProductModel::all(),
            'csrfToken' => Csrf::token(),
        ]);
    }
}
