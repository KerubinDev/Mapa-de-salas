<?php
require_once '../config.php';
require_once '../middleware.php';

try {
    $metodo = $_SERVER['REQUEST_METHOD'];
    
    switch ($metodo) {
        case 'GET': // Lista sessões ativas do usuário
            $usuario = verificarAutenticacao();
            
            // Busca todas as sessões do usuário
            $logs = array_filter($db->getData('logs'), function($log) use ($usuario) {
                return $log['usuarioId'] === $usuario['id'] && 
                       $log['acao'] === 'login' &&
                       strtotime($log['dataCriacao']) > strtotime('-30 days');
            });
            
            // Agrupa por IP e User Agent
            $sessoes = [];
            foreach ($logs as $log) {
                $chave = $log['ip'] . '|' . $log['userAgent'];
                if (!isset($sessoes[$chave])) {
                    $sessoes[$chave] = [
                        'id' => uniqid(),
                        'ip' => $log['ip'],
                        'userAgent' => $log['userAgent'],
                        'dataCriacao' => $log['dataCriacao'],
                        'dispositivo' => [
                            'browser' => getBrowser($log['userAgent']),
                            'sistema' => getOS($log['userAgent']),
                            'atual' => ($log['ip'] === $_SERVER['REMOTE_ADDR'] && 
                                      $log['userAgent'] === $_SERVER['HTTP_USER_AGENT'])
                        ]
                    ];
                }
            }
            
            responderJson(array_values($sessoes));
            break;
            
        case 'DELETE': // Encerra uma sessão específica
            $usuario = verificarAutenticacao();
            $sessaoId = $_GET['id'] ?? null;
            
            if (!$sessaoId) {
                throw new Exception('ID da sessão não informado');
            }
            
            // Como estamos usando logs para controle de sessão,
            // vamos apenas registrar o logout
            $db->insert('logs', [
                'usuarioId' => $usuario['id'],
                'acao' => 'logout',
                'detalhes' => "Sessão encerrada: {$sessaoId}",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            responderJson(['mensagem' => 'Sessão encerrada com sucesso']);
            break;
            
        default:
            throw new Exception('Método não suportado', 405);
    }
    
} catch (Exception $e) {
    responderErro($e->getMessage(), $e->getCode() ?: 400);
}

// Funções auxiliares
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