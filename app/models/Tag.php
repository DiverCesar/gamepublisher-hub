<?php
declare(strict_types=1);

namespace App\Models;

final class Tag extends BaseModel
{
    public function all(): array
    {
        $stmt = $this->pdo->query('select id, name from public.tags order by name asc');
        return $stmt->fetchAll() ?: [];
    }
}
