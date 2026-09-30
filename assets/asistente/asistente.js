// ===== Asistente SOFI - motor =====
(function () {
  const RUTA_DATOS = "/Sofi-main/assets/asistente/asistente-data.json"; // ajusta esta ruta a donde subas el JSON

  const ICONOS_ACCION = {
    crear: "➕",
    editar: "✏️",
    buscar: "🔍",
    consultar: "👁️",
    configurar: "⚙️",
    info: "ℹ️"
  };

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

  // Crea una burbuja de chat con animación escalonada (delay en ms)
  function crearBurbuja(delayMs, claseExtra) {
    const div = document.createElement("div");
    div.className = "sofi-burbuja" + (claseExtra ? " " + claseExtra : "");
    div.style.animationDelay = delayMs + "ms";
    return div;
  }

  function renderPregunta(nodo) {
    const burbujaMsg = crearBurbuja(0);
    burbujaMsg.innerHTML = `<span>${nodo.mensaje}</span>`;
    cuerpo.appendChild(burbujaMsg);

    if (Array.isArray(nodo.opciones) && nodo.opciones.length) {
      const divOpciones = document.createElement("div");
      divOpciones.className = "sofi-opciones";
      nodo.opciones.forEach((op, i) => {
        const btn = document.createElement("button");
        btn.textContent = op.texto;
        btn.style.animationDelay = (120 + i * 70) + "ms";
        btn.addEventListener("click", () => irANodo(op.siguiente));
        divOpciones.appendChild(btn);
      });
      cuerpo.appendChild(divOpciones);
    }
  }

  function renderRespuesta(nodo) {
    let delay = 0;
    const paso = 220; // ms entre cada burbuja

    // Burbuja de introducción, con el ícono de la acción
    const icono = ICONOS_ACCION[nodo.accion] || ICONOS_ACCION.info;
    const burbujaIntro = crearBurbuja(delay);
    burbujaIntro.innerHTML = `<span class="sofi-badge-accion">${icono}</span><span>${nodo.intro}</span>`;
    cuerpo.appendChild(burbujaIntro);
    delay += paso;

    // Burbujas de pasos numerados
    if (Array.isArray(nodo.pasos) && nodo.pasos.length) {
      const contPasos = document.createElement("div");
      contPasos.className = "sofi-pasos";
      nodo.pasos.forEach((paso_texto, i) => {
        const burbujaPaso = crearBurbuja(delay);
        burbujaPaso.innerHTML = `<span class="sofi-num">${i + 1}</span><span>${paso_texto}</span>`;
        contPasos.appendChild(burbujaPaso);
        delay += paso;
      });
      cuerpo.appendChild(contPasos);
    }

    // Botón de enlace directo (si existe)
    if (nodo.enlace) {
      const burbujaEnlace = crearBurbuja(delay);
      const enlace = document.createElement("a");
      enlace.href = nodo.enlace;
      enlace.className = "sofi-enlace-btn";
      enlace.textContent = "Ir a esta sección →";
      burbujaEnlace.appendChild(enlace);
      cuerpo.appendChild(burbujaEnlace);
      delay += paso;
    }

    // Botón para volver al menú principal
    const divOpciones = document.createElement("div");
    divOpciones.className = "sofi-opciones";
    const btn = document.createElement("button");
    btn.textContent = "⬅ Volver al menú principal";
    btn.style.animationDelay = delay + "ms";
    btn.addEventListener("click", reiniciar);
    divOpciones.appendChild(btn);
    cuerpo.appendChild(divOpciones);
  }

  function renderizar() {
    const id = nodoActual();
    const nodo = datos[id];
    if (!nodo) return;

    panel.setAttribute("data-modulo", nodo.modulo || "general");
    cuerpo.innerHTML = "";

    if (nodo.tipo === "respuesta") {
      renderRespuesta(nodo);
    } else {
      renderPregunta(nodo);
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