<?php
require_once 'AuthManager.php';

// Tratamento específico para OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json');
    exit(0);
}

// Verifica autenticação
$auth = AuthManager::getInstance();
if (!$auth->verificarAutenticacao()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

// Obtém o usuário autenticado
$usuarioAtual = $auth->getUsuarioAutenticado();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Lista usuários (apenas admin pode ver todos os usuários)
            if ($usuarioAtual['tipo'] === 'admin') {
                $usuarios = $auth->listarUsuarios();
                echo json_encode($usuarios);
            } else {
                // Usuários normais só podem ver seu próprio perfil
                echo json_encode([$usuarioAtual]);
            }
            break;

        case 'POST':
            // Cria novo usuário (apenas admin)
            if ($usuarioAtual['tipo'] !== 'admin') {
                throw new Exception('Apenas administradores podem criar usuários', 403);
            }

            $dados = json_decode(file_get_contents('php://input'), true);
            
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

            $novoUsuario = $auth->criarUsuario($dados, $usuarioAtual);
            echo json_encode($novoUsuario);
            break;

        case 'PUT':
            // Atualiza usuário
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID do usuário não informado');
            }

            // Apenas admin pode editar outros usuários
            if ($usuarioAtual['tipo'] !== 'admin' && $usuarioAtual['id'] !== $id) {
                throw new Exception('Não autorizado a editar este usuário', 403);
            }

            $dados = json_decode(file_get_contents('php://input'), true);
            $usuarioAtualizado = $auth->atualizarUsuario($id, $dados, $usuarioAtual);
            echo json_encode($usuarioAtualizado);
            break;

        case 'DELETE':
            // Remove usuário (apenas admin)
            if ($usuarioAtual['tipo'] !== 'admin') {
                throw new Exception('Apenas administradores podem remover usuários', 403);
            }

            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID do usuário não informado');
            }

            // Não permite remover o próprio usuário admin
            if ($id === 'admin') {
                throw new Exception('Não é possível remover o usuário administrador principal');
            }

            $auth->removerUsuario($id);
            echo json_encode(['mensagem' => 'Usuário removido com sucesso']);
            break;

        default:
            throw new Exception('Método não suportado', 405);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode(['erro' => $e->getMessage()]);
} 