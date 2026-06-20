<?php
// ============================================================
//  config/database.php
//  Conexión única y centralizada a la base de datos (PDO)
//  Uso:  require_once __DIR__ . '/../config/database.php';
//        $pdo = Database::getConnection();
// ============================================================

class Database
{
    // ── Datos de conexión ────────────────────────────────────
    private static string $host     = 'localhost';
    private static string $dbname   = 'sofi';
    private static string $username = 'root';
    private static string $password = '';
    private static string $charset  = 'utf8mb4';

    // Instancia única (patrón Singleton: una sola conexión por petición)
    private static ?PDO $instance = null;

    // Constructor privado: nadie puede hacer "new Database()"
    private function __construct() {}

    /**
     * Devuelve siempre la misma conexión PDO.
     * Si aún no existe, la crea.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . self::$host
                 . ";dbname="   . self::$dbname
                 . ";charset="  . self::$charset;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lanza excepciones en errores
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Resultados como array asociativo
                PDO::ATTR_EMULATE_PREPARES   => false,                   // Prepared statements reales
            ];

            try {
                self::$instance = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // En producción podrías registrar el error en un log en vez de mostrarlo
                error_log("Error de conexión: " . $e->getMessage());
                die("No se pudo conectar a la base de datos. Intenta más tarde.");
            }
        }

        return self::$instance;
    }
}
