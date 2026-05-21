<?php
declare(strict_types=1);

$game = $game ?? [];
?>

<div class="page-head">
<div>
<p class="eyebrow">Game details</p>
<h2 class="page-title"><?= htmlspecialchars((string) ($game['title'] ?? 'Game')) ?></h2>
<p class="page-subtitle"><?= htmlspecialchars((string) ($game['studio'] ?? 'Studio')) ?></p>
</div>

<div class="row-actions">
<a href="<?= htmlspecialchars(url('/games/edit?id=' . urlencode((string) ($game['id'] ?? '')))) ?>" class="button button--ghost">Edit</a>
<a href="<?= htmlspecialchars(url('/games')) ?>" class="button button--primary">Back to list</a>
</div>
</div>

<section class="panel">
<div class="page-grid page-grid--form">
<div>
<div class="preview-card" style="--accent: <?= htmlspecialchars((string) ($game['accent_color'] ?? '#1b2838')) ?>;">
<div class="preview-card__banner"></div>
<div class="preview-card__body">
<div class="preview-card__avatar">
<?= htmlspecialchars(strtoupper(substr((string) ($game['title'] ?? 'G'), 0, 1))) ?>
</div>
<div>
<p class="preview-card__label">Storefront preview</p>
<h3 class="preview-card__title"><?= htmlspecialchars((string) ($game['title'] ?? '')) ?></h3>
<p class="preview-card__meta"><?= htmlspecialchars((string) ($game['studio'] ?? '')) ?></p>
</div>
</div>
<p class="preview-card__text">
<?= htmlspecialchars((string) ($game['short_description'] ?? '')) ?>
</p>
<div class="preview-card__tags">
<span class="mini-badge"><?= htmlspecialchars((string) ($game['status'] ?? 'draft')) ?></span>
<span class="mini-badge"><?= htmlspecialchars((string) ($game['payment_status'] ?? 'unpaid')) ?></span>
<span class="mini-badge">$<?= number_format((float) ($game['price'] ?? 0), 2) ?></span>
</div>
</div>
</div>

<div class="note-card">
<h4>Metadata</h4>
<p><strong>Genres:</strong> <?= htmlspecialchars((string) ($game['genres'] ?? '')) ?></p>
<p><strong>Platforms:</strong> <?= htmlspecialchars((string) ($game['platforms'] ?? '')) ?></p>
<p><strong>Tags:</strong> <?= htmlspecialchars((string) ($game['tags'] ?? '')) ?></p>
<p><strong>Age rating:</strong> <?= htmlspecialchars((string) ($game['age_rating'] ?? '')) ?></p>
<p><strong>Release date:</strong> <?= htmlspecialchars((string) ($game['release_date'] ?? '')) ?></p>
<p><strong>Published at:</strong> <?= htmlspecialchars((string) ($game['published_at'] ?? 'Not published')) ?></p>
</div>
</div>
</section>
