<?php
declare(strict_types=1);

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

function active_curriculo(PDO $pdo): ?array
{
    $stmt = $pdo->query('SELECT * FROM curriculos WHERE ativo = 1 ORDER BY id DESC LIMIT 1');
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
    $stmt = $pdo->prepare('INSERT INTO logs_execucao (tipo, mensagem) VALUES (?, ?)');
    $stmt->execute([$tipo, $mensagem]);
}
