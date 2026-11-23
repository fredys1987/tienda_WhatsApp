# Tienda WhatsApp: catálogo en línea multitienda

Plataforma sencilla tipo "nube" donde cada dueño crea su tienda, sube sus productos y recibe los pedidos directo en su WhatsApp.

- **Dueño**: se registra, elige el enlace de su tienda (`/tienda/mi-tienda`), sube productos con foto, precio y descripción, y cambia logo, color y número de WhatsApp.
- **Cliente**: abre el enlace, agrega productos al pedido, escribe su nombre y dirección, y al tocar **Enviar pedido por WhatsApp** se abre WhatsApp con el pedido listo y el total, dirigido al número del dueño.
- **Tipo app**: cada tienda se puede "agregar a la pantalla de inicio" del celular y abre como una aplicación, con su propio ícono y color.
- Hecho en **PHP 8 + MySQL**, sin frameworks ni Composer, para que corra en XAMPP y en Hostinger tal cual.

## Correrlo en tu computador (XAMPP)

1. Copia esta carpeta dentro de `C:\xampp\htdocs\` con el nombre `catalogo` (queda `C:\xampp\htdocs\catalogo`).
2. Abre el panel de XAMPP y enciende **Apache** y **MySQL**.
3. Entra a http://localhost/phpmyadmin, crea una base de datos llamada `catalogo` (cotejamiento `utf8mb4_unicode_ci`), y en la pestaña **Importar** sube el archivo `sql/schema.sql`.
4. Copia `config.example.php` como `config.php`. Con XAMPP normal no tienes que cambiar nada (usuario `root` sin clave).
5. Abre http://localhost/catalogo/ y crea tu primera tienda.

Requisitos: PHP 8.1 o más nuevo con la extensión **GD** (en XAMPP ya viene activa) y `mod_rewrite` de Apache (también viene activo en XAMPP).

### Sin XAMPP (servidor de PHP)

Si tienes PHP y MySQL instalados por aparte, después de los pasos 3 y 4:

```
php -S localhost:8000 router.php
```

y abre http://localhost:8000.

## Subirlo a Hostinger

1. En hPanel, crea una base de datos MySQL y un usuario; anota nombre, usuario y clave.
2. Importa `sql/schema.sql` desde phpMyAdmin.
3. Sube todos los archivos a `public_html` (o a una subcarpeta) con el Administrador de archivos o FTP.
4. Crea `config.php` a partir de `config.example.php` con los datos de la base de datos de Hostinger.
5. Verifica que la carpeta `uploads/` tenga permisos de escritura (755).

## Cómo está organizado

| Archivo | Qué hace |
|---|---|
| `index.php` | Página de inicio de la plataforma |
| `registro.php`, `login.php`, `logout.php` | Cuentas de los dueños |
| `panel/index.php` | Lista de productos y enlace para compartir la tienda |
| `panel/producto.php` | Crear, editar, ocultar y eliminar un producto |
| `panel/tienda.php` | Nombre, enlace, WhatsApp, descripción, logo y color |
| `tienda.php` | Tienda pública con el carrito (`/tienda/<enlace>`) |
| `assets/tienda.js` | Carrito y armado del mensaje de WhatsApp |
| `manifest.php`, `icono.php`, `sw.js` | Lo necesario para instalar la tienda como app |
| `inc/` | Conexión, sesión, utilidades y subida de fotos |
| `sql/schema.sql` | Tablas `usuarios`, `tiendas`, `productos` |

## Seguridad incluida

- Contraseñas con `password_hash`, sesión renovada al entrar y freno a intentos repetidos de contraseña.
- Todas las consultas usan sentencias preparadas (PDO), y todos los formularios llevan token CSRF.
- Cada dueño solo puede ver y editar los productos de su propia tienda.
- Las fotos se revisan y se vuelven a guardar como JPG (máx. 1000 px), así que un archivo disfrazado de imagen no llega al servidor; además `uploads/` no ejecuta PHP.
- `config.php`, `inc/` y `sql/` están bloqueados desde el navegador.

## Notas del MVP

- El pedido no se guarda en la base de datos: viaja completo en el mensaje de WhatsApp. El dueño confirma precios y disponibilidad en el chat.
- El número de WhatsApp se guarda con indicativo. Si el dueño escribe 10 dígitos, se agrega el indicativo de `config.php` (57 = Colombia).
- Moneda y formato de precios: `$45.000`. Se cambia el símbolo en `config.php`.
