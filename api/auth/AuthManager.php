<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../database/Database.php';

/**
 * Gerenciador de Autenticação
 */
class AuthManager {
    private $_db;
    private static $_instance = null;
    
    private function __construct() {
        $this->_db = Database::getInstance()->getConnection();
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
     * Realiza o login do usuário
     */
    public function login($email, $senha) {
        $stmt = $this->_db->prepare('
            SELECT * FROM usuarios WHERE email = ? LIMIT 1
        ');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            throw new Exception('Email ou senha inválidos');
        }
        
        // Gera um novo token
        $token = bin2hex(random_bytes(32));
        
        // Atualiza o token do usuário
        $stmt = $this->_db->prepare('
            UPDATE usuarios 
            SET token = ?, data_atualizacao = CURRENT_TIMESTAMP 
            WHERE id = ?
        ');
        $stmt->execute([$token, $usuario['id']]);
        
        // Remove a senha antes de retornar
        unset($usuario['senha']);
        $usuario['token'] = $token;
        
        // Registra o login no log
        $this->registrarLog($usuario['id'], 'login', 'Login realizado com sucesso');
        
        return $usuario;
    }
    
    /**
     * Realiza o logout do usuário
     */
    public function logout($token) {
        $stmt = $this->_db->prepare('
            SELECT id FROM usuarios WHERE token = ? LIMIT 1
        ');
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            // Registra o logout no log
            $this->registrarLog($usuario['id'], 'logout', 'Logout realizado');
            
            // Invalida o token
            $stmt = $this->_db->prepare('
                UPDATE usuarios 
                SET token = NULL, data_atualizacao = CURRENT_TIMESTAMP 
                WHERE id = ?
            ');
            $stmt->execute([$usuario['id']]);
        }
    }
    
    /**
     * Verifica se um token é válido
     */
    public function verificarToken($token) {
        $stmt = $this->_db->prepare('
            SELECT * FROM usuarios WHERE token = ? LIMIT 1
        ');
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            return null;
        }
        
        // Remove a senha antes de retornar
        unset($usuario['senha']);
        return $usuario;
    }
    
    /**
     * Cria um novo usuário
     */
    public function criarUsuario($dados, $usuarioAdmin) {
        if (!$usuarioAdmin || $usuarioAdmin['tipo'] !== 'admin') {
            throw new Exception('Apenas administradores podem criar usuários');
        }
        
        // Verifica se o email já está em uso
        $stmt = $this->_db->prepare('
            SELECT id FROM usuarios WHERE email = ? LIMIT 1
        ');
        $stmt->execute([$dados['email']]);
        if ($stmt->fetch()) {
            throw new Exception('Email já cadastrado');
        }
        
        try {
            $this->_db->beginTransaction();
            
            // Cria o usuário
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
                'usuario'
            ]);
            
            // Registra a criação no log
            $this->registrarLog(
                $usuarioAdmin['id'], 
                'criar', 
                "Usuário {$dados['nome']} criado"
            );
            
            // Busca o usuário criado
            $stmt = $this->_db->prepare('
                SELECT id, nome, email, tipo, data_criacao 
                FROM usuarios WHERE id = ?
            ');
            $stmt->execute([$id]);
            $usuario = $stmt->fetch();
            
            $this->_db->commit();
            return $usuario;
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Lista todos os usuários
     */
    public function listarUsuarios() {
        $stmt = $this->_db->query('
            SELECT id, nome, email, tipo, data_criacao, data_atualizacao 
            FROM usuarios ORDER BY nome
        ');
        return $stmt->fetchAll();
    }
    
    /**
     * Registra uma ação no log
     */
    public function registrarLog($usuarioId, $acao, $detalhes = '') {
        $stmt = $this->_db->prepare('
            INSERT INTO logs (id, usuario_id, acao, detalhes, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            uniqid(),
            $usuarioId,
            $acao,
            $detalhes,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    /**
     * Lista os logs do sistema
     */
    public function listarLogs($filtros = []) {
        $sql = 'SELECT l.*, u.nome as usuario_nome, u.email as usuario_email 
                FROM logs l 
                LEFT JOIN usuarios u ON l.usuario_id = u.id 
                WHERE 1=1';
        $params = [];
        
        if (!empty($filtros['dataInicio'])) {
            $sql .= ' AND l.data_criacao >= ?';
            $params[] = $filtros['dataInicio'];
        }
        
        if (!empty($filtros['dataFim'])) {
            $sql .= ' AND l.data_criacao <= ?';
            $params[] = $filtros['dataFim'];
        }
        
        if (!empty($filtros['usuarioId'])) {
            $sql .= ' AND l.usuario_id = ?';
            $params[] = $filtros['usuarioId'];
        }
        
        if (!empty($filtros['acao'])) {
            $sql .= ' AND l.acao = ?';
            $params[] = $filtros['acao'];
        }
        
        $sql .= ' ORDER BY l.data_criacao DESC';
        
        $stmt = $this->_db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
} 