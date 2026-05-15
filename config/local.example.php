<?php
declare(strict_types=1);

// Copie este arquivo para config/local.php no servidor.
// O arquivo local.php fica fora do Git e pode guardar segredos.

defined('DEPLOY_WEBHOOK_SECRET') || define('DEPLOY_WEBHOOK_SECRET', 'troque-este-segredo-no-servidor');
defined('DEPLOY_BRANCH') || define('DEPLOY_BRANCH', 'main');
defined('DEPLOY_REPO_PATH') || define('DEPLOY_REPO_PATH', ROOT_PATH);
defined('GIT_PATH') || define('GIT_PATH', 'git');
defined('BASE_URL') || define('BASE_URL', '/paula');
