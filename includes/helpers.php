<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function current_user_id(): int
{
    return (int)($_SESSION['usuario']['id'] ?? 0);
}

function is_auth_page(): bool
{
    return in_array(basename($_SERVER['SCRIPT_NAME']), ['login.php', 'register.php'], true);
}

function require_login(): void
{
    if (current_user_id() > 0 || is_auth_page()) {
        return;
    }
    header('Location: ' . url('login.php'));
    exit;
}

function require_login_json(): void
{
    if (current_user_id() <= 0) {
        json_response(['success' => false, 'error' => 'Login necessario.'], 401);
    }
}

function user_where(string $column = 'user_id'): string
{
    return "({$column} = ? OR {$column} IS NULL)";
}

function claim_legacy_rows(PDO $pdo, int $userId): void
{
    foreach (['curriculos', 'buscas', 'vagas', 'logs_execucao'] as $table) {
        $stmt = $pdo->prepare("UPDATE {$table} SET user_id = ? WHERE user_id IS NULL");
        $stmt->execute([$userId]);
    }
}

function active_curriculo(PDO $pdo, ?int $userId = null): ?array
{
    $userId = $userId ?? current_user_id();
    $stmt = $pdo->prepare('SELECT * FROM curriculos WHERE ativo = 1 AND user_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function badge_class(string $status): string
{
    return match ($status) {
        'interessante' => 'text-bg-info',
        'candidatado' => 'text-bg-primary',
        'entrevista' => 'text-bg-success',
        'rejeitada' => 'text-bg-danger',
        'arquivada' => 'text-bg-secondary',
        default => 'text-bg-warning',
    };
}

function status_options(): array
{
    return ['nova', 'interessante', 'candidatado', 'entrevista', 'rejeitada', 'arquivada'];
}

function app_log(PDO $pdo, string $tipo, string $mensagem): void
{
    $stmt = $pdo->prepare('INSERT INTO logs_execucao (user_id, tipo, mensagem) VALUES (?, ?, ?)');
    $stmt->execute([current_user_id() ?: null, $tipo, $mensagem]);
}
