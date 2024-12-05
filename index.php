<?php
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