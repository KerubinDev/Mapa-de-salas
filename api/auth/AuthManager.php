<?php
require_once __DIR__ . '/../config.php';

/**
 * Gerenciador de Autenticação
 */
class AuthManager {
    private $_db;
    private static $_instance = null;
    
    private function __construct() {
        $this->_db = JsonDatabase::getInstance();
    }
    
    /**
     * Retorna a instância única do gerenciador
     */
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Verifica um token de autenticação
     */
    public function verificarToken($token) {
        $usuarios = $this->_db->query('usuarios', ['token' => $token]);
        return reset($usuarios);
    }
    
    /**
     * Realiza o login de um usuário
     */
    public function login($email, $senha) {
        $usuarios = $this->_db->query('usuarios', ['email' => $email]);
        $usuario = reset($usuarios);
        
        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            throw new Exception('Email ou senha inválidos', 401);
        }
        
        // Gera um novo token
        $token = bin2hex(random_bytes(32));
        
        // Atualiza o token do usuário
        $usuario = $this->_db->update('usuarios', $usuario['id'], [
            'token' => $token
        ]);
        
        // Remove dados sensíveis
        unset($usuario['senha']);
        
        // Registra o login no log
        $this->_db->insert('logs', [
            'usuarioId' => $usuario['id'],
            'acao' => 'login',
            'detalhes' => 'Login realizado com sucesso',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        return $usuario;
    }
    
    /**
     * Realiza o logout de um usuário
     */
    public function logout($token) {
        $usuarios = $this->_db->query('usuarios', ['token' => $token]);
        $usuario = reset($usuarios);
        
        if ($usuario) {
            // Remove o token do usuário
            $this->_db->update('usuarios', $usuario['id'], [
                'token' => null
            ]);
            
            // Registra o logout no log
            $this->_db->insert('logs', [
                'usuarioId' => $usuario['id'],
                'acao' => 'logout',
                'detalhes' => 'Logout realizado com sucesso',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        }
    }
    
    /**
     * Busca logs do sistema
     */
    public function buscarLogs($filtros = []) {
        $logs = $this->_db->getData('logs');
        
        // Aplica filtros
        if (!empty($filtros)) {
            $logs = array_filter($logs, function($log) use ($filtros) {
                if (!empty($filtros['usuarioId']) && $log['usuarioId'] !== $filtros['usuarioId']) {
                    return false;
                }
                
                if (!empty($filtros['acao']) && $log['acao'] !== $filtros['acao']) {
                    return false;
                }
                
                if (!empty($filtros['dataInicio']) && 
                    strtotime($log['dataCriacao']) < strtotime($filtros['dataInicio'])) {
                    return false;
                }
                
                if (!empty($filtros['dataFim']) && 
                    strtotime($log['dataCriacao']) > strtotime($filtros['dataFim'])) {
                    return false;
                }
                
                return true;
            });
        }
        
        // Ordena por data decrescente
        usort($logs, function($a, $b) {
            return strtotime($b['dataCriacao']) - strtotime($a['dataCriacao']);
        });
        
        return $logs;
    }
} 