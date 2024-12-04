<?php
require_once __DIR__ . '/../config.php';

/**
 * Gerenciador de Autenticação
 * Responsável por gerenciar usuários e sessões
 */
class AuthManager {
    private $_dados;
    private static $_instance = null;
    
    private function __construct() {
        $this->_dados = lerDados();
        if (!isset($this->_dados['usuarios'])) {
            $this->_dados['usuarios'] = [];
            $this->criarUsuarioAdmin();
        }
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
     * Cria o usuário administrador padrão
     */
    private function criarUsuarioAdmin() {
        // Credenciais do administrador principal
        $admin = [
            'id' => 'admin',
            'nome' => 'Administrador',
            'email' => 'admin@sistema.local',
            'senha' => password_hash('admin123', PASSWORD_DEFAULT),
            'tipo' => 'admin',
            'dataCriacao' => date('Y-m-d H:i:s')
        ];
        
        $this->_dados['usuarios'][] = $admin;
        salvarDados($this->_dados);
    }
    
    /**
     * Realiza o login do usuário
     */
    public function login($email, $senha) {
        $usuario = $this->buscarUsuarioPorEmail($email);
        
        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            throw new Exception('Email ou senha inválidos');
        }
        
        // Remove a senha antes de retornar
        unset($usuario['senha']);
        
        // Inicia a sessão
        session_start();
        $_SESSION['usuario'] = $usuario;
        
        return $usuario;
    }
    
    /**
     * Realiza o logout do usuário
     */
    public function logout() {
        session_start();
        session_destroy();
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    public function verificarAutenticacao() {
        session_start();
        return isset($_SESSION['usuario']);
    }
    
    /**
     * Retorna o usuário autenticado
     */
    public function getUsuarioAutenticado() {
        session_start();
        return $_SESSION['usuario'] ?? null;
    }
    
    /**
     * Cria um novo usuário (apenas admin pode criar)
     */
    public function criarUsuario($dados, $usuarioAdmin) {
        if (!$usuarioAdmin || $usuarioAdmin['tipo'] !== 'admin') {
            throw new Exception('Apenas administradores podem criar usuários');
        }
        
        if ($this->buscarUsuarioPorEmail($dados['email'])) {
            throw new Exception('Email já cadastrado');
        }
        
        $novoUsuario = [
            'id' => uniqid(),
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT),
            'tipo' => 'usuario',
            'dataCriacao' => date('Y-m-d H:i:s')
        ];
        
        $this->_dados['usuarios'][] = $novoUsuario;
        salvarDados($this->_dados);
        
        // Remove a senha antes de retornar
        unset($novoUsuario['senha']);
        return $novoUsuario;
    }
    
    /**
     * Busca um usuário pelo email
     */
    private function buscarUsuarioPorEmail($email) {
        foreach ($this->_dados['usuarios'] as $usuario) {
            if ($usuario['email'] === $email) {
                return $usuario;
            }
        }
        return null;
    }
} 