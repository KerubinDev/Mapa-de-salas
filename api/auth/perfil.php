<?php
require_once '../config.php';
require_once '../middleware.php';
require_once __DIR__ . '/../../database/Database.php';

// Verifica autenticação
try {
    $usuarioAtual = verificarAutenticacao();
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode());
}

/**
 * Gerenciador de Perfil
 */
class GerenciadorPerfil {
    private $_db;
    private $_usuario;
    
    public function __construct($usuario) {
        $this->_db = Database::getInstance()->getConnection();
        $this->_usuario = $usuario;
    }
    
    /**
     * Retorna os dados do perfil
     */
    public function obterPerfil() {
        $stmt = $this->_db->prepare('
            SELECT id, nome, email, tipo, data_criacao, data_atualizacao
            FROM usuarios 
            WHERE id = ?
        ');
        $stmt->execute([$this->_usuario['id']]);
        $perfil = $stmt->fetch();
        
        if (!$perfil) {
            throw new Exception('Usuário não encontrado', 404);
        }
        
        // Adiciona estatísticas se for professor
        if ($this->_usuario['tipo'] === 'professor') {
            $stmt = $this->_db->prepare('
                SELECT COUNT(*) as total_reservas,
                       COUNT(DISTINCT sala_id) as total_salas
                FROM reservas r
                JOIN turmas t ON r.turma_id = t.id
                WHERE t.professor = ?
            ');
            $stmt->execute([$this->_usuario['nome']]);
            $stats = $stmt->fetch();
            
            $perfil['estatisticas'] = $stats;
        }
        
        // Busca últimas atividades
        $stmt = $this->_db->prepare('
            SELECT acao, detalhes, data_criacao
            FROM logs
            WHERE usuario_id = ?
            ORDER BY data_criacao DESC
            LIMIT 5
        ');
        $stmt->execute([$this->_usuario['id']]);
        $perfil['ultimas_atividades'] = $stmt->fetchAll();
        
        responderJson($perfil);
    }
    
    /**
     * Atualiza os dados do perfil
     */
    public function atualizarPerfil($dados) {
        // Validações
        if (isset($dados['email'])) {
            $stmt = $this->_db->prepare('
                SELECT id FROM usuarios 
                WHERE email = ? AND id != ?
            ');
            $stmt->execute([$dados['email'], $this->_usuario['id']]);
            if ($stmt->fetch()) {
                throw new Exception('Email já cadastrado');
            }
        }
        
        try {
            $this->_db->beginTransaction();
            
            // Monta a query de atualização
            $campos = [];
            $valores = [];
            
            if (isset($dados['nome'])) {
                $campos[] = 'nome = ?';
                $valores[] = $dados['nome'];
            }
            
            if (isset($dados['email'])) {
                $campos[] = 'email = ?';
                $valores[] = $dados['email'];
            }
            
            if (isset($dados['senha_atual'], $dados['nova_senha'])) {
                // Verifica a senha atual
                $stmt = $this->_db->prepare('SELECT senha FROM usuarios WHERE id = ?');
                $stmt->execute([$this->_usuario['id']]);
                $usuario = $stmt->fetch();
                
                if (!password_verify($dados['senha_atual'], $usuario['senha'])) {
                    throw new Exception('Senha atual incorreta');
                }
                
                if (strlen($dados['nova_senha']) < 6) {
                    throw new Exception('A nova senha deve ter no mínimo 6 caracteres');
                }
                
                $campos[] = 'senha = ?';
                $valores[] = password_hash($dados['nova_senha'], PASSWORD_DEFAULT);
            }
            
            if (empty($campos)) {
                throw new Exception('Nenhum dado para atualizar');
            }
            
            $campos[] = 'data_atualizacao = CURRENT_TIMESTAMP';
            
            // Adiciona o ID no final do array de valores
            $valores[] = $this->_usuario['id'];
            
            $sql = 'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id = ?';
            $stmt = $this->_db->prepare($sql);
            $stmt->execute($valores);
            
            // Registra a atualização no log
            $stmt = $this->_db->prepare('
                INSERT INTO logs (id, usuario_id, acao, detalhes, ip, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                uniqid(),
                $this->_usuario['id'],
                'perfil',
                'Perfil atualizado',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            // Busca o perfil atualizado
            $stmt = $this->_db->prepare('
                SELECT id, nome, email, tipo, data_criacao, data_atualizacao
                FROM usuarios WHERE id = ?
            ');
            $stmt->execute([$this->_usuario['id']]);
            $perfil = $stmt->fetch();
            
            $this->_db->commit();
            responderJson($perfil);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
}

// Roteamento das requisições
$gerenciador = new GerenciadorPerfil($usuarioAtual);
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    switch ($metodo) {
        case 'GET':
            $gerenciador->obterPerfil();
            break;
            
        case 'PUT':
            $dados = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Dados JSON inválidos');
            }
            $gerenciador->atualizarPerfil($dados);
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 