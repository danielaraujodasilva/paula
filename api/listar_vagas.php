<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login_json();

$where = ['user_id = ?'];
$params = [current_user_id()];
if (!empty($_GET['q'])) {
    $where[] = '(titulo LIKE ? OR empresa LIKE ? OR descricao LIKE ?)';
    $term = '%' . $_GET['q'] . '%';
    $params = array_merge($params, [$term, $term, $term]);
}
if (!empty($_GET['fonte'])) { $where[] = 'fonte = ?'; $params[] = $_GET['fonte']; }
if (!empty($_GET['status'])) { $where[] = 'status = ?'; $params[] = $_GET['status']; }
if (isset($_GET['nota_min']) && $_GET['nota_min'] !== '') { $where[] = 'nota_compatibilidade >= ?'; $params[] = (int)$_GET['nota_min']; }

$sql = 'SELECT * FROM vagas' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY nota_compatibilidade DESC, id DESC LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
json_response(['success' => true, 'data' => $stmt->fetchAll()]);
