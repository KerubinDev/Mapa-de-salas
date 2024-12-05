<?php
require_once '../config.php';
require_once '../middleware.php';
require_once __DIR__ . '/../../database/Database.php';

// Verifica autenticação
try {
    $usuario = verificarAutenticacao();
    if ($usuario['tipo'] !== 'admin') {
        responderErro('Acesso negado', 403);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode());
}

/**
 * Gerenciador de Usuários
 */
class GerenciadorUsuarios {
    private $_db;
    
    public function __construct() {
        $this->_db = Database::getInstance()->getConnection();
    }
    
    /**
     * Lista todos os usuários
     */
    public function listar() {
        $stmt = $this->_db->query('
            SELECT id, nome, email, tipo, data_criacao, data_atualizacao
            FROM usuarios 
            ORDER BY nome
        ');
        responderJson($stmt->fetchAll());
    }
    
    /**
     * Cria um novo usuário
     */
    public function criar($dados) {
        // Validações
        if (empty($dados['nome'])) {
            throw new Exception('Nome é obrigatório');
        }
        
        if (empty($dados['email'])) {
            throw new Exception('Email é obrigatório');
        }
        
        if (empty($dados['senha'])) {
            throw new Exception('Senha é obrigatória');
        }
        
        if (strlen($dados['senha']) < 6) {
            throw new Exception('A senha deve ter no mínimo 6 caracteres');
        }
        
        // Verifica se o email já está em uso
        $stmt = $this->_db->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$dados['email']]);
        if ($stmt->fetch()) {
            throw new Exception('Email já cadastrado');
        }
        
        try {
            $this->_db->beginTransaction();
            
            // Insere o usuário
            $stmt = $this->_db->prepare('
                INSERT INTO usuarios (id, nome, email, senha, tipo)
                VALUES (?, ?, ?, ?, ?)
            ');
            
            $id = uniqid();
            $stmt->execute([
                $id,
                $dados['nome'],
                $dados['email'],
                password_hash($dados['senha'], PASSWORD_DEFAULT),
                $dados['tipo'] ?? 'usuario'
            ]);
            
            // Busca o usuário criado
            $stmt = $this->_db->prepare('
                SELECT id, nome, email, tipo, data_criacao
                FROM usuarios WHERE id = ?
            ');
            $stmt->execute([$id]);
            $usuario = $stmt->fetch();
            
            $this->_db->commit();
            responderJson($usuario, 201);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Atualiza um usuário existente
     */
    public function atualizar($id, $dados) {
        // Não permite alterar o admin principal
        if ($id === 'admin') {
            throw new Exception('Não é permitido alterar o usuário admin');
        }
        
        // Verifica se o usuário existe
        $stmt = $this->_db->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            throw new Exception('Usuário não encontrado', 404);
        }
        
        // Verifica se o novo email já está em uso
        if (!empty($dados['email'])) {
            $stmt = $this->_db->prepare('
                SELECT id FROM usuarios 
                WHERE email = ? AND id != ?
            ');
            $stmt->execute([$dados['email'], $id]);
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
            
            if (isset($dados['senha'])) {
                if (strlen($dados['senha']) < 6) {
                    throw new Exception('A senha deve ter no mínimo 6 caracteres');
                }
                $campos[] = 'senha = ?';
                $valores[] = password_hash($dados['senha'], PASSWORD_DEFAULT);
            }
            
            if (isset($dados['tipo'])) {
                $campos[] = 'tipo = ?';
                $valores[] = $dados['tipo'];
            }
            
            $campos[] = 'data_atualizacao = CURRENT_TIMESTAMP';
            
            // Adiciona o ID no final do array de valores
            $valores[] = $id;
            
            $sql = 'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id = ?';
            $stmt = $this->_db->prepare($sql);
            $stmt->execute($valores);
            
            // Busca o usuário atualizado
            $stmt = $this->_db->prepare('
                SELECT id, nome, email, tipo, data_criacao, data_atualizacao
                FROM usuarios WHERE id = ?
            ');
            $stmt->execute([$id]);
            $usuario = $stmt->fetch();
            
            $this->_db->commit();
            responderJson($usuario);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Remove um usuário
     */
    public function remover($id) {
        // Não permite remover o admin principal
        if ($id === 'admin') {
            throw new Exception('Não é permitido remover o usuário admin');
        }
        
        try {
            $this->_db->beginTransaction();
            
            // Remove o usuário
            $stmt = $this->_db->prepare('DELETE FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Usuário não encontrado', 404);
            }
            
            $this->_db->commit();
            responderJson(['mensagem' => 'Usuário removido com sucesso']);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
}

// Roteamento das requisições
$gerenciador = new GerenciadorUsuarios();
$metodo = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

try {
    switch ($metodo) {
        case 'GET':
            $gerenciador->listar();
            break;
            
        case 'POST':
            $dados = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Dados JSON inválidos');
            }
            $gerenciador->criar($dados);
            break;
            
        case 'PUT':
            if (!$id) throw new Exception('ID do usuário não informado');
            $dados = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Dados JSON inválidos');
            }
            $gerenciador->atualizar($id, $dados);
            break;
            
        case 'DELETE':
            if (!$id) throw new Exception('ID do usuário não informado');
            $gerenciador->remover($id);
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 