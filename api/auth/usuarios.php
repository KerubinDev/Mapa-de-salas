<?php
require_once '../config.php';
require_once '../middleware.php';

// Verifica autenticação
try {
    $usuario = verificarAutenticacao();
    if ($usuario['tipo'] !== 'admin') {
        responderErro('Acesso negado', 403);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode());
}

class GerenciadorUsuarios {
    private $_db;
    
    public function __construct() {
        $this->_db = JsonDatabase::getInstance();
    }
    
    /**
     * Lista todos os usuários
     */
    public function listar() {
        $usuarios = $this->_db->getData('usuarios');
        
        // Remove dados sensíveis
        foreach ($usuarios as &$usuario) {
            unset($usuario['senha']);
            unset($usuario['token']);
            unset($usuario['tokenRecuperacao']);
            unset($usuario['tokenExpiracao']);
        }
        
        // Ordena por nome
        usort($usuarios, function($a, $b) {
            return strcmp($a['nome'], $b['nome']);
        });
        
        responderJson($usuarios);
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
        
        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        if (empty($dados['senha'])) {
            throw new Exception('Senha é obrigatória');
        }
        
        if (strlen($dados['senha']) < 6) {
            throw new Exception('A senha deve ter no mínimo 6 caracteres');
        }
        
        // Verifica se o email já está em uso
        $usuarioExistente = $this->_db->query('usuarios', ['email' => $dados['email']]);
        if (!empty($usuarioExistente)) {
            throw new Exception('Email já cadastrado');
        }
        
        // Cria o usuário
        $usuario = $this->_db->insert('usuarios', [
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT),
            'tipo' => $dados['tipo'] ?? 'usuario'
        ]);
        
        // Remove dados sensíveis
        unset($usuario['senha']);
        
        responderJson($usuario, 201);
    }
    
    /**
     * Atualiza um usuário
     */
    public function atualizar($id, $dados) {
        // Verifica se o usuário existe
        $usuarioExistente = $this->_db->query('usuarios', ['id' => $id]);
        if (empty($usuarioExistente)) {
            throw new Exception('Usuário não encontrado', 404);
        }
        
        // Validações
        if (isset($dados['email'])) {
            if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }
            
            $outroUsuario = $this->_db->query('usuarios', ['email' => $dados['email']]);
            if (!empty($outroUsuario) && reset($outroUsuario)['id'] !== $id) {
                throw new Exception('Email já está em uso');
            }
        }
        
        if (isset($dados['senha'])) {
            if (strlen($dados['senha']) < 6) {
                throw new Exception('A senha deve ter no mínimo 6 caracteres');
            }
            $dados['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
        }
        
        // Remove campos que não podem ser alterados
        unset($dados['id']);
        unset($dados['token']);
        unset($dados['tokenRecuperacao']);
        unset($dados['tokenExpiracao']);
        
        // Atualiza o usuário
        $usuario = $this->_db->update('usuarios', $id, $dados);
        if (!$usuario) {
            throw new Exception('Erro ao atualizar usuário');
        }
        
        // Remove dados sensíveis
        unset($usuario['senha']);
        unset($usuario['token']);
        unset($usuario['tokenRecuperacao']);
        unset($usuario['tokenExpiracao']);
        
        responderJson($usuario);
    }
    
    /**
     * Remove um usuário
     */
    public function remover($id) {
        // Verifica se o usuário existe
        $usuarioExistente = $this->_db->query('usuarios', ['id' => $id]);
        if (empty($usuarioExistente)) {
            throw new Exception('Usuário não encontrado', 404);
        }
        
        // Não permite remover o próprio usuário
        if ($id === $this->_usuario['id']) {
            throw new Exception('Não é possível remover o próprio usuário');
        }
        
        // Remove o usuário
        if (!$this->_db->delete('usuarios', $id)) {
            throw new Exception('Erro ao remover usuário');
        }
        
        responderJson(['mensagem' => 'Usuário removido com sucesso']);
    }
}

// Roteamento
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