// Carrito de la tienda pública. El pedido se guarda en el celular del cliente
// (localStorage) y al confirmar se abre WhatsApp con el mensaje listo.
(function () {
  'use strict';
  var T = window.TIENDA;
  var clave = 'carrito:' + T.slug;
  var porId = {};
  T.productos.forEach(function (p) { porId[p.id] = p; });

  var carrito = leer();

  function leer() {
    try {
      var c = JSON.parse(localStorage.getItem(clave) || '{}');
      // Quita productos que ya no existen en la tienda
      Object.keys(c).forEach(function (id) { if (!porId[id] || !(c[id] > 0)) delete c[id]; });
      return c;
    } catch (e) { return {}; }
  }
  function guardar() {
    try { localStorage.setItem(clave, JSON.stringify(carrito)); } catch (e) {}
  }

  function dinero(v) {
    var dec = Math.round(v) === v ? 0 : 2;
    return T.moneda + v.toLocaleString('es-CO', { minimumFractionDigits: dec, maximumFractionDigits: dec });
  }

  function totales() {
    var cant = 0, total = 0;
    Object.keys(carrito).forEach(function (id) {
      cant += carrito[id];
      total += carrito[id] * porId[id].precio;
    });
    return { cant: cant, total: Math.round(total * 100) / 100 };
  }

  function cambiar(id, delta) {
    var n = (carrito[id] || 0) + delta;
    if (n <= 0) delete carrito[id]; else carrito[id] = Math.min(n, 999);
    guardar();
    pintar();
  }

  var $ = function (id) { return document.getElementById(id); };

  function controlesHTML(id) {
    var n = carrito[id] || 0;
    if (!n) return '<button type="button" class="btn btn-ancho agregar">Agregar</button>';
    return '<div class="contador"><button type="button" class="menos" aria-label="Quitar uno">−</button>' +
      '<span>' + n + '</span><button type="button" class="mas" aria-label="Agregar uno">+</button></div>';
  }

  function escapar(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function pintar() {
    document.querySelectorAll('.controles').forEach(function (el) {
      el.innerHTML = controlesHTML(el.dataset.id);
    });
    var t = totales();
    $('barraCarrito').hidden = t.cant === 0;
    $('barraCant').textContent = t.cant + (t.cant === 1 ? ' producto' : ' productos');
    $('barraTotal').textContent = dinero(t.total);
    $('total').textContent = dinero(t.total);

    var html = '';
    Object.keys(carrito).forEach(function (id) {
      var p = porId[id];
      html += '<li><div><strong>' + escapar(p.nombre) + '</strong><span>' + dinero(p.precio * carrito[id]) + '</span></div>' +
        '<div class="contador" data-id="' + id + '"><button type="button" class="menos">−</button><span>' +
        carrito[id] + '</span><button type="button" class="mas">+</button></div></li>';
    });
    $('items').innerHTML = html || '<li class="nota">Tu pedido está vacío.</li>';
    if (t.cant === 0) cerrar();
  }

  function abrir() { $('carrito').hidden = false; $('velo').hidden = false; }
  function cerrar() { $('carrito').hidden = true; $('velo').hidden = true; }

  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('button');
    if (!b) return;
    var cont = b.closest('[data-id]');
    if (!cont) return;
    var id = cont.dataset.id;
    if (b.classList.contains('agregar') || b.classList.contains('mas')) cambiar(id, 1);
    else if (b.classList.contains('menos')) cambiar(id, -1);
  });

  $('barraCarrito').addEventListener('click', abrir);
  $('cerrar').addEventListener('click', cerrar);
  $('velo').addEventListener('click', cerrar);
  $('vaciar').addEventListener('click', function () { carrito = {}; guardar(); pintar(); });

  // Recordar el nombre del cliente para el próximo pedido
  try { $('cNombre').value = localStorage.getItem('cliente:nombre') || ''; } catch (e) {}

  $('formPedido').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var nombre = $('cNombre').value.trim();
    var notas = $('cNotas').value.trim();
    if (!nombre) { $('cNombre').focus(); return; }
    try { localStorage.setItem('cliente:nombre', nombre); } catch (e) {}

    var lineas = ['*Nuevo pedido - ' + T.tienda + '*', ''];
    Object.keys(carrito).forEach(function (id) {
      var p = porId[id];
      lineas.push(carrito[id] + ' x ' + p.nombre + ' = ' + dinero(p.precio * carrito[id]));
    });
    lineas.push('', '*Total: ' + dinero(totales().total) + '*', '', 'Cliente: ' + nombre);
    if (notas) lineas.push('Notas: ' + notas);

    var url = 'https://wa.me/' + T.whatsapp + '?text=' + encodeURIComponent(lineas.join('\n'));
    window.location.href = url;
  });

  // Buscador
  var buscar = $('buscar');
  if (buscar) {
    buscar.addEventListener('input', function () {
      var q = buscar.value.trim().toLowerCase();
      document.querySelectorAll('.producto').forEach(function (el) {
        el.hidden = q !== '' && el.dataset.nombre.indexOf(q) === -1;
      });
    });
  }

  pintar();
})();
