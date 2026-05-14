<?php

declare(strict_types=1);

namespace App;

use PDO;

final class AssetRepo
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function search(?string $q, ?string $status, ?int $categoryId): array
    {
        $sql = <<<'SQL'
SELECT a.*, c.name AS category_name
FROM assets a
LEFT JOIN categories c ON c.id = a.category_id
WHERE 1=1
SQL;
        $params = [];
        if ($q !== null && $q !== '') {
            $sql .= ' AND (
                a.name LIKE :q OR a.serial_number LIKE :q OR a.assigned_to LIKE :q OR a.location LIKE :q
            )';
            $params[':q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        }
        if ($status !== null && $status !== '') {
            $sql .= ' AND a.status = :status';
            $params[':status'] = $status;
        }
        if ($categoryId !== null) {
            $sql .= ' AND a.category_id = :cid';
            $params[':cid'] = $categoryId;
        }
        $sql .= ' ORDER BY a.updated_at DESC, a.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
SELECT a.*, c.name AS category_name
FROM assets a
LEFT JOIN categories c ON c.id = a.category_id
WHERE a.id = ?
SQL);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
INSERT INTO assets (
  name, description, serial_number, status, location, purchase_date, cost, assigned_to, category_id, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
SQL);
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['serial_number'],
            $data['status'],
            $data['location'],
            $data['purchase_date'],
            $data['cost'],
            $data['assigned_to'],
            $data['category_id'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
UPDATE assets SET
  name = ?, description = ?, serial_number = ?, status = ?, location = ?,
  purchase_date = ?, cost = ?, assigned_to = ?, category_id = ?,
  updated_at = datetime('now')
WHERE id = ?
SQL);
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['serial_number'],
            $data['status'],
            $data['location'],
            $data['purchase_date'],
            $data['cost'],
            $data['assigned_to'],
            $data['category_id'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM assets WHERE id = ?');
        $stmt->execute([$id]);
    }
}
