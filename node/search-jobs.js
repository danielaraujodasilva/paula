const crypto = require('crypto');
const { pool } = require('./lib/db');
const { lines } = require('./lib/text');
const { normalizeText } = require('./lib/matcher');
const { searchRemotive } = require('./sources/remotive');
const { searchArbeitnow } = require('./sources/arbeitnow');
const { searchAdzuna, hasAdzunaKeys } = require('./sources/adzuna');
const { searchRemoteOk } = require('./sources/remoteok');
const { searchGupy, hasGupyToken } = require('./sources/gupy');
const { searchCodante } = require('./sources/codante');
const { searchHimalayas } = require('./sources/himalayas');

const DEFAULT_SOURCES = ['Remotive', 'Arbeitnow', 'RemoteOK', 'Adzuna', 'Gupy', 'Codante', 'Himalayas'];
const DEBUG = process.argv.includes('--debug') || process.env.DEBUG_JOBS === '1';
const userArg = process.argv.find((arg) => arg.startsWith('--user='));
const USER_ID = userArg ? Number(userArg.split('=')[1]) : Number(process.env.PAULA_USER_ID || 0);

function hashJob(job) {
  return crypto.createHash('sha256').update(`${job.fonte}|${job.url}|${job.titulo}|${job.empresa}`).digest('hex');
}

function parseSources() {
  return DEFAULT_SOURCES;
}

function uniqueTerms(values) {
  const terms = [];
  for (const value of values.flat()) {
    const term = String(value || '').trim();
    if (term && !terms.some((item) => item.toLowerCase() === term.toLowerCase())) {
      terms.push(term);
    }
  }
  return terms;
}

function splitFilterTerms(value) {
  return String(value || '')
    .split(/[\n,;|]+/)
    .map((item) => item.trim())
    .filter(Boolean);
}

function jobText(job) {
  return normalizeText(`${job.titulo || ''} ${job.empresa || ''} ${job.localizacao || ''} ${job.descricao || ''}`);
}

function matchesSearchFilters(job, search) {
  const text = jobText(job);
  const required = splitFilterTerms(search.palavras_obrigatorias);
  const forbidden = splitFilterTerms(search.palavras_proibidas);
  const where = normalizeText(search.localizacao || '');
  const remoteWanted = Number(search.remoto || 0) === 1;

  if (required.length && !required.every((term) => text.includes(normalizeText(term)))) {
    return false;
  }

  if (forbidden.length && forbidden.some((term) => text.includes(normalizeText(term)))) {
    return false;
  }

  if (where && where !== 'brasil') {
    const remoteText = text.includes('remote') || text.includes('remoto') || text.includes('anywhere') || text.includes('worldwide');
    if (!remoteWanted && !text.includes(where)) {
      return false;
    }
    if (remoteWanted && !remoteText && !text.includes(where)) {
      return false;
    }
  }

  return true;
}

async function buildFallbackSearches(db) {
  const [rows] = await db.execute('SELECT * FROM curriculos WHERE ativo = 1 AND user_id = ? ORDER BY id DESC LIMIT 1', [USER_ID]);
  if (!rows.length || !rows[0].perfil_json) return [];

  let profile;
  try {
    profile = JSON.parse(rows[0].perfil_json);
  } catch {
    return [];
  }

  const terms = uniqueTerms([
    profile.cargo_alvo || [],
    profile.palavras_chave || [],
    profile.habilidades || []
  ]).slice(0, 8);

  if (!terms.length) return [];

  return [{
    nome: 'Busca automatica pelo perfil ativo',
    termos: terms.join('\n'),
    localizacao: profile.localizacao || 'Brasil',
    remoto: profile.aceita_remoto ? 1 : 0,
    palavras_proibidas: Array.isArray(profile.palavras_proibidas) ? profile.palavras_proibidas.join('\n') : '',
    fontes: JSON.stringify(DEFAULT_SOURCES),
    user_id: USER_ID
  }];
}

async function runSource(source, term, where) {
  if (source === 'Remotive') return searchRemotive(term);
  if (source === 'Arbeitnow') return searchArbeitnow(term);
  if (source === 'Adzuna') return searchAdzuna(term, where);
  if (source === 'RemoteOK') return searchRemoteOk(term);
  if (source === 'Gupy') return searchGupy(term, where);
  if (source === 'Codante') return searchCodante(term);
  if (source === 'Himalayas') return searchHimalayas(term, where);
  return [];
}

async function main() {
  if (!USER_ID) {
    throw new Error('Informe o usuario com --user=ID.');
  }
  const db = pool();
  let [searches] = await db.execute('SELECT * FROM buscas WHERE ativa = 1 AND user_id = ? ORDER BY id DESC', [USER_ID]);
  if (!searches.length) {
    searches = await buildFallbackSearches(db);
    if (!searches.length) {
      console.log('Nenhuma busca ativa encontrada e nao foi possivel montar uma busca pelo perfil ativo.');
      await db.end();
      return;
    }
    console.log('Nenhuma busca ativa encontrada. Usando busca automatica pelo perfil ativo.');
  }

  console.log(`Buscas ativas: ${searches.length}. Adzuna keys: ${hasAdzunaKeys() ? 'configuradas' : 'ausentes'}. Gupy token: ${hasGupyToken() ? 'configurado' : 'ausente'}.`);

  let inserted = 0;
  let ignoredByFilters = 0;
  const failures = [];
  for (const search of searches) {
    const terms = lines(search.termos);
    const sources = parseSources(search.fontes);
    console.log(`Busca "${search.nome || search.id}": fontes ${sources.join(', ')} | termos: ${terms.join(', ')}`);

    for (const term of terms) {
      for (const source of sources) {
        try {
          const jobs = await runSource(source, term, search.localizacao || '');
          let savedFromSource = 0;
          for (const job of jobs) {
            if (!matchesSearchFilters(job, search)) {
              ignoredByFilters += 1;
              continue;
            }

            const hash = hashJob(job);
            const [result] = await db.execute(
              `INSERT IGNORE INTO vagas (user_id, titulo, empresa, localizacao, salario, fonte, url, descricao, data_publicacao, hash_vaga, raw_json)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
              [
                USER_ID,
                job.titulo || '',
                job.empresa || '',
                job.localizacao || '',
                job.salario || '',
                job.fonte || source,
                job.url || '',
                job.descricao || '',
                job.data_publicacao || '',
                hash,
                JSON.stringify(job.raw_json || job)
              ]
            );
            if (result.affectedRows) {
              inserted += 1;
              savedFromSource += 1;
            }
          }
          console.log(`${source}: ${jobs.length} resultados brutos para "${term}". ${savedFromSource} novas vagas salvas.`);
          if (DEBUG && source === 'Adzuna' && jobs.length === 0) {
            console.log(`Debug Adzuna: sem resultados para termo="${term}" local="${search.localizacao || ''}".`);
          }
        } catch (error) {
          const extra = error.response?.data ? ` | resposta: ${JSON.stringify(error.response.data).slice(0, 500)}` : '';
          const warning = `${source} falhou para "${term}": ${error.message}${extra}`;
          failures.push(warning);
          console.error(`ALERTA: ${warning}`);
        }
      }
    }
  }

  const logMessage = `${inserted} vagas novas salvas. ${ignoredByFilters} ignoradas pelos filtros.${failures.length ? ` Alertas: ${failures.length} fonte(s)/termo(s) falharam.` : ''}`;
  await db.execute('INSERT INTO logs_execucao (user_id, tipo, mensagem) VALUES (?, ?, ?)', [USER_ID, 'search-jobs', logMessage]);
  await db.end();
  console.log(logMessage);
  if (failures.length) {
    console.log('Alertas de fontes:');
    failures.forEach((failure) => console.log(`- ${failure}`));
  }

  const { spawnSync } = require('child_process');
  const score = spawnSync(process.execPath, ['score-jobs.js', `--user=${USER_ID}`], { cwd: __dirname, stdio: 'inherit' });
  if (score.status !== 0) process.exit(score.status);
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
