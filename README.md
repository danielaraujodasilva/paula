# Projeto Paula

Sistema web local para enviar curriculos, extrair texto, montar um perfil profissional, buscar vagas gratuitas em APIs publicas, salvar no MySQL e calcular compatibilidade entre perfil e vaga.

## Stack

- PHP 8+
- MySQL ou MariaDB
- Bootstrap 5
- JavaScript vanilla
- Node.js para extracao, busca e pontuacao
- Windows + XAMPP

## Fontes de vagas integradas

Fontes sem chave, gratuitas na primeira versao:

- Remotive
- Arbeitnow
- RemoteOK
- Codante
- Himalayas
- Remotar
- ProgramaThor
- Netvagas

Fonte opcional com cadastro gratuito/chave:

- Adzuna
- Gupy

Observacao honesta: Remotar, ProgramaThor e Netvagas melhoram bastante a cobertura brasileira. Remotive, Arbeitnow, RemoteOK e Himalayas continuam fortes para remoto/internacional. Codante ajuda em tech no Brasil. Para Brasil/Sao Paulo, use termos em portugues e ingles e configure Adzuna e Gupy quando puder. Buscar direto em LinkedIn/Indeed por robo fica fora por enquanto, porque esses sites bloqueiam automacao e scraping com frequencia.

## Instalacao no Windows + XAMPP

1. Copie a pasta do projeto para:

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
define('BASE_URL', '/paula');
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

Adzuna e opcional. Para usar Adzuna, preencha:

```env
ADZUNA_APP_ID=
ADZUNA_APP_KEY=
ADZUNA_COUNTRY=br
GUPY_TOKEN=
```

Sem `GUPY_TOKEN`, a fonte Gupy aparece como ignorada no log da busca. Se qualquer fonte falhar durante a pesquisa, a tela mostra o alerta retornado pelo buscador.

Fontes experimentais podem ser ativadas por busca na tela `Buscas`:

- TrabalhaBrasil Experimental: scraping de listagem publica.
- APinfo Experimental: vagas recentes da APinfo filtradas por termo.
- Nerdin Experimental: scraping de vagas de TI com dados estruturados quando disponiveis.
- RSS Experimental: feeds RSS, incluindo o feed padrao do BNE e URLs extras em `PAULA_EXPERIMENTAL_FEEDS`.

Por serem experimentais, elas ficam desligadas por padrao. Se falharem, a busca continua e mostra o alerta no retorno.

8. Abra no navegador:

```text
https://danieltatuador.com/paula/
```

## Monetizacao opcional

O projeto ja vem preparado para analytics, anuncios e apoio voluntario. Copie `config/local.example.php` para `config/local.php` no servidor e preencha apenas o que quiser ativar:

```php
define('SITE_CONTACT_EMAIL', 'contato@seudominio.com');
define('SITE_PUBLIC_URL', 'https://seudominio.com/paula');
define('GOOGLE_ANALYTICS_ID', 'G-XXXXXXXXXX');
define('GOOGLE_ADSENSE_CLIENT', 'ca-pub-XXXXXXXXXXXXXXXX');
define('GOOGLE_ADSENSE_SLOT_MAIN', '1234567890');
define('DONATION_URL', 'https://github.com/sponsors/seuusuario');
define('DONATION_PIX_KEY', 'sua-chave-pix');
```

Se `GOOGLE_ADSENSE_CLIENT` e `GOOGLE_ADSENSE_SLOT_MAIN` estiverem vazios, nenhum anuncio e carregado. Se `DONATION_URL` ou `DONATION_PIX_KEY` estiverem preenchidos, o sistema mostra um bloco discreto de apoio. As paginas publicas `privacidade.php`, `contato.php` e `apoie.php` ajudam na aprovacao e transparencia.

A tela interna `monetizacao.php` so aparece para o e-mail definido em `ADMIN_EMAIL`. O diretorio `config/` tambem possui bloqueio por `.htaccess`, e os arquivos `config.php` e `database.php` recusam acesso direto pelo navegador.

## Fluxo de uso

1. Acesse `Curriculos` e envie um PDF, DOCX ou TXT.
2. O PHP salva o arquivo e chama `node/extract-resume.js`.
3. O Node extrai o texto e gera um `perfil_json` inicial por heuristicas.
4. Acesse `Perfil` para revisar e editar manualmente.
5. Acesse `Buscas` para configurar termos, localizacao, remoto, palavras obrigatorias e palavras proibidas. As fontes gratuitas configuradas rodam sempre juntas. Fontes experimentais podem ser ligadas por busca.
6. Clique em `Rodar busca agora` no topo do sistema.
7. Veja as vagas em `Vagas`, filtre, abra detalhes e altere status.

## Como melhorar resultado para Brasil/Sao Paulo

Crie buscas com termos variados, por exemplo:

```text
assistente administrativo
auxiliar administrativo
atendimento ao cliente
recepcionista
web designer
designer grafico
social media
marketing digital
```

Em localizacao, use:

```text
Brasil
Sao Paulo
Guarulhos
Remote
```

Use palavras proibidas para cortar furada:

```text
porta a porta
comissao apenas
sem salario fixo
vendedor externo
```

## Busca manual

```bat
cd C:\xampp\htdocs\site\paula\node
node search-jobs.js
```

O script busca vagas novas, aplica filtros antes de salvar, evita duplicadas por `hash_vaga` e chama `score-jobs.js` automaticamente.

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
git clone https://github.com/danielaraujodasilva/paula.git paula
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

### 3. Criar Webhook no GitHub

No repositorio do GitHub:

1. Va em `Settings`.
2. Clique em `Webhooks`.
3. Clique em `Add webhook`.
4. Em `Payload URL`, coloque:

```text
https://SEU_DOMINIO.com/paula/api/github_webhook.php
```

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
- `node/search-jobs.js`: busca Remotive, Arbeitnow, RemoteOK e Adzuna opcional.
- `node/score-jobs.js`: pontuacao local sem API paga.
- `database/schema.sql`: estrutura do banco.

## Proximas melhorias planejadas

- Fontes por pagina de carreira de empresa, como Lever e Greenhouse.
- Painel para cadastrar empresas alvo e procurar vagas diretamente nelas.
- Gerador de curriculo adaptado para cada vaga.
- Alertas por WhatsApp ou e-mail.

Esta versao nao usa IA paga ou frameworks pesados. A pontuacao e baseada em texto normalizado, palavras-chave, habilidades, localizacao, senioridade e alertas simples.
