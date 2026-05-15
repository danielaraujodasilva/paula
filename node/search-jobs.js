const crypto = require('crypto');
const { pool } = require('./lib/db');
const { lines } = require('./lib/text');
const { searchRemotive } = require('./sources/remotive');
const { searchArbeitnow } = require('./sources/arbeitnow');
const { searchAdzuna } = require('./sources/adzuna');

function hashJob(job) {
  return crypto.createHash('sha256').update(`${job.fonte}|${job.url}|${job.titulo}|${job.empresa}`).digest('hex');
}

function parseSources(value) {
  try {
    const parsed = JSON.parse(value || '[]');
    return Array.isArray(parsed) && parsed.length ? parsed : ['Remotive', 'Arbeitnow'];
  } catch {
    return ['Remotive', 'Arbeitnow'];
  }
}

async function runSource(source, term, where) {
  if (source === 'Remotive') return searchRemotive(term);
  if (source === 'Arbeitnow') return searchArbeitnow(term);
  if (source === 'Adzuna') return searchAdzuna(term, where);
  return [];
}

async function main() {
  const db = pool();
  const [searches] = await db.execute('SELECT * FROM buscas WHERE ativa = 1 ORDER BY id DESC');
  if (!searches.length) {
    console.log('Nenhuma busca ativa encontrada.');
    await db.end();
    return;
  }

  let inserted = 0;
  for (const search of searches) {
    const terms = lines(search.termos);
    const sources = parseSources(search.fontes);
    for (const term of terms) {
      for (const source of sources) {
        try {
          const jobs = await runSource(source, term, search.localizacao || '');
          for (const job of jobs) {
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
          console.log(`${source}: ${jobs.length} resultados para "${term}".`);
        } catch (error) {
          console.error(`${source} falhou para "${term}": ${error.message}`);
        }
      }
    }
  }

  await db.execute('INSERT INTO logs_execucao (tipo, mensagem) VALUES (?, ?)', ['search-jobs', `${inserted} vagas novas salvas.`]);
  await db.end();
  console.log(`${inserted} vagas novas salvas.`);

  const { spawnSync } = require('child_process');
  const score = spawnSync(process.execPath, ['score-jobs.js'], { cwd: __dirname, stdio: 'inherit' });
  if (score.status !== 0) process.exit(score.status);
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
