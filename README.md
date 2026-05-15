# Projeto Paula

Primeira versao funcional de um sistema web local para enviar curriculos, extrair texto, montar um perfil profissional, buscar vagas gratuitas em APIs publicas, salvar no MySQL e calcular compatibilidade entre perfil e vaga.

## Stack

- PHP 8+
- MySQL ou MariaDB
- Bootstrap 5
- JavaScript vanilla
- Node.js para extracao, busca e pontuacao
- Windows + XAMPP

## Instalacao no Windows + XAMPP

1. Copie a pasta `agente-vagas` para:

```bat
C:\xampp\htdocs\site\paula
```

2. Inicie Apache e MySQL pelo XAMPP.

3. Crie o banco `agente_vagas` no phpMyAdmin ou MySQL.

4. Importe o arquivo:

```bat
C:\xampp\htdocs\site\paula\database\schema.sql
```

5. Confira a configuracao em:

```bat
C:\xampp\htdocs\site\paula\config\config.php
```

Padrao esperado para XAMPP:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'agente_vagas');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/site/paula/public');
```

6. Instale as dependencias Node:

```bat
cd C:\xampp\htdocs\site\paula\node
npm install
```

7. Copie `.env.example` para `.env`:

```bat
copy .env.example .env
```

Adzuna e opcional. Remotive e Arbeitnow funcionam sem chave. Para usar Adzuna, preencha:

```env
ADZUNA_APP_ID=
ADZUNA_APP_KEY=
ADZUNA_COUNTRY=br
```

8. Abra no navegador:

```text
http://localhost/site/paula/public
```

## Fluxo de uso

1. Acesse `Curriculos` e envie um PDF, DOCX ou TXT.
2. O PHP salva o arquivo e chama `node/extract-resume.js`.
3. O Node extrai o texto e gera um `perfil_json` inicial por heuristicas.
4. Acesse `Perfil` para revisar e editar manualmente.
5. Acesse `Buscas` para configurar termos, localizacao, remoto e fontes.
6. Clique em `Rodar busca agora` no topo do sistema.
7. Veja as vagas em `Vagas`, filtre, abra detalhes e altere status.

## Busca manual

```bat
cd C:\xampp\htdocs\site\paula\node
node search-jobs.js
```

O script busca vagas novas, evita duplicadas por `hash_vaga` e chama `score-jobs.js` automaticamente.

## Recalcular todas as notas

```bat
cd C:\xampp\htdocs\site\paula\node
node score-jobs.js --all
```

## Automatizar no Windows

Crie um arquivo `rodar-busca-paula.bat` com:

```bat
@echo off
cd /d C:\xampp\htdocs\site\paula\node
node search-jobs.js
```

Depois agende esse `.bat` no Agendador de Tarefas do Windows.

## Atualizar servidor automaticamente via GitHub Webhook

O projeto inclui o endpoint:

```text
api/github_webhook.php
```

Quando o GitHub receber um push na branch configurada, ele chama esse arquivo no servidor. O PHP valida a assinatura do GitHub e roda:

```bat
git fetch origin main
git pull --ff-only origin main
```

### 1. Preparar o servidor

No servidor, o projeto precisa estar clonado por Git, nao apenas copiado por FTP:

```bat
cd /d C:\xampp\htdocs\site
git clone https://github.com/SEU_USUARIO/paula.git paula
```

O diretorio `C:\xampp\htdocs\site\paula` precisa conter `config`, `api`, `public`, `node` e tambem a pasta `.git`.

Confira se o Apache/PHP consegue executar `git` pelo PATH. Se nao conseguir, altere em `config/config.php`:

```php
define('GIT_PATH', 'C:\\Program Files\\Git\\cmd\\git.exe');
```

### 2. Configurar segredo no servidor

Edite `config/config.php` no servidor e troque:

```php
define('DEPLOY_WEBHOOK_SECRET', 'troque-este-segredo-no-servidor');
define('DEPLOY_BRANCH', 'main');
define('DEPLOY_REPO_PATH', ROOT_PATH);
```

Use uma frase longa e aleatoria, por exemplo:

```php
define('DEPLOY_WEBHOOK_SECRET', 'paula-uma-frase-bem-grande-e-secreta-2026');
```

Use esse mesmo valor no campo `Secret` do GitHub Webhook.

`DEPLOY_REPO_PATH` precisa apontar para a pasta que contem `.git`. Como o servidor vai usar `C:\xampp\htdocs\site\paula`, use:

```php
define('DEPLOY_REPO_PATH', ROOT_PATH);
```

### 3. Criar Webhook no GitHub

No repositorio do GitHub:

1. Va em `Settings`.
2. Clique em `Webhooks`.
3. Clique em `Add webhook`.
4. Em `Payload URL`, coloque:

```text
https://SEU_DOMINIO.com/site/paula/api/github_webhook.php
```

Se for rede local ou servidor sem HTTPS publico, use a URL publica real que o GitHub consegue acessar.

5. Em `Content type`, selecione:

```text
application/json
```

6. Em `Secret`, coloque exatamente o mesmo valor de `DEPLOY_WEBHOOK_SECRET`.
7. Em eventos, marque:

```text
Just the push event
```

8. Deixe `Active` marcado e salve.

### 4. Testar

Depois de salvar, o GitHub envia um evento `ping`. Se estiver tudo certo, ele deve receber resposta `200`.

Para testar deploy de verdade:

```bat
git add .
git commit -m "Teste deploy webhook"
git push origin main
```

No servidor, veja o log em:

```bat
C:\xampp\htdocs\site\paula\logs\deploy.log
```

Se aparecer erro de permissao, o usuario do Apache precisa ter permissao de escrita na pasta do projeto. Se aparecer erro de Git, rode manualmente no servidor:

```bat
cd /d C:\xampp\htdocs\site\paula
git pull --ff-only origin main
```

## Seguranca local

- As queries usam prepared statements.
- Upload aceita apenas PDF, DOCX e TXT.
- A pasta `uploads/curriculos` tem `.htaccess` para bloquear PHP e listagem.
- A UI sanitiza saida com `htmlspecialchars`.
- O `.env` fica dentro de `node` e nao deve ser exposto publicamente.

## Arquivos principais

- `public/index.php`: dashboard.
- `public/curriculos.php`: upload/lista de curriculos.
- `public/perfil.php`: edicao do perfil profissional.
- `public/configuracoes.php`: configuracao das buscas.
- `public/vagas.php`: filtros e tabela de vagas.
- `public/vaga.php`: detalhe, compatibilidade e mensagem sugerida.
- `node/extract-resume.js`: extracao de texto e perfil inicial.
- `node/search-jobs.js`: busca Remotive, Arbeitnow e Adzuna opcional.
- `node/score-jobs.js`: pontuacao local sem API paga.
- `database/schema.sql`: estrutura do banco.

## Observacoes

Esta versao nao usa IA paga, login obrigatorio ou frameworks pesados. A pontuacao e baseada em texto normalizado, palavras-chave, habilidades, localizacao, senioridade e alertas simples.
