<?php
require_once 'config.php';
require_once 'middleware.php';
require_once __DIR__ . '/../database/Database.php';

// Verifica autenticação para métodos que modificam dados
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $usuario = verificarAutenticacao();
}

/**
 * Gerenciador de Turmas
 */
class GerenciadorTurma {
    private $_db;
    
    public function __construct() {
        $this->_db = Database::getInstance()->getConnection();
    }
    
    /**
     * Lista todas as turmas cadastradas
     */
    public function listar() {
        $stmt = $this->_db->query('SELECT * FROM turmas ORDER BY nome');
        $turmas = $stmt->fetchAll();
        responderJson($turmas);
    }
    
    /**
     * Cadastra uma nova turma
     */
    public function criar() {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Validação dos campos obrigatórios
        if (empty($dados['nome'])) {
            responderErro('Nome da turma é obrigatório');
        }
        
        if (empty($dados['professor'])) {
            responderErro('Professor responsável é obrigatório');
        }
        
        if (empty($dados['numeroAlunos']) || !is_numeric($dados['numeroAlunos'])) {
            responderErro('Número de alunos deve ser um valor válido');
        }
        
        if (empty($dados['turno']) || 
            !in_array($dados['turno'], ['manha', 'tarde', 'noite'])) {
            responderErro('Turno deve ser: manha, tarde ou noite');
        }
        
        // Verifica se já existe turma com mesmo nome
        $stmt = $this->_db->prepare('SELECT id FROM turmas WHERE LOWER(nome) = LOWER(?)');
        $stmt->execute([$dados['nome']]);
        if ($stmt->fetch()) {
            responderErro('Já existe uma turma com este nome');
        }
        
        try {
            $this->_db->beginTransaction();
            
            // Cria nova turma
            $stmt = $this->_db->prepare('
                INSERT INTO turmas (id, nome, professor, numero_alunos, turno)
                VALUES (?, ?, ?, ?, ?)
            ');
            
            $id = uniqid();
            $stmt->execute([
                $id,
                $dados['nome'],
                $dados['professor'],
                (int)$dados['numeroAlunos'],
                $dados['turno']
            ]);
            
            // Busca a turma criada
            $stmt = $this->_db->prepare('SELECT * FROM turmas WHERE id = ?');
            $stmt->execute([$id]);
            $turma = $stmt->fetch();
            
            $this->_db->commit();
            responderJson($turma, 201);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Atualiza uma turma existente
     */
    public function atualizar($id) {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        // Verifica se a turma existe
        $stmt = $this->_db->prepare('SELECT * FROM turmas WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            responderErro('Turma não encontrada', 404);
        }
        
        // Validações
        if (isset($dados['nome'])) {
            $stmt = $this->_db->prepare('
                SELECT id FROM turmas 
                WHERE LOWER(nome) = LOWER(?) AND id != ?
            ');
            $stmt->execute([$dados['nome'], $id]);
            if ($stmt->fetch()) {
                responderErro('Já existe uma turma com este nome');
            }
        }
        
        if (isset($dados['turno']) && 
            !in_array($dados['turno'], ['manha', 'tarde', 'noite'])) {
            responderErro('Turno deve ser: manha, tarde ou noite');
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
            
            if (isset($dados['professor'])) {
                $campos[] = 'professor = ?';
                $valores[] = $dados['professor'];
            }
            
            if (isset($dados['numeroAlunos'])) {
                $campos[] = 'numero_alunos = ?';
                $valores[] = (int)$dados['numeroAlunos'];
            }
            
            if (isset($dados['turno'])) {
                $campos[] = 'turno = ?';
                $valores[] = $dados['turno'];
            }
            
            $campos[] = 'data_atualizacao = CURRENT_TIMESTAMP';
            
            // Adiciona o ID no final do array de valores
            $valores[] = $id;
            
            $sql = 'UPDATE turmas SET ' . implode(', ', $campos) . ' WHERE id = ?';
            $stmt = $this->_db->prepare($sql);
            $stmt->execute($valores);
            
            // Busca a turma atualizada
            $stmt = $this->_db->prepare('SELECT * FROM turmas WHERE id = ?');
            $stmt->execute([$id]);
            $turma = $stmt->fetch();
            
            $this->_db->commit();
            responderJson($turma);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Remove uma turma
     */
    public function remover($id) {
        // Verifica se existem reservas para esta turma
        $stmt = $this->_db->prepare('SELECT id FROM reservas WHERE turma_id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            responderErro('Não é possível remover uma turma com reservas ativas');
        }
        
        try {
            $this->_db->beginTransaction();
            
            // Remove a turma
            $stmt = $this->_db->prepare('DELETE FROM turmas WHERE id = ?');
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                responderErro('Turma não encontrada', 404);
            }
            
            $this->_db->commit();
            responderJson(['mensagem' => 'Turma removida com sucesso']);
            
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }
    }
}

// Roteamento das requisições
$gerenciador = new GerenciadorTurma();
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
            if (!$id) throw new Exception('ID da turma não informado');
            $gerenciador->atualizar($id);
            break;
            
        case 'DELETE':
            if (!$id) throw new Exception('ID da turma não informado');
            $gerenciador->remover($id);
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
} 