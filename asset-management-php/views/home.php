<?php
/** @var string $token */
/** @var list<array<string,mixed>> $list */
/** @var list<array{id:int,name:string,created_at:string}> $catRows */
/** @var list<string> $statuses */
/** @var string $q */
/** @var string $st */
/** @var int|null $cid */
$title = 'Assets';
ob_start();
?>
<section class="panel">
  <h2>Filters</h2>
  <form class="filters" method="get" action="index.php">
    <input type="hidden" name="r" value="home" />
    <label>Search
      <input type="search" name="q" value="<?= h($q) ?>" placeholder="Name, serial, assignee, location" />
    </label>
    <label>Status
      <select name="status">
        <option value="">All</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= h($s) ?>" <?= $st === $s ? 'selected' : '' ?>><?= h($s) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Category
      <select name="category_id">
        <option value="">All</option>
        <?php foreach ($catRows as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $cid === (int) $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit" class="btn primary">Apply</button>
    <a class="btn ghost" href="index.php?r=home">Clear</a>
  </form>
</section>

<section class="panel">
  <h2>Categories</h2>
  <form class="inline" method="post" action="index.php?r=home">
    <input type="hidden" name="_csrf" value="<?= h($token) ?>" />
    <input type="hidden" name="action" value="category_add" />
    <input name="category_name" placeholder="New category name" required />
    <button type="submit" class="btn secondary">Add category</button>
  </form>
  <ul class="chips">
    <?php foreach ($catRows as $c): ?>
      <li>
        <span><?= h($c['name']) ?></span>
        <form method="post" action="index.php?r=home" class="inline" onsubmit="return confirm('Delete this category? Assets keep their data; category link is cleared.');">
          <input type="hidden" name="_csrf" value="<?= h($token) ?>" />
          <input type="hidden" name="action" value="category_delete" />
          <input type="hidden" name="id" value="<?= (int) $c['id'] ?>" />
          <button type="submit" class="link danger" aria-label="Delete <?= h($c['name']) ?>">×</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="panel">
  <div class="row">
    <h2>Asset register</h2>
    <a class="btn primary" href="index.php?r=asset_new">New asset</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Category</th>
          <th>Status</th>
          <th>Serial</th>
          <th>Location</th>
          <th>Assignee</th>
          <th>Purchase</th>
          <th>Cost</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if ($list === []): ?>
          <tr><td colspan="9" class="muted center">No assets match your filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($list as $a): ?>
          <tr>
            <td><strong><?= h((string) $a['name']) ?></strong></td>
            <td><?= h((string) ($a['category_name'] ?? '')) ?: '—' ?></td>
            <td><span class="pill status-<?= h((string) $a['status']) ?>"><?= h((string) $a['status']) ?></span></td>
            <td><?= h((string) ($a['serial_number'] ?? '')) ?: '—' ?></td>
            <td><?= h((string) ($a['location'] ?? '')) ?: '—' ?></td>
            <td><?= h((string) ($a['assigned_to'] ?? '')) ?: '—' ?></td>
            <td><?= h((string) ($a['purchase_date'] ?? '')) ?: '—' ?></td>
            <td><?= isset($a['cost']) && $a['cost'] !== null && $a['cost'] !== '' ? h(number_format((float) $a['cost'], 2)) : '—' ?></td>
            <td class="nowrap">
              <a class="link" href="index.php?r=asset_edit&amp;id=<?= (int) $a['id'] ?>">Edit</a>
              <form method="post" action="index.php?r=home" class="inline" onsubmit="return confirm('Delete this asset permanently?');">
                <input type="hidden" name="_csrf" value="<?= h($token) ?>" />
                <input type="hidden" name="action" value="asset_delete" />
                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>" />
                <button type="submit" class="link danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
$body = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
