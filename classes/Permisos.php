<?php
class Permisos
{
    public static function cargar(PDO $pdo, int $rolId): array
    {
        if ($rolId == 1) return ['__admin__' => true]; // admin: acceso total

        $sql = "SELECT m.clave, p.puede_ver, p.puede_editar
                FROM permisos_rol p
                INNER JOIN modulos m ON m.id = p.modulo_id
                WHERE p.rol_id = :rol_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':rol_id' => $rolId]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $fila) {
            $mapa[$fila['clave']] = [
                'ver'    => (bool)$fila['puede_ver'],
                'editar' => (bool)$fila['puede_editar'],
            ];
        }
        return $mapa;
    }

    public static function puede(string $clave, string $accion = 'ver'): bool
    {
        $permisos = $_SESSION['permisos'] ?? [];
        if (isset($permisos['__admin__'])) return true;
        return $permisos[$clave][$accion] ?? false;
    }

    // Usar al inicio de cada vista de módulo
    public static function exigirVer(string $clave): void
    {
        if (!self::puede($clave, 'ver')) {
            header('Location: ' . self::rutaDashboard());
            exit;
        }
    }

    // Usar al inicio de cada endpoint ajax/*.php que guarda datos
    public static function exigirEditar(string $clave): void
    {
        if (!self::puede($clave, 'editar')) {
            http_response_code(403);
            die('No tienes permiso para editar este módulo.');
        }
    }

    private static function rutaDashboard(): string
    {
        $profundidad = substr_count($_SERVER['SCRIPT_NAME'], '/') - 1;
        return str_repeat('../', max(0, $profundidad - 1)) . 'dashboard.php';
    }
}