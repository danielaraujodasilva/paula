CREATE DATABASE IF NOT EXISTS agente_vagas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agente_vagas;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curriculos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  nome_arquivo VARCHAR(255),
  caminho_arquivo VARCHAR(255),
  tipo_arquivo VARCHAR(50),
  texto_extraido LONGTEXT,
  perfil_json LONGTEXT,
  ativo TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buscas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  curriculo_id INT NULL,
  nome VARCHAR(255),
  termos LONGTEXT,
  localizacao VARCHAR(255),
  remoto TINYINT DEFAULT 1,
  salario_minimo DECIMAL(10,2) NULL,
  palavras_obrigatorias LONGTEXT NULL,
  palavras_proibidas LONGTEXT NULL,
  fontes LONGTEXT NULL,
  ativa TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vagas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  titulo VARCHAR(255),
  empresa VARCHAR(255),
  localizacao VARCHAR(255),
  salario VARCHAR(255) NULL,
  fonte VARCHAR(100),
  url TEXT,
  descricao LONGTEXT,
  data_publicacao VARCHAR(100) NULL,
  hash_vaga VARCHAR(64),
  nota_compatibilidade INT DEFAULT 0,
  resumo_compatibilidade LONGTEXT NULL,
  status VARCHAR(50) DEFAULT 'nova',
  raw_json LONGTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY idx_vagas_usuario_hash (user_id, hash_vaga)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_execucao (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  tipo VARCHAR(100),
  mensagem LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
