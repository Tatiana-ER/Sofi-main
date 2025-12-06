<?php
require('libs/fpdf/fpdf.php');

// ================== CONEXIÓN ==================
include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

// ================== OBTENER DATOS DEL PERFIL ==================
$sql_perfil = "SELECT persona, nombres, apellidos, razon, cedula, digito FROM perfil LIMIT 1";
$stmt_perfil = $pdo->query($sql_perfil);
$perfil = $stmt_perfil->fetch(PDO::FETCH_ASSOC);

if ($perfil) {
    if ($perfil['persona'] == 'juridica' && !empty($perfil['razon'])) {
        $nombre_empresa = $perfil['razon'];
    } else {
        $nombre_empresa = trim($perfil['nombres'] . ' ' . $perfil['apellidos']);
    }
    $nit_empresa = $perfil['cedula'] . ($perfil['digito'] > 0 ? '-' . $perfil['digito'] : '');
} else {
    $nombre_empresa = 'Nombre de la Empresa';
    $nit_empresa = 'NIT de la Empresa';
}

// ================== FILTROS ==================
$periodo_fiscal = isset($_GET['periodo_fiscal']) ? $_GET['periodo_fiscal'] : date('Y');
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$cuenta_codigo = isset($_GET['cuenta']) ? $_GET['cuenta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';
$mostrar_saldo_inicial = isset($_GET['mostrar_saldo_inicial']) ? $_GET['mostrar_saldo_inicial'] === '1' : false;

// ================== FUNCIÓN PARA CONVERTIR TEXTO ==================
function convertText($text) {
    return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
}

// ================== FUNCIÓN PARA CALCULAR SALDOS POR CUENTA ==================
function calcularSaldoCuenta($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '', $calcular_saldo_inicial = false) {
    if ($calcular_saldo_inicial) {
        $ano_fiscal = date('Y', strtotime($fecha_desde));
        $fecha_inicio_saldo_inicial = $ano_fiscal . '-01-01';
        $fecha_fin_saldo_inicial = date('Y-m-d', strtotime($fecha_desde . ' -1 day'));
        
        $sql = "SELECT 
                    COALESCE(SUM(debito), 0) as total_debito,
                    COALESCE(SUM(credito), 0) as total_credito
                FROM libro_diario 
                WHERE codigo_cuenta = :cuenta 
                  AND fecha BETWEEN :desde AND :hasta";
        
        $params = [
            ':cuenta' => $codigo_cuenta, 
            ':desde' => $fecha_inicio_saldo_inicial, 
            ':hasta' => $fecha_fin_saldo_inicial
        ];
    } else {
        $sql = "SELECT 
                    COALESCE(SUM(debito), 0) as total_debito,
                    COALESCE(SUM(credito), 0) as total_credito
                FROM libro_diario 
                WHERE codigo_cuenta = :cuenta 
                  AND fecha BETWEEN :desde AND :hasta";
        
        $params = [
            ':cuenta' => $codigo_cuenta, 
            ':desde' => $fecha_desde, 
            ':hasta' => $fecha_hasta
        ];
    }
    
    if ($tercero != '') {
        $sql .= " AND tercero_identificacion = :tercero";
        $params[':tercero'] = $tercero;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ================== OBTENER NOMBRES DE CUENTAS ==================
function obtenerNombresCuentas($pdo) {
    $sql = "SELECT nivel1, nivel2, nivel3, nivel4, nivel5, nivel6 FROM cuentas_contables";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $nombres = [];
    foreach ($resultados as $fila) {
        for ($i = 1; $i <= 6; $i++) {
            $campo = 'nivel' . $i;
            if (!empty($fila[$campo])) {
                $partes = explode('-', $fila[$campo], 2);
                if (count($partes) == 2) {
                    $codigo = trim($partes[0]);
                    $nombre = trim($partes[1]);
                    $nombres[$codigo] = $nombre;
                }
            }
        }
    }
    return $nombres;
}

// ================== OBTENER RESULTADO DEL EJERCICIO ==================
function obtenerResultadoEjercicio($pdo, $fecha_desde, $fecha_hasta, $tercero = '') {
    $sql_ingresos = "SELECT COALESCE(SUM(credito - debito), 0) as saldo 
                     FROM libro_diario 
                     WHERE SUBSTRING(codigo_cuenta, 1, 1) = '4' 
                     AND fecha BETWEEN :desde AND :hasta";
    
    $params_ingresos = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_ingresos .= " AND tercero_identificacion = :tercero";
        $params_ingresos[':tercero'] = $tercero;
    }
    
    $stmt = $pdo->prepare($sql_ingresos);
    $stmt->execute($params_ingresos);
    $ingresos = $stmt->fetch(PDO::FETCH_ASSOC)['saldo'];
    
    $sql_costos = "SELECT COALESCE(SUM(debito - credito), 0) as saldo 
                   FROM libro_diario 
                   WHERE SUBSTRING(codigo_cuenta, 1, 1) = '6' 
                   AND fecha BETWEEN :desde AND :hasta";
    
    $params_costos = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_costos .= " AND tercero_identificacion = :tercero";
        $params_costos[':tercero'] = $tercero;
    }
    
    $stmt = $pdo->prepare($sql_costos);
    $stmt->execute($params_costos);
    $costos = $stmt->fetch(PDO::FETCH_ASSOC)['saldo'];
    
    $sql_gastos = "SELECT COALESCE(SUM(debito - credito), 0) as saldo 
                   FROM libro_diario 
                   WHERE SUBSTRING(codigo_cuenta, 1, 1) = '5' 
                   AND fecha BETWEEN :desde AND :hasta";
    
    $params_gastos = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_gastos .= " AND tercero_identificacion = :tercero";
        $params_gastos[':tercero'] = $tercero;
    }
    
    $stmt = $pdo->prepare($sql_gastos);
    $stmt->execute($params_gastos);
    $gastos = $stmt->fetch(PDO::FETCH_ASSOC)['saldo'];
    
    return $ingresos - $costos - $gastos;
}

// Obtener nombres de cuentas
$nombres_cuentas = obtenerNombresCuentas($pdo);

// ================== OBTENER DATOS ==================
$sql_cuentas = "SELECT DISTINCT 
                    codigo_cuenta, 
                    nombre_cuenta,
                    SUBSTRING(codigo_cuenta, 1, 1) as clase
                FROM libro_diario 
                WHERE fecha BETWEEN :desde AND :hasta
                  AND SUBSTRING(codigo_cuenta, 1, 1) IN ('1', '2', '3')";

$params_cuentas = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

if ($cuenta_codigo != '') {
    $sql_cuentas .= " AND codigo_cuenta = :cuenta";
    $params_cuentas[':cuenta'] = $cuenta_codigo;
}

if ($tercero != '') {
    $sql_cuentas .= " AND tercero_identificacion = :tercero";
    $params_cuentas[':tercero'] = $tercero;
}

$sql_cuentas .= " ORDER BY clase, codigo_cuenta";

$stmt = $pdo->prepare($sql_cuentas);
$stmt->execute($params_cuentas);
$todas_cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== PROCESAR DATOS ==================
$activos = []; $pasivos = []; $patrimonios = [];
$totalActivos = 0; $totalPasivos = 0; $totalPatrimonios = 0;
$totalSaldoInicialActivos = 0; $totalSaldoInicialPasivos = 0; $totalSaldoInicialPatrimonios = 0;

$cuentas_procesadas = [];

foreach ($todas_cuentas as $cuenta) {
    $codigo = $cuenta['codigo_cuenta'];
    $clase = $cuenta['clase'];
    
    $movimientos = calcularSaldoCuenta($pdo, $codigo, $fecha_desde, $fecha_hasta, $tercero);
    $debito = floatval($movimientos['total_debito']);
    $credito = floatval($movimientos['total_credito']);
    
    $saldo_inicial = 0;
    if ($mostrar_saldo_inicial) {
        $movimientos_inicial = calcularSaldoCuenta($pdo, $codigo, $fecha_desde, $fecha_hasta, $tercero, true);
        $debito_inicial = floatval($movimientos_inicial['total_debito']);
        $credito_inicial = floatval($movimientos_inicial['total_credito']);
        
        if ($clase == '1') {
            $saldo_inicial = $debito_inicial - $credito_inicial;
        } else {
            $saldo_inicial = $credito_inicial - $debito_inicial;
        }
    }
    
    $saldo = 0;
    if ($clase == '1') {
        $saldo = $debito - $credito;
    } else {
        $saldo = $credito - $debito;
    }
    
    if ($saldo != 0 || $saldo_inicial != 0) {
        $nombre_cuenta = isset($nombres_cuentas[$codigo]) ? $nombres_cuentas[$codigo] : $cuenta['nombre_cuenta'];
        
        $item = [
            'codigo' => $codigo,
            'nombre' => $nombre_cuenta,
            'saldo_inicial' => $saldo_inicial,
            'saldo' => $saldo,
            'nivel' => strlen($codigo)
        ];
        
        if ($clase == '1') {
            $activos[] = $item;
            $totalActivos += $saldo;
            $totalSaldoInicialActivos += $saldo_inicial;
        } elseif ($clase == '2') {
            $pasivos[] = $item;
            $totalPasivos += $saldo;
            $totalSaldoInicialPasivos += $saldo_inicial;
        } elseif ($clase == '3') {
            $patrimonios[] = $item;
            $totalPatrimonios += $saldo;
            $totalSaldoInicialPatrimonios += $saldo_inicial;
        }
        
        $cuentas_procesadas[] = $codigo;
    }
}

// ================== AGREGAR AGRUPACIONES ==================
function agregarAgrupaciones(&$array_cuentas, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial = false) {
    $agrupaciones = [];
    $niveles_validos = [1, 2, 4, 6, 8, 10];
    
    // Crear todas las agrupaciones necesarias
    foreach ($array_cuentas as $cuenta) {
        $codigo = $cuenta['codigo'];
        $longitud_actual = strlen($codigo);
        
        foreach ($niveles_validos as $longitud) {
            if ($longitud < $longitud_actual) {
                $grupo = substr($codigo, 0, $longitud);
                
                if ($grupo != $codigo && !in_array($grupo, $cuentas_procesadas)) {
                    $nombre = isset($nombres_cuentas[$grupo]) ? $nombres_cuentas[$grupo] : 'Grupo ' . $grupo;
                    
                    if (!isset($agrupaciones[$grupo])) {
                        $agrupaciones[$grupo] = [
                            'codigo' => $grupo,
                            'nombre' => $nombre,
                            'saldo_inicial' => 0,
                            'saldo' => 0,
                            'nivel' => strlen($grupo),
                            'es_grupo' => true
                        ];
                        $cuentas_procesadas[] = $grupo;
                    }
                }
            }
        }
    }
    
    // Calcular saldos sumando SOLO los hijos directos (del nivel inmediatamente inferior)
    // Procesar de mayor a menor nivel para acumular correctamente
    foreach (array_reverse($niveles_validos) as $nivel_actual) {
        // Para cada agrupación de este nivel
        foreach ($agrupaciones as $cod_grupo => &$grupo) {
            if ($grupo['nivel'] == $nivel_actual) {
                $nivel_hijo_esperado = null;
                
                // Determinar el nivel del hijo directo esperado
                foreach ($niveles_validos as $nv) {
                    if ($nv > $nivel_actual) {
                        $nivel_hijo_esperado = $nv;
                        break;
                    }
                }
                
                if ($nivel_hijo_esperado) {
                    // Sumar cuentas detalle del nivel hijo
                    foreach ($array_cuentas as $cuenta) {
                        if (strlen($cuenta['codigo']) == $nivel_hijo_esperado && 
                            strpos($cuenta['codigo'], $cod_grupo) === 0) {
                            $grupo['saldo'] += $cuenta['saldo'];
                            if ($mostrar_saldo_inicial) {
                                $grupo['saldo_inicial'] += $cuenta['saldo_inicial'];
                            }
                        }
                    }
                    
                    // Sumar agrupaciones del nivel hijo
                    foreach ($agrupaciones as $cod_hijo => $hijo) {
                        if ($hijo['nivel'] == $nivel_hijo_esperado && 
                            strpos($cod_hijo, $cod_grupo) === 0 && 
                            $cod_hijo != $cod_grupo) {
                            $grupo['saldo'] += $hijo['saldo'];
                            if ($mostrar_saldo_inicial) {
                                $grupo['saldo_inicial'] += $hijo['saldo_inicial'];
                            }
                        }
                    }
                }
            }
        }
        unset($grupo);
    }
    
    $resultado = array_merge(array_values($agrupaciones), $array_cuentas);
    
    usort($resultado, function($a, $b) {
        return strcmp($a['codigo'], $b['codigo']);
    });
    
    return $resultado;
}

// Aplicar agrupaciones a activos y pasivos
$activos = agregarAgrupaciones($activos, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);
$pasivos = agregarAgrupaciones($pasivos, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);

// ================== AGREGAR RESULTADO DEL EJERCICIO ANTES DE AGRUPACIONES ==================
$resultado_ejercicio = obtenerResultadoEjercicio($pdo, $fecha_desde, $fecha_hasta, $tercero);

if ($resultado_ejercicio != 0) {
    $cuenta_resultado = [
        'codigo' => ($resultado_ejercicio >= 0) ? '360501' : '361001',
        'nombre' => ($resultado_ejercicio >= 0) ? 'Utilidad del ejercicio' : 'Pérdida del ejercicio',
        'saldo_inicial' => 0,
        'saldo' => $resultado_ejercicio, // VALOR CON SIGNO (negativo si es pérdida)
        'nivel' => 6,
        'es_resultado' => true
    ];
    
    $patrimonios[] = $cuenta_resultado;
    $totalPatrimonios += $resultado_ejercicio;
    $cuentas_procesadas[] = $cuenta_resultado['codigo'];
}

// Ahora sí aplicar agrupaciones al patrimonio
$patrimonios = agregarAgrupaciones($patrimonios, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);

// ================== CREAR PDF CON FPDF ==================
class PDF extends FPDF {
    private $mostrar_saldo_inicial;
    private $nombre_empresa;
    private $nit_empresa;
    private $fecha_desde;
    private $fecha_hasta;
    
    function __construct($mostrar_saldo_inicial = false, $nombre_empresa = '', $nit_empresa = '', $fecha_desde = '', $fecha_hasta = '') {
        parent::__construct();
        $this->mostrar_saldo_inicial = $mostrar_saldo_inicial;
        $this->nombre_empresa = $nombre_empresa;
        $this->nit_empresa = $nit_empresa;
        $this->fecha_desde = $fecha_desde;
        $this->fecha_hasta = $fecha_hasta;
    }
    
    function Header() {
        // Logo (si existe)
        if (file_exists('assets/img/logo.png')) {
            $this->Image('assets/img/logo.png', 10, 8, 33);
        } elseif (file_exists('./Img/sofilogo5pequeño.png')) {
            $this->Image('./Img/sofilogo5pequeño.png', 10, 8, 33);
        }
        
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, convertText('ESTADO DE SITUACIÓN FINANCIERA'), 0, 1, 'C');
        
        // Información de la empresa centrada (MEJORA SOLICITADA)
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, convertText('NOMBRE DE LA EMPRESA: ') . convertText($this->nombre_empresa), 0, 1, 'C');
        $this->Cell(0, 6, convertText('NIT DE LA EMPRESA: ') . $this->nit_empresa, 0, 1, 'C');
        
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, convertText('PERÍODO: ') . date('d/m/Y', strtotime($this->fecha_desde)) . ' - ' . date('d/m/Y', strtotime($this->fecha_hasta)), 0, 1, 'C');
        
        $this->SetLineWidth(0.5);
        $this->Line(10, 40, 200, 40);
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, convertText('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    function ChapterTitle($title) {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(0, 8, convertText($title), 0, 1, 'L', true);
        $this->Ln(2);
    }
    
    function TableHeader() {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(5, 74, 133);
        $this->SetTextColor(255, 255, 255);
        
        if ($this->mostrar_saldo_inicial) {
            $this->Cell(30, 8, convertText('Código'), 1, 0, 'C', true);
            $this->Cell(80, 8, convertText('Nombre de la cuenta'), 1, 0, 'C', true);
            $this->Cell(40, 8, convertText('Saldo Inicial'), 1, 0, 'C', true);
            $this->Cell(40, 8, convertText('Saldo'), 1, 1, 'C', true);
        } else {
            $this->Cell(30, 8, convertText('Código'), 1, 0, 'C', true);
            $this->Cell(100, 8, convertText('Nombre de la cuenta'), 1, 0, 'C', true);
            $this->Cell(60, 8, convertText('Saldo'), 1, 1, 'C', true);
        }
    }
    
    function TableRow($fila, $fill) {
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0, 0, 0);
        
        $font_style = '';
        $indent = ($fila['nivel'] - 1) * 3;
        
        if ($fila['nivel'] <= 2) {
            $font_style = 'B';
            $this->SetFillColor(230, 240, 255);
        } elseif ($fila['nivel'] <= 4) {
            $font_style = '';
            $this->SetFillColor(245, 245, 245);
        } else {
            $font_style = '';
            $this->SetFillColor(255, 255, 255);
        }
        
        $this->SetFont('Arial', $font_style, 9);
        
        // Texto indentado
        $nombre_texto = convertText(str_repeat('  ', $indent) . $fila['nombre']);
        
        if ($this->mostrar_saldo_inicial) {
            // Anchos de columna
            $ancho_codigo = 30;
            $ancho_nombre = 80;
            $ancho_saldo_inicial = 40;
            $ancho_saldo = 40;
            
            // Dividir el nombre en líneas que caben en el ancho disponible
            $nombre_largo = $this->GetStringWidth($nombre_texto);
            $max_ancho_nombre = $ancho_nombre - 1; // Margen pequeño
            
            if ($nombre_largo > $max_ancho_nombre) {
                // Dividir el texto en múltiples líneas
                $lineas = [];
                $palabras = explode(' ', $nombre_texto);
                $linea_actual = '';
                
                foreach ($palabras as $palabra) {
                    $prueba_linea = $linea_actual . ($linea_actual ? ' ' : '') . $palabra;
                    if ($this->GetStringWidth($prueba_linea) <= $max_ancho_nombre) {
                        $linea_actual = $prueba_linea;
                    } else {
                        if ($linea_actual) {
                            $lineas[] = $linea_actual;
                        }
                        $linea_actual = $palabra;
                    }
                }
                if ($linea_actual) {
                    $lineas[] = $linea_actual;
                }
                
                $num_lineas = count($lineas);
                
                // Imprimir cada línea con bordes apropiados
                for ($i = 0; $i < $num_lineas; $i++) {
                    // Definir bordes para cada línea
                    $borde_izquierdo = ($i == 0) ? 'LTR' : 'LR';
                    $borde_nombre = ($i == 0) ? 'TR' : 'R';
                    $borde_saldo_inicial = ($i == 0) ? 'TR' : 'R';
                    $borde_saldo = ($i == 0) ? 'TR' : 'R';
                    $borde_derecho_final = ($i == 0 && $num_lineas == 1) ? 'R' : 'R';
                    
                    // Si es la última línea, agregar borde inferior
                    if ($i == $num_lineas - 1) {
                        $borde_izquierdo = ($i == 0) ? 'LTRB' : 'LRB';
                        $borde_nombre = ($i == 0) ? 'TRB' : 'RB';
                        $borde_saldo_inicial = ($i == 0) ? 'TRB' : 'RB';
                        $borde_saldo = ($i == 0) ? 'TRB' : 'RB';
                    }
                    
                    // Si es la primera línea, mostrar código, saldo inicial y saldo
                    if ($i == 0) {
                        $this->Cell($ancho_codigo, 7, $fila['codigo'], $borde_izquierdo, 0, 'L', $fill);
                        $this->Cell($ancho_nombre, 7, $lineas[$i], $borde_nombre, 0, 'L', $fill);
                        $this->Cell($ancho_saldo_inicial, 7, number_format($fila['saldo_inicial'], 2, ',', '.'), $borde_saldo_inicial, 0, 'R', $fill);
                        $this->Cell($ancho_saldo, 7, number_format($fila['saldo'], 2, ',', '.'), $borde_saldo, 1, 'R', $fill);
                    } else {
                        // Líneas adicionales: código en blanco, solo el texto, saldos en blanco
                        $this->Cell($ancho_codigo, 7, '', $borde_izquierdo, 0, 'L', $fill);
                        $this->Cell($ancho_nombre, 7, $lineas[$i], $borde_nombre, 0, 'L', $fill);
                        $this->Cell($ancho_saldo_inicial, 7, '', $borde_saldo_inicial, 0, 'R', $fill);
                        $this->Cell($ancho_saldo, 7, '', $borde_saldo, 1, 'R', $fill);
                    }
                }
            } else {
                // Si el nombre cabe en una línea
                $this->Cell($ancho_codigo, 7, $fila['codigo'], 1, 0, 'L', $fill);
                $this->Cell($ancho_nombre, 7, $nombre_texto, 1, 0, 'L', $fill);
                $this->Cell($ancho_saldo_inicial, 7, number_format($fila['saldo_inicial'], 2, ',', '.'), 1, 0, 'R', $fill);
                $this->Cell($ancho_saldo, 7, number_format($fila['saldo'], 2, ',', '.'), 1, 1, 'R', $fill);
            }
            
        } else {
            // Anchos de columna sin saldo inicial
            $ancho_codigo = 30;
            $ancho_nombre = 100;
            $ancho_saldo = 60;
            
            // Dividir el nombre en líneas que caben en el ancho disponible
            $nombre_largo = $this->GetStringWidth($nombre_texto);
            $max_ancho_nombre = $ancho_nombre - 1; // Margen pequeño
            
            if ($nombre_largo > $max_ancho_nombre) {
                // Dividir el texto en múltiples líneas
                $lineas = [];
                $palabras = explode(' ', $nombre_texto);
                $linea_actual = '';
                
                foreach ($palabras as $palabra) {
                    $prueba_linea = $linea_actual . ($linea_actual ? ' ' : '') . $palabra;
                    if ($this->GetStringWidth($prueba_linea) <= $max_ancho_nombre) {
                        $linea_actual = $prueba_linea;
                    } else {
                        if ($linea_actual) {
                            $lineas[] = $linea_actual;
                        }
                        $linea_actual = $palabra;
                    }
                }
                if ($linea_actual) {
                    $lineas[] = $linea_actual;
                }
                
                $num_lineas = count($lineas);
                
                // Imprimir cada línea con bordes apropiados
                for ($i = 0; $i < $num_lineas; $i++) {
                    // Definir bordes para cada línea
                    $borde_izquierdo = ($i == 0) ? 'LTR' : 'LR';
                    $borde_nombre = ($i == 0) ? 'TR' : 'R';
                    $borde_saldo = ($i == 0) ? 'TR' : 'R';
                    
                    // Si es la última línea, agregar borde inferior
                    if ($i == $num_lineas - 1) {
                        $borde_izquierdo = ($i == 0) ? 'LTRB' : 'LRB';
                        $borde_nombre = ($i == 0) ? 'TRB' : 'RB';
                        $borde_saldo = ($i == 0) ? 'TRB' : 'RB';
                    }
                    
                    // Si es la primera línea, mostrar código y saldo
                    if ($i == 0) {
                        $this->Cell($ancho_codigo, 7, $fila['codigo'], $borde_izquierdo, 0, 'L', $fill);
                        $this->Cell($ancho_nombre, 7, $lineas[$i], $borde_nombre, 0, 'L', $fill);
                        $this->Cell($ancho_saldo, 7, number_format($fila['saldo'], 2, ',', '.'), $borde_saldo, 1, 'R', $fill);
                    } else {
                        // Líneas adicionales: código en blanco, solo el texto, saldo en blanco
                        $this->Cell($ancho_codigo, 7, '', $borde_izquierdo, 0, 'L', $fill);
                        $this->Cell($ancho_nombre, 7, $lineas[$i], $borde_nombre, 0, 'L', $fill);
                        $this->Cell($ancho_saldo, 7, '', $borde_saldo, 1, 'R', $fill);
                    }
                }
            } else {
                // Si el nombre cabe en una línea
                $this->Cell($ancho_codigo, 7, $fila['codigo'], 1, 0, 'L', $fill);
                $this->Cell($ancho_nombre, 7, $nombre_texto, 1, 0, 'L', $fill);
                $this->Cell($ancho_saldo, 7, number_format($fila['saldo'], 2, ',', '.'), 1, 1, 'R', $fill);
            }
        }
        
        // Restaurar color de texto
        $this->SetTextColor(0, 0, 0);
    }
    
    function TableTotal($label, $total_saldo_inicial, $total) {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(220, 220, 220);
        
        if ($this->mostrar_saldo_inicial) {
            $this->Cell(110, 8, convertText($label), 1, 0, 'R', true);
            $this->Cell(40, 8, number_format($total_saldo_inicial, 2, ',', '.'), 1, 0, 'R', true);
            $this->Cell(40, 8, number_format($total, 2, ',', '.'), 1, 1, 'R', true);
        } else {
            $this->Cell(130, 8, convertText($label), 1, 0, 'R', true);
            $this->Cell(60, 8, number_format($total, 2, ',', '.'), 1, 1, 'R', true);
        }
    }
}

// Crear instancia del PDF con datos de la empresa
$pdf = new PDF($mostrar_saldo_inicial, $nombre_empresa, $nit_empresa, $fecha_desde, $fecha_hasta);
$pdf->AliasNbPages();
$pdf->AddPage();

// ACTIVOS
$pdf->ChapterTitle('ACTIVOS');
$pdf->TableHeader();

$fill = false;
foreach ($activos as $fila) {
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $pdf->ChapterTitle('ACTIVOS (continuación)');
        $pdf->TableHeader();
        $fill = false;
    }
    
    $pdf->TableRow($fila, $fill);
    $fill = !$fill;
}

$pdf->TableTotal('TOTAL ACTIVOS', $totalSaldoInicialActivos, $totalActivos);
$pdf->Ln(8);

// PASIVOS
if ($pdf->GetY() > 200) {
    $pdf->AddPage();
}

$pdf->ChapterTitle('PASIVOS');
$pdf->TableHeader();

$fill = false;
foreach ($pasivos as $fila) {
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $pdf->ChapterTitle('PASIVOS (continuación)');
        $pdf->TableHeader();
        $fill = false;
    }
    
    $pdf->TableRow($fila, $fill);
    $fill = !$fill;
}

$pdf->TableTotal('TOTAL PASIVOS', $totalSaldoInicialPasivos, $totalPasivos);
$pdf->Ln(8);

// PATRIMONIO
if ($pdf->GetY() > 200) {
    $pdf->AddPage();
}

$pdf->ChapterTitle('PATRIMONIO');
$pdf->TableHeader();

$fill = false;
foreach ($patrimonios as $fila) {
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $pdf->ChapterTitle('PATRIMONIO (continuación)');
        $pdf->TableHeader();
        $fill = false;
    }
    
    $pdf->TableRow($fila, $fill);
    $fill = !$fill;
}

$pdf->TableTotal('TOTAL PATRIMONIO', $totalSaldoInicialPatrimonios, $totalPatrimonios);
$pdf->Ln(10);

// EQUILIBRIO CONTABLE
$total_pasivo_patrimonio = $totalPasivos + $totalPatrimonios;
$diferencia = $totalActivos - $total_pasivo_patrimonio;
$esta_equilibrado = abs($diferencia) < 0.01;

// MEJORA: Fuente más pequeña para que quepa en la línea
$pdf->SetFont('Arial', 'B', 9); // Reducido de 12 a 9

if ($esta_equilibrado) {
    // MEJORA: Color acorde (no verde que sobresale)
    $pdf->SetFillColor(220, 240, 255); // Azul claro en lugar de verde
    $equilibrio_texto = 'ACTIVOS = PASIVOS + PATRIMONIO';
} else {
    $pdf->SetFillColor(255, 220, 220);
    $equilibrio_texto = 'DESEQUILIBRIO CONTABLE';
}

// MEJORA: Texto más corto para mejor visualización
$texto_corto = $esta_equilibrado ? 'ACTIVOS = PASIVOS + PATRIMONIO' : 'DESEQUILIBRIO';

if ($mostrar_saldo_inicial) {
    $pdf->Cell(95, 8, convertText($texto_corto), 1, 0, 'C', true);
    $pdf->SetFont('Arial', '', 8); // Fuente aún más pequeña para números
    $pdf->Cell(45, 8, number_format($totalSaldoInicialActivos, 2, ',', '.') . ' = ' . 
                number_format($totalSaldoInicialPasivos + $totalSaldoInicialPatrimonios, 2, ',', '.'), 1, 0, 'C', true);
    $pdf->Cell(50, 8, number_format($totalActivos, 2, ',', '.') . ' = ' . 
                number_format($total_pasivo_patrimonio, 2, ',', '.'), 1, 1, 'C', true);
} else {
    $pdf->Cell(100, 8, convertText($texto_corto), 1, 0, 'C', true);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(90, 8, number_format($totalActivos, 2, ',', '.') . ' = ' . 
                number_format($total_pasivo_patrimonio, 2, ',', '.'), 1, 1, 'C', true);
}

if (!$esta_equilibrado) {
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(0, 6, convertText('Diferencia: ') . number_format($diferencia, 2, ',', '.'), 0, 1, 'C');
}

$pdf->Ln(15);

// INFORMACIÓN ADICIONAL (MEJORA: añadir pie de página con información)
$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 5, convertText('Información del Reporte:'), 0, 1, 'L', true);
$pdf->Cell(0, 4, convertText('Generado el: ').date('Y-m-d H:i:s'), 0, 1, 'L');
$pdf->Cell(0, 4, convertText('Período fiscal: ').date('Y', strtotime($fecha_desde)), 0, 1, 'L');

// Salida del PDF
$pdf->Output('I', 'Estado_Situacion_Financiera_' . date('Y-m-d') . '.pdf');
?>