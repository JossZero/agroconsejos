<?php
require_once 'conexion.php';
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$conexion = new Conexion();
$conn = $conexion->getConexion();

$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

$errores = [];

// Validaciones
if (strlen($nombre) < 2 || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $nombre)) {
    $errores[] = "Nombre inválido";
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Correo electrónico inválido";
}

if (!preg_match("/^\d{10}$/", $telefono)) {
    $errores[] = "Teléfono debe tener 10 dígitos";
}

if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $contrasena)) {
    $errores[] = "La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.";
}

// Verificar si el correo ya existe
$sql = "SELECT id FROM usuarios WHERE correo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $errores[] = "Este correo electrónico ya está registrado";
}
$stmt->close();

if (empty($errores)) {
    $contrasena_encriptada = $conexion->encriptarContrasena($contrasena);

    if (!$contrasena_encriptada) {
        echo json_encode(['success' => false, 'message' => 'Error al encriptar la contraseña.']);
        exit;
    }

    $sql = "INSERT INTO usuarios (nombre, correo, telefono, contrasena, rol, activo, fecha_registro)
            VALUES (?, ?, ?, ?, 'usuario', 1, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $correo, $telefono, $contrasena_encriptada);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registro exitoso. Redirigiendo...']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar usuario: ' . $conn->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => implode(', ', $errores)]);
}

$conexion->cerrar();
?>
