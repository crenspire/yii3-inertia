<?php

declare(strict_types=1);

/**
 * @var Crenspire\Inertia\View\InertiaView $inertia
 * @var Crenspire\Inertia\Vite\Vite $vite
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title data-inertia>Inertia on PSR-15</title>
    <?= $vite->reactRefresh() ?>
    <?= $vite->tags('resources/js/app.jsx') ?>
    <?= $inertia->head() ?>
</head>
<body>
    <?= $inertia->body() ?>
</body>
</html>
