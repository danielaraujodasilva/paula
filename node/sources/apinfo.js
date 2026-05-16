const axios = require('axios');
const cheerio = require('cheerio');
const { containsAny } = require('../lib/matcher');

function compact(value) {
  return String(value || '').replace(/\s+/g, ' ').trim();
}

async function fetchLatin1(url) {
  const response = await axios.get(url, {
    timeout: 20000,
    responseType: 'arraybuffer',
    headers: { 'User-Agent': 'Projeto Paula Job Search Bot' }
  });
  return Buffer.from(response.data).toString('latin1');
}

function parseDetail(html, url, fallbackTitle) {
  const $ = cheerio.load(html);
  const text = compact($('body').text());
  const locationDate = text.match(/([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÀ-ÿ\s.'-]+-\s*[A-Z]{2})\s*-\s*(\d{2}\/\d{2}\/\d{2})/);
  const company = compact((text.match(/Empresa\s*\.{0,10}\s*:\s*(.+?)\s+C[oó]digo/i) || [])[1] || '');
  const code = compact((text.match(/Código\s*\.*:\s*(\d+)/i) || [])[1] || '');
  const title = compact(fallbackTitle || (text.match(/Resultado da pesquisa.*?\d{2}\/\d{2}\/\d{2}\s+(.+?)\s+Empresa/i) || [])[1] || '');
  const description = compact((text.match(new RegExp(`${title.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s+([\\s\\S]+?)\\s+Empresa\\s*\\.{0,10}\\s*:`, 'i')) || [])[1] || text);
  return {
    titulo: title,
    empresa: company,
    localizacao: locationDate ? compact(locationDate[1]) : '',
    salario: '',
    fonte: 'APinfo Experimental',
    url,
    descricao: description,
    data_publicacao: locationDate ? locationDate[2] : '',
    raw_json: { code, text, experimental: true }
  };
}

async function searchApinfo(term) {
  const html = await fetchLatin1('https://www.apinfo.com/');
  const $ = cheerio.load(html);
  const links = $('a[href*="list44.cfm"]').map((_, element) => ({
    url: new URL($(element).attr('href'), 'https://www.apinfo.com').href,
    title: compact($(element).text())
  })).get().slice(0, 12);

  const jobs = [];
  for (const link of links) {
    const detail = parseDetail(await fetchLatin1(link.url), link.url, link.title);
    if (containsAny(`${detail.titulo} ${detail.empresa} ${detail.localizacao} ${detail.descricao}`, [term])) {
      jobs.push(detail);
    }
  }
  return jobs;
}

module.exports = { searchApinfo };
