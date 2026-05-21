<?php
declare(strict_types=1);

$metrics = $metrics ?? [];
$recentGames = $recentGames ?? [];
$genreHistogram = $genreHistogram ?? [];
$monthlyHistogram = $monthlyHistogram ?? [];
$paymentStatusSeries = $paymentStatusSeries ?? [];

$genresChartLabels = array_map(static fn ($row) => $row['name'], $genreHistogram);
$genresChartValues = array_map(static fn ($row) => (int) $row['total'], $genreHistogram);

$monthsChartLabels = array_map(static fn ($row) => $row['month'], $monthlyHistogram);
$monthsChartValues = array_map(static fn ($row) => (int) $row['total'], $monthlyHistogram);

$paymentLabels = array_map(static fn ($row) => $row['payment_status'], $paymentStatusSeries);
$paymentValues = array_map(static fn ($row) => (int) $row['total'], $paymentStatusSeries);
?>

<div class="page-head">
<div>
<p class="eyebrow">Publishing dashboard</p>
<h2 class="page-title">Overview and metrics</h2>
<p class="page-subtitle">A more complete panel with totals, trends, and distribution charts.</p>
</div>

<a href="<?= htmlspecialchars(url('/games/create')) ?>" class="button button--primary">New Game</a>
</div>

<section class="metrics-grid">
<article class="metric-card">
<span>Total games</span>
<strong><?= number_format((int) ($metrics['totalGames'] ?? 0)) ?></strong>
</article>
<article class="metric-card">
<span>Published</span>
<strong><?= number_format((int) ($metrics['publishedGames'] ?? 0)) ?></strong>
</article>
<article class="metric-card">
<span>Drafts</span>
<strong><?= number_format((int) ($metrics['draftGames'] ?? 0)) ?></strong>
</article>
<article class="metric-card">
<span>Paid games</span>
<strong><?= number_format((int) ($metrics['paidGames'] ?? 0)) ?></strong>
</article>
<article class="metric-card">
<span>Average price</span>
<strong>$<?= number_format((float) ($metrics['avgPrice'] ?? 0), 2) ?></strong>
</article>
<article class="metric-card">
<span>Fees collected</span>
<strong>$<?= number_format((float) ($metrics['totalFeesCollected'] ?? 0), 2) ?></strong>
</article>
</section>

<section class="dashboard-grid">
<div class="panel">
<div class="panel__header">
<div>
<h3 class="panel__title">Games by genre</h3>
<p class="panel__subtitle">Histogram style distribution across the catalog.</p>
</div>
</div>
<canvas id="genreChart" height="180"></canvas>
</div>

<div class="panel">
<div class="panel__header">
<div>
<h3 class="panel__title">Monthly uploads</h3>
<p class="panel__subtitle">Track publishing activity over time.</p>
</div>
</div>
<canvas id="monthlyChart" height="180"></canvas>
</div>

<div class="panel">
<div class="panel__header">
<div>
<h3 class="panel__title">Payment status</h3>
<p class="panel__subtitle">How many games are paid, pending, or unpaid.</p>
</div>
</div>
<canvas id="paymentChart" height="180"></canvas>
</div>

<div class="panel panel--wide">
<div class="panel__header">
<div>
<h3 class="panel__title">Recent games</h3>
<p class="panel__subtitle">Latest entries with visual accents.</p>
</div>
</div>

<div class="recent-list">
<?php foreach ($recentGames as $game): ?>
<article class="recent-card" style="--accent: <?= htmlspecialchars((string) ($game['accent_color'] ?? '#1b2838')) ?>;">
<div class="recent-card__media"></div>
<div class="recent-card__content">
<div class="recent-card__top">
<div>
<h4><?= htmlspecialchars((string) $game['title']) ?></h4>
<p><?= htmlspecialchars((string) $game['studio']) ?></p>
</div>
<strong>$<?= number_format((float) ($game['price'] ?? 0), 2) ?></strong>
</div>

<p class="recent-card__text"><?= htmlspecialchars(mb_strimwidth((string) ($game['genres'] ?? ''), 0, 120, '...')) ?></p>

<div class="recent-card__footer">
<span class="mini-badge"><?= htmlspecialchars((string) ($game['status'] ?? 'draft')) ?></span>
<span class="mini-badge"><?= htmlspecialchars((string) ($game['payment_status'] ?? 'unpaid')) ?></span>
</div>
</div>
</article>
<?php endforeach; ?>
</div>
</div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const genreLabels = <?= json_encode($genresChartLabels, JSON_UNESCAPED_UNICODE) ?>;
const genreValues = <?= json_encode($genresChartValues, JSON_UNESCAPED_UNICODE) ?>;
const monthLabels = <?= json_encode($monthsChartLabels, JSON_UNESCAPED_UNICODE) ?>;
const monthValues = <?= json_encode($monthsChartValues, JSON_UNESCAPED_UNICODE) ?>;
const paymentLabels = <?= json_encode($paymentLabels, JSON_UNESCAPED_UNICODE) ?>;
const paymentValues = <?= json_encode($paymentValues, JSON_UNESCAPED_UNICODE) ?>;

const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }
    },
    scales: {
        x: { ticks: { color: '#c7d5e0' }, grid: { color: 'rgba(255,255,255,0.05)' } },
        y: { ticks: { color: '#c7d5e0' }, grid: { color: 'rgba(255,255,255,0.05)' } }
    }
};

new Chart(document.getElementById('genreChart'), {
    type: 'bar',
    data: {
        labels: genreLabels,
        datasets: [{
            data: genreValues,
            borderWidth: 0
        }]
    },
    options: baseOptions
});

new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [{
            data: monthValues,
            tension: 0.35,
            fill: true,
            borderWidth: 2
        }]
    },
    options: baseOptions
});

new Chart(document.getElementById('paymentChart'), {
    type: 'doughnut',
    data: {
        labels: paymentLabels,
        datasets: [{
            data: paymentValues,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: '#c7d5e0' } }
        }
    }
});
</script>
