<?php
// ============================
// 🌿 AGROCONSEJOS - RESPALDO BD (Linux/Windows)
// ============================

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$rutaConexion = _DIR_ . '/conexion.php';
if (!file_exists($rutaConexion)) {
    echo json_encode(['success' => false, 'message' => 'Error: Archivo de conexión no encontrado']);
    exit;
}
require_once $rutaConexion;

if (file_exists(_DIR_ . '/config.php')) {
    require_once _DIR_ . '/config.php';
}

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
    'host' => 'localhost',
    'port' => 3306,
    'usuario' => 'agroapp',
    'password' => '12345',
    'database' => 'agroconsejos',
    'ruta_mysqldump' => '/usr/bin/mysqldump', // Linux
    'ruta_mysql' => '/usr/bin/mysql'          // Linux
];

$conexion = new Conexion();
$conn = $conexion->getConexion();

$method = $_SERVER['REQUEST_METHOD'];
$action = $method === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');

switch ($action) {
    case 'listar_respaldos':
        listarRespaldos();
        break;
    case 'verificar_mysqldump':
        verificarMysqldump($config);
        break;
    case 'generar_respaldo':
        generarRespaldo($config);
        break;
    case 'restaurar_respaldo':
        restaurarRespaldo($config);
        break;
    case 'eliminar_respaldo':
        eliminarRespaldo();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

// ====================================
// 🔹 Verificar mysqldump disponible
// ====================================
function verificarMysqldump($config) {
    $comando = escapeshellcmd($config['ruta_mysqldump']) . " --version 2>&1";
    exec($comando, $output, $returnCode);

    if ($returnCode === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'mysqldump disponible: ' . implode(' ', $output),
            'version' => $output[0] ?? 'Desconocida'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'mysqldump no encontrado. Asegúrate de que MySQL esté instalado correctamente.',
            'error' => implode(', ', $output)
        ]);
    }
}

// ====================================
// 🔹 Generar respaldo y enviar descarga
// ====================================
function generarRespaldo($config) {
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "agroconsejos_respaldo_" . $fecha;
    $directorioBackups = _DIR_ . "/backups/";
    $rutaSQL = $directorioBackups . $nombreArchivo . ".sql";
    $rutaZip = $directorioBackups . $nombreArchivo . ".zip";

    if (!is_dir($directorioBackups)) mkdir($directorioBackups, 0755, true);

    $comando = sprintf(
        '%s --user=%s --password=%s --host=%s --port=%d %s --single-transaction --routines --triggers --events --add-drop-table --complete-insert > %s 2>&1',
        escapeshellcmd($config['ruta_mysqldump']),
        escapeshellarg($config['usuario']),
        escapeshellarg($config['password']),
        escapeshellarg($config['host']),
        $config['port'],
        escapeshellarg($config['database']),
        escapeshellarg($rutaSQL)
    );

    exec($comando, $output, $returnCode);

    if ($returnCode !== 0) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Error en mysqldump',
            'comando' => $comando,
            'output' => $output
        ]);
        exit;
    }

    if (file_exists($rutaSQL) && filesize($rutaSQL) > 0) {
        if (comprimirArchivo($rutaSQL, $rutaZip)) {
            unlink($rutaSQL);
            registrarAccionBitacora("Respaldo generado: " . basename($rutaZip));

            // 🔹 Enviar archivo al navegador para descarga
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($rutaZip) . '"');
            header('Content-Length: ' . filesize($rutaZip));
            readfile($rutaZip);

            // 🔹 Opcional: borrar el ZIP después de enviarlo
            unlink($rutaZip);

            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al comprimir respaldo']);
        }
    } else {
        $errorMsg = implode(', ', $output);
        if (file_exists($rutaSQL)) unlink($rutaSQL);
        echo json_encode(['success' => false, 'message' => '❌ Error al generar respaldo: ' . $errorMsg]);
    }
}

// ====================================
// 🔹 Comprimir respaldo
// ====================================
function comprimirArchivo($archivoOrigen, $archivoDestino) {
    $zip = new ZipArchive();
    if ($zip->open($archivoDestino, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($archivoOrigen, basename($archivoOrigen));
        $zip->addFromString('metadatos.json', json_encode([
            'sistema' => 'AgroConsejos',
            'fecha_respaldo' => date('Y-m-d H:i:s'),
            'version_bd' => '1.0',
            'tablas_incluidas' => 'Todas las tablas del sistema'
        ], JSON_PRETTY_PRINT));
        $zip->close();
        return true;
    }
    return false;
}

// ====================================
// 🔹 Funciones auxiliares
// ====================================
function listarRespaldos() {
    $dir = _DIR_ . "/backups/";
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

function formatoTamaño($bytes) {
    $unidades = ['B','KB','MB','GB','TB'];
    $i = 0; while ($bytes >= 1024 && $i < count($unidades)-1) { $bytes /= 1024; $i++; }
    return round($bytes,2).' '.$unidades[$i];
}

function eliminarDirectorio($dir) {
    if (!is_dir($dir)) return;
    $archivos = array_diff(scandir($dir), ['.','..']);
    foreach ($archivos as $archivo) {
        $ruta = "$dir/$archivo";
        if (is_dir($ruta)) eliminarDirectorio($ruta);
        else unlink($ruta);
    }
    rmdir($dir);
}

function registrarAccionBitacora($accion) {
    $log = _DIR_ . "/backups/bitacora.txt";
    file_put_contents($log,"[".date('Y-m-d H:i:s')."] $accion\n",FILE_APPEND);
}

function eliminarRespaldo() {
    if (!isset($_POST['archivo'])) { echo json_encode(['success'=>false,'message'=>'No se especificó archivo a eliminar']); return; }
    $archivo = _DIR_."/backups/".basename($_POST['archivo']);
    if (file_exists($archivo)) {
        unlink($archivo);
        registrarAccionBitacora("Respaldo eliminado: ".basename($archivo));
        echo json_encode(['success'=>true,'message'=>'Archivo eliminado correctamente']);
    } else { echo json_encode(['success'=>false,'message'=>'El archivo no existe']); }
}

// 🔹 La función restaurarRespaldo se mantiene igual, no se toca.
?>
