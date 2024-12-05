<?php
require_once '../config.php';
require_once '../middleware.php';
require_once __DIR__ . '/../../database/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $metodo = $_SERVER['REQUEST_METHOD'];
    
    switch ($metodo) {
        case 'GET': // Lista sessões ativas do usuário
            $usuario = verificarAutenticacao();
            
            $stmt = $db->prepare('
                SELECT id, ip, user_agent, data_criacao, data_expiracao
                FROM sessoes
                WHERE usuario_id = ? AND data_expiracao > CURRENT_TIMESTAMP
                ORDER BY data_criacao DESC
            ');
            $stmt->execute([$usuario['id']]);
            $sessoes = $stmt->fetchAll();
            
            // Adiciona informações do dispositivo
            foreach ($sessoes as &$sessao) {
                $sessao['dispositivo'] = [
                    'browser' => getBrowser($sessao['user_agent']),
                    'sistema' => getOS($sessao['user_agent']),
                    'atual' => $sessao['id'] === session_id()
                ];
            }
            
            responderJson($sessoes);
            break;
            
        case 'POST': // Cria uma nova sessão
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (empty($dados['email']) || empty($dados['senha'])) {
                throw new Exception('Email e senha são obrigatórios');
            }
            
            // Verifica as credenciais
            $stmt = $db->prepare('SELECT * FROM usuarios WHERE email = ?');
            $stmt->execute([$dados['email']]);
            $usuario = $stmt->fetch();
            
            if (!$usuario || !password_verify($dados['senha'], $usuario['senha'])) {
                throw new Exception('Credenciais inválidas', 401);
            }
            
            try {
                $db->beginTransaction();
                
                // Gera um novo token
                $token = bin2hex(random_bytes(32));
                $expira = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                // Cria a sessão
                $stmt = $db->prepare('
                    INSERT INTO sessoes (id, usuario_id, token, ip, user_agent, data_expiracao)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                
                $sessaoId = uniqid();
                $stmt->execute([
                    $sessaoId,
                    $usuario['id'],
                    $token,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    $expira
                ]);
                
                // Atualiza o token do usuário
                $stmt = $db->prepare('
                    UPDATE usuarios 
                    SET token = ?, data_atualizacao = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ');
                $stmt->execute([$token, $usuario['id']]);
                
                // Remove a senha antes de retornar
                unset($usuario['senha']);
                $usuario['token'] = $token;
                $usuario['sessao_id'] = $sessaoId;
                
                $db->commit();
                responderJson($usuario);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        case 'DELETE': // Encerra uma sessão
            $usuario = verificarAutenticacao();
            $sessaoId = $_GET['id'] ?? null;
            
            if (!$sessaoId) {
                throw new Exception('ID da sessão não informado');
            }
            
            // Verifica se a sessão pertence ao usuário
            $stmt = $db->prepare('
                SELECT id FROM sessoes 
                WHERE id = ? AND usuario_id = ?
            ');
            $stmt->execute([$sessaoId, $usuario['id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Sessão não encontrada', 404);
            }
            
            // Remove a sessão
            $stmt = $db->prepare('DELETE FROM sessoes WHERE id = ?');
            $stmt->execute([$sessaoId]);
            
            responderJson(['mensagem' => 'Sessão encerrada com sucesso']);
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
    
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
}

/**
 * Funções auxiliares para identificar o navegador e sistema operacional
 */
function getBrowser($userAgent) {
    $browser = "Desconhecido";
    $browsers = [
        '/msie/i'      => 'Internet Explorer',
        '/firefox/i'   => 'Firefox',
        '/safari/i'    => 'Safari',
        '/chrome/i'    => 'Chrome',
        '/edge/i'      => 'Edge',
        '/opera/i'     => 'Opera',
        '/mobile/i'    => 'Mobile Browser'
    ];

    foreach ($browsers as $regex => $value) {
        if (preg_match($regex, $userAgent)) {
            $browser = $value;
            break;
        }
    }
    return $browser;
}

function getOS($userAgent) {
    $os = "Desconhecido";
    $sistemas = [
        '/windows nt/i'     => 'Windows',
        '/macintosh|mac os/i' => 'MacOS',
        '/linux/i'         => 'Linux',
        '/ubuntu/i'        => 'Ubuntu',
        '/iphone/i'        => 'iPhone',
        '/ipod/i'          => 'iPod',
        '/ipad/i'          => 'iPad',
        '/android/i'       => 'Android',
        '/webos/i'         => 'Mobile'
    ];

    foreach ($sistemas as $regex => $value) {
        if (preg_match($regex, $userAgent)) {
            $os = $value;
            break;
        }
    }
    return $os;
} 