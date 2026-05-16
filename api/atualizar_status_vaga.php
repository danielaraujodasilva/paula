<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login_json();

$payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = (int)($payload['id'] ?? 0);
$status = (string)($payload['status'] ?? '');

if ($id <= 0 || !in_array($status, status_options(), true)) {
    json_response(['success' => false, 'error' => 'Status ou vaga invalida.'], 400);
}

$stmt = $pdo->prepare('UPDATE vagas SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
$stmt->execute([$status, $id, current_user_id()]);
json_response(['success' => true]);
