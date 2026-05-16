const fs = require('fs/promises');
const path = require('path');
const pdf = require('pdf-parse');
const mammoth = require('mammoth');
const { pool } = require('./lib/db');
const { normalizeText } = require('./lib/text');

const SKILLS = ['php', 'mysql', 'mariadb', 'javascript', 'node.js', 'node', 'html', 'css', 'bootstrap', 'laravel', 'react', 'vue', 'python', 'java', 'c#', 'sql', 'git', 'docker', 'api', 'rest', 'linux', 'windows', 'excel', 'power bi'];
const TOOLS = ['xampp', 'git', 'github', 'docker', 'figma', 'jira', 'trello', 'postman', 'vscode', 'visual studio', 'aws', 'azure'];
const ROLES = ['desenvolvedor php', 'desenvolvedor backend', 'desenvolvedor full stack', 'analista de sistemas', 'analista de suporte', 'devops', 'frontend', 'backend'];
const SENIORITIES = ['estagio', 'junior', 'pleno', 'senior', 'especialista'];

function detectList(text, dictionary) {
  const normalized = normalizeText(text);
  return dictionary.filter((item) => normalized.includes(normalizeText(item)));
}

function firstEmail(text) {
  return (text.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i) || [''])[0];
}

function firstPhone(text) {
  return (text.match(/(\+?\d{2,3}\s?)?(\(?\d{2}\)?\s?)?\d{4,5}[-.\s]?\d{4}/) || [''])[0];
}

function detectName(text) {
  const firstLine = text.split(/\r?\n/).map((line) => line.trim()).find((line) => line.length > 3 && line.length < 80);
  return firstLine || '';
}

function buildProfile(text) {
  const skills = detectList(text, SKILLS);
  const tools = detectList(text, TOOLS);
  const roles = detectList(text, ROLES);
  const seniority = detectList(text, SENIORITIES)[0] || '';
  const locationMatch = text.match(/\b(Sao Paulo|São Paulo|Rio de Janeiro|Belo Horizonte|Curitiba|Porto Alegre|Recife|Salvador|Brasilia|Brasília|Remoto)\b/i);
  const email = firstEmail(text);
  const phone = firstPhone(text);
  return {
    nome: detectName(text),
    cargo_alvo: roles.length ? roles : ['desenvolvedor', 'analista'],
    senioridade: seniority,
    localizacao: locationMatch ? locationMatch[0] : '',
    aceita_remoto: normalizeText(text).includes('remoto') || normalizeText(text).includes('remote'),
    habilidades: skills,
    ferramentas: tools,
    experiencias: text.split(/\r?\n/).filter((line) => line.trim().length > 80).slice(0, 5),
    palavras_chave: [...new Set([...roles, ...skills])].slice(0, 20),
    palavras_proibidas: [],
    contato: { email, telefone: phone }
  };
}

async function extractText(filePath, type) {
  if (type === 'txt') return fs.readFile(filePath, 'utf8');
  if (type === 'pdf') {
    const buffer = await fs.readFile(filePath);
    const data = await pdf(buffer);
    return data.text || '';
  }
  if (type === 'docx') {
    const result = await mammoth.extractRawText({ path: filePath });
    return result.value || '';
  }
  throw new Error(`Tipo nao suportado: ${type}`);
}

async function main() {
  const id = Number(process.argv[2]);
  const userId = Number(process.argv[3] || process.env.PAULA_USER_ID || 0);
  if (!id) throw new Error('Informe curriculo_id.');
  if (!userId) throw new Error('Informe user_id.');
  const db = pool();
  const [rows] = await db.execute('SELECT * FROM curriculos WHERE id = ? AND user_id = ?', [id, userId]);
  const resume = rows[0];
  if (!resume) throw new Error('Curriculo nao encontrado.');
  const text = await extractText(path.resolve(resume.caminho_arquivo), resume.tipo_arquivo);
  const profile = buildProfile(text);
  await db.execute('UPDATE curriculos SET texto_extraido = ?, perfil_json = ?, updated_at = NOW() WHERE id = ?', [
    text,
    JSON.stringify(profile, null, 2),
    id
  ]);
  await db.end();
  console.log(`Curriculo ${id} extraido com ${text.length} caracteres.`);
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
