<?php
declare(strict_types=1);

namespace App\Models;

final class Payment extends BaseModel
{
    public function latestByGame(string $gameId): array|false
    {
        $stmt = $this->pdo->prepare(
            'select * from public.payments where game_id = :game_id order by created_at desc limit 1'
        );
        $stmt->execute([':game_id' => $gameId]);

        return $stmt->fetch() ?: false;
    }
}
