<?php
require_once 'config.php';
require_once 'middleware.php';
require_once __DIR__ . '/../database/Database.php';

// Verifica autenticação para métodos que modificam dados
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $usuario = verificarAutenticacao();
}

/**
 * Gerenciador de Salas
 */
class GerenciadorSala {
    private $_db;
    
    public function __construct() {
        $this->_db = Database::getInstance()->getConnection();
    }
    
    /**
     * Lista todas as salas cadastradas
     */
    public function listar() {
        $stmt = $this->_db->query('SELECT * FROM salas ORDER BY nome');
        $salas = $stmt->fetchAll();
        responderJson($salas);
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
        $stmt = $this->_db->prepare('SELECT id FROM salas WHERE LOWER(nome) = LOWER(?)');
        $stmt->execute([$dados['nome']]);
        if ($stmt->fetch()) {
            responderErro('Já existe uma sala com este nome');
        }
        
        try {
            $this->_db->beginTransaction();
            
            // Cria nova sala
            $stmt = $this->_db->prepare('
                INSERT INTO salas (id, nome, capacidade, descricao)
                VALUES (?, ?, ?, ?)
            ');
            
            $id = uniqid();
            $stmt->execute([
                $id,
                $dados['nome'],
                (int)$dados['capacidade'],
                $dados['descricao'] ?? ''
            ]);
            
            // Busca a sala criada
            $stmt = $this->_db->prepare('SELECT * FROM salas WHERE id = ?');
            $stmt->execute([$id]);
            $sala = $stmt->fetch();
            
            $this->_db->commit();
            responderJson($sala, 201);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Atualiza uma sala existente
     */
    public function atualizar($id) {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Verifica se a sala existe
        $stmt = $this->_db->prepare('SELECT * FROM salas WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            responderErro('Sala não encontrada', 404);
        }
        
        // Validações
        if (isset($dados['nome'])) {
            $stmt = $this->_db->prepare('
                SELECT id FROM salas 
                WHERE LOWER(nome) = LOWER(?) AND id != ?
            ');
            $stmt->execute([$dados['nome'], $id]);
            if ($stmt->fetch()) {
                responderErro('Já existe uma sala com este nome');
            }
        }
        
        if (isset($dados['capacidade']) && !is_numeric($dados['capacidade'])) {
            responderErro('Capacidade deve ser um número válido');
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
            
            if (isset($dados['capacidade'])) {
                $campos[] = 'capacidade = ?';
                $valores[] = (int)$dados['capacidade'];
            }
            
            if (isset($dados['descricao'])) {
                $campos[] = 'descricao = ?';
                $valores[] = $dados['descricao'];
            }
            
            $campos[] = 'data_atualizacao = CURRENT_TIMESTAMP';
            
            // Adiciona o ID no final do array de valores
            $valores[] = $id;
            
            $sql = 'UPDATE salas SET ' . implode(', ', $campos) . ' WHERE id = ?';
            $stmt = $this->_db->prepare($sql);
            $stmt->execute($valores);
            
            // Busca a sala atualizada
            $stmt = $this->_db->prepare('SELECT * FROM salas WHERE id = ?');
            $stmt->execute([$id]);
            $sala = $stmt->fetch();
            
            $this->_db->commit();
            responderJson($sala);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Remove uma sala
     */
    public function remover($id) {
        // Verifica se existem reservas para esta sala
        $stmt = $this->_db->prepare('SELECT id FROM reservas WHERE sala_id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            responderErro('Não é possível remover uma sala com reservas ativas');
        }
        
        try {
            $this->_db->beginTransaction();
            
            // Remove a sala
            $stmt = $this->_db->prepare('DELETE FROM salas WHERE id = ?');
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                responderErro('Sala não encontrada', 404);
            }
            
            $this->_db->commit();
            responderJson(['mensagem' => 'Sala removida com sucesso']);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
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