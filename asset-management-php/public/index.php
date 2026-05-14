<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/helpers.php';

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $base = dirname(__DIR__) . '/app/' . substr($class, 4) . '.php';
    if (is_file($base)) {
        require $base;
    }
});

use App\AssetRepo;
use App\CategoryRepo;
use App\Db;
use App\Schema;

session_start();

$pdo = Db::pdo();
Schema::migrate($pdo);
$categories = new CategoryRepo($pdo);
$assets = new AssetRepo($pdo);

$route = $_GET['r'] ?? 'home';
$flashOk = $_SESSION['flash_ok'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_ok'], $_SESSION['flash_err']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    if ($action === 'category_add') {
        $name = (string) ($_POST['category_name'] ?? '');
        try {
            $categories->create($name);
            $_SESSION['flash_ok'] = 'Category added.';
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = 'Could not add category (duplicate name?).';
        }
        header('Location: ' . url('home'));
        exit;
    }
    if ($action === 'category_delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $categories->delete($id);
            $_SESSION['flash_ok'] = 'Category removed.';
        }
        header('Location: ' . url('home'));
        exit;
    }
    if ($action === 'asset_delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $assets->delete($id);
            $_SESSION['flash_ok'] = 'Asset deleted.';
        }
        header('Location: ' . url('home'));
        exit;
    }
    if ($action === 'asset_save') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $payload = normalize_asset_post($_POST);
        if ($payload['name'] === '') {
            $_SESSION['flash_err'] = 'Name is required.';
            header('Location: ' . url($id > 0 ? 'asset_edit' : 'asset_new', ['id' => $id > 0 ? $id : null]));
            exit;
        }
        try {
            if ($id > 0) {
                $assets->update($id, $payload);
                $_SESSION['flash_ok'] = 'Asset updated.';
            } else {
                $assets->create($payload);
                $_SESSION['flash_ok'] = 'Asset created.';
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = 'Could not save asset.';
        }
        header('Location: ' . url('home'));
        exit;
    }
}

/** @param array<string,mixed> $post */
function normalize_asset_post(array $post): array
{
    $allowed = ['active', 'maintenance', 'retired', 'disposed'];
    $status = (string) ($post['status'] ?? 'active');
    if (!in_array($status, $allowed, true)) {
        $status = 'active';
    }

    $cid = $post['category_id'] ?? '';
    $categoryId = ($cid === '' || $cid === null) ? null : (int) $cid;

    $costRaw = trim((string) ($post['cost'] ?? ''));
    $cost = $costRaw === '' ? null : (float) $costRaw;

    $pd = trim((string) ($post['purchase_date'] ?? ''));
    $purchaseDate = $pd === '' ? null : $pd;

    return [
        'name' => trim((string) ($post['name'] ?? '')),
        'description' => trim((string) ($post['description'] ?? '')) ?: null,
        'serial_number' => trim((string) ($post['serial_number'] ?? '')) ?: null,
        'status' => $status,
        'location' => trim((string) ($post['location'] ?? '')) ?: null,
        'purchase_date' => $purchaseDate,
        'cost' => $cost,
        'assigned_to' => trim((string) ($post['assigned_to'] ?? '')) ?: null,
        'category_id' => $categoryId,
    ];
}

function url(string $r, array $query = []): string
{
    $q = array_merge(['r' => $r], $query);
    $qs = http_build_query(array_filter($q, static fn ($v) => $v !== null && $v !== ''));
    return 'index.php?' . $qs;
}

$statuses = ['active', 'maintenance', 'retired', 'disposed'];

if ($route === 'asset_new') {
    render_asset_form(null, $categories->all(), $statuses, $flashOk, $flashErr);
    exit;
}

if ($route === 'asset_edit') {
    $id = (int) ($_GET['id'] ?? 0);
    $row = $id > 0 ? $assets->find($id) : null;
    if ($row === null) {
        http_response_code(404);
        echo 'Asset not found.';
        exit;
    }
    render_asset_form($row, $categories->all(), $statuses, $flashOk, $flashErr);
    exit;
}

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$st = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
$cid = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int) $_GET['category_id'] : null;

$list = $assets->search($q !== '' ? $q : null, $st !== '' ? $st : null, $cid);
$catRows = $categories->all();

render_home($list, $catRows, $statuses, $q, $st, $cid, $flashOk, $flashErr);

/**
 * @param list<array<string,mixed>> $list
 * @param list<array{id:int,name:string,created_at:string}> $catRows
 * @param list<string> $statuses
 */
function render_home(array $list, array $catRows, array $statuses, string $q, string $st, ?int $cid, mixed $flashOk, mixed $flashErr): void
{
    $title = 'Assets';
    $token = csrf_token();
    include dirname(__DIR__) . '/views/home.php';
}

/**
 * @param array<string,mixed>|null $asset
 * @param list<array{id:int,name:string,created_at:string}> $catRows
 * @param list<string> $statuses
 */
function render_asset_form(?array $asset, array $catRows, array $statuses, mixed $flashOk, mixed $flashErr): void
{
    $token = csrf_token();
    include dirname(__DIR__) . '/views/asset_form.php';
}
