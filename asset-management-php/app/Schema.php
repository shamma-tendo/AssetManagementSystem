<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Schema
{
    public static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
SQL);
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS assets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT,
  serial_number TEXT,
  status TEXT NOT NULL DEFAULT 'active',
  location TEXT,
  purchase_date TEXT,
  cost REAL,
  assigned_to TEXT,
  category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);
SQL);
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_name ON assets(name)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_status ON assets(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_category ON assets(category_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_serial ON assets(serial_number)');

        $defaults = ['Hardware', 'Software', 'Furniture', 'Vehicle', 'Other'];
        $stmt = $pdo->query('SELECT COUNT(*) FROM categories');
        $count = (int) $stmt->fetchColumn();
        if ($count === 0) {
            $ins = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
            foreach ($defaults as $name) {
                $ins->execute([$name]);
            }
        }
    }
}
