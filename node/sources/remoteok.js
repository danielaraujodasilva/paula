const axios = require('axios');
const { containsAny } = require('../lib/matcher');

async function searchRemoteOk(term) {
  const response = await axios.get('https://remoteok.com/api', {
    timeout: 25000,
    headers: {
      'User-Agent': 'Projeto Paula Job Search Bot - contato local',
      'Accept': 'application/json'
    }
  });

  const rows = Array.isArray(response.data) ? response.data.slice(1) : [];
  return rows
    .filter((job) => containsAny(`${job.position || ''} ${job.company || ''} ${(job.tags || []).join(' ')} ${job.description || ''}`, [term]))
    .map((job) => ({
      titulo: job.position || '',
      empresa: job.company || '',
      localizacao: job.location || 'Remote',
      salario: job.salary_min || job.salary_max ? `${job.salary_min || 0} - ${job.salary_max || 0}` : '',
      fonte: 'RemoteOK',
      url: job.apply_url || job.url || '',
      descricao: job.description || '',
      data_publicacao: job.date || '',
      raw_json: job
    }));
}

module.exports = { searchRemoteOk };
