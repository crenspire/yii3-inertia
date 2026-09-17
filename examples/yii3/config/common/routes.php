<?php

declare(strict_types=1);

use App\Web;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create()
        ->routes(
            Route::get('/')
                ->action(Web\HomePage\Action::class)
                ->name('home'),
            Route::get('/users')
                ->action(Web\Users\IndexAction::class)
                ->name('users/index'),
            Route::get('/users/create')
                ->action(Web\Users\CreateAction::class)
                ->name('users/create'),
            Route::post('/users')
                ->action(Web\Users\StoreAction::class)
                ->name('users/store'),
        ),
];
