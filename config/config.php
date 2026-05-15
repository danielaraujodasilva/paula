<?php
declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'agente_vagas');
define('DB_USER', 'root');
define('DB_PASS', '');

define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'curriculos');

define('NODE_PATH', 'node');
define('NODE_SCRIPT_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'node');

$localConfig = __DIR__ . '/local.php';
if (is_file($localConfig)) {
    require_once $localConfig;
}

defined('DEPLOY_WEBHOOK_SECRET') || define('DEPLOY_WEBHOOK_SECRET', 'troque-este-segredo-no-servidor');
defined('DEPLOY_BRANCH') || define('DEPLOY_BRANCH', 'main');
defined('DEPLOY_REPO_PATH') || define('DEPLOY_REPO_PATH', ROOT_PATH);
defined('GIT_PATH') || define('GIT_PATH', 'git');
defined('BASE_URL') || define('BASE_URL', '/paula');
