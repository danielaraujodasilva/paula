const axios = require('axios');
const cheerio = require('cheerio');

function compact(value) {
  return String(value || '').replace(/\s+/g, ' ').trim();
}

function decodeHtml(value) {
  return cheerio.load(`<span>${value || ''}</span>`)('span').text();
}

function inferLocation(text) {
  const remote = text.match(/\bRemoto\b/i)?.[0];
  const city = text.match(/[A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÀ-ÿ\s.'-]{2,40}\s+\((?:Remoto|Híbrido|Presencial)\)/);
  return city ? compact(city[0]) : (remote || '');
}

function inferSalary(text) {
  return compact((text.match(/(?:Até\s*)?R\$\s?[\d.,]+(?:\s*-\s*R\$\s?[\d.,]+)?/i) || [''])[0]);
}

function parseLooseJson(value) {
  try {
    return JSON.parse(value.replace(/[\u0000-\u001F]+/g, ' '));
  } catch {
    return null;
  }
}

async function loadDetail(url, fallback) {
  try {
    const response = await axios.get(url, {
      timeout: 20000,
      headers: { 'User-Agent': 'Projeto Paula Job Search Bot' }
    });
    const $ = cheerio.load(response.data);
    let posting = null;
    $('script[type="application/ld+json"]').each((_, script) => {
      if (posting) return;
      const data = parseLooseJson($(script).contents().text());
      if (data?.['@type'] === 'JobPosting') posting = data;
    });
    const body = compact($('body').text());
    const address = posting?.jobLocation?.address || {};
    const locationParts = [address.addressLocality, address.addressRegion]
      .map((part) => compact(part))
      .filter((part) => part && part !== '-');
    const locationFromJson = compact(locationParts.join(' - '));
    const location = compact(body.match(/Localização:\s*([^🏢]+?)(?:Salário:|Júnior|Pleno|Sênior|Aceito|Não Aceito|Descrição)/)?.[1] || '');
    return {
      titulo: decodeHtml(posting?.title || fallback.titulo),
      empresa: decodeHtml(posting?.hiringOrganization?.name || fallback.empresa),
      localizacao: locationFromJson || location || fallback.localizacao,
      salario: inferSalary(body) || fallback.salario,
      descricao: posting?.description || body.slice(0, 5000),
      data_publicacao: posting?.datePosted || '',
      raw_json: posting || fallback.raw_json
    };
  } catch {
    return fallback;
  }
}

async function searchProgramaThor(term) {
  const response = await axios.get('https://programathor.com.br/jobs', {
    timeout: 20000,
    headers: { 'User-Agent': 'Projeto Paula Job Search Bot' },
    params: { search: term }
  });

  const $ = cheerio.load(response.data);
  const seen = new Set();
  const jobs = [];
  $('a[href^="/jobs/"]').each((_, element) => {
    const href = $(element).attr('href');
    if (!href || seen.has(href)) return;
    seen.add(href);
    const text = compact($(element).text());
    if (!text) return;
    const title = compact(text.split(/NOVA|Remoto|São Paulo|Sao Paulo|Rio de Janeiro|Belo Horizonte|Curitiba|Porto Alegre|Salvador|Recife/)[0]) || text.slice(0, 120);
    const localizacao = inferLocation(text);
    const salario = inferSalary(text);
    const afterTitle = compact(text.slice(title.length));
    const empresa = compact(afterTitle.split(/Remoto|[A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÀ-ÿ\s.'-]{2,40}\s+\(/)[0].replace(/^NOVA/, ''));
    jobs.push({
      titulo: title,
      empresa,
      localizacao,
      salario,
      fonte: 'ProgramaThor',
      url: new URL(href, 'https://programathor.com.br').href,
      descricao: text,
      data_publicacao: '',
      raw_json: { href, text }
    });
  });
  const detailed = [];
  for (const job of jobs.slice(0, 12)) {
    detailed.push({ ...job, ...(await loadDetail(job.url, job)) });
  }
  return detailed;
}

module.exports = { searchProgramaThor };
