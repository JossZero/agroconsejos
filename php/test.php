<?php
require_once 'config.php';
require_once 'conexion.php';

echo "<h2>🔍 TEST COMPLETO DEL SISTEMA - AGROCONSEJOS</h2>";
echo "<hr>";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==============================
// 1️⃣ PRUEBA DE CONEXIÓN MYSQL
// ==============================
echo "<h3>1️⃣ Prueba de conexión MySQL</h3>";

try {
    // Forzar salida de errores reales de MySQL
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASS, Config::DB_NAME);
    $conn->set_charset("utf8mb4");
    echo "✅ Conexión a MySQL exitosa<br>";

} catch (mysqli_sql_exception $e) {
    echo "❌ Error de conexión MySQL:<br>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    exit;
}

// ==============================
// 2️⃣ EXISTENCIA DE BASE DE DATOS
// ==============================
echo "<h3>2️⃣ Verificando base de datos</h3>";
try {
    $db_check = $conn->query("SELECT DATABASE()");
    $row = $db_check->fetch_row();
    echo "✅ Base de datos activa: <b>{$row[0]}</b><br>";
} catch (Exception $e) {
    echo "❌ Error al acceder a la base de datos:<br><pre>" . $e->getMessage() . "</pre>";
}

// ==============================
// 3️⃣ EXISTENCIA DE TABLA USUARIOS
// ==============================
echo "<h3>3️⃣ Verificando tabla 'usuarios'</h3>";
try {
    $table_check = $conn->query("SHOW TABLES LIKE 'usuarios'");
    if ($table_check->num_rows > 0) {
        echo "✅ Tabla 'usuarios' encontrada<br>";

        $cols = $conn->query("DESCRIBE usuarios");
        $columnas = [];
        while ($col = $cols->fetch_assoc()) {
            $columnas[] = $col['Field'];
        }

        echo "📋 Columnas: <b>" . implode(', ', $columnas) . "</b><br>";

    } else {
        echo "❌ Tabla 'usuarios' no existe<br>";
    }
} catch (Exception $e) {
    echo "❌ Error al verificar la tabla:<br><pre>" . $e->getMessage() . "</pre>";
}

// ==============================
// 4️⃣ PRUEBA DE CONSULTA SQL
// ==============================
echo "<h3>4️⃣ Prueba de consulta SQL</h3>";
try {
    $q = $conn->query("SELECT COUNT(*) FROM usuarios");
    $r = $q->fetch_row();
    echo "✅ Total de usuarios en tabla: <b>{$r[0]}</b><br>";
} catch (Exception $e) {
    echo "❌ Error al ejecutar consulta:<br><pre>" . $e->getMessage() . "</pre>";
}

// ==============================
// 5️⃣ PRUEBA DE ENCRIPTACIÓN
// ==============================
echo "<h3>5️⃣ Prueba de encriptación</h3>";
try {
    $conexion = new Conexion();
    $pass = "Prueba123@";
    $enc = $conexion->encriptarContrasena($pass);
    $dec = $conexion->desencriptarContrasena($enc);

    if ($pass === $dec) {
        echo "✅ Encriptación y desencriptación correctas<br>";
    } else {
        echo "⚠️ Error al verificar encriptación<br>";
    }
} catch (Exception $e) {
    echo "❌ Error en la clase de encriptación:<br><pre>" . $e->getMessage() . "</pre>";
}

// ==============================
// 6️⃣ INFORMACIÓN DEL SISTEMA
// ==============================
echo "<h3>6️⃣ Información del servidor</h3>";
echo "PHP: " . PHP_VERSION . "<br>";
echo "MySQL host: " . Config::DB_HOST . "<br>";
echo "Base de datos: " . Config::DB_NAME . "<br>";
echo "Sistema operativo: " . PHP_OS . "<br>";

$conn->close();
echo "<hr><b>✅ TEST FINALIZADO</b>";
?>
