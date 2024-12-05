<?php
// Tamanhos dos ícones necessários
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// Carrega a imagem original
$source = __DIR__ . '/../assets/icons/icon-original.png';
$image = imagecreatefrompng($source);

// Gera os ícones em diferentes tamanhos
foreach ($sizes as $size) {
    $resized = imagecreatetruecolor($size, $size);
    
    // Preserva transparência
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    
    // Redimensiona
    imagecopyresampled(
        $resized, $image,
        0, 0, 0, 0,
        $size, $size,
        imagesx($image), imagesy($image)
    );
    
    // Salva
    imagepng($resized, __DIR__ . "/../assets/icons/icon-{$size}x{$size}.png");
    imagedestroy($resized);
}

imagedestroy($image);
echo "Ícones gerados com sucesso!\n"; 