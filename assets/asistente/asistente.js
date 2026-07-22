// ===== Asistente SOFI - motor =====
(function () {
  const RUTA_DATOS = "/Sofi-main/assets/asistente/asistente-data.json"; // ajusta esta ruta a donde subas el JSON

  let datos = null;
  let historial = []; // pila de nodos visitados, ej: ["inicio", "catalogos", "cat_terceros"]

  const panel = document.getElementById("sofi-asistente-panel");
  const burbuja = document.getElementById("sofi-asistente-burbuja");
  const cuerpo = document.getElementById("sofi-asistente-cuerpo");
  const btnAtras = document.getElementById("sofi-asistente-atras");
  const btnReiniciar = document.getElementById("sofi-asistente-reiniciar");
  const btnCerrar = document.getElementById("sofi-asistente-cerrar");

  async function cargarDatos() {
    if (datos) return datos;
    const resp = await fetch(RUTA_DATOS);
    datos = await resp.json();
    return datos;
  }

  function nodoActual() {
    return historial[historial.length - 1];
  }

  async function irANodo(idNodo) {
    await cargarDatos();
    historial.push(idNodo);
    renderizar();
  }

  function volver() {
    if (historial.length > 1) {
      historial.pop();
      renderizar();
    }
  }

  function reiniciar() {
    historial = ["inicio"];
    renderizar();
  }

  function renderizar() {
    const id = nodoActual();
    const nodo = datos[id];
    if (!nodo) return;

    cuerpo.innerHTML = "";

    const divMensaje = document.createElement("div");
    divMensaje.className = "sofi-mensaje";
    divMensaje.textContent = nodo.mensaje;

    if (nodo.tipo === "respuesta" && nodo.enlace) {
      const enlace = document.createElement("a");
      enlace.href = nodo.enlace;
      enlace.className = "sofi-enlace-btn";
      enlace.textContent = "Ir a esta sección →";
      divMensaje.appendChild(document.createElement("br"));
      divMensaje.appendChild(enlace);
    }
    cuerpo.appendChild(divMensaje);

    if (Array.isArray(nodo.opciones) && nodo.opciones.length) {
      const divOpciones = document.createElement("div");
      divOpciones.className = "sofi-opciones";
      nodo.opciones.forEach((op) => {
        const btn = document.createElement("button");
        btn.textContent = op.texto;
        btn.addEventListener("click", () => irANodo(op.siguiente));
        divOpciones.appendChild(btn);
      });
      cuerpo.appendChild(divOpciones);
    }

    if (nodo.tipo === "respuesta") {
      const btnMenu = document.createElement("div");
      btnMenu.className = "sofi-opciones";
      const btn = document.createElement("button");
      btn.textContent = "⬅ Volver al menú principal";
      btn.addEventListener("click", reiniciar);
      btnMenu.appendChild(btn);
      cuerpo.appendChild(btnMenu);
    }

    cuerpo.scrollTop = 0;
    btnAtras.disabled = historial.length <= 1;
  }

  function abrirPanel() {
    panel.classList.add("abierto");
    if (historial.length === 0) {
      reiniciar();
    }
  }

  function cerrarPanel() {
    panel.classList.remove("abierto");
  }

  burbuja.addEventListener("click", () => {
    if (panel.classList.contains("abierto")) {
      cerrarPanel();
    } else {
      abrirPanel();
    }
  });
  btnCerrar.addEventListener("click", cerrarPanel);
  btnAtras.addEventListener("click", volver);
  btnReiniciar.addEventListener("click", reiniciar);

  // Precarga los datos en segundo plano para que el primer clic sea instantáneo
  cargarDatos();
})();