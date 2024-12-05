<?php
require_once '../config.php';
require_once '../middleware.php';

// Verifica autenticação
try {
    $usuarioAtual = verificarAutenticacao();
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode());
}

class GerenciadorPerfil {
    private $_db;
    private $_usuario;
    
    public function __construct($usuario) {
        $this->_db = JsonDatabase::getInstance();
        $this->_usuario = $usuario;
    }
    
    /**
     * Retorna os dados do perfil
     */
    public function obterPerfil() {
        $usuario = $this->_usuario;
        
        // Remove dados sensíveis
        unset($usuario['senha']);
        unset($usuario['token']);
        
        // Busca estatísticas do usuário
        if ($usuario['tipo'] === 'professor') {
            $reservas = $this->_db->query('reservas', ['professorId' => $usuario['id']]);
            $salas = array_unique(array_column($reservas, 'salaId'));
            
            $usuario['estatisticas'] = [
                'totalReservas' => count($reservas),
                'totalSalas' => count($salas)
            ];
        }
        
        // Busca últimas atividades
        $logs = array_filter($this->_db->getData('logs'), function($log) {
            return $log['usuarioId'] === $this->_usuario['id'];
        });
        
        // Ordena logs por data decrescente e pega os 5 últimos
        usort($logs, function($a, $b) {
            return strtotime($b['dataCriacao']) - strtotime($a['dataCriacao']);
        });
        
        $usuario['ultimasAtividades'] = array_slice($logs, 0, 5);
        
        responderJson($usuario);
    }
    
    /**
     * Atualiza os dados do perfil
     */
    public function atualizarPerfil($dados) {
        // Validações
        if (isset($dados['email'])) {
            if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }
            
            // Verifica se o email já está em uso
            $usuarioExistente = $this->_db->query('usuarios', ['email' => $dados['email']]);
            if (!empty($usuarioExistente) && reset($usuarioExistente)['id'] !== $this->_usuario['id']) {
                throw new Exception('Email já está em uso');
            }
        }
        
        // Atualiza a senha se fornecida
        if (isset($dados['senhaAtual']) && isset($dados['novaSenha'])) {
            if (!password_verify($dados['senhaAtual'], $this->_usuario['senha'])) {
                throw new Exception('Senha atual incorreta');
            }
            
            if (strlen($dados['novaSenha']) < 6) {
                throw new Exception('A nova senha deve ter no mínimo 6 caracteres');
            }
            
            $dados['senha'] = password_hash($dados['novaSenha'], PASSWORD_DEFAULT);
            unset($dados['senhaAtual']);
            unset($dados['novaSenha']);
        }
        
        // Remove campos que não podem ser alterados
        unset($dados['id']);
        unset($dados['tipo']);
        unset($dados['token']);
        
        // Atualiza o usuário
        $usuario = $this->_db->update('usuarios', $this->_usuario['id'], $dados);
        if (!$usuario) {
            throw new Exception('Erro ao atualizar perfil');
        }
        
        // Remove dados sensíveis
        unset($usuario['senha']);
        unset($usuario['token']);
        
        // Registra a atualização no log
        $this->_db->insert('logs', [
            'usuarioId' => $this->_usuario['id'],
            'acao' => 'perfil',
            'detalhes' => 'Perfil atualizado',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        responderJson($usuario);
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