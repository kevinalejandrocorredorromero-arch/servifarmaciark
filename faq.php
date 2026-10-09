<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="description" content="Preguntas frecuentes de SERVIFARMACIA RK - Consulta las dudas más comunes sobre pedidos y entregas" />
  <title>Preguntas Frecuentes - SERVIFARMACIA RK</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --brand: #0a6dc2;
      --accent: #f47b20;
      --ink: #1a1a2e;
      --ink-secondary: #555570;
      --surface: #ffffff;
      --border: #dde0ec;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', system-ui, sans-serif; background: var(--surface); color: var(--ink); line-height: 1.7; font-size: 1rem; padding: 2rem 1rem; }
    .container { max-width: 800px; margin: 0 auto; }
    .header { text-align: center; margin-bottom: 3rem; }
    .header h1 { font-size: 2rem; font-weight: 700; color: var(--brand); margin-bottom: 0.5rem; }
    .header p { color: var(--ink-secondary); font-size: 0.9rem; }
    .section { margin-bottom: 1.5rem; }
    .question { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(15, 41, 71, 0.04); }
    .question h3 { font-size: 1.05rem; font-weight: 600; color: var(--brand); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
    .question h3::before { content: "+"; font-size: 1.4rem; color: var(--accent); }
    .question.active h3::before { content: "−"; }
    .answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
    .question.active .answer { max-height: 500px; }
    .answer p { margin-bottom: 0.5rem; color: var(--ink-secondary); }
    .footer-legal { text-align: center; margin-top: 3rem; padding-top: 1.5rem; border-top: 1px solid var(--border); color: var(--ink-secondary); font-size: 0.85rem; }
    a { color: var(--brand); }
    .back-link { display: inline-block; margin-bottom: 1.5rem; color: var(--brand); font-weight: 500; }
    @media (max-width: 640px) { body { padding: 1rem 0.5rem; } .header h1 { font-size: 1.5rem; } }
  </style>
</head>
<body>
<div class="container">
  <a href="index.php" class="back-link">← Volver al sitio</a>
  <div class="header">
    <h1>Preguntas Frecuentes</h1>
    <p>Las dudas más comunes sobre SERVIFARMACIA RK</p>
  </div>

  <div class="question">
    <h3>¿Cómo realizo un pedido?</h3>
    <div class="answer">
      <p>Agrega los productos a tu carrito, pulsa "Proceder al Pago", completa tus datos de entrega y elige tu método de pago (Efectivo, Nequi o Daviplata). Recibirás la confirmación de tu pedido al instante.</p>
    </div>
  </div>

  <div class="question">
    <h3>¿Qué métodos de pago aceptan?</h3>
    <div class="answer">
      <p>Aceptamos <strong>Efectivo</strong> (al recibir tu pedido), <strong>Nequi</strong> y <strong>Daviplata</strong>. Para Nequi y Daviplata, te mostramos un código QR para realizar el pago. Una vez pagado, envía el comprobante por WhatsApp.</p>
    </div>
  </div>

  <div class="question">
    <h3>¿Cuál es el tiempo de entrega?</h3>
    <div class="answer">
      <p>Las entregas se coordinan según tu ubicación. Nuestro equipo te contactará vía WhatsApp (+57 311 563 1854) para confirmar la hora de entrega. El tiempo estimado depende de la disponibilidad del producto y tu ubicación.</p>
    </div>
  </div>

  <div class="question">
    <h3>¿Puedo hacer seguimiento a mi pedido?</h3>
    <div class="answer">
      <p>Sí. Desde "Mi Cuenta" puedes ver el estado de tus pedidos. También puedes escribirnos por el chat en vivo o por WhatsApp al +57 311 563 1854.</p>
    </div>
  </div>

  <div class="question">
    <h3>¿Cómo contacto a soporte?</h3>
    <div class="answer">
      <p>Puedes usar el <strong>Chat en Vivo</strong> (ícono de chat en la esquina inferior derecha), escribirnos al WhatsApp <a href="https://wa.me/573115631854" target="_blank" rel="noopener">311 563 1854</a> o enviarnos un correo a <a href="mailto:info@servifarmaciark.com">info@servifarmaciark.com</a>.</p>
    </div>
  </div>

  <div class="question">
    <h3>¿Realizan envíos a domicilio?</h3>
    <div class="answer">
      <p>Sí, realizamos entregas a domicilio en Bogotá y Cundinamarca. Coordinamos la entrega directamente contigo por WhatsApp.</p>
    </div>
  </div>

  <div class="question">
    <h3>¿Puedo cambiar o cancelar mi pedido?</h3>
    <div class="answer">
      <p>Puedes cancelar o modificar tu pedido antes de que sea confirmado por nuestro equipo. Una vez confirmado el pedido y/o en proceso de entrega, no es posible cancelar. Contáctanos por WhatsApp para coordinar cambios.</p>
    </div>
  </div>

  <div class="question">
    <h3>¿Los productos tienen garantía?</h3>
    <div class="answer">
      <p>Todos los productos farmacéuticos cuentan con la garantía del fabricante e invima. No se aceptan devoluciones una vez entregados, excepto en casos de producto vencido o diferente al solicitado.</p>
    </div>
  </div>

  <div class="footer-legal">
    <p>© 2026 SERVIFARMACIA RK. Todos los derechos reservados.<br>
    Desarrollado con ❤️ por <a href="https://codestreamsoftware.com" target="_blank" rel="noopener">CodeStream Software</a></p>
  </div>
</div>
<script>
  document.querySelectorAll('.question h3').forEach(h3 => {
    h3.addEventListener('click', () => {
      const q = h3.parentElement;
      q.classList.toggle('active');
      document.querySelectorAll('.question').forEach(other => {
        if (other !== q) other.classList.remove('active');
      });
    });
  });
</script>
</body>
</html>