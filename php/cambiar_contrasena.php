<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario está verificado
if (!isset($_SESSION['usuario_verificado_id']) || !$_SESSION['codigo_verificado']) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida o código no verificado']);
    exit;
}

$conexion = new Conexion();
$conn = $conexion->getConexion();

$nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
$usuario_id = $_SESSION['usuario_verificado_id'];

// Validar contraseña
if (strlen($nueva_contrasena) < 8) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres']);
    exit;
}

if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/", $nueva_contrasena)) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe incluir mayúsculas, minúsculas, números y caracteres especiales']);
    exit;
}

try {
    // Encriptar la nueva contraseña
    $contrasena_encriptada = $conexion->encriptarContrasena($nueva_contrasena);
    
    if (!$contrasena_encriptada) {
        throw new Exception("Error al encriptar la contraseña");
    }

    // Actualizar contraseña en la base de datos
    $sql = "UPDATE usuarios SET contrasena = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $contrasena_encriptada, $usuario_id);
    
    if ($stmt->execute()) {
        // Limpiar sesión después del cambio exitoso
        unset($_SESSION['usuario_verificado_id']);
        unset($_SESSION['codigo_verificado']);
        
        // Eliminar códigos de recuperación del usuario
        $sql_delete = "DELETE FROM reseteo_contrasenas WHERE usuario_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $usuario_id);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Contraseña cambiada exitosamente. Redirigiendo al login...'
        ]);
    } else {
        throw new Exception("Error al actualizar la contraseña en la base de datos");
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error en cambiar_contrasena.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}

$conexion->cerrar();
?>