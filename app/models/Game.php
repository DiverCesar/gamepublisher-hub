<?php
declare(strict_types=1);

namespace App\Models;

final class Game extends BaseModel
{
    public function findById(string $id): array|false
    {
        $stmt = $this->pdo->prepare('select * from public.games where id = :id limit 1');
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: false;
    }

    public function deleteById(string $id): bool
    {
        $stmt = $this->pdo->prepare('delete from public.games where id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
