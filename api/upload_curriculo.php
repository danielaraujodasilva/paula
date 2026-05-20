<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login_json();
$userId = current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['curriculo'])) {
    json_response(['success' => false, 'error' => 'Arquivo nao enviado.'], 400);
}

$file = $_FILES['curriculo'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(['success' => false, 'error' => 'Erro no upload.'], 400);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['pdf', 'docx', 'txt'], true)) {
    json_response(['success' => false, 'error' => 'Formato invalido. Use PDF, DOCX ou TXT.'], 400);
}

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0775, true);
}

$safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
$target = UPLOAD_DIR . DIRECTORY_SEPARATOR . date('YmdHis') . '-' . $safeName . '.' . $ext;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    json_response(['success' => false, 'error' => 'Nao foi possivel salvar o arquivo.'], 500);
}

$pdo->beginTransaction();
$stmt = $pdo->prepare('UPDATE curriculos SET ativo = 0 WHERE user_id = ?');
$stmt->execute([$userId]);
$stmt = $pdo->prepare('INSERT INTO curriculos (user_id, nome_arquivo, caminho_arquivo, tipo_arquivo, ativo) VALUES (?, ?, ?, ?, 1)');
$stmt->execute([$userId, $file['name'], $target, $ext]);
$id = (int)$pdo->lastInsertId();
$pdo->commit();

$cmd = 'cd /d ' . escapeshellarg(NODE_SCRIPT_DIR) . ' && ' . escapeshellcmd(NODE_PATH) . ' extract-resume.js ' . $id . ' ' . $userId . ' 2>&1';
$output = [];
$code = 0;
exec($cmd, $output, $code);
app_log($pdo, 'upload_curriculo', implode("\n", $output));

if ($code !== 0) {
    json_response(['success' => false, 'error' => 'Arquivo salvo, mas a extracao falhou. Rode npm install em node/.', 'details' => $output], 500);
}

$scoreCmd = 'cd /d ' . escapeshellarg(NODE_SCRIPT_DIR) . ' && ' . escapeshellcmd(NODE_PATH) . ' score-jobs.js --user=' . $userId . ' --all 2>&1';
$scoreOutput = [];
$scoreCode = 0;
exec($scoreCmd, $scoreOutput, $scoreCode);
app_log($pdo, 'score_curriculo', implode("\n", $scoreOutput));

if ($scoreCode !== 0) {
    json_response(['success' => true, 'message' => 'Curriculo enviado, mas nao foi possivel recalcular as vagas agora.', 'curriculo_id' => $id, 'score_warning' => $scoreOutput]);
}

json_response(['success' => true, 'message' => 'Curriculo enviado, perfil gerado e vagas recalculadas.', 'curriculo_id' => $id]);
