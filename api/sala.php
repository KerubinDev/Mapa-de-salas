<?php
require_once 'config.php';

// Tratamento específico para OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

// Verifica se o diretório tem permissões corretas
$dir = __DIR__;
if (!is_writable($dir)) {
    chmod($dir, 0755);
}

// Verifica se o arquivo de banco de dados tem permissões corretas
if (file_exists(ARQUIVO_DB) && !is_writable(ARQUIVO_DB)) {
    chmod(ARQUIVO_DB, 0666);
}

/**
 * Gerenciador de Salas
 * Endpoints:
 * GET / - Lista todas as salas
 * POST / - Cria uma nova sala
 * PUT /{id} - Atualiza uma sala existente
 * DELETE /{id} - Remove uma sala
 */

class GerenciadorSala {
    private $_dados;
    
    public function __construct() {
        $this->_dados = lerDados();
    }
    
    /**
     * Lista todas as salas cadastradas
     */
    public function listar() {
        responderJson($this->_dados['salas']);
    }
    
    /**
     * Cadastra uma nova sala
     */
    public function criar() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Validação dos campos obrigatórios
        if (empty($dados['nome'])) {
            responderErro('Nome da sala é obrigatório');
        }
        
        if (empty($dados['capacidade']) || !is_numeric($dados['capacidade'])) {
            responderErro('Capacidade deve ser um número válido');
        }
        
        // Verifica se já existe sala com mesmo nome
        $salaExistente = array_filter($this->_dados['salas'], 
            function($sala) use ($dados) {
                return strtolower($sala['nome']) === strtolower($dados['nome']);
            }
        );
        
        if (!empty($salaExistente)) {
            responderErro('Já existe uma sala com este nome');
        }
        
        // Cria nova sala
        $novaSala = [
            'id' => uniqid(),
            'nome' => $dados['nome'],
            'capacidade' => (int)$dados['capacidade'],
            'descricao' => $dados['descricao'] ?? '',
            'dataCriacao' => date('Y-m-d H:i:s')
        ];
        
        $this->_dados['salas'][] = $novaSala;
        salvarDados($this->_dados);
        
        responderJson($novaSala, 201);
    }
    
    /**
     * Atualiza uma sala existente
     */
    public function atualizar($id) {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Localiza a sala
        $indice = array_search($id, array_column($this->_dados['salas'], 'id'));
        
        if ($indice === false) {
            responderErro('Sala não encontrada', 404);
        }
        
        // Validações
        if (isset($dados['nome'])) {
            $salaExistente = array_filter($this->_dados['salas'], 
                function($sala) use ($dados, $id) {
                    return $sala['id'] !== $id && 
                           strtolower($sala['nome']) === strtolower($dados['nome']);
                }
            );
            
            if (!empty($salaExistente)) {
                responderErro('Já existe uma sala com este nome');
            }
            
            $this->_dados['salas'][$indice]['nome'] = $dados['nome'];
        }
        
        if (isset($dados['capacidade'])) {
            if (!is_numeric($dados['capacidade'])) {
                responderErro('Capacidade deve ser um número válido');
            }
            $this->_dados['salas'][$indice]['capacidade'] = 
                (int)$dados['capacidade'];
        }
        
        if (isset($dados['descricao'])) {
            $this->_dados['salas'][$indice]['descricao'] = $dados['descricao'];
        }
        
        $this->_dados['salas'][$indice]['dataAtualizacao'] = date('Y-m-d H:i:s');
        salvarDados($this->_dados);
        
        responderJson($this->_dados['salas'][$indice]);
    }
    
    /**
     * Remove uma sala
     */
    public function remover($id) {
        // Verifica se existem reservas para esta sala
        $reservasExistentes = array_filter($this->_dados['reservas'], 
            function($reserva) use ($id) {
                return $reserva['salaId'] === $id;
            }
        );
        
        if (!empty($reservasExistentes)) {
            responderErro('Não é possível remover uma sala com reservas ativas');
        }
        
        // Localiza e remove a sala
        $indice = array_search($id, array_column($this->_dados['salas'], 'id'));
        
        if ($indice === false) {
            responderErro('Sala não encontrada', 404);
        }
        
        array_splice($this->_dados['salas'], $indice, 1);
        salvarDados($this->_dados);
        
        responderJson(['mensagem' => 'Sala removida com sucesso']);
    }
}

// Roteamento das requisições
$gerenciador = new GerenciadorSala();
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
            $gerenciador->criar();
            break;
            
        case 'PUT':
            if (!$id) throw new Exception('ID da sala não informado');
            $gerenciador->atualizar($id);
            break;
            
        case 'DELETE':
            if (!$id) throw new Exception('ID da sala não informado');
            $gerenciador->remover($id);
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 