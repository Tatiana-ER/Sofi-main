<?php
// test_triggers.php
include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

// Agrega esto JUSTO DESPUÉS de la conexión ($pdo = $conn->connect();)
// y ANTES de la verificación de triggers que ya tienes

echo "<h3>ANÁLISIS DE TRIGGERS - VER QUÉ HACEN EXACTAMENTE</h3>";

try {
    // Ver la definición COMPLETA del trigger de compra
    $sqlTriggerCompra = "SHOW CREATE TRIGGER after_insert_compan";
    $stmt = $pdo->query($sqlTriggerCompra);
    $triggerCompra = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h4>Trigger after_insert_compan (INSERT en detallefacturac):</h4>";
    if ($triggerCompra && isset($triggerCompra['SQL Original Statement'])) {
        echo "<pre style='background: #f0f0f0; padding: 10px;'>";
        echo htmlspecialchars($triggerCompra['SQL Original Statement']);
        echo "</pre>";
        
        // Analizar si actualiza inventario
        if (strpos($triggerCompra['SQL Original Statement'], 'productoinventarios') !== false) {
            echo "<p style='color: red; font-weight: bold;'>⚠️ ESTE TRIGGER ACTUALIZA EL INVENTARIO</p>";
        }
        if (strpos($triggerCompra['SQL Original Statement'], 'cantidad') !== false) {
            echo "<p style='color: red; font-weight: bold;'>⚠️ ESTE TRIGGER MODIFICA CANTIDADES</p>";
        }
    } else {
        echo "<p>No se pudo obtener la definición del trigger</p>";
    }
    
    echo "<hr>";
    
    // Ver la definición COMPLETA del trigger de venta
    $sqlTriggerVenta = "SHOW CREATE TRIGGER after_insert_venta";
    $stmt = $pdo->query($sqlTriggerVenta);
    $triggerVenta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h4>Trigger after_insert_venta (INSERT en factura_detalle):</h4>";
    if ($triggerVenta && isset($triggerVenta['SQL Original Statement'])) {
        echo "<pre style='background: #f0f0f0; padding: 10px;'>";
        echo htmlspecialchars($triggerVenta['SQL Original Statement']);
        echo "</pre>";
        
        // Analizar si actualiza inventario
        if (strpos($triggerVenta['SQL Original Statement'], 'productoinventarios') !== false) {
            echo "<p style='color: red; font-weight: bold;'>⚠️ ESTE TRIGGER ACTUALIZA EL INVENTARIO</p>";
        }
        if (strpos($triggerVenta['SQL Original Statement'], 'cantidad') !== false) {
            echo "<p style='color: red; font-weight: bold;'>⚠️ ESTE TRIGGER MODIFICA CANTIDADES</p>";
        }
    }
    
    echo "<hr>";
    echo "<h4>PRUEBA DE DIAGNÓSTICO:</h4>";
    echo "<p>Para verificar el problema, crea una compra de 10 unidades y revisa:</p>";
    echo "<ol>";
    echo "<li>¿Cuántas veces se ejecuta UPDATE en productoinventarios? (PHP + Trigger)</li>";
    echo "<li>¿El resultado final es correcto? (Ej: de 100 a 110, NO a 120)</li>";
    echo "</ol>";
    
    // exit; // DESCOMENTA ESTA LÍNEA para ver solo los triggers y salir
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error al analizar triggers: " . $e->getMessage() . "</p>";
}

// ============================================
// CONTINÚA CON EL CÓDIGO ACTUAL QUE YA TIENES
// ============================================

echo "<h2>VERIFICACIÓN DE TRIGGERS EN LA BASE DE DATOS</h2>";

// Agrega esto al inicio del archivo, después de la conexión
try {
    // Ver definición completa de los triggers
    $sqlTriggerDef = "SHOW CREATE TRIGGER after_insert_compan";
    $stmt = $pdo->query($sqlTriggerDef);
    $triggerDef = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>DEFINICIÓN DEL TRIGGER after_insert_compan:</h3>";
    echo "<pre>" . htmlspecialchars($triggerDef['SQL Original Statement'] ?? 'No encontrado') . "</pre>";
    
    $sqlTriggerDef2 = "SHOW CREATE TRIGGER after_insert_venta";
    $stmt2 = $pdo->query($sqlTriggerDef2);
    $triggerDef2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>DEFINICIÓN DEL TRIGGER after_insert_venta:</h3>";
    echo "<pre>" . htmlspecialchars($triggerDef2['SQL Original Statement'] ?? 'No encontrado') . "</pre>";
    
    // exit; // Descomenta para ver solo los triggers y salir
} catch (Exception $e) {
    echo "Error al consultar triggers: " . $e->getMessage();
}

// Método 1: SHOW TRIGGERS
echo "<h3>1. TODOS LOS TRIGGERS (SHOW TRIGGERS):</h3>";
try {
    $stmt = $pdo->query("SHOW TRIGGERS");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($triggers) == 0) {
        echo "<p style='color: green;'>✓ No se encontraron triggers en la base de datos.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Trigger</th><th>Evento</th><th>Tabla</th><th>Timing</th></tr>";
        foreach ($triggers as $trigger) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($trigger['Trigger']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['Event']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['Table']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['Timing']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Método 2: INFORMATION_SCHEMA
echo "<h3>2. TRIGGERS ESPECÍFICOS DE TABLAS DE FACTURAS:</h3>";
$sql = "SELECT 
    TRIGGER_NAME,
    EVENT_OBJECT_TABLE,
    EVENT_MANIPULATION,
    ACTION_TIMING,
    ACTION_STATEMENT
FROM information_schema.TRIGGERS 
WHERE EVENT_OBJECT_TABLE IN ('facturac', 'facturav', 'detallefacturac', 'factura_detalle')
AND TRIGGER_SCHEMA = DATABASE()";

try {
    $stmt = $pdo->query($sql);
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($triggers) == 0) {
        echo "<p style='color: green;'>✓ No se encontraron triggers en las tablas de facturas.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Nombre</th><th>Tabla</th><th>Evento</th><th>Timing</th><th>Definición</th></tr>";
        foreach ($triggers as $trigger) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($trigger['TRIGGER_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['EVENT_OBJECT_TABLE']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['EVENT_MANIPULATION']) . "</td>";
            echo "<td>" . htmlspecialchars($trigger['ACTION_TIMING']) . "</td>";
            echo "<td><small>" . htmlspecialchars(substr($trigger['ACTION_STATEMENT'], 0, 200)) . "...</small></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Verificar si hay procedimientos almacenados o eventos
echo "<h3>3. PROCEDIMIENTOS ALMACENADOS Y EVENTOS:</h3>";

// Procedimientos
$sqlProcs = "SHOW PROCEDURE STATUS WHERE Db = DATABASE()";
try {
    $stmt = $pdo->query($sqlProcs);
    $procs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($procs) == 0) {
        echo "<p style='color: green;'>✓ No se encontraron procedimientos almacenados.</p>";
    } else {
        echo "<p>Procedimientos encontrados (" . count($procs) . "):</p>";
        echo "<ul>";
        foreach ($procs as $proc) {
            echo "<li>" . htmlspecialchars($proc['Name']) . "</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p>Error al consultar procedimientos: " . $e->getMessage() . "</p>";
}

// Eventos programados
echo "<h4>Eventos programados:</h4>";
try {
    $stmt = $pdo->query("SHOW EVENTS");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($events) == 0) {
        echo "<p style='color: green;'>✓ No se encontraron eventos programados.</p>";
    } else {
        echo "<p>Eventos encontrados (" . count($events) . "):</p>";
        echo "<ul>";
        foreach ($events as $event) {
            echo "<li>" . htmlspecialchars($event['Name']) . " - " . htmlspecialchars($event['Interval']) . "</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p>Error al consultar eventos: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>4. PRUEBA DE ACTUALIZACIÓN DE INVENTARIO:</h3>";

// Verificar el estado actual del inventario
$sqlInventario = "SELECT codigoProducto, descripcionProducto, cantidad FROM productoinventarios LIMIT 5";
$stmt = $pdo->query($sqlInventario);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Estado actual del inventario (primeros 5 productos):</p>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Código</th><th>Producto</th><th>Cantidad</th></tr>";
foreach ($productos as $producto) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($producto['codigoProducto']) . "</td>";
    echo "<td>" . htmlspecialchars($producto['descripcionProducto']) . "</td>";
    echo "<td>" . htmlspecialchars($producto['cantidad']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>5. DIAGNÓSTICO:</h3>";

// Verificar si hay algún archivo de configuración que pueda estar causando el problema
echo "<p>Si no hay triggers, procedimientos o eventos, el problema puede estar en:</p>";
echo "<ol>";
echo "<li>Código PHP duplicado (revisar includes, funciones que se llaman dos veces)</li>";
echo "<li>Formulario que se envía dos veces por JavaScript</li>";
echo "<li>Problema de lógica en el código PHP (actualización doble)</li>";
echo "<li>Otros archivos incluidos que también actualizan inventario</li>";
echo "</ol>";

echo "<p><strong>Sugerencia de prueba:</strong> Crea una compra de 1 unidad de un producto y revisa:</p>";
echo "<ul>";
echo "<li>¿Cuántas veces se ejecuta el UPDATE en productoinventarios?</li>";
echo "<li>¿Hay algún mensaje de error o log que muestre duplicación?</li>";
echo "<li>¿El formulario se envía una o dos veces? (revisa Network en DevTools)</li>";
echo "</ul>";
?>