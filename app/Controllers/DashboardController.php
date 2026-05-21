<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use PDO;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $pdo = Database::connection();

        $metrics = $this->loadMetrics($pdo);
        $recentGames = $this->loadRecentGames($pdo);
        $genreHistogram = $this->loadGenreHistogram($pdo);
        $monthlyHistogram = $this->loadMonthlyHistogram($pdo);
        $paymentStatusSeries = $this->loadPaymentStatusSeries($pdo);

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'metrics' => $metrics,
            'recentGames' => $recentGames,
            'genreHistogram' => $genreHistogram,
            'monthlyHistogram' => $monthlyHistogram,
            'paymentStatusSeries' => $paymentStatusSeries,
        ]);
    }

    private function loadMetrics(PDO $pdo): array
    {
        $sql = <<<SQL
        select
        count(*)::int as total_games,
        coalesce(sum(case when status = 'published' then 1 else 0 end), 0)::int as published_games,
        coalesce(sum(case when status = 'draft' then 1 else 0 end), 0)::int as draft_games,
        coalesce(sum(case when payment_status = 'paid' then 1 else 0 end), 0)::int as paid_games,
        coalesce(avg(price), 0)::numeric(10,2) as avg_price,
        coalesce(sum(case when payment_status = 'paid' then publication_fee else 0 end), 0)::numeric(10,2) as total_fees_collected,
        coalesce(sum(price), 0)::numeric(10,2) as potential_revenue
        from public.games
        SQL;

        $stmt = $pdo->query($sql);
        $row = $stmt->fetch() ?: [];

        return [
            'totalGames' => (int) ($row['total_games'] ?? 0),
            'publishedGames' => (int) ($row['published_games'] ?? 0),
            'draftGames' => (int) ($row['draft_games'] ?? 0),
            'paidGames' => (int) ($row['paid_games'] ?? 0),
            'avgPrice' => (float) ($row['avg_price'] ?? 0),
            'totalFeesCollected' => (float) ($row['total_fees_collected'] ?? 0),
            'potentialRevenue' => (float) ($row['potential_revenue'] ?? 0),
        ];
    }

    private function loadRecentGames(PDO $pdo): array
    {
        $sql = <<<SQL
        select
        g.id,
        g.title,
        g.studio,
        g.price,
        g.status,
        g.payment_status,
        g.accent_color,
        g.banner_path,
        g.cover_path,
        g.created_at,
        coalesce(string_agg(distinct ge.name, ', '), '') as genres,
        coalesce(string_agg(distinct pl.name, ', '), '') as platforms
        from public.games g
        left join public.game_genres gg on gg.game_id = g.id
        left join public.genres ge on ge.id = gg.genre_id
        left join public.game_platforms gp on gp.game_id = g.id
        left join public.platforms pl on pl.id = gp.platform_id
        group by g.id
        order by g.created_at desc
        limit 8
        SQL;

        return $pdo->query($sql)->fetchAll() ?: [];
    }

    private function loadGenreHistogram(PDO $pdo): array
    {
        $sql = <<<SQL
        select
        ge.name,
        count(*)::int as total
        from public.genres ge
        left join public.game_genres gg on gg.genre_id = ge.id
        group by ge.id, ge.name
        order by total desc, ge.name asc
        limit 12
        SQL;

        return $pdo->query($sql)->fetchAll() ?: [];
    }

    private function loadMonthlyHistogram(PDO $pdo): array
    {
        $sql = <<<SQL
        select
        to_char(date_trunc('month', created_at), 'YYYY-MM') as month,
        count(*)::int as total
        from public.games
        group by 1
        order by 1 asc
        SQL;

        return $pdo->query($sql)->fetchAll() ?: [];
    }

    private function loadPaymentStatusSeries(PDO $pdo): array
    {
        $sql = <<<SQL
        select
        payment_status,
        count(*)::int as total
        from public.games
        group by payment_status
        order by total desc
        SQL;

        return $pdo->query($sql)->fetchAll() ?: [];
    }
}
