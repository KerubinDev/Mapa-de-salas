<?php
/**
 * Gera meta tags SEO para as páginas
 */
function gerarMetasSEO($pagina = []) {
    $defaults = [
        'titulo' => 'Sistema de Gestão de Salas',
        'descricao' => 'Sistema para gerenciamento de reservas de salas de aula, controle de horários e turmas.',
        'palavrasChave' => 'gestão de salas, reserva de salas, sistema escolar, controle de horários',
        'imagem' => '/assets/img/og-image.jpg',
        'tipo' => 'website',
        'url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
    ];

    $meta = array_merge($defaults, $pagina);

    return <<<HTML
    <!-- Meta tags básicas -->
    <meta name="description" content="{$meta['descricao']}">
    <meta name="keywords" content="{$meta['palavrasChave']}">
    <meta name="author" content="Nome da Instituição">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{$meta['url']}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{$meta['tipo']}">
    <meta property="og:url" content="{$meta['url']}">
    <meta property="og:title" content="{$meta['titulo']}">
    <meta property="og:description" content="{$meta['descricao']}">
    <meta property="og:image" content="{$meta['imagem']}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{$meta['url']}">
    <meta property="twitter:title" content="{$meta['titulo']}">
    <meta property="twitter:description" content="{$meta['descricao']}">
    <meta property="twitter:image" content="{$meta['imagem']}">

    <!-- Outras meta tags -->
    <meta name="application-name" content="Sistema de Gestão de Salas">
    <meta name="theme-color" content="#1d4ed8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Gestão de Salas">

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
HTML;
} 