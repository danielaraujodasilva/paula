const axios = require('axios');

async function searchRemotive(term) {
  const url = 'https://remotive.com/api/remote-jobs';
  const response = await axios.get(url, { params: { search: term }, timeout: 20000 });
  return (response.data.jobs || []).map((job) => ({
    titulo: job.title || '',
    empresa: job.company_name || '',
    localizacao: job.candidate_required_location || 'Remoto',
    salario: job.salary || '',
    fonte: 'Remotive',
    url: job.url || '',
    descricao: job.description || '',
    data_publicacao: job.publication_date || '',
    raw_json: job
  }));
}

module.exports = { searchRemotive };
