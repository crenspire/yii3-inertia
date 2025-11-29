<?php

declare(strict_types=1);

namespace App\Action;

use Crenspire\Yii3Inertia\Action\InertiaAction;
use Psr\Http\Message\ResponseInterface;

/**
 * Example DashboardAction using InertiaAction base class
 */
class DashboardAction extends InertiaAction
{
    public function __invoke(): ResponseInterface
    {
        // Example: Get query parameters
        $page = (int) $this->getQueryParam('page', 1);
        
        return $this->render('Dashboard', [
            'title' => 'Dashboard',
            'page' => $page,
            'stats' => [
                'users' => 1234,
                'revenue' => 56789,
                'orders' => 890,
            ],
        ]);
    }
}

