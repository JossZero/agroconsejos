<?php
// Habilitar el reporting de errores para desarrollo (quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cabeceras para CORS y tipo de contenido JSON
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// Manejar petición preflight CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'conexion.php'; // Asegúrate de que esta ruta es correcta

session_start();

// Verificar que la petición es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Obtener y validar el código
$codigo = $_POST['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(['success' => false, 'message' => 'El código no puede estar vacío.']);
    exit;
}

$conexion = new Conexion();
$conn = $conexion->getConexion();

try {
    // 1. Verificar si el código existe y no ha expirado
    $sql = "SELECT rc.id, rc.usuario_id, rc.expiracion, u.correo 
            FROM reseteo_contrasenas rc 
            INNER JOIN usuarios u ON rc.usuario_id = u.id 
            WHERE rc.codigo = ? AND rc.utilizado = 0 AND rc.expiracion > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Código inválido o expirado.']);
        exit;
    }

    $datosCodigo = $result->fetch_assoc();
    $stmt->close();

    // 2. Marcar el código como utilizado
    $sqlUpdate = "UPDATE reseteo_contrasenas SET utilizado = 1 WHERE id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("i", $datosCodigo['id']);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    // 3. Guardar en sesión que la verificación fue exitosa para el siguiente paso
    $_SESSION['usuario_verificado_id'] = $datosCodigo['usuario_id'];
    $_SESSION['codigo_verificado'] = true;

    echo json_encode(['success' => true, 'message' => 'Código verificado correctamente.']);

} catch (Exception $e) {
    error_log("Error en verificar_codigo.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}

$conexion->cerrar();
?>