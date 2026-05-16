const { pool } = require('./lib/db');
const { scoreJob } = require('./lib/matcher');

async function main() {
  const recalcAll = process.argv.includes('--all');
  const userArg = process.argv.find((arg) => arg.startsWith('--user='));
  const userId = userArg ? Number(userArg.split('=')[1]) : Number(process.env.PAULA_USER_ID || 0);
  if (!userId) throw new Error('Informe o usuario com --user=ID.');
  const db = pool();
  const [profiles] = await db.execute('SELECT * FROM curriculos WHERE ativo = 1 AND user_id = ? ORDER BY id DESC LIMIT 1', [userId]);
  if (!profiles.length || !profiles[0].perfil_json) {
    console.log('Nenhum curriculo ativo com perfil_json encontrado.');
    await db.end();
    return;
  }
  const profile = JSON.parse(profiles[0].perfil_json);
  const [jobs] = await db.execute(`SELECT * FROM vagas WHERE user_id = ? ${recalcAll ? '' : 'AND nota_compatibilidade = 0'} ORDER BY id DESC`, [userId]);
  let updated = 0;
  for (const job of jobs) {
    const result = scoreJob(job, profile);
    await db.execute('UPDATE vagas SET nota_compatibilidade = ?, resumo_compatibilidade = ?, updated_at = NOW() WHERE id = ?', [
      result.score,
      result.resumo,
      job.id
    ]);
    updated += 1;
  }
  await db.execute('INSERT INTO logs_execucao (user_id, tipo, mensagem) VALUES (?, ?, ?)', [userId, 'score-jobs', `${updated} vagas pontuadas.`]);
  await db.end();
  console.log(`${updated} vagas pontuadas.`);
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
