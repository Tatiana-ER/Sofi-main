<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/auth_check.php'; ?>
<?php

// Configuración de conexión a la base de datos
$host = 'localhost';
$dbname = 'sofi';
$username = 'root';  // Cambiar según tu configuración
$password = '';      // Cambiar según tu configuración

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Filtros
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$filtro_fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$filtro_usuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';

// Construir consulta con filtros
$sql = "SELECT * FROM documentos_eliminados WHERE 1=1";
$params = array();

if (!empty($filtro_tipo)) {
    $sql .= " AND tipo_documento = :tipo";
    $params[':tipo'] = $filtro_tipo;
}

if (!empty($filtro_fecha_inicio)) {
    $sql .= " AND fecha_eliminacion >= :fecha_inicio";
    $params[':fecha_inicio'] = $filtro_fecha_inicio;
}

if (!empty($filtro_fecha_fin)) {
    $sql .= " AND fecha_eliminacion <= :fecha_fin";
    $params[':fecha_fin'] = $filtro_fecha_fin;
}

if (!empty($filtro_usuario)) {
    $sql .= " AND nombre_usuario LIKE :usuario";
    $params[':usuario'] = '%' . $filtro_usuario . '%';
}

$sql .= " ORDER BY fecha_eliminacion DESC, hora_eliminacion DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener tipos de documentos únicos para el filtro
$sql_tipos = "SELECT DISTINCT tipo_documento FROM documentos_eliminados ORDER BY tipo_documento";
$stmt_tipos = $conn->query($sql_tipos);
$tipos_documentos = $stmt_tipos->fetchAll(PDO::FETCH_COLUMN);

// Obtener usuarios únicos para el filtro
$sql_usuarios = "SELECT DISTINCT nombre_usuario FROM documentos_eliminados ORDER BY nombre_usuario";
$stmt_usuarios = $conn->query($sql_usuarios);
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos Eliminados - SOFI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(155deg, #0a2748 0%, #103669 55%, #1b4b82 100%);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            position: relative;
        }

        /* Botón cerrar flotante (discreto, esquina superior derecha) */
        .btn-cerrar-flotante {
            position: absolute;
            top: 12px;
            right: 16px;
            background: none;
            border: none;
            font-size: 28px;
            line-height: 1;
            color: #bbb;
            cursor: pointer;
            padding: 4px 8px;
            z-index: 10;
            transition: color 0.2s, transform 0.2s;
        }

        .btn-cerrar-flotante:hover {
            color: #eb0404;
            transform: scale(1.15);
        }

        .filters {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #103669;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .btn-filter,
        .btn-clear {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-filter {
            background-color: #103669;
            color: white;
        }

        .btn-filter:hover {
            background-color: #0a2748;
        }

        .btn-clear {
            background-color: #e0e0e0;
            color: #333;
        }

        .btn-clear:hover {
            background-color: #d0d0d0;
        }

        .search-box {
            margin-bottom: 20px;
            padding: 12px;
            width: 100%;
            max-width: 500px;
            border: 2px solid #103669;
            border-radius: 8px;
            font-size: 15px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        thead {
            background: linear-gradient(135deg, #103669 0%, #1b4b82 100%);
            color: white;
        }

        th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }

        tbody tr {
            transition: background-color 0.2s;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-factura {
            background-color: #e3f2fd;
            color: #103669;
        }

        .badge-recibo {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .badge-comprobante {
            background-color: #e8eaf6;
            color: #1b4b82;
        }

        .badge-default {
            background-color: #e0e0e0;
            color: #424242;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-data-icon {
            font-size: 64px;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        .no-data p {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .no-data small {
            color: #999;
        }

        .total-money {
            font-weight: 600;
            color: #2e7d32;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 8px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">

        <!-- Botón cerrar flotante -->
        <button type="button" class="btn-cerrar-flotante" onclick="cerrarDocumentosEliminados()" title="Cerrar">
            &times;
        </button>

        <!-- Filtros -->
        <form method="GET" action="">
            <div class="filters">
                <div class="filter-group">
                    <label>Tipo de Documento</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <?php foreach ($tipos_documentos as $tipo): ?>
                            <option value="<?php echo htmlspecialchars($tipo); ?>" 
                                <?php echo ($filtro_tipo == $tipo) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Usuario</label>
                    <select name="usuario">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?php echo htmlspecialchars($usuario); ?>" 
                                <?php echo ($filtro_usuario == $usuario) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usuario); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Fecha Desde</label>
                    <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($filtro_fecha_inicio); ?>">
                </div>

                <div class="filter-group">
                    <label>Fecha Hasta</label>
                    <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($filtro_fecha_fin); ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-filter">🔍 Filtrar</button>
                    <a href="documentos_eliminados.php" class="btn-clear">✖️ Limpiar</a>
                </div>
            </div>
        </form>

        <!-- Búsqueda rápida -->
        <input type="text" id="searchInput" class="search-box" 
               placeholder="🔎 Buscar por documento, tercero, número..." 
               onkeyup="filtrarTabla()">

        <!-- Tabla -->
        <div class="table-container">
            <?php if (count($documentos) > 0): ?>
                <table id="tablaDocumentos">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Usuario</th>
                            <th>Tipo</th>
                            <th>N° Documento</th>
                            <th>Nombre</th>
                            <th>Tercero</th>
                            <th>Total</th>
                            <th>Fecha Doc.</th>
                            <th>Fecha Eliminación</th>
                            <th>Hora</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documentos as $index => $doc): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($doc['nombre_usuario']); ?></strong></td>
                                <td>
                                    <?php
                                    $tipo = strtolower($doc['tipo_documento']);
                                    $badgeClass = 'badge-default';
                                    if (strpos($tipo, 'factura') !== false) {
                                        $badgeClass = 'badge-factura';
                                    } elseif (strpos($tipo, 'recibo') !== false) {
                                        $badgeClass = 'badge-recibo';
                                    } elseif (strpos($tipo, 'comprobante') !== false) {
                                        $badgeClass = 'badge-comprobante';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($doc['tipo_documento']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($doc['numero_documento']); ?></td>
                                <td><?php echo htmlspecialchars($doc['nombre_documento']); ?></td>
                                <td><?php echo htmlspecialchars($doc['tercero']); ?></td>
                                <td class="total-money">
                                    <?php echo $doc['total'] ? '$' . number_format($doc['total'], 2) : '-'; ?>
                                </td>
                                <td>
                                    <?php echo $doc['fecha_documento'] ? date('d/m/Y', strtotime($doc['fecha_documento'])) : '-'; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($doc['fecha_eliminacion'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($doc['hora_eliminacion'])); ?></td>
                                <td><?php echo htmlspecialchars($doc['detalles']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📭</div>
                    <p>No hay documentos eliminados registrados</p>
                    <small>Los documentos eliminados aparecerán aquí automáticamente</small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filtrarTabla() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("tablaDocumentos");
            
            if (table) {
                const tr = table.getElementsByTagName("tr");
                
                for (let i = 1; i < tr.length; i++) {
                    let found = false;
                    const td = tr[i].getElementsByTagName("td");
                    
                    for (let j = 0; j < td.length; j++) {
                        if (td[j]) {
                            const txtValue = td[j].textContent || td[j].innerText;
                            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                                found = true;
                                break;
                            }
                        }
                    }
                    
                    tr[i].style.display = found ? "" : "none";
                }
            }
        }

        function cerrarDocumentosEliminados() {
            // Si está dentro del modal (iframe), le avisa a la página padre que lo cierre
            if (window.parent !== window) {
                window.parent.postMessage('cerrarModalDocumentos', '*');
            } else {
                // Si por algún motivo se abrió directo en una pestaña, la cierra
                window.close();
            }
        }
    </script>
</body>
</html>