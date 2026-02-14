<?php
declare(strict_types=1);

namespace App\Modules\Home;

use App\Core\View;

final class Controller
{
    public function index(): void
    {
        View::render('home');
    }
}
