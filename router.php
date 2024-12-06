<?php
require_once __DIR__ . '/config.php';

// Função para servir arquivos HTML
function servirHtml($arquivo) {
    if (file_exists($arquivo)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($arquivo);
        exit;
    }
    return false;
}

// Mapeia rotas administrativas para arquivos HTML
$rotasAdmin = [
    '/admin' => 'admin/adminpanel.html',
    '/admin/salas' => 'admin/salas.html',
    '/admin/reservas' => 'admin/reservas.html',
    '/admin/usuarios' => 'admin/usuarios.html',
    '/admin/configuracoes' => 'admin/configuracoes.html',
    '/login' => 'login.html'
];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove .html se presente na URI
$uri = preg_replace('/\.html$/', '', $uri);

// Verifica se é uma rota administrativa
if (isset($rotasAdmin[$uri])) {
    if (servirHtml($rotasAdmin[$uri])) {
        exit;
    }
}

// Se não for uma rota administrativa, trata como API
if (preg_match('/^\/api\//', $uri)) {
    require_once __DIR__ . '/api/rotas.php';
    exit;
}

// Se chegou aqui, tenta servir um arquivo estático
$arquivoFisico = __DIR__ . $uri;
if (file_exists($arquivoFisico) && !is_dir($arquivoFisico)) {
    $ext = pathinfo($arquivoFisico, PATHINFO_EXTENSION);
    switch ($ext) {
        case 'js':
            header('Content-Type: application/javascript');
            break;
        case 'css':
            header('Content-Type: text/css');
            break;
        case 'json':
            header('Content-Type: application/json');
            break;
        case 'png':
            header('Content-Type: image/png');
            break;
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            break;
    }
    readfile($arquivoFisico);
    exit;
}

// Se nenhuma rota foi encontrada, retorna 404
header('Content-Type: application/json');
http_response_code(404);
echo json_encode([
    'sucesso' => false,
    'erro' => [
        'codigo' => 404,
        'mensagem' => 'Rota não encontrada',
        'detalhes' => [
            'metodo' => $_SERVER['REQUEST_METHOD'],
            'uri' => $uri,
            'rotasDisponiveis' => array_merge(
                array_keys($rotasAdmin),
                [
                    'GET:/sala',
                    'POST:/sala',
                    'PUT:/sala',
                    'DELETE:/sala',
                    'GET:/reserva',
                    'POST:/reserva',
                    'PUT:/reserva',
                    'DELETE:/reserva',
                    'POST:/auth/login',
                    'POST:/auth/logout',
                    'GET:/auth/perfil',
                    'PUT:/auth/perfil'
                ]
            )
        ]
    ]
]);