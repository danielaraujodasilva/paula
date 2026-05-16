<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nome VARCHAR(120) NOT NULL,
      email VARCHAR(190) NOT NULL UNIQUE,
      senha_hash VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    foreach (['curriculos', 'buscas', 'vagas', 'logs_execucao'] as $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'user_id'");
        $stmt->execute([$table]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN user_id INT NULL AFTER id");
        }
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vagas' AND INDEX_NAME = 'hash_vaga'");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() > 0) {
        $pdo->exec('ALTER TABLE vagas DROP INDEX hash_vaga');
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vagas' AND INDEX_NAME = 'idx_vagas_usuario_hash'");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE vagas ADD UNIQUE KEY idx_vagas_usuario_hash (user_id, hash_vaga)');
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit('Erro ao conectar no banco de dados. Confira config/config.php e se o MySQL esta ativo.');
}
