<?php
// ============================
// 🌿 AGROCONSEJOS - RESPALDO BD (Linux)
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'listar_respaldos') {
        listarRespaldos();
    } elseif ($action === 'verificar_mysqldump') {
        verificarMysqldump($config);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generar_respaldo') {
        generarRespaldo($config);
    } elseif ($action === 'restaurar_respaldo') {
        restaurarRespaldo($config);
    } elseif ($action === 'eliminar_respaldo') {
        eliminarRespaldo();
    }
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
// 🔹 Generar respaldo
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
            echo json_encode([
                'success' => true,
                'message' => '✅ Respaldo generado exitosamente',
                'archivo' => basename($rutaZip),
                'tamaño' => formatoTamaño(filesize($rutaZip))
            ]);
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
// 🔹 Restaurar respaldo
// ====================================
function restaurarRespaldo($config) {
    if (!isset($_FILES['archivo_respaldo']) || $_FILES['archivo_respaldo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '❌ No se recibió archivo de respaldo válido']);
        return;
    }

    $directorioTemp = _DIR_ . '/temp_restore/';
    if (!is_dir($directorioTemp)) mkdir($directorioTemp, 0755, true);

    $archivoZip = $directorioTemp . basename($_FILES['archivo_respaldo']['name']);
    move_uploaded_file($_FILES['archivo_respaldo']['tmp_name'], $archivoZip);

    $zip = new ZipArchive();
    if ($zip->open($archivoZip) !== TRUE) {
        echo json_encode(['success' => false, 'message' => '❌ No se pudo abrir el archivo ZIP']);
        return;
    }

    $carpetaExtraida = $directorioTemp . 'extraido_' . time() . '/';
    mkdir($carpetaExtraida, 0755, true);
    $zip->extractTo($carpetaExtraida);
    $zip->close();

    $archivos = glob($carpetaExtraida . '*.sql');
    if (empty($archivos)) {
        echo json_encode(['success' => false, 'message' => '❌ El respaldo no contiene un archivo SQL válido']);
        eliminarDirectorio($carpetaExtraida);
        return;
    }

    $archivoSQL = $archivos[0];

    $comando = sprintf(
        '%s --user=%s --password=%s --host=%s --port=%d %s < %s 2>&1',
        escapeshellcmd($config['ruta_mysql']),
        escapeshellarg($config['usuario']),
        escapeshellarg($config['password']),
        escapeshellarg($config['host']),
        $config['port'],
        escapeshellarg($config['database']),
        escapeshellarg($archivoSQL)
    );

    exec($comando, $output, $returnCode);

    eliminarDirectorio($carpetaExtraida);
    unlink($archivoZip);

    if ($returnCode === 0) {
        registrarAccionBitacora("Base de datos restaurada desde: " . basename($_FILES['archivo_respaldo']['name']));
        echo json_encode([
            'success' => true,
            'message' => '✅ Restauración completada exitosamente desde ' . basename($_FILES['archivo_respaldo']['name'])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '❌ Error al restaurar base de datos',
            'output' => implode("\n", $output),
            'comando' => $comando
        ]);
    }
}

// ====================================
// 🔹 Comprimir respaldo y funciones auxiliares
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
?>
