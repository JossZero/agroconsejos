<?php
session_start();

// Registrar cierre de sesión en bitácora
if (isset($_SESSION['bitacora_id'])) {
    require_once 'conexion.php';
    $conexion = new Conexion();
    $conn = $conexion->getConexion();
    
    $bitacora_id = $_SESSION['bitacora_id'];
    $sql = "UPDATE bitacora_accesos SET fecha_salida = NOW(), activo = 0, tiempo_sesion = TIMESTAMPDIFF(SECOND, fecha_entrada, NOW()) WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bitacora_id);
    $stmt->execute();
    $stmt->close();
    
    $conexion->cerrar();
}

// Destruir sesión
session_destroy();

// Redirigir al login
header('Location: ../login.html');
exit;
?>