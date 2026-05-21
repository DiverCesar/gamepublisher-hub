<?php
declare(strict_types=1);

namespace App\Models;

final class Stats extends BaseModel
{
    public function metrics(): array
    {
        $stmt = $this->pdo->query(<<<SQL
        select
        count(*)::int as total_games,
                                  coalesce(sum(case when status = 'published' then 1 else 0 end), 0)::int as published_games,
                                  coalesce(sum(case when status = 'draft' then 1 else 0 end), 0)::int as draft_games,
                                  coalesce(sum(case when payment_status = 'paid' then 1 else 0 end), 0)::int as paid_games,
                                  coalesce(avg(price), 0)::numeric(10,2) as avg_price,
                                  coalesce(sum(case when payment_status = 'paid' then publication_fee else 0 end), 0)::numeric(10,2) as total_fees_collected
                                  from public.games
                                  SQL);

        return $stmt->fetch() ?: [];
    }

    public function genreHistogram(): array
    {
    $stmt = $this->pdo->query(<<<SQL
    select
    ge.name,
    count(*)::int as total
    from public.genres ge
    left join public.game_genres gg on gg.genre_id = ge.id
    group by ge.id, ge.name
    order by total desc, ge.name asc
    limit 12
    SQL);

        return $stmt->fetchAll() ?: [];
    }

    public function monthlyHistogram(): array
    {
    $stmt = $this->pdo->query(<<<SQL
    select
    to_char(date_trunc('month', created_at), 'YYYY-MM') as month,
                                  count(*)::int as total
                                  from public.games
                                  group by 1
                                  order by 1 asc
                                  SQL);

        return $stmt->fetchAll() ?: [];
    }

    public function paymentSeries(): array
    {
    $stmt = $this->pdo->query(<<<SQL
    select
    payment_status,
    count(*)::int as total
    from public.games
    group by payment_status
    order by total desc
    SQL);

        return $stmt->fetchAll() ?: [];
    }

    public function recentGames(int $limit = 8): array
    {
    $stmt = $this->pdo->prepare(<<<SQL
    select
    g.id,
    g.title,
    g.studio,
    g.price,
    g.status,
    g.payment_status,
    g.accent_color,
    g.created_at,
    coalesce(string_agg(distinct ge.name, ', '), '') as genres
    from public.games g
    left join public.game_genres gg on gg.game_id = g.id
    left join public.genres ge on ge.id = gg.genre_id
    group by g.id
    order by g.created_at desc
    limit :limit
    SQL);

        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }
    }
