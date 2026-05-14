<?php
/** @var array<string,mixed>|null $asset */
/** @var list<array{id:int,name:string,created_at:string}> $catRows */
/** @var list<string> $statuses */
/** @var string $token */
$isEdit = $asset !== null;
$title = $isEdit ? 'Edit asset' : 'New asset';
ob_start();
?>
<section class="panel narrow">
  <div class="row">
    <h2><?= h($title) ?></h2>
    <a class="btn ghost" href="index.php?r=home">Back</a>
  </div>
  <form method="post" action="index.php?r=<?= $isEdit ? 'asset_edit' : 'asset_new' ?>" class="stack">
    <input type="hidden" name="_csrf" value="<?= h($token) ?>" />
    <input type="hidden" name="action" value="asset_save" />
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int) $asset['id'] ?>" />
    <?php endif; ?>

    <label>Name *
      <input name="name" required value="<?= h($isEdit ? (string) $asset['name'] : '') ?>" />
    </label>
    <label>Category
      <select name="category_id">
        <option value="">—</option>
        <?php foreach ($catRows as $c): ?>
          <?php $sel = $isEdit && (int) ($asset['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : ''; ?>
          <option value="<?= (int) $c['id'] ?>" <?= $sel ?>><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Status
      <select name="status">
        <?php $cur = $isEdit ? (string) $asset['status'] : 'active'; ?>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= h($s) ?>" <?= $cur === $s ? 'selected' : '' ?>><?= h($s) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Serial number
      <input name="serial_number" value="<?= h($isEdit ? (string) ($asset['serial_number'] ?? '') : '') ?>" />
    </label>
    <label>Location
      <input name="location" value="<?= h($isEdit ? (string) ($asset['location'] ?? '') : '') ?>" />
    </label>
    <label>Assigned to
      <input name="assigned_to" value="<?= h($isEdit ? (string) ($asset['assigned_to'] ?? '') : '') ?>" />
    </label>
    <label>Purchase date
      <input type="date" name="purchase_date" value="<?= h($isEdit ? (string) ($asset['purchase_date'] ?? '') : '') ?>" />
    </label>
    <label>Cost
      <input inputmode="decimal" name="cost" placeholder="0.00" value="<?= h($isEdit && isset($asset['cost']) && $asset['cost'] !== null && $asset['cost'] !== '' ? (string) $asset['cost'] : '') ?>" />
    </label>
    <label>Description
      <textarea name="description" rows="4"><?= h($isEdit ? (string) ($asset['description'] ?? '') : '') ?></textarea>
    </label>
    <div class="row">
      <button type="submit" class="btn primary"><?= $isEdit ? 'Save changes' : 'Create asset' ?></button>
    </div>
  </form>
</section>
<?php
$body = ob_get_clean();
include dirname(__DIR__) . '/views/layout.php';
