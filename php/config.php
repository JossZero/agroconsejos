<?php
class Config {
    // Base de datos
    const DB_HOST = 'localhost';
    const DB_USER = 'root';
    const DB_PASS = '12345';
    const DB_NAME = 'agroconsejos';
    const DB_PORT = 3306;

    
    // Encriptación
    const ENCRYPTION_KEY = 'AgroConsejos_2024_Sistema_Seguro_Clave_Secreta_Muy_Larga_Aqui';
    
    // Correo electrónico (Gmail) - CORREGIDO
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USER = 'diegokuevase16@gmail.com';
    const SMTP_PASS = 'dnbscipzpthqrmuk';  // ← CONTRASEÑA COMPLETA
    const SMTP_FROM = 'diegokuevase16@gmail.com';  // ← CORREO CORRECTO
    const SMTP_FROM_NAME = 'AgroConsejos';
    
    // Rutas del sistema
    const BACKUP_PATH = __DIR__ . '/../backups/';
    const TEMP_PATH = __DIR__ . '/../temp/';
    
    // Configuración de respaldos
    const MYSQLDUMP_PATH = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    const MAX_BACKUP_SIZE = 500; // MB
    const BACKUP_RETENTION_DAYS = 30; // Días para mantener respaldos
    
    // Seguridad
    const MAX_LOGIN_ATTEMPTS = 5;
    const SESSION_TIMEOUT = 3600; // 1 hora en segundos
}

// Función para verificar requisitos del sistema
function verificarRequisitosSistema() {
    $errores = [];
    
    // Verificar extensiones PHP necesarias
    $extensiones_requeridas = ['mysqli', 'openssl', 'zip', 'json'];
    foreach ($extensiones_requeridas as $ext) {
        if (!extension_loaded($ext)) {
            $errores[] = "Extensión PHP requerida: $ext";
        }
    }
    
    // Verificar permisos de directorios
    if (!is_writable(Config::BACKUP_PATH)) {
        $errores[] = "Directorio de backups no tiene permisos de escritura";
    }
    
    if (!is_writable(Config::TEMP_PATH)) {
        $errores[] = "Directorio temporal no tiene permisos de escritura";
    }
    
    return $errores;
}

// Función para obtener información del sistema
function obtenerInfoSistema() {
    return [
        'php_version' => PHP_VERSION,
        'mysql_version' => obtenerVersionMySQL(),
        'sistema_operativo' => PHP_OS,
        'memoria_limite' => ini_get('memory_limit'),
        'tiempo_maximo_ejecucion' => ini_get('max_execution_time'),
        'tamano_maximo_upload' => ini_get('upload_max_filesize')
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
