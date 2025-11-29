<?php

declare(strict_types=1);

namespace App\Action;

use Crenspire\Yii3Inertia\Action\InertiaAction;
use Psr\Http\Message\ResponseInterface;

/**
 * Example HomeAction using InertiaAction base class
 */
class HomeAction extends InertiaAction
{
    public function __invoke(): ResponseInterface
    {
        return $this->render('Home', [
            'title' => 'Welcome to Yii3 + Inertia.js',
            'message' => 'This action extends InertiaAction base class',
        ]);
    }
}

