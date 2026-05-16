<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
$pageTitle = $pageTitle ?? 'Projeto Paula';
require_login();
$isAuthPage = is_auth_page();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="<?= $isAuthPage ? 'auth-shell' : 'app-shell' ?>">
    <?php if (!$isAuthPage) { require __DIR__ . '/sidebar.php'; } ?>
    <main class="<?= $isAuthPage ? 'auth-main' : 'app-main' ?>">
        <?php if (!$isAuthPage): ?>
        <div class="topbar d-flex align-items-center justify-content-between">
            <div>
                <span class="eyebrow">Projeto Paula</span>
                <h1 class="h4 mb-0"><?= e($pageTitle) ?></h1>
            </div>
            <button class="btn btn-accent" id="runSearchBtn" type="button">
                <i class="bi bi-lightning-charge"></i> Rodar busca agora
            </button>
        </div>
        <?php endif; ?>
