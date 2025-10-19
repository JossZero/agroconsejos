<?php
require_once 'conexion.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$conexion = new Conexion();
$conn = $conexion->getConexion();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'obtener_usuarios') {
        obtenerUsuarios($conn, $conexion);
    } elseif ($action === 'obtener_usuario') {
        $id = $_GET['id'] ?? 0;
        obtenerUsuario($conn, $conexion, $id);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'actualizar_usuario') {
        actualizarUsuario($conn, $conexion);
    } elseif ($action === 'eliminar_usuario') {
        eliminarUsuario($conn);
    }
}

function obtenerUsuarios($conn, $conexion) {
    $sql = "SELECT id, nombre, correo, telefono, contrasena, rol, fecha_registro FROM usuarios ORDER BY fecha_registro DESC";
    $result = $conn->query($sql);
    
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        // Desencriptar contraseña para mostrar en texto plano
        $contrasena_plana = $conexion->desencriptarContrasena($row['contrasena']);
        
        $usuarios[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'correo' => $row['correo'],
            'telefono' => $row['telefono'],
            'contrasena_plana' => $contrasena_plana,
            'rol' => $row['rol'],
            'fecha_registro' => $row['fecha_registro']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $usuarios]);
}

function obtenerUsuario($conn, $conexion, $id) {
    $sql = "SELECT id, nombre, correo, telefono, contrasena, rol FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        return;
    }
    
    $usuario = $result->fetch_assoc();
    $usuario['contrasena_plana'] = $conexion->desencriptarContrasena($usuario['contrasena']);
    
    echo json_encode(['success' => true, 'data' => $usuario]);
    $stmt->close();
}

function actualizarUsuario($conn, $conexion) {
    $id = $_POST['id'] ?? 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $rol = $_POST['rol'] ?? '';
    
    // Validaciones
    if (empty($nombre) || empty($correo) || empty($telefono) || empty($rol)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        return;
    }
    
    if (!preg_match("/^\d{10}$/", $telefono)) {
        echo json_encode(['success' => false, 'message' => 'Teléfono debe tener 10 dígitos']);
        return;
    }
    
    // Verificar si el correo ya existe en otro usuario
    $sql = "SELECT id FROM usuarios WHERE correo = ? AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $correo, $id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El correo ya está en uso por otro usuario']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    // Construir consulta de actualización
    if (!empty($contrasena)) {
        // Validar contraseña
        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/", $contrasena) || strlen($contrasena) < 8) {
            echo json_encode(['success' => false, 'message' => 'La contraseña no cumple con los requisitos de seguridad']);
            return;
        }
        
        $contrasena_encriptada = $conexion->encriptarContrasena($contrasena);
        $sql = "UPDATE usuarios SET nombre = ?, correo = ?, telefono = ?, contrasena = ?, rol = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $nombre, $correo, $telefono, $contrasena_encriptada, $rol, $id);
    } else {
        $sql = "UPDATE usuarios SET nombre = ?, correo = ?, telefono = ?, rol = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $nombre, $correo, $telefono, $rol, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario: ' . $conn->error]);
    }
    
    $stmt->close();
}

function eliminarUsuario($conn) {
    $id = $_POST['id'] ?? 0;
    
    // No permitir eliminar al propio administrador
    if ($id == $_SESSION['usuario_id']) {
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propio usuario']);
        return;
    }
    
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario: ' . $conn->error]);
    }
    
    $stmt->close();
}

$conexion->cerrar();
?>