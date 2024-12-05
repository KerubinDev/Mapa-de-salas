<?php
/**
 * Router principal da aplicação
 * 
 * @author Seu Nome
 */

// Define constantes
define('DIRETORIO_BASE', __DIR__);
define('DIRETORIO_API', __DIR__ . '/api');

// Configura headers padrão
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Trata requisições OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

/**
 * Serve um arquivo estático se ele existir
 * 
 * @param string $caminhoArquivo Caminho do arquivo requisitado
 * @return bool True se o arquivo foi servido, False caso contrário
 */
function servirArquivoEstatico($caminhoArquivo) {
    if (file_exists($caminhoArquivo) && is_file($caminhoArquivo)) {
        // Define o tipo MIME baseado na extensão
        $extensao = strtolower(pathinfo($caminhoArquivo, PATHINFO_EXTENSION));
        $mimeTypes = [
            'html' => 'text/html',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'ico'  => 'image/x-icon',
            'webmanifest' => 'application/manifest+json'
        ];

        $contentType = $mimeTypes[$extensao] ?? 'text/plain';
        
        // Remove todos os headers anteriores
        header_remove();
        
        // Define o Content-Type apropriado
        header("Content-Type: $contentType; charset=UTF-8");
        
        // Lê e envia o arquivo
        readfile($caminhoArquivo);
        return true;
    }
    return false;
}

// Obtém a URI requisitada
$requestUri = $_SERVER['REQUEST_URI'];
$requestUri = strtok($requestUri, '?'); // Remove query string
$requestUri = rtrim($requestUri, '/'); // Remove barra final
if (empty($requestUri)) $requestUri = '/';

// Tenta servir arquivo estático primeiro
$caminhoArquivo = DIRETORIO_BASE . $requestUri;
if ($requestUri === '/') {
    $caminhoArquivo = DIRETORIO_BASE . '/index.html';
}

if (servirArquivoEstatico($caminhoArquivo)) {
    exit(0);
}

// Se não for arquivo estático, trata como requisição API
$metodo = $_SERVER['REQUEST_METHOD'];

// Remove o prefixo /api se existir
$rotaUri = $requestUri;
if (strpos($rotaUri, '/api') === 0) {
    $rotaUri = substr($rotaUri, 4);
}

$rotaChave = "$metodo:$rotaUri";

// Define as rotas disponíveis
$rotas = [
    // Rotas da API (mantém o Content-Type: application/json)
    'GET:/sala' => DIRETORIO_API . '/sala.php',
    'POST:/sala' => DIRETORIO_API . '/sala.php',
    'PUT:/sala' => DIRETORIO_API . '/sala.php',
    'DELETE:/sala' => DIRETORIO_API . '/sala.php',
    'GET:/reserva' => DIRETORIO_API . '/reserva.php',
    'POST:/reserva' => DIRETORIO_API . '/reserva.php',
    'PUT:/reserva' => DIRETORIO_API . '/reserva.php',
    'DELETE:/reserva' => DIRETORIO_API . '/reserva.php',
    'POST:/auth/login' => DIRETORIO_API . '/auth/login.php',
    'POST:/auth/logout' => DIRETORIO_API . '/auth/logout.php',
    'GET:/auth/perfil' => DIRETORIO_API . '/auth/perfil.php',
    'PUT:/auth/perfil' => DIRETORIO_API . '/auth/perfil.php',
    
    // Rotas de páginas (serão tratadas como arquivos estáticos)
    'GET:/admin' => DIRETORIO_BASE . '/admin/adminpanel.html',
    'GET:/admin/salas' => DIRETORIO_BASE . '/admin/salas.html',
    'GET:/admin/reservas' => DIRETORIO_BASE . '/admin/reservas.html',
    'GET:/admin/usuarios' => DIRETORIO_BASE . '/admin/usuarios.html',
    'GET:/admin/configuracoes' => DIRETORIO_BASE . '/admin/configuracoes.html'
];

// Verifica se a rota existe
if (isset($rotas[$rotaChave])) {
    $arquivoRota = $rotas[$rotaChave];
    
    // Se for um arquivo HTML, serve como arquivo estático
    if (pathinfo($arquivoRota, PATHINFO_EXTENSION) === 'html') {
        if (servirArquivoEstatico($arquivoRota)) {
            exit(0);
        }
    } else {
        // Se não for HTML, assume que é um arquivo PHP da API
        require $arquivoRota;
        exit(0);
    }
}

// Se chegou aqui, a rota não foi encontrada
http_response_code(404);
echo json_encode([
    'sucesso' => false,
    'erro' => [
        'codigo' => 404,
        'mensagem' => 'Rota não encontrada',
        'detalhes' => [
            'metodo' => $metodo,
            'uri' => $requestUri,
            'rotaUri' => $rotaUri,
            'rotaBase' => '/',
            'rotaChave' => $rotaChave,
            'rotasDisponiveis' => array_keys($rotas)
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);