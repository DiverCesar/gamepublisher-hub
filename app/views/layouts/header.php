<?php
declare(strict_types=1);

$pageTitle = $title ?? 'GamePublisher Hub';
$appName = $config['app']['name'] ?? 'GamePublisher Hub';

$flashSuccess = $_SESSION['flash']['success'] ?? null;
$flashError = $_SESSION['flash']['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle . ' | ' . $appName) ?></title>
<meta name="description" content="Game publishing portal inspired by modern storefront workflows.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= htmlspecialchars(asset('css/app.css')) ?>">
</head>
<body class="app-body">
<div class="app-shell">
<header class="topbar">
<div class="topbar__brand">
<div class="brand-mark">GP</div>
<div>
<h1 class="brand-title"><?= htmlspecialchars($appName) ?></h1>
<p class="brand-subtitle">Game publishing management portal</p>
</div>
</div>

<nav class="topbar__nav" aria-label="Main navigation">
<a href="<?= htmlspecialchars(url('/dashboard')) ?>" class="nav-link">Dashboard</a>
<a href="<?= htmlspecialchars(url('/games')) ?>" class="nav-link">Games</a>
<a href="<?= htmlspecialchars(url('/games/create')) ?>" class="nav-link nav-link--primary">New Game</a>
</nav>
</header>

<main class="app-main">
<?php if ($flashSuccess): ?>
<div class="alert alert--success" role="status">
<?= htmlspecialchars((string) $flashSuccess) ?>
</div>
<?php endif; ?>

<?php if ($flashError): ?>
<div class="alert alert--error" role="alert">
<?= htmlspecialchars((string) $flashError) ?>
</div>
<?php endif; ?>

<section class="page-frame">
