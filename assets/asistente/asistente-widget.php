<?php
/**
 * Widget del Asistente Virtual SOFI.
 * Incluir justo antes de </body> en cada página donde quieras que aparezca
 * (por ejemplo, en tu footer.php o layout general):
 *
 *   <?php include $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/assets/asistente/asistente-widget.php'; ?>
 *
 * Ajusta las rutas de los <link> y <script> de abajo según donde
 * subas los archivos asistente.css / asistente.js dentro de tu proyecto.
 */
?>
<link rel="stylesheet" href="/Sofi-main/assets/asistente/asistente.css">

<button id="sofi-asistente-burbuja" title="¿Necesitas ayuda?">💬</button>

<div id="sofi-asistente-panel">
  <div id="sofi-asistente-header">
    <span class="titulo">Asistente SOFI</span>
    <div class="acciones">
      <button id="sofi-asistente-cerrar" title="Cerrar">✕</button>
    </div>
  </div>
  <div id="sofi-asistente-cuerpo"></div>
  <div id="sofi-asistente-footer">
    <button id="sofi-asistente-atras">⬅ Atrás</button>
    <button id="sofi-asistente-reiniciar">Reiniciar</button>
  </div>
</div>

<script src="/Sofi-main/assets/asistente/asistente.js"></script>