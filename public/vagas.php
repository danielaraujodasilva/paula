<?php
$pageTitle = 'Vagas';
require_once __DIR__ . '/../includes/header.php';

$userId = current_user_id();
$params = [$userId];
$where = ['user_id = ?'];
foreach (['titulo'] as $field) {
    if (!empty($_GET[$field])) { $where[] = "$field LIKE ?"; $params[] = '%' . $_GET[$field] . '%'; }
}
foreach (['fonte', 'status', 'empresa', 'localizacao', 'salario', 'data_publicacao'] as $field) {
    if (!empty($_GET[$field])) { $where[] = "$field = ?"; $params[] = $_GET[$field]; }
}
if (!empty($_GET['q'])) { $where[] = '(titulo LIKE ? OR empresa LIKE ? OR descricao LIKE ? OR localizacao LIKE ?)'; $params[] = '%' . $_GET['q'] . '%'; $params[] = '%' . $_GET['q'] . '%'; $params[] = '%' . $_GET['q'] . '%'; $params[] = '%' . $_GET['q'] . '%'; }
if (isset($_GET['nota_min']) && $_GET['nota_min'] !== '') { $where[] = 'nota_compatibilidade >= ?'; $params[] = (int)$_GET['nota_min']; }
if (isset($_GET['nota_max']) && $_GET['nota_max'] !== '') { $where[] = 'nota_compatibilidade <= ?'; $params[] = (int)$_GET['nota_max']; }
if (!empty($_GET['remoto'])) { $where[] = "(localizacao LIKE '%remote%' OR localizacao LIKE '%remoto%' OR descricao LIKE '%remote%' OR descricao LIKE '%remoto%')"; }
$orderMap = [
    'nota' => 'nota_compatibilidade',
    'recentes' => 'id',
    'empresa' => 'empresa',
    'titulo' => 'titulo',
    'localizacao' => 'localizacao',
    'fonte' => 'fonte',
    'salario' => 'salario',
    'status' => 'status',
    'data' => 'data_publicacao',
];
$sortKey = $_GET['ordem'] ?? 'nota';
$sortDir = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
$orderColumn = $orderMap[$sortKey] ?? 'nota_compatibilidade';
$order = $orderColumn . ' ' . $sortDir . ', id DESC';
$sql = 'SELECT * FROM vagas' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $order LIMIT 300";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vagas = $stmt->fetchAll();

function distinct_options(PDO $pdo, int $userId, string $field): array
{
    $allowed = ['fonte', 'status', 'empresa', 'localizacao', 'salario', 'data_publicacao'];
    if (!in_array($field, $allowed, true)) { return []; }
    $stmt = $pdo->prepare("SELECT DISTINCT {$field} AS value FROM vagas WHERE user_id = ? AND {$field} IS NOT NULL AND {$field} <> '' ORDER BY {$field} ASC LIMIT 500");
    $stmt->execute([$userId]);
    return array_map('strval', array_column($stmt->fetchAll(), 'value'));
}

function filter_select(string $name, string $label, array $options): void
{
    $current = (string)($_GET[$name] ?? '');
    echo '<div class="col-md-2"><label class="form-label">' . e($label) . '</label><select name="' . e($name) . '" class="form-select">';
    echo '<option value="">Todos</option>';
    foreach ($options as $option) {
        echo '<option value="' . e($option) . '"' . ($current === $option ? ' selected' : '') . '>' . e($option) . '</option>';
    }
    echo '</select></div>';
}

$opcoes = [
    'fonte' => distinct_options($pdo, $userId, 'fonte'),
    'status' => distinct_options($pdo, $userId, 'status') ?: status_options(),
    'empresa' => distinct_options($pdo, $userId, 'empresa'),
    'localizacao' => distinct_options($pdo, $userId, 'localizacao'),
    'salario' => distinct_options($pdo, $userId, 'salario'),
    'data_publicacao' => distinct_options($pdo, $userId, 'data_publicacao'),
];

function sort_link(string $key, string $label): string
{
    $params = $_GET;
    $params['ordem'] = $key;
    $active = ($_GET['ordem'] ?? 'nota') === $key;
    $dir = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
    $params['dir'] = $active && $dir === 'asc' ? 'desc' : 'asc';
    $icon = $active ? ($dir === 'asc' ? ' <i class="bi bi-caret-up-fill"></i>' : ' <i class="bi bi-caret-down-fill"></i>') : '';
    return '<a class="link-light text-decoration-none" href="?' . http_build_query($params) . '">' . e($label) . $icon . '</a>';
}
?>
<?php render_monetization_block('vagas'); ?>

<form class="card mb-4"><div class="card-body row g-3 align-items-end">
    <div class="col-md-3"><label class="form-label">Texto livre</label><input name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label">Titulo</label><input name="titulo" class="form-control" value="<?= e($_GET['titulo'] ?? '') ?>"></div>
    <?php filter_select('fonte', 'Fonte', $opcoes['fonte']); ?>
    <?php filter_select('status', 'Status', $opcoes['status']); ?>
    <div class="col-md-2"><label class="form-label">Nota minima</label><input name="nota_min" type="number" class="form-control" value="<?= e($_GET['nota_min'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label">Nota maxima</label><input name="nota_max" type="number" class="form-control" value="<?= e($_GET['nota_max'] ?? '') ?>"></div>
    <?php filter_select('empresa', 'Empresa', $opcoes['empresa']); ?>
    <?php filter_select('localizacao', 'Localidade', $opcoes['localizacao']); ?>
    <?php filter_select('salario', 'Salario', $opcoes['salario']); ?>
    <?php filter_select('data_publicacao', 'Data', $opcoes['data_publicacao']); ?>
    <div class="col-md-2"><label class="form-label">Ordenar</label><select name="ordem" class="form-select"><?php foreach (['nota' => 'Nota', 'recentes' => 'Mais recentes', 'localizacao' => 'Localidade', 'empresa' => 'Empresa', 'titulo' => 'Titulo', 'fonte' => 'Fonte', 'salario' => 'Salario', 'status' => 'Status', 'data' => 'Data publicacao'] as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['ordem'] ?? 'nota') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label">Direcao</label><select name="dir" class="form-select"><option value="desc" <?= ($_GET['dir'] ?? 'desc') === 'desc' ? 'selected' : '' ?>>Decrescente</option><option value="asc" <?= ($_GET['dir'] ?? '') === 'asc' ? 'selected' : '' ?>>Crescente</option></select></div>
    <div class="col-md-2 form-check ms-2"><input name="remoto" value="1" class="form-check-input" type="checkbox" <?= !empty($_GET['remoto']) ? 'checked' : '' ?>> <label class="form-check-label">Somente remoto</label></div>
    <div class="col-md-2"><button class="btn btn-accent w-100" type="submit"><i class="bi bi-filter"></i> Filtrar</button></div>
    <div class="col-md-2"><a class="btn btn-outline-light w-100" href="vagas.php"><i class="bi bi-x-circle"></i> Limpar</a></div>
</div></form>
<div class="card"><div class="card-body">
<div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="h5 mb-0">Resultados</h2>
    <span class="text-muted small"><?= count($vagas) ?> vagas exibidas, limite de 300.</span>
</div>
<div class="table-responsive"><table class="table align-middle">
<thead><tr><th><?= sort_link('nota', 'Nota') ?></th><th><?= sort_link('titulo', 'Titulo') ?></th><th><?= sort_link('empresa', 'Empresa') ?></th><th><?= sort_link('localizacao', 'Local') ?></th><th><?= sort_link('fonte', 'Fonte') ?></th><th><?= sort_link('salario', 'Salario') ?></th><th><?= sort_link('status', 'Status') ?></th><th><?= sort_link('data', 'Data') ?></th><th>Acoes</th></tr></thead>
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
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
