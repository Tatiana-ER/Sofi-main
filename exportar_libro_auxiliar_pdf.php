<?php
// ================== EXPORTAR LIBRO AUXILIAR A PDF ==================
require('libs/fpdf/fpdf.php');

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
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-12-31');
$cuenta_codigo = isset($_GET['cuenta']) ? $_GET['cuenta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';

// ================== FUNCIÓN HELPER PARA CONVERTIR TEXTO ==================
function convertir_texto($texto) {
    return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
}

// ================== OBTENER CUENTAS ==================
if ($cuenta_codigo != '') {
    $sql_cuentas = "SELECT codigo_cuenta, MIN(nombre_cuenta) as nombre_cuenta
                    FROM libro_diario
                    WHERE codigo_cuenta = :cuenta
                    GROUP BY codigo_cuenta
                    ORDER BY codigo_cuenta";
    $stmt_cuentas = $pdo->prepare($sql_cuentas);
    $stmt_cuentas->execute([':cuenta' => $cuenta_codigo]);
} else {
    $sql_cuentas = "SELECT codigo_cuenta, MIN(nombre_cuenta) as nombre_cuenta
                    FROM libro_diario
                    WHERE fecha BETWEEN :desde AND :hasta
                    GROUP BY codigo_cuenta
                    ORDER BY codigo_cuenta";
    $stmt_cuentas = $pdo->prepare($sql_cuentas);
    $stmt_cuentas->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
}
$cuentas = $stmt_cuentas->fetchAll(PDO::FETCH_ASSOC);

// ================== FUNCIÓN PARA OBTENER MOVIMIENTOS ==================
function obtenerMovimientosCuenta($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
    $naturaleza = substr($codigo_cuenta, 0, 1);

    // Saldo acumulado anterior al periodo
    $sql_saldo = "SELECT 
                    COALESCE(SUM(debito),0) as suma_debito_prev,
                    COALESCE(SUM(credito),0) as suma_credito_prev
                  FROM libro_diario
                  WHERE codigo_cuenta = :cuenta AND fecha < :desde";
    $stmt_saldo = $pdo->prepare($sql_saldo);
    $stmt_saldo->execute([':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde]);
    $row = $stmt_saldo->fetch(PDO::FETCH_ASSOC);

    $deb_prev = floatval($row['suma_debito_prev']);
    $cred_prev = floatval($row['suma_credito_prev']);

    if (in_array($naturaleza, ['1','5','6','7'])) {
        $saldo_inicial = $deb_prev - $cred_prev;
    } else {
        $saldo_inicial = $cred_prev - $deb_prev;
    }

    if ($saldo_inicial < 0 && strpos($codigo_cuenta, '2408') === false) {
        $saldo_inicial = 0;
    }

    // Obtener movimientos del período
    $sql_mov = "SELECT * FROM libro_diario
                WHERE codigo_cuenta = :cuenta
                  AND fecha BETWEEN :desde AND :hasta";
    $params = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_mov .= " AND tercero_identificacion = :tercero";
        $params[':tercero'] = $tercero;
    }
    
    $sql_mov .= " ORDER BY fecha ASC, id ASC";
    $stmt_mov = $pdo->prepare($sql_mov);
    $stmt_mov->execute($params);
    $movimientos = $stmt_mov->fetchAll(PDO::FETCH_ASSOC);

    $saldo = $saldo_inicial;
    foreach ($movimientos as $k => $m) {
        $debito = floatval($m['debito']);
        $credito = floatval($m['credito']);

        $movimientos[$k]['saldo_inicial_fila'] = $saldo;

        if (in_array($naturaleza, ['1','5','6','7'])) {
            $saldo += ($debito - $credito);
        } else {
            $saldo += ($credito - $debito);
        }

        if ($saldo < 0 && strpos($codigo_cuenta, '2408') === false) {
            $saldo = 0;
        }

        $movimientos[$k]['saldo_final_fila'] = $saldo;
    }

    return [
        'saldo_inicial' => $saldo_inicial,
        'movimientos' => $movimientos,
        'saldo_final_cuenta' => $saldo
    ];
}

// ================== GENERAR PDF ==================
class PDF extends FPDF {
    private $fecha_desde;
    private $fecha_hasta;
    private $cuenta_codigo;
    private $tercero;
    private $nombre_empresa;
    private $nit_empresa;
    
    function __construct($desde, $hasta, $cuenta, $terc, $nombre_emp, $nit_emp) {
        parent::__construct('L','mm','A4');
        $this->fecha_desde = $desde;
        $this->fecha_hasta = $hasta;
        $this->cuenta_codigo = $cuenta;
        $this->tercero = $terc;
        $this->nombre_empresa = $nombre_emp;
        $this->nit_empresa = $nit_emp;
    }
    
    function Header() {
        // Logo (si existe)
        if (file_exists('assets/img/logo.png')) {
            $this->Image('assets/img/logo.png', 10, 8, 33);
        }
        
        $this->SetFont('Arial','B',14);
        $this->Cell(0,8,convertir_texto('LIBRO AUXILIAR'),0,1,'C');
        
        // Información de la empresa centrada
        $this->SetFont('Arial','B',10);
        $this->Cell(0,6,convertir_texto('') . convertir_texto($this->nombre_empresa),0,1,'C');
        $this->Cell(0,6,convertir_texto('') . $this->nit_empresa,0,1,'C');
        
        $this->SetFont('Arial','',9);
        $this->Cell(0,6,convertir_texto('PERIODO: ') . date('d/m/Y', strtotime($this->fecha_desde)) . ' A ' . date('d/m/Y', strtotime($this->fecha_hasta)),0,1,'C');
        
        if ($this->cuenta_codigo != '') {
            $this->Cell(0,6,convertir_texto('CUENTA: ') . $this->cuenta_codigo,0,1,'C');
        }
        if ($this->tercero != '') {
            $this->Cell(0,6,convertir_texto('TERCERO: ') . $this->tercero,0,1,'C');
        }
        
        $this->Ln(5);
        
        // Encabezados de columnas - AJUSTAR ANCHOS
        $this->SetFont('Arial','B',7);
        $this->SetFillColor(5,74,133);
        $this->SetTextColor(255,255,255);
        
        // Ajustar anchos para que sumen 280mm (ancho A4 landscape)
        $this->Cell(18,6,convertir_texto('Código'),1,0,'C',true);           // 18
        $this->Cell(35,6,convertir_texto('Nombre Cuenta'),1,0,'C',true);    // 35
        $this->Cell(18,6,convertir_texto('ID Tercero'),1,0,'C',true);       // 18
        $this->Cell(32,6,convertir_texto('Nombre Tercero'),1,0,'C',true);   // 32
        $this->Cell(15,6,convertir_texto('Fecha'),1,0,'C',true);            // 15
        $this->Cell(28,6,convertir_texto('Comprobante'),1,0,'C',true);      // 28
        $this->Cell(28,6,convertir_texto('Concepto'),1,0,'C',true);         // 28
        $this->Cell(22,6,convertir_texto('Saldo Inicial'),1,0,'C',true);    // 22
        $this->Cell(22,6,convertir_texto('Débito'),1,0,'C',true);           // 22
        $this->Cell(22,6,convertir_texto('Crédito'),1,0,'C',true);          // 22
        $this->Cell(20,6,convertir_texto('Saldo Final'),1,1,'C',true);      // 20
        
        $this->SetTextColor(0,0,0);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,convertir_texto('Página ').$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF($fecha_desde, $fecha_hasta, $cuenta_codigo, $tercero, $nombre_empresa, $nit_empresa);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',6);

$total_debito = 0;
$total_credito = 0;
$total_saldo_final = 0;
$ultimo_saldo_final = 0;
$total_movimientos = 0;

if (count($cuentas) == 0) {
    $pdf->SetFont('Arial','I',9);
    $pdf->Cell(0,10,convertir_texto('No se encontraron movimientos en el período seleccionado.'),0,1,'C');
} else {
    foreach ($cuentas as $cuenta) {
        $datos = obtenerMovimientosCuenta($pdo, $cuenta['codigo_cuenta'], $fecha_desde, $fecha_hasta, $tercero);

        if (count($datos['movimientos']) > 0) {
            foreach ($datos['movimientos'] as $mov) {
                $total_debito += floatval($mov['debito']);
                $total_credito += floatval($mov['credito']);
                $ultimo_saldo_final = floatval($mov['saldo_final_fila']);
                $total_movimientos++;
                
                // Separar identificación y nombre
                $tercero_id = $mov['tercero_identificacion'] ?? '';
                $tercero_nombre = $mov['tercero_nombre'] ?? '';
                
                if (empty($tercero_nombre) && strpos($tercero_id, ' - ') !== false) {
                    $partes = explode(' - ', $tercero_id, 2);
                    $tercero_id = trim($partes[0]);
                    $tercero_nombre = trim($partes[1]);
                } elseif (empty($tercero_nombre) && strpos($tercero_id, '-') !== false) {
                    $partes = explode('-', $tercero_id, 2);
                    $tercero_id = trim($partes[0]);
                    $tercero_nombre = trim($partes[1]);
                }
                
                // Formato del comprobante
                $tipo_comp = '';
                switch ($mov['tipo_documento']) {
                    case 'factura_venta': $tipo_comp = 'FAC.VTA.'; break;
                    case 'factura_compra': $tipo_comp = 'FRA.COMP.'; break;
                    case 'recibo_caja': $tipo_comp = 'REC.CAJA'; break;
                    case 'comprobante_egreso': $tipo_comp = 'COMP.EGR.'; break;
                    case 'comprobante_contable': $tipo_comp = 'COMP.CONT.'; break;
                    default: $tipo_comp = strtoupper($mov['tipo_documento']);
                }
                $comprobante = $tipo_comp . $mov['numero_documento'];

                $pdf->Cell(18,5,convertir_texto(substr($cuenta['codigo_cuenta'], 0, 10)),1,0,'L');
                $pdf->Cell(35,5,convertir_texto(substr($cuenta['nombre_cuenta'], 0, 22)),1,0,'L');
                $pdf->Cell(18,5,convertir_texto(substr($tercero_id, 0, 12)),1,0,'L');
                $pdf->Cell(32,5,convertir_texto(substr($tercero_nombre, 0, 20)),1,0,'L');
                $pdf->Cell(15,5,date('d/m/Y', strtotime($mov['fecha'])),1,0,'C');
                $pdf->Cell(28,5,convertir_texto(substr($comprobante, 0, 18)),1,0,'L');
                $pdf->Cell(28,5,convertir_texto(substr($mov['concepto'], 0, 20)),1,0,'L');
                $pdf->Cell(22,5,number_format($mov['saldo_inicial_fila'], 2, '.', ','),1,0,'R');
                $pdf->Cell(22,5,$mov['debito'] > 0 ? number_format($mov['debito'], 2, '.', ',') : '',1,0,'R');
                $pdf->Cell(22,5,$mov['credito'] > 0 ? number_format($mov['credito'], 2, '.', ',') : '',1,0,'R');
                $pdf->Cell(20,5,number_format($mov['saldo_final_fila'], 2, '.', ','),1,1,'R'); // Ajustado a 20
            }
            // Sumar el último saldo final de cada cuenta
            $total_saldo_final += $ultimo_saldo_final;
        }
    }
    
    // Totales - CON los 3 totales - AJUSTAR ANCHOS
    $pdf->SetFont('Arial','B',7);
    $pdf->SetFillColor(217,225,242);
    
    // Suma de las primeras 7 columnas: 18+35+18+32+15+28+28 = 174
    $pdf->Cell(174,6,convertir_texto('TOTALES:'),1,0,'R',true);
    $pdf->Cell(22,6,'',1,0,'R',true); // Saldo inicial vacío (22)
    $pdf->Cell(22,6,number_format($total_debito, 2, '.', ','),1,0,'R',true); // Débito (22)
    $pdf->Cell(22,6,number_format($total_credito, 2, '.', ','),1,0,'R',true); // Crédito (22)
    $pdf->Cell(20,6,number_format($total_saldo_final, 2, '.', ','),1,1,'R',true); // Saldo final (20)
}

// INFORMACIÓN ADICIONAL
$pdf->Ln(5);
$pdf->SetFont('Arial','I',8);
$pdf->SetFillColor(240,240,240);
$pdf->Cell(0,5,convertir_texto('Información del Reporte:'),0,1,'L',true);
$pdf->Cell(0,4,convertir_texto('Generado el: ').date('Y-m-d H:i:s'),0,1,'L');
$pdf->Cell(0,4,convertir_texto('Total de movimientos: ').$total_movimientos,0,1,'L');

$pdf->Output('I', 'Libro_Auxiliar_' . date('Ymd_His') . '.pdf');