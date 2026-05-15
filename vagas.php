<?php
$pageTitle = 'Vagas';
require_once __DIR__ . '/includes/header.php';

$params = [];
$where = [];
foreach (['fonte', 'status', 'empresa'] as $field) {
    if (!empty($_GET[$field])) { $where[] = "$field LIKE ?"; $params[] = '%' . $_GET[$field] . '%'; }
}
if (!empty($_GET['q'])) { $where[] = '(titulo LIKE ? OR empresa LIKE ? OR descricao LIKE ?)'; $params[] = '%' . $_GET['q'] . '%'; $params[] = '%' . $_GET['q'] . '%'; $params[] = '%' . $_GET['q'] . '%'; }
if (isset($_GET['nota_min']) && $_GET['nota_min'] !== '') { $where[] = 'nota_compatibilidade >= ?'; $params[] = (int)$_GET['nota_min']; }
if (!empty($_GET['remoto'])) { $where[] = "(localizacao LIKE '%remote%' OR localizacao LIKE '%remoto%' OR descricao LIKE '%remote%' OR descricao LIKE '%remoto%')"; }
$orderMap = ['nota' => 'nota_compatibilidade DESC', 'empresa' => 'empresa ASC', 'fonte' => 'fonte ASC', 'recentes' => 'id DESC'];
$order = $orderMap[$_GET['ordem'] ?? 'nota'] ?? 'nota_compatibilidade DESC';
$sql = 'SELECT * FROM vagas' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $order LIMIT 300";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vagas = $stmt->fetchAll();
?>
<form class="card mb-4"><div class="card-body row g-3 align-items-end">
    <div class="col-md-3"><label class="form-label">Texto livre</label><input name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label">Fonte</label><input name="fonte" class="form-control" value="<?= e($_GET['fonte'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">Todos</option><?php foreach (status_options() as $s): ?><option <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label">Nota minima</label><input name="nota_min" type="number" class="form-control" value="<?= e($_GET['nota_min'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label">Empresa</label><input name="empresa" class="form-control" value="<?= e($_GET['empresa'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label">Ordenar</label><select name="ordem" class="form-select"><?php foreach (['nota' => 'Maior nota', 'recentes' => 'Mais recentes', 'empresa' => 'Empresa', 'fonte' => 'Fonte'] as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['ordem'] ?? 'nota') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2 form-check ms-2"><input name="remoto" value="1" class="form-check-input" type="checkbox" <?= !empty($_GET['remoto']) ? 'checked' : '' ?>> <label class="form-check-label">Remoto/local</label></div>
    <div class="col-md-2"><button class="btn btn-accent w-100" type="submit"><i class="bi bi-filter"></i> Filtrar</button></div>
</div></form>
<div class="card"><div class="card-body">
<div class="table-responsive"><table class="table align-middle">
<thead><tr><th>Nota</th><th>Titulo</th><th>Empresa</th><th>Local</th><th>Fonte</th><th>Salario</th><th>Status</th><th>Data</th><th>Acoes</th></tr></thead>
<tbody>
<?php foreach ($vagas as $vaga): ?>
<tr>
    <td><span class="score-pill"><?= (int)$vaga['nota_compatibilidade'] ?></span></td>
    <td><?= e($vaga['titulo']) ?></td><td><?= e($vaga['empresa']) ?></td><td><?= e($vaga['localizacao']) ?></td><td><?= e($vaga['fonte']) ?></td><td><?= e($vaga['salario']) ?></td>
    <td><span class="badge <?= badge_class($vaga['status']) ?>"><?= e($vaga['status']) ?></span></td><td><?= e($vaga['data_publicacao']) ?></td>
    <td class="text-nowrap">
        <a class="btn btn-sm btn-outline-light small-action" href="<?= url('vaga.php?id=' . (int)$vaga['id']) ?>"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-info small-action" data-status-id="<?= (int)$vaga['id'] ?>" data-status="interessante"><i class="bi bi-star"></i></button>
        <button class="btn btn-sm btn-outline-primary small-action" data-status-id="<?= (int)$vaga['id'] ?>" data-status="candidatado"><i class="bi bi-send"></i></button>
        <button class="btn btn-sm btn-outline-secondary small-action" data-status-id="<?= (int)$vaga['id'] ?>" data-status="arquivada"><i class="bi bi-archive"></i></button>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$vagas): ?><tr><td colspan="9" class="text-muted">Nenhuma vaga encontrada.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
