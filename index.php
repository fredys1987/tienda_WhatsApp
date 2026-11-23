<?php
require __DIR__ . '/inc/app.php';
require __DIR__ . '/inc/layout.php';

cabecera('Tu catálogo en línea');
?>
<section class="hero">
  <h1>Tu tienda en línea, con pedidos directo a tu WhatsApp</h1>
  <p>Crea tu catálogo en minutos, sube tus productos con foto y precio, y comparte tu enlace.
     Tus clientes arman su pedido y te llega listo por WhatsApp.</p>
  <a class="btn" href="<?= e(base_url('registro.php')) ?>">Crear mi tienda gratis</a>
</section>

<section class="pasos">
  <div class="tarjeta"><strong>1. Crea tu tienda</strong><p>Elige el nombre y el enlace de tu tienda.</p></div>
  <div class="tarjeta"><strong>2. Sube productos</strong><p>Foto, nombre, precio y descripción.</p></div>
  <div class="tarjeta"><strong>3. Recibe pedidos</strong><p>El pedido te llega al WhatsApp con el total.</p></div>
</section>
<?php pie();
