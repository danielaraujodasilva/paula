const axios = require('axios');

function locationLabel(job) {
  const restrictions = Array.isArray(job.locationRestrictions) ? job.locationRestrictions : [];
  const timezones = Array.isArray(job.timezoneRestriction) ? job.timezoneRestriction : [];
  if (restrictions.length) return restrictions.join(', ');
  if (timezones.length) return `Remoto ${timezones.join(', ')}`;
  return 'Remote / Worldwide';
}

async function searchHimalayas(term, where = '') {
  const params = { q: term, sort: 'recent', page: 1 };
  const normalizedWhere = String(where || '').trim().toLowerCase();
  if (normalizedWhere === 'brasil' || normalizedWhere === 'brazil') {
    params.country = 'BR';
  }

  const response = await axios.get('https://himalayas.app/jobs/api/search', {
    timeout: 20000,
    params
  });

  const rows = Array.isArray(response.data?.jobs)
    ? response.data.jobs
    : (Array.isArray(response.data) ? response.data : []);
  return rows.map((job) => ({
    titulo: job.title || '',
    empresa: job.companyName || '',
    localizacao: locationLabel(job),
    salario: job.minSalary || job.maxSalary ? `${job.currency || ''} ${job.minSalary || ''} - ${job.maxSalary || ''}`.trim() : '',
    fonte: 'Himalayas',
    url: job.applicationLink || job.url || '',
    descricao: job.description || job.excerpt || '',
    data_publicacao: job.pubDate || '',
    raw_json: job
  }));
}

module.exports = { searchHimalayas };
