<?php
declare(strict_types=1);

$games = $games ?? [];
$search = $search ?? '';
$page = (int) ($page ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$total = (int) ($total ?? 0);

$badgeClass = static function (string $status): string {
    return match ($status) {
        'published' => 'badge badge--success',
        'pending_review' => 'badge badge--warning',
        'rejected' => 'badge badge--danger',
        default => 'badge badge--neutral',
    };
};
?>

<div class="page-head">
<div>
<p class="eyebrow">Game library</p>
<h2 class="page-title">All submitted games</h2>
<p class="page-subtitle">Search, inspect, and manage every game in the portal.</p>
</div>

<a href="<?= htmlspecialchars(url('/games/create')) ?>" class="button button--primary">New Game</a>
</div>

<section class="panel">
<form method="get" action="<?= htmlspecialchars(url('/games')) ?>" class="search-bar">
<input
type="search"
name="search"
value="<?= htmlspecialchars((string) $search) ?>"
placeholder="Search by title or studio..."
class="input"
>
<button type="submit" class="button button--ghost">Search</button>
</form>

<div class="stats-row">
<div class="stat-card">
<span>Total games</span>
<strong><?= number_format($total) ?></strong>
</div>
<div class="stat-card">
<span>Current page</span>
<strong><?= number_format($page) ?>/<?= number_format($totalPages) ?></strong>
</div>
</div>

<div class="table-wrap">
<table class="table">
<thead>
<tr>
<th>Game</th>
<th>Genres</th>
<th>Platforms</th>
<th>Price</th>
<th>Status</th>
<th>Payment</th>
<th>Created</th>
<th></th>
</tr>
</thead>
<tbody>
<?php if (empty($games)): ?>
<tr>
<td colspan="8" class="empty-state">No games found yet.</td>
</tr>
<?php endif; ?>

<?php foreach ($games as $game): ?>
<tr>
<td>
<div class="game-cell">
<div class="game-cell__swatch" style="background: <?= htmlspecialchars((string) ($game['accent_color'] ?? '#1b2838')) ?>;"></div>
<div>
<strong><?= htmlspecialchars((string) $game['title']) ?></strong>
<p><?= htmlspecialchars((string) $game['studio']) ?></p>
</div>
</div>
</td>
<td><?= htmlspecialchars((string) ($game['genres'] ?? '')) ?></td>
<td><?= htmlspecialchars((string) ($game['platforms'] ?? '')) ?></td>
<td>$<?= number_format((float) ($game['price'] ?? 0), 2) ?></td>
<td><span class="<?= htmlspecialchars($badgeClass((string) ($game['status'] ?? 'draft'))) ?>"><?= htmlspecialchars((string) ($game['status'] ?? 'draft')) ?></span></td>
<td><span class="<?= htmlspecialchars($badgeClass((string) ($game['payment_status'] ?? 'unpaid'))) ?>"><?= htmlspecialchars((string) ($game['payment_status'] ?? 'unpaid')) ?></span></td>
<td><?= htmlspecialchars((string) ($game['created_at'] ?? '')) ?></td>
<td>
<div class="row-actions">
<a href="<?= htmlspecialchars(url('/games/show?id=' . urlencode((string) $game['id']))) ?>" class="link-action">View</a>
<a href="<?= htmlspecialchars(url('/games/edit?id=' . urlencode((string) $game['id']))) ?>" class="link-action">Edit</a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="pagination">
<?php if ($page > 1): ?>
<a href="<?= htmlspecialchars(url('/games?page=' . ($page - 1))) ?>" class="button button--ghost">Previous</a>
<?php endif; ?>

<?php if ($page < $totalPages): ?>
<a href="<?= htmlspecialchars(url('/games?page=' . ($page + 1))) ?>" class="button button--ghost">Next</a>
<?php endif; ?>
</div>
</section>
