<?php

declare(strict_types=1);

/**
 * Inertia root view, rendered on the first visit.
 *
 * @var Crenspire\Inertia\View\InertiaView $inertia
 * @var Crenspire\Inertia\Vite\Vite $vite
 * @var Psr\Http\Message\ServerRequestInterface $request
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php /* Inertia replaces elements marked data-inertia with the ones from <Head>; remove the attribute if you do not use <Head>. */ ?>
    <title data-inertia>Application</title>
    <?= $vite->reactRefresh() ?>
    <?= $vite->tags('resources/js/app.jsx') ?>
    <?= $inertia->head() ?>
</head>
<body>
    <?= $inertia->body() ?>
</body>
</html>
