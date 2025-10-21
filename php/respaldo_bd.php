<?php
// ============================
// 🌿 AGROCONSEJOS - RESPALDO BD (Multiusuario Web)
// ============================

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$rutaConexion = __DIR__ . '/conexion.php';
if (!file_exists($rutaConexion)) {
    echo json_encode(['success' => false, 'message' => 'Error: Archivo de conexión no encontrado']);
    exit;
}
require_once $rutaConexion;

if (!file_exists(__DIR__ . '/config.php')) {
    echo json_encode(['success' => false, 'message' => 'Error: Archivo config.php no encontrado']);
    exit;
}
require_once __DIR__ . '/config.php';

session_start();

// Validación de sesión (solo admin)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// ============================
// 🔧 CONFIGURACIÓN BASE DE DATOS
// ============================
$config = [
    'host' => Config::DB_HOST,
    'port' => Config::DB_PORT,
    'usuario' => Config::DB_USER,
    'password' => Config::DB_PASS,
    'database' => Config::DB_NAME,
    'ruta_mysqldump' => '/usr/bin/mysqldump', // Siempre en la Pi
];

// ============================
// 🔹 Acción
// ============================
$method = $_SERVER['REQUEST_METHOD'];
$action = $method === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');

switch ($action) {
    case 'listar_respaldos':
        listarRespaldos();
        break;
    case 'generar_respaldo':
        generarRespaldo($config);
        break;
    case 'eliminar_respaldo':
        eliminarRespaldo();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

// ====================================
// 🔹 Listar respaldos existentes
// ====================================
function listarRespaldos() {
    $dir = Config::BACKUP_PATH;
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $archivos = glob($dir . "*.zip");
    $lista = [];
    foreach ($archivos as $archivo) {
        $lista[] = [
            'nombre' => basename($archivo),
            'tamaño' => formatoTamaño(filesize($archivo)),
            'fecha' => date("Y-m-d H:i:s", filemtime($archivo))
        ];
    }
    echo json_encode(['success' => true, 'respaldos' => $lista]);
}

// ====================================
// 🔹 Generar respaldo y descargar
// ====================================
function generarRespaldo($config) {
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "agroconsejos_respaldo_" . $fecha;
    $directorioBackups = Config::BACKUP_PATH;
    $rutaSQL = $directorioBackups . $nombreArchivo . ".sql";
    $rutaZip = $directorioBackups . $nombreArchivo . ".zip";

    if (!is_dir($directorioBackups)) mkdir($directorioBackups, 0755, true);

    $comando = sprintf(
        '%s --user=%s --password=%s --host=%s --port=%d %s --single-transaction --routines --triggers --events --add-drop-table --complete-insert --result-file=%s 2>&1',
        escapeshellcmd($config['ruta_mysqldump']),
        escapeshellarg($config['usuario']),
        escapeshellarg($config['password']),
        escapeshellarg($config['host']),
        $config['port'],
        escapeshellarg($config['database']),
        escapeshellarg($rutaSQL)
    );

    exec($comando, $output, $returnCode);

    if ($returnCode !== 0 || !file_exists($rutaSQL)) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Error al generar respaldo',
            'comando' => $comando,
            'output' => $output
        ]);
        return;
    }

    // Comprimir SQL en ZIP
    $zip = new ZipArchive();
    if ($zip->open($rutaZip, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($rutaSQL, basename($rutaSQL));
        $zip->addFromString('metadatos.json', json_encode([
            'sistema' => 'AgroConsejos',
            'fecha_respaldo' => date('Y-m-d H:i:s'),
            'version_bd' => '1.0'
        ], JSON_PRETTY_PRINT));
        $zip->close();
        unlink($rutaSQL); // borrar .sql
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear ZIP']);
        return;
    }

    // Descargar ZIP
    if (file_exists($rutaZip)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($rutaZip) . '"');
        header('Content-Length: ' . filesize($rutaZip));
        readfile($rutaZip);
        unlink($rutaZip); // borrar después de descargar
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Archivo ZIP no encontrado']);
    }
}

// ====================================
// 🔹 Eliminar respaldo
// ====================================
function eliminarRespaldo() {
    if (!isset($_POST['archivo'])) { echo json_encode(['success'=>false,'message'=>'No se especificó archivo a eliminar']); return; }
    $archivo = Config::BACKUP_PATH . basename($_POST['archivo']);
    if (file_exists($archivo)) {
        unlink($archivo);
        echo json_encode(['success'=>true,'message'=>'Archivo eliminado correctamente']);
    } else { 
        echo json_encode(['success'=>false,'message'=>'El archivo no existe']); 
    }
}

// ====================================
// 🔹 Funciones auxiliares
// ====================================
function formatoTamaño($bytes) {
    $unidades = ['B','KB','MB','GB','TB'];
    $i = 0; while ($bytes >= 1024 && $i < count($unidades)-1) { $bytes /= 1024; $i++; }
    return round($bytes,2).' '.$unidades[$i];
}
?>
