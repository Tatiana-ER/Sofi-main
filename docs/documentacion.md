# Documentación SOFI

Carpeta: docs/

Esta documentación contiene descripciones de los archivos y módulos rediseñados, corregidos y creados en el proyecto SOFI.

# improved-style.css

- Ubicación: assets/css/improved-style.css
- Propósito: Estilos globales y nuevo diseño aplicado a las páginas rediseñadas.
- Contenido principal: Paleta de colores, tipografías, variables CSS, layout de header/footer, utilidades para tablas y botones.
- Uso: Importado en la mayoría de vistas; reemplaza/acompaña CSS base.

# index.php

- Ruta: index.php
- Propósito: Página de inicio / login o landing rediseñada.
- Elementos: Enlaces a dashboard, incluye improved-style.css y assets JS.
- Acciones: Redirección o enlace a dashboard.php tras autenticación.

# dashboard.php

- Ruta: dashboard.php
- Propósito: Panel principal después del login.
- Contenido: Widgets resumen, accesos a módulos (Catálogos, Documentos, Informes, Libros).
- Integración: Consume endpoints/consultas para mostrar métricas (si existen).

# perfil.php (Mi Negocio)
- Ruta: perfil.php
- Propósito: Gestionar datos del negocio/empresa (tributarios, ubicación, responsabilidades fiscales).
- Parámetros GET/POST: persona, cedula, digito, nombres, apellidos, razon, departamento, ciudad, direccion, email, regimen, actividad, tarifa, aiu, telefono, responsabilidades[].

Funciones clave:
- toggleFields() (JavaScript):
  - Habilita campos según Persona Natural o Jurídica.
  - Deshabilita "Dígito" si es Persona Natural (solo NIT lleva dígito).

- Búsqueda departamentos/ciudades:
  - Igual que en catalogosterceros.php: autocomplete + select cascada.

- Select2 multi-select para responsabilidades tributarias:
  - Lista predefinida de 40+ responsabilidades (Aporte justicia, GMF, Retención, Autorretenedor, etc.).
  - Permite múltiple selección (max 10).
  - Requiere seleccionar al menos una (required).

Flujo principal (POST):
  1. Validar que cedula contenga solo números y ≤ 10 caracteres.
  2. Validar que email sea válido.
  3. Validar que al menos una responsabilidad esté seleccionada.
  4. Convertir array responsabilidades a string (implode).
  5. Convertir checkbox AIU a valor binario.
  6. Insertar en tabla `perfil`.
  7. Redirect con msg=guardado (cargar SweetAlert2 de éxito).

Tablas implicadas:
- `perfil` (id, persona, cedula, digito, nombres, apellidos, razon, departamento, ciudad, direccion, email, regimen, actividad, tarifa, aiu, telefono, responsabilidad).

Validaciones y notas:
- Limitar cedula a máximo 10 caracteres (via maxlength + oninput limitLength).
- Validar email con type="email" HTML5.
- Responsabilidades: array de strings "numero - nombre" o solo números.
- Tarifa IVA: number con step="0.0001" (ej: 0.0040 para 0.40%).
- AIU: checkbox simple que genera valor 1/0.

Funciones JavaScript principales:
- toggleFields(): habilita/deshabilita campos por tipo de persona.
- limitLength(input, maxLength): limita caracteres cedula.
- Select2: multi-select para responsabilidades tributarias.
- Búsqueda departamentos/ciudades: igual que catalogosterceros.

Pruebas recomendadas:
- Crear perfil Persona Natural (cedula, nombres, apellidos).
- Crear perfil Persona Jurídica (cedula, razón social).
- Cedula > 10 caracteres: debe truncarse.
- Seleccionar múltiples responsabilidades tributarias.
- Guardar y validar que msg=guardado muestra SweetAlert2.
- Verificar que datos se persisten en tabla `perfil`.

---

# Módulo Catálogos

Este documento presenta la documentación con el mismo nivel de detalle que librosestadodesituacionfinanciera.php para los archivos del módulo Catálogos:
- menucatalogos.php
- catalogosterceros.php
- catalogoscuentascontables.php
- catalogosinventarios.php
- catalogosmediosdepago.php
- catalogosparametrosdedocumentos.php

---

# menucatalogos.php
- Ruta: menucatalogos.php
- Propósito: Hub/navegación para el módulo de Catálogos.
- Funcionalidad: Presenta 5 tarjetas (icon-box) con enlaces a los catálogos disponibles:
  - TERCEROS
  - CUENTAS CONTABLES
  - INVENTARIOS
  - MEDIOS DE PAGO
  - PARÁMETROS DE DOCUMENTOS
- Estructura HTML: Header, sección con ícono-caja y descripción breve por catálogo, footer.

---

# catalogosterceros.php
- Ruta: catalogosterceros.php
- Propósito: Gestionar terceros (clientes, proveedores, otros) con validaciones y búsqueda avanzada.
- Parámetros GET/POST: tipoTercero[], tipoPersona, cedula, digito, nombres, apellidos, razonSocial, departamento, ciudad, direccion, telefono, correo, tipoRegimen, actividadEconomica, activo.

Funciones clave:
- limpiar($valor):
  - Sanitiza inputs con htmlspecialchars(trim($valor)).
  - Previene inyección de código y espacios inadecuados.

- Búsqueda de departamentos/ciudades (JavaScript):
  - Array departamentos con lista de ciudades por cada departamento.
  - Input autocomplete para seleccionar departamento.
  - Al seleccionar, carga ciudades en <select> dinámicamente.

- Validación de teléfono:
  - Patrón: 7-10 dígitos numéricos.
  - Si no cumple, redirige con msg=telefono_invalido.

- Validación de duplicados (btnAgregar):
  - Verifica unicidad de cedula, correo y telefono.
  - Si existe, redirige con msg=duplicado.

- toggleFields() (JavaScript):
  - Habilita campos "Nombres/Apellidos" si es Persona Natural.
  - Habilita "Razón Social" si es Persona Jurídica.
  - Deshabilita campos según selección.

Flujo principal (btnAgregar):
  1. Validar que tipoTercero[] no esté vacío (convertir array a string con implode).
  2. Sanitizar todos los inputs con limpiar().
  3. Validar teléfono con patrón regex (si existe).
  4. Verificar duplicados: cedula, correo, telefono.
  5. Si pasa validaciones, insertar en tabla `catalogosterceros`.
  6. Redirect con msg=agregado.

Flujo principal (btnModificar):
  1. Validar datos nuevamente.
  2. UPDATE en tabla `catalogosterceros` WHERE id = :id.
  3. Redirect con msg=modificado.

Flujo principal (btnEliminar):
  1. DELETE FROM `catalogosterceros` WHERE id = :id.
  2. Redirect con msg=eliminado.

Tablas implicadas:
- `catalogosterceros` (id, tipoTercero, tipoPersona, cedula, digito, nombres, apellidos, razonSocial, departamento, ciudad, direccion, telefono, correo, tipoRegimen, actividadEconomica, activo).

Validaciones y notas:
- Usar prepared statements en todos los casos.
- Mensajes SweetAlert2 para feedback (éxito, duplicado, teléfono inválido).
- Redirige con URL params (?msg=...) que se limpian vía JavaScript.
- toggleFields() se llama al cambiar tipo de persona y al cargar formulario en modo edición.

Funciones JavaScript principales:
- toggleFields(): habilita/deshabilita campos según tipoPersona.
- Búsqueda de departamentos: input listener + mostrarSugerencias() + activarCiudadSelect().
- Carga de ciudades: llenado de <select ciudad> al seleccionar departamento.
- SweetAlert2: confirmaciones para modificar/eliminar.
- Botones Editar/Eliminar en tabla: cargan datos en formulario vía inputs hidden.

Pruebas recomendadas:
- Crear tercero como Persona Natural (nombres/apellidos habilitados).
- Crear tercero como Persona Jurídica (razón social habilitada).
- Intentar crear con cedula/correo/telefono duplicado: debe rechazar.
- Teléfono con menos de 7 o más de 10 dígitos: debe rechazar.
- Buscar/seleccionar departamento y validar ciudades cargadas.
- Editar tercero (cambiar datos) y validar UPDATE.
- Eliminar tercero (con confirmación SweetAlert2).

---

# catalogoscuentascontables.php
- Ruta: catalogoscuentascontables.php
- Propósito: Gestionar plan de cuentas contables con estructura jerárquica (clase → grupo → cuenta → subcuenta → auxiliar).
- Parámetros GET/POST: clase, grupo, cuenta, subcuenta, auxiliar, moduloInventarios, naturalezaContable, controlCartera, activa.

Funciones clave:
- Carga desde JSON (`cuentas_contables.json`):
  - Estructura anidada: Clase → Grupo → Cuenta → Subcuenta/Auxiliar.
  - Select en cascada: al seleccionar Clase, se habilita Grupo; al seleccionar Grupo, se habilita Cuenta, etc.

- inicializarCatalogo(datos):
  - Llena select de Clase con todas las opciones del JSON.
  - Agrega listeners para cambios en cascada.

- activarGrupoSelect(clase), activarCuentaSelect(clase, grupo), activarSubcuentaSelect(clase, grupo, cuenta):
  - Funciones que habilitan/llenan selects dinámicamente.

- Naturaleza contable (radio buttons):
  - Opciones: Débito o Crédito.
  - Afecta cálculo de saldos en reportes (signología).

- Checkboxes:
  - moduloInventarios: marca si cuenta se usa en inventarios.
  - controlCartera: marca si es cuenta de control de clientes/proveedores.
  - activa: estado de la cuenta.

Flujo principal (btnAgregar):
  1. Validar que Clase, Grupo, Cuenta, Subcuenta no estén vacíos.
  2. Validar que naturalezaContable esté seleccionada.
  3. Convertir checkboxes a valores binarios (1/0).
  4. Insertar en tabla `catalogoscuentascontables`.
  5. Redirect msg=agregado.

Flujo principal (btnModificar):
  1. Cargar datosPHP en selects en cascada (usar cargarDatosEdicion()).
  2. Validar nuevos datos.
  3. UPDATE tabla `catalogoscuentascontables`.
  4. Redirect msg=modificado.

Flujo principal (btnEliminar):
  1. Verificar que no existan movimientos en `libro_diario` (opcional, según política).
  2. DELETE FROM `catalogoscuentascontables` WHERE id = :id.
  3. Redirect msg=eliminado.

Tablas implicadas:
- `catalogoscuentascontables` (id, clase, grupo, cuenta, subcuenta, auxiliar, moduloInventarios, naturalezaContable, controlCartera, activa).
- `libro_diario` (lectura para evitar eliminar cuentas con movimientos).

Filtros/Búsqueda (tabla):
- Inputs de búsqueda por columna: Clase, Grupo, Cuenta, Subcuenta, Auxiliar.
- Selects para Módulo, Naturaleza, Cartera, Activa (filtro dicotómico Sí/No).
- Función limpiarFiltros() borra todos los criterios.

Validaciones y notas:
- Select2 no se usa aquí; cascada con JavaScript vanilla.
- Asegurar que códigos de niveles sean únicos dentro de su contexto.
- Revisar que estructura JSON coincida con base de datos.
- Signología correcta: Clase 1 (Activos) = Débito, Clases 2/3 (Pasivos/Patrimonio) = Crédito.

Funciones JavaScript principales:
- fetch("cuentas_contables.json"): carga datos desde archivo.
- inicializarCatalogo(datos): construye selects en cascada.
- activarGrupoSelect/Cuenta/Subcuenta: llenan opciones dinámicamente.
- cargarDatosEdicion(clase, grupo, cuenta, subcuenta): reconstruye cascada en modo edición.
- filtrarTabla(): filtra filas por criterios (ejecutado en keyup/change de inputs).
- SweetAlert2: confirmaciones para modificar/eliminar.

Pruebas recomendadas:
- Crear cuenta validando cascada (Clase → Grupo → Cuenta → Subcuenta).
- Editar cuenta y verificar que cascada carga correctamente.
- Intentar eliminar cuenta con movimientos: debe validar o advertir.
- Filtrar tabla por Clase/Grupo/Naturaleza: validar resultados.
- Verificar que naturaleza (débito/crédito) se refleja en reportes.

---

# catalogosinventarios.php
- Ruta: catalogosinventarios.php
- Propósito: Gestionar categorías de inventario y productos/servicios con mapeo a cuentas contables.
- Dos secciones: CATEGORÍAS y PRODUCTOS.

**SECCIÓN CATEGORÍAS:**

Parámetros: categoria, codigoCuentaVentas, cuentaVentas, codigoCuentaInventarios, cuentaInventarios, codigoCuentaCostos, cuentaCostos, codigoCuentaDevoluciones, cuentaDevoluciones.

Funciones clave:
- Select2 AJAX para cuentas contables:
  - obtener_cuentas_inventarios.php devuelve lista de cuentas por tipo (ventas, inventarios, costos, devoluciones).
  - Formato: { valor: "110505-Caja General", texto: "110505 - Caja General", nombre_puro: "Caja General" }.
  - Al seleccionar, rellena input hidden con código (ej: "110505") e input text con nombre limpio.

- Dropdown select por tipo (vendidas, inventarios, etc.):
  - Busca y filtra cuentas según tipo.
  - Método AJAX con parámetro "tipo" y "search".

Flujo principal (btnAgregarCategoria):
  1. Validar que categoría y todas las cuentas estén seleccionadas.
  2. Insertar en tabla `categoriainventarios`.
  3. Redirect msg=agregado.

Flujo principal (btnModificarCategoria):
  1. Cargar datos previos en Select2 (vía cargarDatosEdicion o atributos data-campo).
  2. UPDATE tabla `categoriainventarios`.
  3. Redirect msg=modificado.

Flujo principal (btnEliminarCategoria):
  1. Verificar que no existan productos en esta categoría (opcional).
  2. DELETE tabla `categoriainventarios`.
  3. Redirect msg=eliminado.

**SECCIÓN PRODUCTOS:**

Parámetros: categoriaInventarios, codigoProducto, descripcionProducto, unidadMedida, cantidad, productoIva, tipoItem, facturacionCero, activo.

Funciones clave:
- Select de categorías (cargado de DB via PHP loop):
  - Lista desplegable de categorías registradas.

- Select2 para unidades de medida:
  - Lista fija (BBL, CEN, CM3, DZN, GRM, KGM, LTR, etc.).
  - Búsqueda AJAX habilitada.

- Radio buttons para tipoItem:
  - Opciones: Producto o Servicio.

- Checkboxes:
  - productoIva: aplica IVA al producto.
  - facturacionCero: permite facturar aunque stock sea cero.
  - activo: marca producto como activo.

Flujo principal (btnAgregarProducto):
  1. Validar que código y descripción sean únicos.
  2. Convertir checkboxes a valores binarios.
  3. Insertar en tabla `productoinventarios`.
  4. Redirect msg=agregadoProducto.

Flujo principal (btnModificarProducto):
  1. Cargar datos previos en inputs/selects/checkboxes (vía data-campo).
  2. UPDATE tabla `productoinventarios`.
  3. Redirect msg=modificadoProducto.

Flujo principal (btnEliminarProducto):
  1. Verificar si producto se usa en documentos (opcional).
  2. DELETE tabla `productoinventarios`.
  3. Redirect msg=eliminadoProducto.

Tablas implicadas:
- `categoriainventarios` (id, categoria, codigoCuentaVentas, cuentaVentas, codigoCuentaInventarios, cuentaInventarios, codigoCuentaCostos, cuentaCostos, codigoCuentaDevoluciones, cuentaDevoluciones).
- `productoinventarios` (id, categoriaInventarios, codigoProducto, descripcionProducto, unidadMedida, cantidad, productoIva, tipoItem, facturacionCero, activo).

Validaciones y notas:
- Asegurar que codigoCuentas seleccionadas existan en `cuentas_contables`.
- Select2 requiere script `obtener_cuentas_inventarios.php` que devuelva JSON.
- Preparar fallback si AJAX falla.
- Validar que categoría tenga al menos una categoría registrada antes de agregar productos.

Funciones JavaScript principales:
- inicializarSelectCuenta(selector, tipoCuenta, placeholderText): configura Select2 por tipo.
- actualizarNombreCuenta(selectId, inputId): carga el nombre limpio al cambiar select.
- Edición de categorías: cargar valores en Select2 manualmente.
- Edición de productos: cargar valores en inputs, selects, checkboxes, radio buttons.
- SweetAlert2: confirmaciones.

Pruebas recomendadas:
- Crear categoría con cuentas válidas; validar mapeo.
- Crear producto asociado a categoría; validar que categoría existe.
- Editar producto (cambiar categoría, cantidad, IVA).
- Eliminar producto/categoría con confirmación.
- Verificar que Select2 carga cuentas según tipo en AJAX.
- Probar con categoría sin productos: editar/eliminar debe funcionar.

---

# catalogosmediosdepago.php
- Ruta: catalogosmediosdepago.php
- Propósito: Gestionar medios de pago (efectivo, transferencia, tarjeta, etc.) y mapear a cuentas contables.
- Parámetros GET/POST: metodoPago, cuentaContable.

Funciones clave:
- Select dropdown para metodoPago:
  - Opciones fijas: Efectivo, Pago Electrónico, Crédito.

- Select2 AJAX para cuentaContable:
  - Carga cuentas de tipo bancario/caja dinámicamente.
  - Busca y filtra por input.

- Tabla desplegable (collapsible):
  - Agrupa medios por tipo de pago.
  - Al hacer clic en header, expande/contrae sección.
  - Muestra código y nombre de cuenta asociada.

Flujo principal (btnAgregar):
  1. Validar que metodoPago y cuentaContable no estén vacíos.
  2. Insertar en tabla `mediosdepago`.
  3. Redirect msg=agregado.

Flujo principal (btnModificar):
  1. Cargar valores previos en select (vía data-campo hidden inputs).
  2. UPDATE tabla `mediosdepago`.
  3. Redirect msg=modificado.

Flujo principal (btnEliminar):
  1. Verificar que no se use en documentos (opcional).
  2. DELETE tabla `mediosdepago`.
  3. Redirect msg=eliminado.

Tablas implicadas:
- `mediosdepago` (id, metodoPago, cuentaContable).

Validaciones y notas:
- cuentaContable debe existir en `cuentas_contables`.
- metodoPago predefinido (no libre).
- Select2 requiere `obtener_cuentas.php` que devuelva JSON con cuentas de tipo banco/caja.

Funciones JavaScript principales:
- toggleMetodo(metodo): expande/contrae sección de tabla.
- Select2 inicialización: configura autocompletado.
- Botones Editar/Eliminar en tabla: cargan datos en formulario.
- SweetAlert2: confirmaciones.

Pruebas recomendadas:
- Crear medio de pago (Efectivo, Transferencia, Crédito).
- Editar medio (cambiar cuenta contable).
- Eliminar medio con confirmación.
- Validar que tabla agrupa por tipo.
- Expandir/contraer secciones de la tabla.

---

# catalogosparametrosdedocumentos.php
- Ruta: catalogosparametrosdedocumentos.php
- Propósito: Hub/navegación para parámetros de documentos.
- Enlaces internos:
  - parametrosfacturadeventa.php
  - parametrosfacturadecompra.php
  - parametrosrecibodecaja.php
  - parametroscomprobantedeegreso.php
  - parametroscomprobantecontable.php
- Funcionalidad: Presenta 5 tarjetas (icon-box) con descripción de cada tipo de documento y enlace.

---

# parametrosfacturadeventa.php
- Ruta: parametrosfacturadeventa.php
- Propósito: Configurar parámetros de facturación de venta.
- Campos/Parámetros:
  - prefijo, serie, consecutivo_actual, cuenta_ingresos_por_defecto, cuenta_iva, impuestos_activados, plantilla_impresion.
- Funcionalidad clave:
  - Validar que cuentas configuradas existan en `cuentas_contables`.
  - Control de bloqueo de edición si hay facturas ya emitidas con la serie.
- Tablas implicadas: `parametros_factura_venta` o `parametros_documentos`.
- Pruebas:
  - Emitir factura y verificar consecutivo y asiento contable.

---

# parametrosfacturadecompra.php
- Ruta: parametrosfacturadecompra.php
- Propósito: Parámetros para facturas de compra.
- Campos/Parámetros:
  - prefijo, serie, cuentas_involucradas (inventario, IVA descontable), si aplica retenciones, plantillas.
- Funcionalidad clave:
  - Definir cuentas por defecto y reglas de contabilización automática.
- Tablas: `parametros_factura_compra`.
- Pruebas: Registrar compra y validar asiento y actualización de inventario.

---

# parametrosrecibodecaja.php
- Ruta: parametrosrecibodecaja.php
- Propósito: Parámetros para recibos de caja.
- Campos: prefijo/serie, consecutivo, cuenta_caja_por_defecto, formato_impresion.
- Funcionalidad: Asociar medios de pago y cuentas; reglas para aplicar recibos a facturas.
- Tablas: `parametros_recibo_caja`.
- Pruebas: Registrar recibo y aplicar a factura; verificar asiento y conciliación.

---

# parametroscomprobantedeegreso.php
- Ruta: parametroscomprobantedeegreso.php
- Propósito: Parámetros para comprobantes de egreso.
- Campos: serie, consecutivo, cuentas_por_defecto (gasto, banco/caja), autorizaciones.
- Funcionalidad clave: Control de flujo de aprobación y validación de fondos si aplicable.
- Tablas: `parametros_comprobante_egreso`.
- Pruebas: Generar comprobante y validar asiento (debito = gasto, credito = banco).

---

# parametroscomprobantecontable.php
- Ruta: parametroscomprobantecontable.php
- Propósito: Parámetros para comprobantes contables manuales.
- Campos: tipos_comprobante, prefijos, numeración, reglas de validación (fecha, diario abierto).
- Funcionalidad clave:
  - Listado de tipos y plantillas.
  - Reglas para validación previa a registro (debe=haber).
- Tablas: `parametros_comprobantes_contables`.
- Pruebas: Crear comprobante manual y verificar registro en `libro_diario`.

---

# Módulo Documentos

Este documento presenta la documentación con el mismo nivel de detalle que librosestadodesituacionfinanciera.php para los archivos del módulo Documentos:
- menudocumentos.php
- documentosfacturadeventa.php
- documentosfacturadecompra.php
- documentosrecibodecaja.php
- documentoscomprobantedeegreso.php
- documentoscomprobantecontable.php

---

# menudocumentos.php
- Ruta: menudocumentos.php
- Propósito: Hub/navegación para el módulo de Documentos.
- Funcionalidad: Presenta 5 tarjetas (icon-box) con enlaces a los tipos de documentos disponibles:
  - FACTURA DE VENTA
  - FACTURA DE COMPRA
  - RECIBO DE CAJA
  - COMPROBANTE DE EGRESO
  - COMPROBANTE CONTABLE
- Estructura HTML: Header, sección con ícono-caja y descripción breve por documento, footer.

# documentosfacturadeventa.php
- Ruta: documentosfacturadeventa.php
- Propósito: Crear, editar, eliminar y listar facturas de venta.
- Filtros/params GET/POST: identificacion (cliente), nombre, fecha, consecutivo, formaPago, subtotal, ivaTotal, retenciones, valorTotal, observaciones, detalles (JSON).

Funciones clave:
- get_consecutivo (GET):
  - Obtiene el siguiente número de consecutivo desde tabla `facturav`.
  - SELECT MAX(CAST(consecutivo AS UNSIGNED)) AS ultimo FROM facturav.
  - Retorna JSON con consecutivo = ultimo + 1.

- registrarFacturaVenta($libroDiario, $idFactura) — parte de LibroDiario.php:
  - Carga datos de factura y detalles desde `facturav` y `factura_detalle`.
  - Calcula impuestos y totales.
  - Genera asiento contable con débito a cliente/caja y crédito a ingresos/IVA.

- Búsqueda cliente (POST, sin accion):
  - Por identificación: consulta `catalogosterceros` WHERE cedula = :cedula AND tipoTercero LIKE '%Cliente%'.
  - Por nombre: búsqueda LIKE en nombre completo.
  - Retorna JSON con nombre e identificacion.

- Búsqueda producto (POST, sin accion):
  - Por código: SELECT FROM `productoinventarios` WHERE codigoProducto = :codigo.
  - Por nombre: búsqueda LIKE en descripcionProducto.
  - Retorna JSON con codigoProducto, nombreProducto, tipoItem, stockDisponible (si es producto).

Flujo principal (btnAgregar):
  1. Validar datos de cliente (identificacion, nombre, fecha, consecutivo, formaPago).
  2. Validar detalles (código, cantidad, precio unitario no nulos).
  3. Insertar en tabla `facturav` (identificacion, nombre, fecha, consecutivo, formaPago, subtotal, ivaTotal, retenciones, valorTotal, observaciones).
  4. Obtener $idFactura = pdo->lastInsertId().
  5. Para cada detalle:
     - Verificar que codigoProducto exista en `productoinventarios` (si no, throw Exception).
     - Si tipoItem = 'producto': verificar cantidad disponible (cantidad >= solicitada); si no, throw Exception.
     - Restar cantidad del inventario: UPDATE productoinventarios SET cantidad = cantidad - :cantidad WHERE codigoProducto = :codigo.
     - Insertar detalle en `factura_detalle` (id_factura, codigoProducto, nombreProducto, cantidad, precio_unitario, iva, total).
  6. Registrar en libro diario: $libroDiario->registrarFacturaVenta($idFactura).
  7. Commit transacción; redirect con msg=agregado.

Flujo principal (btnModificar):
  1. Cargar detalles antiguos desde `factura_detalle` WHERE id_factura = :txtId.
  2. Para cada detalle antiguo (solo si tipoItem = 'producto'): restaurar inventario += cantidad (REVERTIR).
  3. Eliminar asientos contables antiguos: $libroDiario->eliminarMovimientos('factura_venta', $txtId).
  4. Eliminar detalles antiguos: DELETE FROM factura_detalle WHERE id_factura = :txtId.
  5. Actualizar datos de factura principal en `facturav`.
  6. Insertar nuevos detalles (repetir validaciones de agregar).
  7. Registrar nuevos asientos: $libroDiario->registrarFacturaVenta($txtId).
  8. Commit; redirect con msg=modificado.

Flujo principal (btnEliminar):
  1. Cargar detalles desde `factura_detalle` WHERE id_factura = :txtId.
  2. Para cada detalle (solo productos): restaurar inventario += cantidad.
  3. Eliminar asientos contables: $libroDiario->eliminarMovimientos('factura_venta', $txtId).
  4. Eliminar detalles: DELETE FROM factura_detalle WHERE id_factura = :txtId.
  5. Eliminar factura principal: DELETE FROM facturav WHERE id = :txtId.
  6. Commit; redirect con msg=eliminado.

Tablas implicadas:
- `facturav` (id, identificacion, nombre, fecha, consecutivo, formaPago, subtotal, ivaTotal, retenciones, valorTotal, observaciones).
- `factura_detalle` (id, id_factura, codigoProducto, nombreProducto, cantidad, precio_unitario, iva, total).
- `productoinventarios` (codigoProducto, descripcionProducto, tipoItem, cantidad, ...).
- `catalogosterceros` (cedula, nombres, apellidos, tipoTercero, ...).
- `libro_diario` (autogenerado por LibroDiario::registrarFacturaVenta).
- `mediosdepago` (metodoPago, cuentaContable).

Validaciones y notas:
- Usar transacciones (beginTransaction/commit/rollBack) para atomicidad.
- Evitar stock negativo: validar cantidad disponible antes de restar.
- Calcular IVA (19% en Colombia) automáticamente en JS: iva = cantidad * precio * 0.19.
- Sanitizar inputs con prepared statements.
- Control de permisos por rol de usuario.
- Auditar operación (usuario, fecha, acción).

Funciones JavaScript principales:
- window.addEventListener('DOMContentLoaded'): obtener consecutivo automáticamente.
- inputIdentificacion.addEventListener("input"): búsqueda cliente por cédula.
- inputNombre.addEventListener("input"): búsqueda cliente por nombre.
- document.querySelector("#product-table").addEventListener("input", function(e)): búsqueda producto y cálculo automático de totales.
- calcularValores(): recalcula subtotal, iva, retenciones, total.
- addRow(): agregar nueva fila a tabla de productos.
- removeRowSafe(btn): eliminar fila (validando mínimo 1 fila).
- Confirmaciones SweetAlert2 para modificar/eliminar.
- Empaquetar detalles en JSON antes de submit.

Pruebas recomendadas:
- Crear factura con cliente válido, varios ítems (productos + servicios), validar stock actualizado.
- Intentar crear con stock insuficiente: debe rechazar.
- Editar factura (cambiar cliente, agregar/remover ítems): validar restauración de stock anterior y nueva deducción.
- Eliminar factura: validar que stock se restaure.
- Verificar asiento contable en libro_diario: débito cliente = crédito ingresos + crédito iva.
- Exportadores PDF/Excel si existen.

---

# documentosfacturadecompra.php
- Ruta: documentosfacturadecompra.php
- Propósito: Crear, editar, eliminar y listar facturas de compra.
- Filtros/params GET/POST: identificacion (proveedor), nombre, fecha, consecutivo, numeroFactura, formaPago, subtotal, ivaTotal, retenciones, valorTotal, observaciones, detalles (JSON).

Funciones clave:
- get_consecutivo (GET):
  - SELECT MAX(CAST(consecutivo AS UNSIGNED)) AS ultimo FROM facturac.
  - Retorna siguiente consecutivo.

- registrarFacturaCompra($libroDiario, $idFactura) — parte de LibroDiario.php:
  - Genera asiento contable: débito a inventario/compras, crédito a proveedor/IVA.
  - Nota: revisar cuenta IVA descontable (debería ser 135515, no 240805).

- Búsqueda proveedor (POST, es_ajax='proveedor'):
  - Similar a búsqueda cliente en factura venta, pero filtra WHERE tipoTercero LIKE '%Proveedor%'.

- Búsqueda producto (POST, es_ajax='producto'):
  - Igual que factura venta: retorna codigoProducto, nombreProducto, tipoItem.

Flujo principal (btnAgregar):
  1. Validar datos de proveedor e identificación.
  2. Insertar en `facturac` (identificacion, nombre, fecha, consecutivo, numeroFactura, formaPago, subtotal, ivaTotal, retenciones, valorTotal, observaciones).
  3. Obtener $idFactura.
  4. Para cada detalle:
     - Verificar codigoProducto existe en `productoinventarios`.
     - Si tipoItem = 'producto': SUMAR inventario (UPDATE cantidad = cantidad + :cantidad) — a diferencia de venta, aquí SUMAMOS.
     - Insertar en `detallefacturac` (factura_id, codigoProducto, nombreProducto, cantidad, precioUnitario, iva, valorTotal).
  5. Registrar en libro: $libroDiario->registrarFacturaCompra($idFactura).
  6. Commit; redirect msg=agregado.

Flujo principal (btnModificar):
  1. Cargar detalles antiguos y REVERTIR inventario (cantidad = cantidad - :cantidad, porque antes sumamos).
  2. Eliminar asientos contables: $libroDiario->eliminarMovimientos('factura_compra', $txtId).
  3. Eliminar detalles antiguos.
  4. Actualizar factura principal.
  5. Insertar nuevos detalles (con SUMA de inventario).
  6. Registrar nuevos asientos.
  7. Commit; redirect msg=modificado.

Flujo principal (btnEliminar):
  1. Cargar detalles; para cada producto: restar inventario (cantidad -= cantidad).
  2. Eliminar asientos contables.
  3. Eliminar detalles.
  4. Eliminar factura.
  5. Commit; redirect msg=eliminado.

Tablas implicadas:
- `facturac` (id, identificacion, nombre, fecha, consecutivo, numeroFactura, formaPago, subtotal, ivaTotal, retenciones, valorTotal, observaciones, saldoReal).
- `detallefacturac` (id, factura_id, codigoProducto, nombreProducto, cantidad, precioUnitario, iva, valorTotal).
- `productoinventarios` (codigoProducto, descripcionProducto, tipoItem, cantidad).
- `catalogosterceros` (cedula, nombres, apellidos, tipoTercero).
- `libro_diario`.
- `mediosdepago`.

Notas de validación y mejoras:
- DIFERENCIA CLAVE con factura venta: en compra SUMAMOS inventario (entrada); en venta RESTAMOS (salida).
- Revisar y corregir cuenta IVA descontable en LibroDiario::registrarFacturaCompra (debería ser 135515 en lugar de 240805).
- Envolver en transacciones.
- Auditar cambios.

Funciones JavaScript: similares a factura venta (búsqueda proveedor/producto, cálculo automático, confirmaciones).

Pruebas recomendadas:
- Crear compra con proveedor válido, varios ítems (productos): validar aumento de stock.
- Editar: restaurar stock anterior, agregar nuevo stock.
- Eliminar: validar que stock se reduzca nuevamente.
- Verificar asiento contable en libro_diario.
- Validar que retenciones se apliquen si corresponde.

---

# documentosrecibodecaja.php
- Ruta: documentosrecibodecaja.php
- Propósito: Registrar recibos de caja (cobros a clientes).
- Filtros/params: identificacion (cliente), nombre, fecha, consecutivo, numeroFactura, fechaVencimiento, valor, valorTotal, formaPago, observaciones, facturasData (JSON).

Funciones clave:
- get_consecutivo (GET): SELECT MAX(CAST(consecutivo AS UNSIGNED)) AS ultimo FROM docrecibodecaja.

- get_facturas (GET, identificacion):
  - SELECT id, consecutivo, fecha, CAST(valorTotal AS DECIMAL(10,2)) as valorTotal, COALESCE(CAST(saldoReal AS DECIMAL(10,2)), CAST(valorTotal AS DECIMAL(10,2))) as saldoReal FROM facturav WHERE identificacion = :identificacion AND formaPago LIKE '%Credito%' AND saldoReal > 0 ORDER BY fecha ASC.
  - Retorna JSON con facturas pendientes del cliente.

- get_detalles (GET, idRecibo):
  - SELECT consecutivoFactura, valorAplicado, fechaVencimiento FROM detalle_recibo_caja WHERE idRecibo = :idRecibo.

- buscar_cliente (POST): similar a otros, busca en catalogosterceros con tipoTercero LIKE '%Cliente%'.

- registrarReciboCaja($libroDiario, $idRecibo):
  - Genera asiento: débito a caja/banco, crédito a cliente (cuenta por cobrar).

- actualizarSaldosFacturas($pdo, $facturasData):
  - Para cada factura en array: calcula nuevo saldo = saldoReal - valor aplicado.
  - UPDATE facturav SET saldoReal = :nuevoSaldo WHERE consecutivo = :consecutivo.

- restaurarSaldosFacturas($pdo, $idRecibo):
  - Carga detalles del recibo; para cada factura: suma nuevamente el valor.
  - UPDATE facturav SET saldoReal = COALESCE(saldoReal, valorTotal) + :valor.

Flujo principal (btnAgregar):
  1. Validar cliente (identificacion, nombre).
  2. Cargar facturas pendientes del cliente.
  3. Usuario selecciona facturas y monto a aplicar.
  4. Validar que monto <= saldo pendiente de cada factura.
  5. Insertar recibo principal en `docrecibodecaja` (fecha, consecutivo, identificacion, nombre, numeroFactura, fechaVencimiento, valor, valorTotal, formaPago, observaciones).
  6. Obtener $idRecibo.
  7. Para cada factura en facturasData: insertar en `detalle_recibo_caja` (idRecibo, consecutivoFactura, valorAplicado, fechaVencimiento).
  8. Actualizar saldos en `facturav`: saldoReal -= valor aplicado.
  9. Registrar en libro: $libroDiario->registrarReciboCaja($idRecibo).
  10. Commit; redirect msg=agregado.

Flujo principal (btnModificar):
  1. Restaurar saldos de las facturas del recibo anterior (sumar nuevamente los valores).
  2. Eliminar asientos contables: $libroDiario->eliminarMovimientos('recibo_caja', $txtId).
  3. Validar nuevos montos <= nuevos saldos.
  4. Actualizar recibo principal.
  5. Eliminar detalles antiguos.
  6. Insertar nuevos detalles.
  7. Actualizar saldos con nuevos montos.
  8. Registrar nuevos asientos.
  9. Commit; redirect msg=modificado.

Flujo principal (btnEliminar):
  1. Restaurar saldos de todas las facturas del recibo.
  2. Eliminar asientos contables.
  3. Eliminar detalles.
  4. Eliminar recibo.
  5. Commit; redirect msg=eliminado.

Tablas implicadas:
- `docrecibodecaja` (id, fecha, consecutivo, identificacion, nombre, numeroFactura, fechaVencimiento, valor, valorTotal, formaPago, observaciones).
- `detalle_recibo_caja` (id, idRecibo, consecutivoFactura, valorAplicado, fechaVencimiento).
- `facturav` (..saldoReal importante).
- `catalogosterceros`.
- `libro_diario`.
- `mediosdepago`.

Notas de validación:
- Usar transacciones.
- Validar que monto aplicado <= saldo pendiente.
- Evitar aplicar 2 veces el mismo recibo (validar uniqueness).
- Auditar.

Funciones JavaScript:
- Búsqueda cliente por identificación/nombre.
- btnCargarFacturas(): llamada AJAX a ?get_facturas=1&identificacion={id}; renderiza tabla con facturas pendientes.
- Para cada fila de factura: checkbox "Seleccionar", input con valor a aplicar (máximo = saldo pendiente).
- Cálculo automático de total: sum(valores aplicados de facturas seleccionadas).
- Confirmaciones SweetAlert2.

Pruebas recomendadas:
- Cargar facturas de cliente válido; seleccionar una y aplicar monto parcial: validar saldo actualizado.
- Aplicar resto de la factura en otro recibo: validar que factura quede en 0.
- Modificar recibo (cambiar montos): validar que saldos se restauren y actualicen correctamente.
- Eliminar recibo: validar restauración completa de saldos.
- Comparar totales con cuantomedeben.php.

---

# documentoscomprobantedeegreso.php
- Ruta: documentoscomprobantedeegreso.php
- Propósito: Registrar comprobantes de egreso (pagos a proveedores).
- Filtros/params: identificacion (proveedor), nombre, fecha, consecutivo, numeroFactura, fechaVencimiento, valor, valorTotal, formaPago, observaciones, facturasData (JSON).

Funciones clave:
- get_consecutivo (GET): SELECT MAX(CAST(consecutivo AS UNSIGNED)) AS ultimo FROM doccomprobanteegreso.

- get_facturas (GET, identificacion):
  - Similar a recibo caja, pero desde `facturac`: SELECT ... FROM facturac WHERE identificacion = :identificacion AND formaPago LIKE '%Credito%' AND saldoReal > 0.

- get_detalles (GET, idComprobante):
  - SELECT consecutivoFactura, valorAplicado, fechaVencimiento FROM detalle_comprobante_egreso WHERE idComprobante = :idComprobante.

- buscar_proveedor (POST): busca en catalogosterceros con tipoTercero LIKE '%Proveedor%'.

- registrarComprobanteEgreso($libroDiario, $idComprobante):
  - Genera asiento: débito a gasto/proveedor, crédito a banco/caja.

- actualizarSaldosFacturasCompra($pdo, $facturasData): similar a recibos, resta monto de saldoReal en `facturac`.

- restaurarSaldosFacturasCompra($pdo, $idComprobante): suma monto a saldoReal en `facturac`.

Flujo principal (btnAgregar):
  1. Validar proveedor.
  2. Cargar facturas de compra pendientes.
  3. Usuario selecciona facturas y monto a pagar.
  4. Validar monto <= saldo.
  5. Insertar en `doccomprobanteegreso`.
  6. Insertar detalles en `detalle_comprobante_egreso`.
  7. Actualizar saldos en `facturac`: saldoReal -= valor.
  8. Registrar en libro: $libroDiario->registrarComprobanteEgreso($idComprobante).
  9. Commit; redirect msg=agregado.

Flujo principal (btnModificar): similar a recibo caja.

Flujo principal (btnEliminar): similar a recibo caja.

Tablas implicadas:
- `doccomprobanteegreso` (id, fecha, consecutivo, identificacion, nombre, numeroFactura, fechaVencimiento, valor, valorTotal, formaPago, observaciones).
- `detalle_comprobante_egreso` (id, idComprobante, consecutivoFactura, valorAplicado, fechaVencimiento).
- `facturac` (...saldoReal).
- `catalogosterceros`.
- `libro_diario`.
- `mediosdepago`.

Notas:
- DIFERENCIA CON RECIBO: aquí pagamos a proveedores (reduce CxP); en recibo cobramos de clientes (reduce CxC).
- Usar transacciones.
- Validar fondos disponibles (opcional, según política).

Funciones JavaScript: similares a recibo caja.

Pruebas recomendadas:
- Cargar facturas de proveedor; pagar parcialmente.
- Modificar (cambiar montos).
- Eliminar.
- Validar asientos en libro.
- Comparar con cuantodebo.php.

---

# documentoscomprobantecontable.php
- Ruta: documentoscomprobantecontable.php
- Propósito: Crear asientos contables manuales (comprobantes contables).
- Filtros/params: fecha, consecutivo, observaciones, detalles (JSON con líneas: cuentaContable, descripcionCuenta, tercero, detalle, valorDebito, valorCredito).

Funciones clave:
- get_consecutivo (GET): SELECT MAX(consecutivo) AS ultimo FROM doccomprobantecontable.

- Búsqueda cuentas (AJAX con Select2):
  - obtener_cuentas_comprobantecontable.php: retorna lista de cuentas (código + descripción) para autocompletar.

- Búsqueda terceros (AJAX con Select2):
  - obtener_terceros.php: retorna terceros para seleccionar (opcional en línea).

- registrarComprobanteContable($libroDiario, $idComprobante):
  - Lee `detallecomprobantecontable` y `doccomprobantecontable`.
  - Para cada línea: registra movimiento en `libro_diario` con débito/crédito según corresponda.

Flujo principal (btnAgregar):
  1. Validar fecha, consecutivo, observaciones.
  2. Validar que para cada línea: debe + crédito > 0 (no línea vacía) y suma débitos = suma créditos.
  3. Insertar en `doccomprobantecontable` (fecha, consecutivo, observaciones).
  4. Obtener $idComprobante.
  5. Para cada línea (desde detalles JSON):
     - Extraer código de cuenta (si viene "1105-Caja", extraer "1105").
     - Insertar en `detallecomprobantecontable` (comprobante_id, cuentaContable, descripcionCuenta, tercero, detalle, valorDebito, valorCredito).
  6. Registrar en libro: $libroDiario->registrarComprobanteContable($idComprobante).
  7. Commit; redirect msg=agregado.

Flujo principal (btnModificar):
  1. Validar nuevos datos.
  2. Eliminar asientos contables: $libroDiario->eliminarMovimientos('comprobante_contable', $txtId).
  3. Eliminar detalles antiguos.
  4. Actualizar comprobante principal.
  5. Insertar nuevos detalles.
  6. Registrar nuevos asientos.
  7. Commit; redirect msg=modificado.

Flujo principal (btnEliminar):
  1. Eliminar asientos contables.
  2. Eliminar detalles.
  3. Eliminar comprobante.
  4. Commit; redirect msg=eliminado.

Tablas implicadas:
- `doccomprobantecontable` (id, fecha, consecutivo, observaciones).
- `detallecomprobantecontable` (id, comprobante_id, cuentaContable, descripcionCuenta, tercero, detalle, valorDebito, valorCredito).
- `libro_diario`.
- `cuentas_contables` (para búsqueda/validación).
- `terceros` (para búsqueda opcional).

Validaciones clave:
- Suma débitos DEBE = Suma créditos.
- Cuentas contables deben existir.
- Fecha debe ser válida.
- No permitir asiento con 0 líneas.
- Edición restringida después de contabilizado (opcional, según política).

Funciones JavaScript:
- Select2 para búsqueda de cuentas (AJAX a obtener_cuentas_comprobantecontable.php).
- Select2 para búsqueda de terceros (AJAX a obtener_terceros.php).
- calcularTotales(): suma débitos y créditos; calcula diferencia; valida igualdad.
- addRow(): agregar nueva línea.
- removeRowSafe(btn): eliminar línea (mínimo 1).
- Confirmaciones SweetAlert2.
- Empaquetar detalles en JSON antes de submit.

Pruebas recomendadas:
- Crear asiento de compra: débito a inventario, crédito a proveedor. Validar que débito = crédito.
- Crear asiento de ajuste: p.ej. por diferencia de caja.
- Intentar guardar con débito ≠ crédito: debe rechazar.
- Modificar asiento (cambiar cuentas, montos).
- Eliminar asiento.
- Verificar que líneas se registren correctamente en libro_diario.
- Validar en reportes (balance general, estado resultados).

---

# Módulo Informes

Este documento presenta la documentación con el mismo nivel de detalle que librosestadodesituacionfinanciera.php para los archivos del módulo Informes:
- menuinformes.php
- informesclientes.php
- cuantomedeben.php
- informesproveedores.php
- cuantodebo.php
- informesinventarios.php
- existencias.php

---

# menuinformes.php
- Ruta: menuinformes.php
- Propósito: Panel de navegación / hub para acceder a los reportes e informes del sistema.
- Funcionalidad principal: Enlaces a módulos de reportes agrupados por categoría (Clientes, Proveedores, Inventarios).
- Estructura:
  - Header con navegación general y logo.
  - Sección con 3 tarjetas/cajas (icon-box) que enlazan a:
    - informesclientes.php (reporte de clientes y cartera)
    - informesproveedores.php (reporte de proveedores y obligaciones)
    - informesinventarios.php (reporte de inventarios y movimientos)
  - Footer minimalista con créditos UDES.
- Notas:
  - No contiene lógica de servidor (PHP sin procesamiento).
  - Controlar permisos de acceso a cada módulo según rol de usuario (implementar en controller centralizado).
  - Actualizar enlaces si las rutas de archivos cambian.

---

# informesclientes.php
- Ruta: informesclientes.php
- Propósito: Hub/menú para reportes relacionados con clientes.
- Funcionalidad:
  - Tarjetas con enlaces a subformularios:
    - cuantomedeben.php (cartera y saldos por cobrar)
    - edadesdecarteraclientes.php (antigüedad de cartera)
  - Descripción de cada reporte.
- Notas:
  - No procesa datos; es un navegador.
  - Verificar que archivos enlazados existan.

---

# informesproveedores.php
- Ruta: informesproveedores.php
- Propósito: Hub para reportes relacionados con proveedores.
- Funcionalidad:
  - Tarjetas con enlaces a:
    - cuantodebo.php (obligaciones por pagar)
    - edadesdecarteraproveedores.php (antigüedad de obligaciones)
  - Descripción de cada reporte.
- Notas: Similar a informesclientes.php; es navegador, no procesa datos.

---

# informesinventarios.php
- Ruta: informesinventarios.php
- Propósito: Hub para reportes de inventario.
- Funcionalidad:
  - Tarjetas con enlaces a:
    - existencias.php (estado actual de existencias por artículo/almacén)
    - movimientodeinventarios.php (historial de entradas/salidas)
  - Descripción de cada reporte.
- Notas: Navegador; verifica que existencias.php esté correctamente enlazado.

---

# cuantomedeben.php
- Ruta: cuantomedeben.php
- Propósito: Reporte de cartera: mostrar cuánto deben los clientes (cuentas por cobrar).
- Parámetros GET/POST: cedula (identificación cliente), nombre (búsqueda), fecha (corte).

Funcionalidad clave:
- Búsqueda bidireccional de cliente:
  - Por identificación (cédula/NIT): busca en tabla `facturav`.
  - Por nombre: búsqueda LIKE parcial.
- Fetch AJAX (action=fetchCliente) que devuelve:
  - nombre, totalFacturado, valorAnticipos, saldoCobrar
- Tabla dinámica donde el usuario agrega clientes uno a uno; evita duplicados.
- Cálculo de totales fila a fila (suma de saldos por cliente).
- Generación de PDF con datos de la tabla.

Flujo principal:
1. Usuario ingresa identificación o nombre del cliente.
2. Script AJAX busca en `facturav` y devuelve datos en JSON.
3. Al hacer fetch con action=fetchCliente:
   - Calcula totalFacturado: SUM de valorTotal donde formaPago LIKE '%Credito%' en `facturav`.
   - Calcula valorAnticipos (abonos): SUM de valorAplicado en `detalle_recibo_caja` vinculado por identificación.
   - saldoCobrar = totalFacturado - valorAnticipos.
4. Fila se agrega a tabla con estos datos (sin duplicados).
5. Totales se actualizan al agregar/eliminar filas.
6. Al presionar "Generar PDF": se envían arrays de datos a generar_pdf.php.

Tablas implicadas:
- `facturav` (identificacion, nombre, valorTotal, formaPago) — fuente de facturas de venta.
- `detalle_recibo_caja` (idRecibo, valorAplicado).
- `docrecibodecaja` (id, identificacion).

Validaciones/Notas:
- Inputs sanitizados con prepared statements (bindParam).
- Búsqueda por nombre usa LIKE; limitar a 1 resultado para evitar confusión.
- Formato monetario: formatearMoneda() convierte a "1,234.56".
- Duplicados evitados via array clientesAgregados.
- Errores capturados en try-catch con mensajes SweetAlert.

Funciones JavaScript principales:
- obtenerDatosCartera(cedula): realiza fetch POST a action=fetchCliente.
- agregarFilaCliente(...): inserta fila en tabla con datos.
- eliminarFila(cedula): elimina fila del DOM y del array.
- limpiarTabla(): borra todas las filas.
- actualizarTotales(): recalcula sumas.
- formatearMoneda(valor): formatea a "X,XXX.XX".
- generarPDF(): arma formulario oculto y envía a generar_pdf.php.

Pruebas recomendadas:
- Buscar cliente válido e inválido; verificar mensaje SweetAlert.
- Agregar cliente duplicado; debe rechazar.
- Agregar múltiples clientes y verificar cálculo de totales.
- Eliminar fila y validar que totales se recalculen.
- Generar PDF y comparar datos con tabla.

---

# cuantodebo.php
- Ruta: cuantodebo.php
- Propósito: Reporte de obligaciones: mostrar cuánto se debe a proveedores (cuentas por pagar).
- Parámetros GET/POST: cedula (identificación proveedor), nombre (búsqueda), fecha (corte).

Funcionalidad clave:
- Búsqueda bidireccional de proveedor (similar a cuantomedeben.php).
- Fetch AJAX (action=fetchProveedor) que devuelve:
  - nombre, totalAdeudado, valorPagos, saldoPagar
- Tabla dinámica de proveedores agregados (evita duplicados).
- Cálculo de totales.
- Generación de PDF.

Flujo principal:
1. Usuario ingresa identificación o nombre del proveedor.
2. Script AJAX busca en `facturac` y devuelve datos en JSON.
3. Al hacer fetch con action=fetchProveedor:
   - Calcula totalAdeudado: SUM de valorTotal donde formaPago LIKE '%Credito%' en `facturac`.
   - Calcula valorPagos (abonos): SUM de valorAplicado en `detalle_comprobante_egreso` vinculado por identificación.
   - saldoPagar = totalAdeudado - valorPagos.
4. Fila se agrega a tabla.
5. Totales se actualizan.
6. Al presionar "Generar PDF": envía datos a generar_pdf_proveedores.php.

Tablas implicadas:
- `facturac` (identificacion, nombre, valorTotal, formaPago) — fuente de facturas de compra.
- `detalle_comprobante_egreso` (idComprobante, valorAplicado).
- `doccomprobanteegreso` (id, identificacion).

Diferencia con cuantomedeben.php:
- Usa tabla `facturac` en lugar de `facturav`.
- Usa tabla `detalle_comprobante_egreso` y `doccomprobanteegreso` para pagos en lugar de recibos de caja.
- Los nombres de campos son análogos pero orientados a proveedores.

Validaciones/Notas: Similares a cuantomedeben.php; aplicar mismas prácticas.

Pruebas recomendadas: Similares a cuantomedeben.php; verificar diferencias en tablas usadas.

---

# existencias.php
- Ruta: existencias.php
- Propósito: Reporte de existencias por artículo; mostrar stock disponible, entradas/salidas, valor de inventario.
- Parámetros GET/POST: codigoBuscar (SKU), nombreBuscar (nombre producto), fechaDesde, fechaHasta.

Funcionalidad clave:
- Búsqueda por código (AJAX):
  - action=buscarCodigo: consulta `productoinventarios` por codigoProducto.
  - Retorna descripcionProducto, cantidad.
- Búsqueda por nombre (autocompletado AJAX):
  - action=buscarNombre: consulta LIKE en descripcionProducto.
  - Retorna listado (LIMIT 10) para seleccionar.
- Tabla dinámica donde se agregan productos encontrados.
- Cálculo de total de cantidades (agregado con Math.round para enteros).
- Impresión/PDF de la tabla vía generarPdfExistencias.php.

Flujo principal:
1. Usuario ingresa código o nombre de producto.
2. AJAX dispara búsqueda en `productoinventarios`.
3. Si busca por código, encuentra un único producto y lo agrega a tabla.
4. Si busca por nombre, muestra sugerencias; usuario selecciona una.
5. Producto se agrega a tabla solo una vez (evita duplicados).
6. Cantidad se muestra como entero (Math.round).
7. Total de cantidades se calcula automáticamente.
8. Al presionar "Descargar PDF": formulario envía arrays de códigos, nombres, saldos a generarPdfExistencias.php.

Tablas implicadas:
- `productoinventarios` (codigoProducto, descripcionProducto, cantidad).

Notas de implementación (JavaScript):
- Debounce con setTimeout para evitar múltiples AJAX al escribir.
- Inputs dentro de form con name="codigos[]", "nombres[]", "saldos[]" para envío en POST.
- Inputs con readonly para que no se editen.
- formatearMoneda no se usa aquí (es para cantidades, no moneda).
- Math.round(parseFloat(cantidad)): convierte 30.00 a 30, 30.5 a 31.

Validaciones/Notas:
- Evitar duplicados: verifica si el código ya existe en la tabla.
- No permitir edición de cantidad en tabla (readonly).
- Inputs con name[] se envían como arrays en POST para generar PDF.

Funciones JavaScript principales:
- buscarPorCodigo(codigo): AJAX a action=buscarCodigo.
- buscarPorNombre(nombre): AJAX autocompletado; dispara action=buscarNombre.
- agregarProductoTabla(codigo, nombre, cantidad): inserta fila (si no existe).
- calcularTotal(): suma cantidad de rows "saldos[]".
- mostrarSugerencias(productos): renderiza lista de sugerencias con click handler.

Pruebas recomendadas:
- Buscar por código válido/inválido.
- Autocompletado por nombre; seleccionar de sugerencias.
- Intentar agregar producto duplicado; debe rechazar.
- Agregar varios productos y verificar total.
- Descargar PDF y verificar tabla.

---

# Flujos de datos comunes en Informes

Patrón de búsqueda AJAX (cuantomedeben, cuantodebo):
```
Usuario escribe -> input listener -> fetch POST con identificacion/nombre
       ↓
PHP procesa; prepared statement en tabla (facturav o facturac)
       ↓
Retorna JSON con {nombre, totalFacturado, valorAnticipos, saldoCobrar}
       ↓
JS agrega fila a tabla; evita duplicados
       ↓
Actualiza totales
```

Patrón de PDF (cuantomedeben, cuantodebo, existencias):
```
Usuario presiona "Generar PDF"
       ↓
JS valida que tabla no esté vacía
       ↓
Crea inputs hidden en form con arrays de datos
       ↓
form.submit() envía a generarPdf*.php
       ↓
PHP genera PDF con librería (FPDF/TCPDF/PhpSpreadsheet)
```

---

# Módulo Libros

Este documento presenta la documentación con el mismo nivel de detalle que la sección de librosestadodesituacionfinanciera.php para los archivos:
- LibroDiario.php
- libroslibroauxiliar.php
- librosbalancedeprueba.php
- librosestadodesituacionfinanciera.php
- librosestadoderesultados.php

---

# menulibros.php
- Ruta: menulibros.php
- Propósito: Navegación a libros contables y reportes financieros.
- Enlaces: ver_libro_diario.php, librosestadodesituacionfinanciera.php, librosestadoderesultados.php, librosbalancedeprueba.php, libroslibroauxiliar.php.

---

# LibroDiario.php
- Ruta: LibroDiario.php
- Propósito: Clase de utilidad para registrar y eliminar movimientos contables y construir asientos por diferentes documentos (factura venta/compra, recibo de caja, comprobantes).
- Dependencia: requiere un PDO válido pasado al constructor.

Funciones clave:
- __construct($pdo)
  - Inicializa con PDO.

- registrarMovimiento($params)
  - Inserta fila en tabla `libro_diario` con los campos: fecha, tipo_documento, numero_documento, id_documento, codigo_cuenta, nombre_cuenta, tercero_identificacion, tercero_nombre, concepto, debito, credito.
  - Usa prepared statements.

- eliminarMovimientos($tipo_documento, $id_documento)
  - Borra filas relacionadas a un documento.

- obtenerCuentaMedioPago($formaPago)
  - Analiza texto de medio de pago para extraer código y nombre de cuenta (usa regex).
  - Retorno por defecto: ['codigo'=>'110505','nombre'=>'Caja General'].
  - Nota: formato esperado varía; se recomienda normalizar bancos/medios en tabla `medios_pago`.

- obtenerCuentasCategoria($categoriaId)
  - Consulta tabla `categoriainventarios` para obtener cuentas asociadas: ventas, inventario, costos.

- registrarFacturaVenta($idFactura)
  - Flujo:
    1. Carga factura (`facturav`) y detalles (`factura_detalle` + `productoinventarios`).
    2. Determina forma de pago (crédito vs contado).
    3. Registra débito a cuenta cliente (130505) o a cuenta caja/banco según `obtenerCuentaMedioPago`.
    4. Registra crédito por IVA (240805) si aplica.
    5. Agrupa ventas por categoría y registra créditos en cuentas de ventas (desde `categoriainventarios`).
    6. Para productos, calcula costo unitario (consulta histórica en `detallefacturac`/`facturac`) y registra débito a costo y crédito a inventario por categoría.
  - Validaciones/observaciones:
    - Posible fuente de discrepancias si la consulta de costo no retorna valor; revisar query y fallback.
    - Debe ejecutarse dentro de transacción para mantener atomicidad.

- registrarFacturaCompra($idFactura)
  - Flujo:
    1. Carga factura de compra (`facturac`) y detalles.
    2. Registra débito a inventario/compras por categoría.
    3. Registra débito por IVA (nota en código: IVA descontable debería usar cuenta 135515 en vez de 240805 — revisar catálogo).
    4. Registra crédito por retenciones (236505) si aplica.
    5. Registra crédito a proveedor o banco según formaPago.
  - Observación: Corregir cuenta IVA descontable y envolver en transacción.

- registrarReciboCaja($idRecibo)
  - Registra débito a caja/banco (según medio) y crédito a Clientes (130505). Tablas: `docrecibodecaja`.

- registrarComprobanteEgreso($idComprobante)
  - Registra débito a cuentas de gasto/proveedor y crédito a caja/banco. Tablas: `doccomprobanteegreso`.

- registrarComprobanteContable($idComprobante)
  - Lee `detallecomprobantecontable` y `doccomprobantecontable`; registra línea por línea. Extrae código de cuenta si viene concatenado "1105-Caja".

Tablas implicadas:
- libro_diario, facturav, factura_detalle, productoinventarios, detallefacturac, facturac, docrecibodecaja, doccomprobanteegreso, detallecomprobantecontable, doccomprobantecontable, categoriainventarios.

Notas de validación y mejoras:
- Envolver creación de asiento completo en transacción (beginTransaction/commit/rollBack).
- Validar que la suma de débitos = suma de créditos por documento; si no, abortar y registrar error.
- Reemplazar regex y heurísticas por un mapeo de medios de pago en DB (`medios_pago`) para mayor fiabilidad.
- Manejar errores/excepciones y logs (usuario, id_documento).
- Comprobar la corrección del código de cuenta IVA descontable en registrarFacturaCompra.

Pruebas recomendadas:
- Insertar factura venta con varios ítems (productos + servicios), verificar asientos en `libro_diario` y que débitos = créditos.
- Probar factura compra con retenciones e IVA; validar cuentas usadas.
- Probar recibo de caja con medio de pago que tenga formatos distintos y validar `obtenerCuentaMedioPago`.

---

# libroslibroauxiliar.php
- Ruta: libroslibroauxiliar.php
- Propósito: Mostrar detalle cronológico de movimientos por cuenta y/o tercero (libro auxiliar) con saldos fila a fila.
- Filtros/params GET: desde (fecha), hasta (fecha), cuenta, tercero.

Funciones clave:
- obtenerMovimientosCuenta($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '')
  - Calcula saldo inicial (sumas antes de `desde`) y obtiene movimientos en el rango.
  - Determina naturaleza por primer dígito del código: si naturaleza ∈ {1,5,6,7} => débito - crédito, else crédito - débito.
  - Genera saldo fila a fila: agrega campos saldo_inicial_fila y saldo_final_fila a cada movimiento.
  - Normaliza presentación: evita mostrar saldos negativos salvo cuentas con código que contenga '2408' (IVA).
  - Soporta filtro por tercero.

Flujo principal del script:
1. Recupera lista de cuentas presentes en `libro_diario` para el rango (o la cuenta seleccionada).
2. Para cada cuenta, llama a obtenerMovimientosCuenta y renderiza filas con:
   - fecha, comprobante formateado por tipo_documento, concepto, saldo inicial, débito, crédito, saldo final.
3. Genera selects para cuentas y terceros (listas desde libro_diario).

Tablas implicadas:
- libro_diario (todas las operaciones se leen desde aquí).

Notas de validación y mejoras:
- El cálculo de saldo inicial y la regla de "no mostrar saldo negativo" puede ocultar información; considerar mantener signo y formatear con color.
- Para rangos grandes, paginar por cuenta o limitar resultados; actualmente carga todos los movimientos por cuenta en memoria.
- Localización/formatos: date('F d \D\E Y') usa mes en inglés en servidor en-US; use setlocale() o formato dd/mm/YYYY para consistencia.
- Sanitizar inputs y validar fechas.

Pruebas recomendadas:
- Cuenta con movimientos antes y dentro del período: verificar saldo inicial y balances acumulados.
- Movimientos con terceros concatenados en campo tercero_identificacion; validar separación ID/nombre.
- Exportar a Excel/PDF usando scripts exportar_libro_auxiliar* y comparar con HTML.

---

# librosbalancedeprueba.php
- Ruta: librosbalancedeprueba.php
- Propósito: Generar Balance de Prueba (listado por cuenta con saldo inicial, movimientos y saldo final) y agrupar cuentas por niveles jerárquicos.
- Filtros/params GET: periodo_fiscal, desde, hasta, cuenta, tercero, mostrar_saldo_inicial (0/1).

Funciones clave:
- calcularMovimientos($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '')
  - Calcula saldo inicial (sumas antes de desde), movimientos en el período y saldo final según naturaleza de la cuenta (primer dígito).
  - Retorna array: saldo_inicial, debito, credito, saldo_final.

- Lógica de construcción de jerarquía
  - Recupera cuentas distintas desde `libro_diario` en período.
  - Para cada cuenta se añade elemento detalle y se generan códigos de niveles superiores (substr 6,4,2,1).
  - Crea agrupaciones (es_agrupacion) y luego suma totales por código (totales_por_codigo).
  - Asigna totales calculados a agrupaciones y calcula totales generales.

- Nombres de agrupación
  - Mapa $nombres_agrupacion define nombres para códigos raíz (1,2,3...) y grupos comunes.

Tablas implicadas:
- libro_diario (fuente de datos).

Notas de validación y mejoras:
- Asumir estructura fija de códigos contables (longitudes de niveles) — confirmar con catálogo `cuentas_contables`.
- Riesgo de duplicación si existen códigos incompletos: use `cuentas_contables` para nombres y niveles en lugar de derivarlos solo de libro_diario.
- Performance: la agregación y suma por cada cuenta puede ser costosa con muchos códigos; considerar calcular sumas agregadas por SQL (GROUP BY) y luego construir jerarquía en memoria.

Pruebas recomendadas:
- Comparar saldos y totales con ver_libro_diario y exportadores.
- Activar/desactivar mostrar_saldo_inicial y validar consistencia.
- Probar con cuentas que no tengan movimientos en rango (deben omitirse o aparecer según diseño).

---

# librosestadodesituacionfinanciera.php
- Ruta: librosestadodesituacionfinanciera.php
- Propósito: Generar Estado de Situación Financiera (Balance general) entre dos fechas, con opción de mostrar saldo inicial y agrupar por niveles (Activos, Pasivos, Patrimonio).
- Filtros/params GET: periodo_fiscal, desde, hasta, cuenta, tercero, mostrar_saldo_inicial (bool).

Funciones clave:
- calcularSaldoCuenta($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '', $calcular_saldo_inicial = false)
  - Ejecuta SUM(debito)/SUM(credito) para una cuenta en rango (o rango de saldo inicial).
  - Soporta filtrado por tercero.

- obtenerNombresCuentas($pdo)
  - Lee tabla `cuentas_contables` (niveles 1..6) y mapea códigos a nombres (espera formato "codigo - nombre").

- obtenerResultadoEjercicio($pdo, $fecha_desde, $fecha_hasta, $tercero = '')
  - Calcula resultado neto: ingresos (clase '4' como crédito - débito) menos costos ('6') y gastos ('5').

- agregarAgrupaciones(&$array_cuentas, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial = false)
  - Crea grupos superiores para mostrar subtotales por niveles válidos (1,2,4,6,8,10).
  - Acumula saldos de hijos a padres respetando signos.

Flujo principal:
1. Recupera cuentas de clase 1,2,3 desde `libro_diario` en el período.
2. Para cada cuenta calcula saldo y (si requerido) saldo inicial.
3. Clasifica en arrays $activos, $pasivos, $patrimonios y acumula totales.
4. Calcula resultado del ejercicio y lo agrega al patrimonio (cuenta 360501 o 361001).
5. Llama agregarAgrupaciones para cada sección, construye listas para selects y verifica equilibrio (ACT = PAS + PAT).

Tablas implicadas:
- libro_diario, cuentas_contables.

Notas de validación y mejoras:
- Signos dependen de la "clase" (primer carácter del código): implementar función utilitaria para naturaleza.
- Verificar que `connection.php` configure PDO con UTF-8 y zona horaria.
- Recomendar usar transacciones al modificar datos (no aplicado aquí, solo lectura).
- Considerar cache de nombres de cuentas para evitar lecturas repetidas.

Pruebas recomendadas:
- Comparar totales con librosbalancedeprueba y ver_libro_diario.
- Realizar pruebas con filtro por tercero y por cuenta.
- Probar exportadores PDF/Excel para validar que la lógica de presentación coincide.

---

# librosestadoderesultados.php
- Ruta: librosestadoderesultados.php
- Propósito: Generar Estado de Resultados (Pérdidas y Ganancias) para un periodo, mostrando ingresos, costos, gastos y utilidades.
- Filtros/params GET: periodo_fiscal, desde, hasta, cuenta, tercero, mostrar_saldo_inicial (bool).

Funciones clave:
- calcularSaldoCuenta(...) — igual que en otros scripts (riesgo de duplicidad; considerar refactorizar a helper central).
- obtenerNombresCuentas($pdo) — reutilizable.
- agregarAgrupaciones(...) — reutilizable para crear subtotales por niveles.
- Cálculos principales:
  - ingresos: cuentas clase '4' (saldo = crédito - débito)
  - costos: clase '6' (saldo = débito - crédito)
  - gastos: clase '5' (saldo = débito - crédito)
  - utilidad bruta = ingresos - costos
  - utilidad operacional = utilidad bruta - gastos
  - resultado ejercicio = utilidad_operacional

Tablas implicadas:
- libro_diario, cuentas_contables.

Notas de validación y mejoras:
- Asegurar consistencia de criterios (qué cuentas incluir en cada clase).
- Evitar duplicar lógica de cálculo en múltiples archivos; extraer helpers compartidos (por ejemplo: SaldosHelper::calcularCuenta).
- Manejar casos donde cuentas de ajuste u operaciones no operacionales deban excluirse.

Pruebas recomendadas:
- Validar cálculos con dataset de prueba conocido.
- Comparar exportadores y cálculos con reportes manuales.

---

# Exportadores (PDF / Excel) — Detalle
- Archivos típicos: exportar_estado_situacion_pdf.php, exportar_estado_situacion_excel.php, exportar_excel_libro_diario.php, exportar_balance_prueba_*.php
- Propósito: Generar archivos para descarga/reporte.
- Parámetros: reciben mismos filtros que las vistas (desde, hasta, cuenta, tercero, etc.).
- Dependencias recomendadas:
  - PDF: FPDF/TCPDF/FPDI.
  - Excel: PhpSpreadsheet.
- Buenas prácticas:
  - Validar permisos y parámetros antes de ejecutar consultas.
  - Separar lógica: una función obtiene datos; otra formatea el archivo.
  - Controlar memoria y tiempos en exportes grandes (paginación/streaming).
- Pruebas:
  - Exportar con datasets pequeños y grandes; comparar totales con vistas en pantalla.

---

# Recomendaciones generales
- Conexión: centralizar en connection.php con PDO + UTF-8.
- Seguridad: usar consultas preparadas, sanitizar GET/POST, controlar permisos.
- Auditoría: registrar cambios en parámetros y operaciones contables.
- Tests: pruebas manuales y casos de comparación de sumas entre vistas y exportadores.
- Documentación adicional: agregar fragmentos SQL relevantes y ejemplos de requests/responses por endpoint.
