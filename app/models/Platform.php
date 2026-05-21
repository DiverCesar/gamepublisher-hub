<?php
declare(strict_types=1);

namespace App\Models;

final class Platform extends BaseModel
{
    public function all(): array
    {
        $stmt = $this->pdo->query('select id, name from public.platforms order by display_order asc, name asc');
        return $stmt->fetchAll() ?: [];
    }
}
