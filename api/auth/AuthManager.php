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
    
    /**
     * Lista todos os usuários (sem as senhas)
     */
    public function listarUsuarios() {
        $usuarios = array_map(function($usuario) {
            $usuarioSemSenha = $usuario;
            unset($usuarioSemSenha['senha']);
            return $usuarioSemSenha;
        }, $this->_dados['usuarios']);

        return $usuarios;
    }
    
    /**
     * Atualiza um usuário existente
     */
    public function atualizarUsuario($id, $dados, $usuarioAdmin) {
        // Localiza o usuário
        $indice = array_search($id, array_column($this->_dados['usuarios'], 'id'));
        
        if ($indice === false) {
            throw new Exception('Usuário não encontrado', 404);
        }
        
        // Apenas admin pode alterar o tipo do usuário
        if (isset($dados['tipo']) && $usuarioAdmin['tipo'] !== 'admin') {
            unset($dados['tipo']);
        }
        
        // Se estiver alterando o email, verifica se já existe
        if (isset($dados['email']) && 
            $dados['email'] !== $this->_dados['usuarios'][$indice]['email']) {
            if ($this->buscarUsuarioPorEmail($dados['email'])) {
                throw new Exception('Email já cadastrado');
            }
        }
        
        // Atualiza os dados
        $this->_dados['usuarios'][$indice] = array_merge(
            $this->_dados['usuarios'][$indice],
            array_filter([
                'nome' => $dados['nome'] ?? null,
                'email' => $dados['email'] ?? null,
                'tipo' => $dados['tipo'] ?? null,
                'dataAtualizacao' => date('Y-m-d H:i:s')
            ])
        );
        
        // Se forneceu nova senha, atualiza
        if (!empty($dados['senha'])) {
            if (strlen($dados['senha']) < 6) {
                throw new Exception('A senha deve ter no mínimo 6 caracteres');
            }
            $this->_dados['usuarios'][$indice]['senha'] = 
                password_hash($dados['senha'], PASSWORD_DEFAULT);
        }
        
        salvarDados($this->_dados);
        
        // Retorna usuário sem a senha
        $usuarioAtualizado = $this->_dados['usuarios'][$indice];
        unset($usuarioAtualizado['senha']);
        return $usuarioAtualizado;
    }
    
    /**
     * Remove um usuário
     */
    public function removerUsuario($id) {
        // Não permite remover o admin principal
        if ($id === 'admin') {
            throw new Exception('Não é possível remover o usuário administrador principal');
        }
        
        $indice = array_search($id, array_column($this->_dados['usuarios'], 'id'));
        
        if ($indice === false) {
            throw new Exception('Usuário não encontrado', 404);
        }
        
        array_splice($this->_dados['usuarios'], $indice, 1);
        salvarDados($this->_dados);
    }
    
    /**
     * Registra uma ação no log
     */
    public function registrarLog($usuarioId, $acao, $detalhes = '') {
        if (!isset($this->_dados['logs'])) {
            $this->_dados['logs'] = [];
        }

        $log = [
            'id' => uniqid(),
            'usuarioId' => $usuarioId,
            'acao' => $acao,
            'detalhes' => $detalhes,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'userAgent' => $_SERVER['HTTP_USER_AGENT'],
            'data' => date('Y-m-d H:i:s')
        ];

        $this->_dados['logs'][] = $log;
        salvarDados($this->_dados);
        return $log;
    }
    
    /**
     * Lista os logs do sistema
     */
    public function listarLogs($filtros = []) {
        if (!isset($this->_dados['logs'])) {
            return [];
        }

        $logs = $this->_dados['logs'];

        // Aplica filtros
        if (!empty($filtros)) {
            $logs = array_filter($logs, function($log) use ($filtros) {
                foreach ($filtros as $campo => $valor) {
                    if ($campo === 'dataInicio' && $log['data'] < $valor) return false;
                    if ($campo === 'dataFim' && $log['data'] > $valor) return false;
                    if ($campo === 'usuarioId' && $log['usuarioId'] !== $valor) return false;
                    if ($campo === 'acao' && $log['acao'] !== $valor) return false;
                }
                return true;
            });
        }

        // Ordena por data decrescente
        usort($logs, function($a, $b) {
            return strcmp($b['data'], $a['data']);
        });

        return $logs;
    }
    
    /**
     * Verifica se um token é válido
     */
    public function verificarToken($token) {
        $dados = lerDados();
        foreach ($dados['usuarios'] as $usuario) {
            if (isset($usuario['token']) && $usuario['token'] === $token) {
                return $usuario;
            }
        }
        return null;
    }
} 