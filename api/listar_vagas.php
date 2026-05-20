<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login_json();

$where = ['user_id = ?'];
$params = [current_user_id()];
foreach (['titulo'] as $field) {
    if (!empty($_GET[$field])) { $where[] = "$field LIKE ?"; $params[] = '%' . $_GET[$field] . '%'; }
}
if (!empty($_GET['q'])) {
    $where[] = '(titulo LIKE ? OR empresa LIKE ? OR descricao LIKE ? OR localizacao LIKE ?)';
    $term = '%' . $_GET['q'] . '%';
    $params = array_merge($params, [$term, $term, $term, $term]);
}
foreach (['fonte', 'status', 'empresa', 'localizacao', 'salario', 'data_publicacao'] as $field) {
    if (!empty($_GET[$field])) { $where[] = "$field = ?"; $params[] = $_GET[$field]; }
}
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
$limit = min(max((int)($_GET['limit'] ?? 50), 1), 100);
$offset = max((int)($_GET['offset'] ?? 0), 0);
$order = $orderColumn . ' ' . $sortDir . ', id DESC';
$countSql = 'SELECT COUNT(*) FROM vagas' . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$sql = 'SELECT * FROM vagas' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $order LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
json_response([
    'success' => true,
    'data' => $rows,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset,
    'has_more' => $offset + count($rows) < $total,
]);
