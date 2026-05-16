<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login_json();
$userId = current_user_id();

$rawPayload = json_decode(file_get_contents('php://input'), true);
if (is_array($rawPayload) && isset($rawPayload['perfil_json'])) {
    $decoded = json_decode((string)$rawPayload['perfil_json'], true);
    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
        json_response(['success' => false, 'error' => 'perfil_json invalido.'], 400);
    }
    $id = (int)($rawPayload['curriculo_id'] ?? 0);
    if ($id <= 0) {
        $active = active_curriculo($pdo, $userId);
        $id = (int)($active['id'] ?? 0);
    }
    if ($id <= 0) {
        json_response(['success' => false, 'error' => 'Nenhum curriculo ativo encontrado.'], 400);
    }
    $stmt = $pdo->prepare('UPDATE curriculos SET perfil_json = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
    $stmt->execute([json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), $id, $userId]);
    json_response(['success' => true]);
}

function field_lines(string $name): array
{
    $value = $_POST[$name] ?? '';
    return array_values(array_filter(array_map('trim', preg_split('/\R+/', (string)$value))));
}

$id = (int)($_POST['curriculo_id'] ?? 0);
if ($id <= 0) {
    json_response(['success' => false, 'error' => 'Curriculo invalido.'], 400);
}

$profile = [
    'nome' => trim((string)($_POST['nome'] ?? '')),
    'cargo_alvo' => field_lines('cargo_alvo'),
    'senioridade' => trim((string)($_POST['senioridade'] ?? '')),
    'localizacao' => trim((string)($_POST['localizacao'] ?? '')),
    'aceita_remoto' => (bool)($_POST['aceita_remoto'] ?? false),
    'habilidades' => field_lines('habilidades'),
    'ferramentas' => field_lines('ferramentas'),
    'experiencias' => field_lines('experiencias'),
    'palavras_chave' => field_lines('palavras_chave'),
    'palavras_proibidas' => field_lines('palavras_proibidas'),
];

$json = json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if (json_last_error() !== JSON_ERROR_NONE) {
    json_response(['success' => false, 'error' => 'JSON invalido.'], 400);
}

$stmt = $pdo->prepare('UPDATE curriculos SET perfil_json = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
$stmt->execute([$json, $id, $userId]);
header('Location: ../public/perfil.php?id=' . $id);
exit;
