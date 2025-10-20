<?php
class Config {
    // --- Base de datos ---
    const DB_HOST = 'localhost';
    const DB_USER = 'agroapp';
    const DB_PASS = '12345';
    const DB_NAME = 'agroconsejos';
    const DB_PORT = 3306;

    // --- Encriptación ---
    const ENCRYPTION_KEY = 'AgroConsejos_2024_Sistema_Seguro_Clave_Secreta_Muy_Larga_Aqui';
    
    // --- Correo electrónico (Gmail) ---
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USER = 'diegokuevase16@gmail.com';
    const SMTP_PASS = 'dnbscipzpthqrmuk';
    const SMTP_FROM = 'diegokuevase16@gmail.com';
    const SMTP_FROM_NAME = 'AgroConsejos';
    
    // --- Rutas del sistema ---
    const BACKUP_PATH = __DIR__ . '/../backups/';
    const TEMP_PATH = __DIR__ . '/../temp/';
    
    // --- Configuración de respaldos ---
    const MAX_BACKUP_SIZE = 500; // MB
    const BACKUP_RETENTION_DAYS = 30; // Días para mantener respaldos

    // Detecta automáticamente el sistema operativo y asigna la ruta correcta de mysqldump
    public static function getMysqldumpPath() {
        $os = strtoupper(substr(PHP_OS, 0, 3));
        if ($os === 'WIN') {
            return 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        } else {
            return '/usr/bin/mysqldump';
        }
    }
    
    // --- Seguridad ---
    const MAX_LOGIN_ATTEMPTS = 5;
    const SESSION_TIMEOUT = 3600; // 1 hora en segundos
}

// --- Funciones del sistema ---
function verificarRequisitosSistema() {
    $errores = [];

    $extensiones_requeridas = ['mysqli', 'openssl', 'zip', 'json'];
    foreach ($extensiones_requeridas as $ext) {
        if (!extension_loaded($ext)) {
            $errores[] = "Extensión PHP requerida: $ext";
        }
    }

    if (!is_writable(Config::BACKUP_PATH)) {
        $errores[] = "El directorio de backups no tiene permisos de escritura.";
    }
    if (!is_writable(Config::TEMP_PATH)) {
        $errores[] = "El directorio temporal no tiene permisos de escritura.";
    }

    $mysqldumpPath = Config::getMysqldumpPath();
    if (!file_exists($mysqldumpPath)) {
        $errores[] = "No se encontró mysqldump en la ruta esperada: $mysqldumpPath";
    }

    return $errores;
}

function obtenerInfoSistema() {
    return [
        'php_version' => PHP_VERSION,
        'mysql_version' => obtenerVersionMySQL(),
        'sistema_operativo' => PHP_OS,
        'memoria_limite' => ini_get('memory_limit'),
        'tiempo_maximo_ejecucion' => ini_get('max_execution_time'),
        'tamano_maximo_upload' => ini_get('upload_max_filesize'),
        'ruta_mysqldump' => Config::getMysqldumpPath()
    ];
}

function obtenerVersionMySQL() {
    $conexion = new mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASS);
    if (!$conexion->connect_error) {
        $version = $conexion->server_version;
        $conexion->close();
        return $version;
    }
    return 'Desconocida';
}
?>
