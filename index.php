<?php
require_once __DIR__ . '/config.php';

// Rotas para arquivos HTML
$rotasHtml = [
    '/admin' => __DIR__ . '/admin/adminpanel.html',
    '/admin/salas' => __DIR__ . '/admin/salas.html', 
    '/admin/reservas' => __DIR__ . '/admin/reservas.html',
    '/admin/configuracoes' => __DIR__ . '/admin/configuracoes.html',
    '/login' => __DIR__ . '/login.html'
];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Verifica se é uma rota HTML
if (isset($rotasHtml[$uri])) {
    if (file_exists($rotasHtml[$uri])) {
        header('Content-Type: text/html');
        readfile($rotasHtml[$uri]);
        exit;
    }
}

// Se não for uma rota HTML, continua com o roteamento da API
require_once __DIR__ . '/api/rotas.php';

require_once 'includes/seo.php';

$seo = [
    'titulo' => 'Quadro de Horários - Sistema de Gestão de Salas',
    'descricao' => 'Visualize o quadro de horários e disponibilidade das salas em tempo real.',
    'palavrasChave' => 'quadro de horários, salas disponíveis, reservas, horários de aula'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $seo['titulo']; ?></title>
    <?php echo gerarMetasSEO($seo); ?>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/js/temas.js"></script>
    
    <!-- Estruturado Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Sistema de Gestão de Salas",
        "applicationCategory": "EducationalApplication",
        "operatingSystem": "Web Browser",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "BRL"
        },
        "description": "<?php echo $seo['descricao']; ?>",
        "browserRequirements": "Requires JavaScript. Requires HTML5.",
        "permissions": "Requires authentication for administrative features",
        "softwareVersion": "1.0.0"
    }
    </script>
</head>
<!-- ... resto do código ... -->