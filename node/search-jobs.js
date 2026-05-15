const crypto = require('crypto');
const { pool } = require('./lib/db');
const { lines } = require('./lib/text');
const { normalizeText } = require('./lib/matcher');
const { searchRemotive } = require('./sources/remotive');
const { searchArbeitnow } = require('./sources/arbeitnow');
const { searchAdzuna } = require('./sources/adzuna');
const { searchRemoteOk } = require('./sources/remoteok');

const DEFAULT_SOURCES = ['Remotive', 'Arbeitnow', 'RemoteOK'];

function hashJob(job) {
  return crypto.createHash('sha256').update(`${job.fonte}|${job.url}|${job.titulo}|${job.empresa}`).digest('hex');
}

function parseSources(value) {
  try {
    const parsed = JSON.parse(value || '[]');
    return Array.isArray(parsed) && parsed.length ? parsed : DEFAULT_SOURCES;
  } catch {
    return DEFAULT_SOURCES;
  }
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
  const [rows] = await db.execute('SELECT * FROM curriculos WHERE ativo = 1 ORDER BY id DESC LIMIT 1');
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
    fontes: JSON.stringify(DEFAULT_SOURCES)
  }];
}

async function runSource(source, term, where) {
  if (source === 'Remotive') return searchRemotive(term);
  if (source === 'Arbeitnow') return searchArbeitnow(term);
  if (source === 'Adzuna') return searchAdzuna(term, where);
  if (source === 'RemoteOK') return searchRemoteOk(term);
  return [];
}

async function main() {
  const db = pool();
  let [searches] = await db.execute('SELECT * FROM buscas WHERE ativa = 1 ORDER BY id DESC');
  if (!searches.length) {
    searches = await buildFallbackSearches(db);
    if (!searches.length) {
      console.log('Nenhuma busca ativa encontrada e nao foi possivel montar uma busca pelo perfil ativo.');
      await db.end();
      return;
    }
    console.log('Nenhuma busca ativa encontrada. Usando busca automatica pelo perfil ativo.');
  }

  let inserted = 0;
  let ignoredByFilters = 0;
  for (const search of searches) {
    const terms = lines(search.termos);
    const sources = parseSources(search.fontes);
    for (const term of terms) {
      for (const source of sources) {
        try {
          const jobs = await runSource(source, term, search.localizacao || '');
          for (const job of jobs) {
            if (!matchesSearchFilters(job, search)) {
              ignoredByFilters += 1;
              continue;
            }

            const hash = hashJob(job);
            const [result] = await db.execute(
              `INSERT IGNORE INTO vagas (titulo, empresa, localizacao, salario, fonte, url, descricao, data_publicacao, hash_vaga, raw_json)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
              [
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
            if (result.affectedRows) inserted += 1;
          }
          console.log(`${source}: ${jobs.length} resultados brutos para "${term}".`);
        } catch (error) {
          console.error(`${source} falhou para "${term}": ${error.message}`);
        }
      }
    }
  }

  const logMessage = `${inserted} vagas novas salvas. ${ignoredByFilters} ignoradas pelos filtros.`;
  await db.execute('INSERT INTO logs_execucao (tipo, mensagem) VALUES (?, ?)', ['search-jobs', logMessage]);
  await db.end();
  console.log(logMessage);

  const { spawnSync } = require('child_process');
  const score = spawnSync(process.execPath, ['score-jobs.js'], { cwd: __dirname, stdio: 'inherit' });
  if (score.status !== 0) process.exit(score.status);
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
