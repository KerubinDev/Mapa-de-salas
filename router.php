<?php
// Mapeia extensões para tipos MIME
$mimeTypes = [
    'js' => 'application/javascript',
    'webmanifest' => 'application/manifest+json',
    'json' => 'application/json',
    'css' => 'text/css',
    'html' => 'text/html',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'gif' => 'image/gif'
];

// Obtém o caminho da requisição
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$ext = pathinfo($uri, PATHINFO_EXTENSION);

// Se for um arquivo estático e existir
if ($ext && file_exists(__DIR__ . $uri)) {
    // Define o tipo MIME correto
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    readfile(__DIR__ . $uri);
    exit;
}

// Mapeia rotas da API
$rotas = [
    '/api/auth/login' => '/api/auth/login.php',
    '/api/auth/logout' => '/api/auth/logout.php',
    '/api/auth/registro' => '/api/auth/registro.php',
    '/api/auth/perfil' => '/api/auth/perfil.php',
    '/api/sala' => '/api/sala.php',
    '/api/turma' => '/api/turma.php',
    '/api/reserva' => '/api/reserva.php'
];

// Verifica se a rota existe
$rota = $rotas[$uri] ?? null;
if ($rota) {
    include __DIR__ . $rota;
    exit;
}

// Se não for uma rota conhecida, retorna 404
http_response_code(404);
echo json_encode(['erro' => 'Rota não encontrada']);
 