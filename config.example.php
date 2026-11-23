<?php
// Copia este archivo como config.php y ajusta los datos.
// config.php NO se sube a GitHub (está en .gitignore).

return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'catalogo',
        'user'    => 'root',
        'pass'    => '',          // En XAMPP el usuario root no tiene clave por defecto
    ],
    // Nombre que aparece en la página principal y en el panel
    'app_name' => 'MiCatálogo',
    // Símbolo de moneda que se muestra junto a los precios
    'moneda'   => '$',
    // Indicativo de país que se agrega si el dueño escribe el WhatsApp sin él (57 = Colombia)
    'indicativo_por_defecto' => '57',
];
