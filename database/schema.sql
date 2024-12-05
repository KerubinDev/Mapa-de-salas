-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id TEXT PRIMARY KEY,
    nome TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    senha TEXT NOT NULL,
    tipo TEXT NOT NULL DEFAULT 'usuario',
    token TEXT,
    token_recuperacao TEXT,
    token_expiracao DATETIME,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME
);

-- Tabela de Sessões
CREATE TABLE IF NOT EXISTS sessoes (
    id TEXT PRIMARY KEY,
    usuario_id TEXT NOT NULL,
    token TEXT NOT NULL,
    ip TEXT,
    user_agent TEXT,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_expiracao DATETIME,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de Logs
CREATE TABLE IF NOT EXISTS logs (
    id TEXT PRIMARY KEY,
    usuario_id TEXT,
    acao TEXT NOT NULL,
    detalhes TEXT,
    ip TEXT,
    user_agent TEXT,
    browser TEXT,
    sistema_operacional TEXT,
    dispositivo TEXT,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabela de Salas
CREATE TABLE IF NOT EXISTS salas (
    id TEXT PRIMARY KEY,
    nome TEXT NOT NULL,
    capacidade INTEGER NOT NULL,
    descricao TEXT,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME
);

-- Tabela de Turmas
CREATE TABLE IF NOT EXISTS turmas (
    id TEXT PRIMARY KEY,
    nome TEXT NOT NULL,
    professor TEXT NOT NULL,
    numero_alunos INTEGER NOT NULL,
    turno TEXT NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME
);

-- Tabela de Reservas
CREATE TABLE IF NOT EXISTS reservas (
    id TEXT PRIMARY KEY,
    sala_id TEXT NOT NULL,
    turma_id TEXT NOT NULL,
    data DATE NOT NULL,
    horario_inicio TIME NOT NULL,
    horario_fim TIME NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME,
    FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
);

-- Tabela de Configurações
CREATE TABLE IF NOT EXISTS configuracoes (
    chave TEXT PRIMARY KEY,
    valor TEXT NOT NULL,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios(email);
CREATE INDEX IF NOT EXISTS idx_usuarios_token ON usuarios(token);
CREATE INDEX IF NOT EXISTS idx_usuarios_token_recuperacao ON usuarios(token_recuperacao);
CREATE INDEX IF NOT EXISTS idx_sessoes_token ON sessoes(token);
CREATE INDEX IF NOT EXISTS idx_sessoes_usuario ON sessoes(usuario_id);
CREATE INDEX IF NOT EXISTS idx_logs_usuario ON logs(usuario_id);
CREATE INDEX IF NOT EXISTS idx_logs_data ON logs(data_criacao);
CREATE INDEX IF NOT EXISTS idx_reservas_sala ON reservas(sala_id);
CREATE INDEX IF NOT EXISTS idx_reservas_turma ON reservas(turma_id);
CREATE INDEX IF NOT EXISTS idx_reservas_data ON reservas(data);

-- Triggers
CREATE TRIGGER IF NOT EXISTS atualizar_data_modificacao_usuario
AFTER UPDATE ON usuarios
BEGIN
    UPDATE usuarios SET data_atualizacao = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS limpar_sessoes_expiradas
AFTER INSERT ON sessoes
BEGIN
    DELETE FROM sessoes WHERE data_expiracao < CURRENT_TIMESTAMP;
END;

-- Inserir usuário admin padrão se não existir
INSERT OR IGNORE INTO usuarios (id, nome, email, senha, tipo) 
VALUES (
    'admin',
    'Administrador',
    'admin@sistema.local',
    '$2y$10$8H0IzP3wUJGQJ9mN6qXQo.DYWFqxRvEgn3H3QHbUZoYZ6TZQQqK6.',
    'admin'
);

-- Configurações padrão
INSERT OR IGNORE INTO configuracoes (chave, valor) VALUES
('horario_abertura', '07:00'),
('horario_fechamento', '22:00'),
('dias_funcionamento', '1,2,3,4,5'),
('duracao_minima', '15'),
('intervalo_reservas', '0'),
('notificar_reservas', '0'),
('notificar_cancelamentos', '0'),
('notificar_conflitos', '0'),
('backup_automatico', '0');