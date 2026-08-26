<?php
require_once '../config/database.php';
require_once '../classes/CentroNotificaciones.php';

header('Content-Type: application/json');

try {
    $pdo = Database::getConnection();

    // Umbral de "próxima a vencer": 5 días. Umbral de stock bajo: 5 unidades.
    $centro = new CentroNotificaciones($pdo, 5, 5);

    $accion = $_POST['accion'] ?? $_GET['accion'] ?? 'listar';

    switch ($accion) {

        case 'marcarLeida':
            $key = $_POST['key'] ?? '';
            if ($key === '') {
                echo json_encode(['success' => false, 'error' => 'Falta el parámetro key']);
                exit;
            }
            $centro->marcarComoLeida($key);
            echo json_encode(['success' => true]);
            break;

        case 'marcarTodas':
            $total = $centro->marcarTodasComoLeidas();
            echo json_encode(['success' => true, 'marcadas' => $total]);
            break;

        case 'listar':
        default:
            $notificaciones = $centro->obtenerTodas();
            $noLeidas = $centro->contarNoLeidas($notificaciones);

            echo json_encode([
                'success' => true,
                'total' => count($notificaciones),
                'no_leidas' => $noLeidas,
                'notificaciones' => $notificaciones
            ]);
            break;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}