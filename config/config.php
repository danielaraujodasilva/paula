<?php
declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'agente_vagas');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_URL', '/site/paula/public');
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'curriculos');

define('NODE_PATH', 'node');
define('NODE_SCRIPT_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'node');

define('DEPLOY_WEBHOOK_SECRET', 'troque-este-segredo-no-servidor');
define('DEPLOY_BRANCH', 'main');
define('DEPLOY_REPO_PATH', ROOT_PATH);
define('GIT_PATH', 'git');
