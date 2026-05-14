<?php

declare(strict_types=1);

namespace App;

use PDO;

final class CategoryRepo
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array{id:int,name:string,created_at:string}> */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, created_at FROM categories ORDER BY name COLLATE NOCASE');
        return $stmt->fetchAll();
    }

    public function create(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }
        $stmt = $this->pdo->prepare('INSERT INTO categories (name) VALUES (?)');
        $stmt->execute([$name]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
    }
}
