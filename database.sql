-- Tradução fiel do app/models.py para PostgreSQL
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    username VARCHAR(64) UNIQUE NOT NULL,
    password_hash VARCHAR(256) NOT NULL,
    role VARCHAR(64) NOT NULL,
    must_change_password BOOLEAN DEFAULT TRUE
);

CREATE TABLE documents (
    id SERIAL PRIMARY KEY,
    protocol VARCHAR(32) UNIQUE NOT NULL,
    name VARCHAR(128) NOT NULL,
    cpf_cnpj VARCHAR(20),
    solemp VARCHAR(50),
    status VARCHAR(64) DEFAULT 'Caixa de Entrada - Enc. Finanças',
    is_priority BOOLEAN DEFAULT FALSE,
    current_observation TEXT,
    uploader_name VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE events (
    id SERIAL PRIMARY KEY,
    document_id INTEGER REFERENCES documents(id) ON DELETE CASCADE,
    user_name VARCHAR(64),
    action VARCHAR(64) NOT NULL,
    observation TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE document_files (
    id SERIAL PRIMARY KEY,
    document_id INTEGER REFERENCES documents(id) ON DELETE CASCADE,
    filename VARCHAR(256) NOT NULL,
    file_type VARCHAR(64) NOT NULL
);