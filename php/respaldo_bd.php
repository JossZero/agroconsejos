<?php
// ============================
// 🌿 AGROCONSEJOS - RESPALDO BD
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

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
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
    'port' => 3307,
    'usuario' => 'root',
    'password' => '',
    'database' => 'agroconsejos',
    'ruta_mysqldump' => 'C:\\xampp\\mysql\\bin\\mysqldump.exe'
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
    $comando = '"' . $config['ruta_mysqldump'] . '" --version 2>&1';
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
    $directorioBackups = __DIR__ . "/backups/";
    $rutaSQL = $directorioBackups . $nombreArchivo . ".sql";
    $rutaZip = $directorioBackups . $nombreArchivo . ".zip";

    if (!is_dir($directorioBackups)) {
        mkdir($directorioBackups, 0755, true);
    }

    if (empty($config['password'])) {
        $comando = sprintf(
            '"%s" --user=%s --host=%s --port=%d %s --single-transaction --routines --triggers --events --add-drop-table --complete-insert > "%s" 2>&1',
            $config['ruta_mysqldump'],
            escapeshellarg($config['usuario']),
            escapeshellarg($config['host']),
            $config['port'],
            escapeshellarg($config['database']),
            $rutaSQL
        );
    } else {
        $comando = sprintf(
            '"%s" --user=%s --password=%s --host=%s --port=%d %s --single-transaction --routines --triggers --events --add-drop-table --complete-insert > "%s" 2>&1',
            $config['ruta_mysqldump'],
            escapeshellarg($config['usuario']),
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            $config['port'],
            escapeshellarg($config['database']),
            $rutaSQL
        );
    }

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

            // ==============================
            // ✅ COPIA AUTOMÁTICA A USB (D:)
            // ==============================
            $rutaUSB = 'D:\\RespaldoAgroconsejos\\';
            if (is_dir($rutaUSB)) {
                if (!copy($rutaZip, $rutaUSB . basename($rutaZip))) {
                    registrarAccionBitacora("⚠️ No se pudo copiar respaldo a la memoria USB (E:)");
                } else {
                    registrarAccionBitacora("✅ Copia adicional en memoria USB: " . $rutaUSB);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => '✅ Respaldo generado y copiado exitosamente',
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
// 🔹 Comprimir respaldo
// ====================================
function comprimirArchivo($archivoOrigen, $archivoDestino) {
    $zip = new ZipArchive();
    if ($zip->open($archivoDestino, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($archivoOrigen, basename($archivoOrigen));
        $metadatos = [
            'sistema' => 'AgroConsejos',
            'fecha_respaldo' => date('Y-m-d H:i:s'),
            'version_bd' => '1.0',
            'tablas_incluidas' => 'Todas las tablas del sistema'
        ];
        $zip->addFromString('metadatos.json', json_encode($metadatos, JSON_PRETTY_PRINT));
        $zip->close();
        return true;
    }
    return false;
}

// ====================================
// 🔹 Funciones auxiliares
// ====================================
function listarRespaldos() {
    $dir = __DIR__ . "/backups/";
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
    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($unidades) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $unidades[$i];
}

function eliminarDirectorio($dir) {
    if (!is_dir($dir)) return;
    $archivos = array_diff(scandir($dir), ['.', '..']);
    foreach ($archivos as $archivo) {
        $ruta = "$dir/$archivo";
        if (is_dir($ruta)) eliminarDirectorio($ruta);
        else unlink($ruta);
    }
    rmdir($dir);
}

function registrarAccionBitacora($accion) {
    $log = __DIR__ . "/backups/bitacora.txt";
    file_put_contents($log, "[" . date('Y-m-d H:i:s') . "] $accion\n", FILE_APPEND);
}

function eliminarRespaldo() {
    if (!isset($_POST['archivo'])) {
        echo json_encode(['success' => false, 'message' => 'No se especificó archivo a eliminar']);
        return;
    }
    $archivo = __DIR__ . "/backups/" . basename($_POST['archivo']);
    if (file_exists($archivo)) {
        unlink($archivo);
        registrarAccionBitacora("Respaldo eliminado: " . basename($archivo));
        echo json_encode(['success' => true, 'message' => 'Archivo eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'El archivo no existe']);
    }
}
// ====================================
// 🔹 Restaurar respaldo
// ====================================
function restaurarRespaldo($config) {
    // Verificar si se envió un archivo ZIP
    if (!isset($_FILES['archivo_respaldo']) || $_FILES['archivo_respaldo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '❌ No se recibió archivo de respaldo válido']);
        return;
    }

    // Crear carpeta temporal para restauración
    $directorioTemp = __DIR__ . '/temp_restore/';
    if (!is_dir($directorioTemp)) {
        mkdir($directorioTemp, 0755, true);
    }

    // Guardar el archivo subido
    $archivoZip = $directorioTemp . basename($_FILES['archivo_respaldo']['name']);
    move_uploaded_file($_FILES['archivo_respaldo']['tmp_name'], $archivoZip);

    // Verificar que el ZIP sea válido
    $zip = new ZipArchive();
    if ($zip->open($archivoZip) !== TRUE) {
        echo json_encode(['success' => false, 'message' => '❌ No se pudo abrir el archivo ZIP']);
        return;
    }

    // Extraer contenido en carpeta temporal
    $carpetaExtraida = $directorioTemp . 'extraido_' . time() . '/';
    mkdir($carpetaExtraida, 0755, true);
    $zip->extractTo($carpetaExtraida);
    $zip->close();

    // Buscar el archivo .sql dentro del ZIP
    $archivos = glob($carpetaExtraida . '*.sql');
    if (empty($archivos)) {
        echo json_encode(['success' => false, 'message' => '❌ El respaldo no contiene un archivo SQL válido']);
        eliminarDirectorio($carpetaExtraida);
        return;
    }

    $archivoSQL = $archivos[0]; // Solo uno por respaldo

    // Restaurar la base de datos
    $comando = sprintf(
        '"%s" --user=%s %s --host=%s --port=%d %s < "%s" 2>&1',
        $config['ruta_mysqldump'],
        escapeshellarg($config['usuario']),
        empty($config['password']) ? '' : '--password=' . escapeshellarg($config['password']),
        escapeshellarg($config['host']),
        $config['port'],
        escapeshellarg($config['database']),
        $archivoSQL
    );

    // Cambiar comando a mysql.exe (no mysqldump)
    $comando = str_replace('mysqldump', 'mysql', $comando);

    exec($comando, $output, $returnCode);

    // Limpiar archivos temporales
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


?>


