<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'verificar_requisitos') {
        $errores = verificarRequisitosSistema();
        $info = obtenerInfoSistema();
        
        echo json_encode([
            'success' => empty($errores),
            'errores' => $errores,
            'info_sistema' => $info,
            'requisitos_cumplidos' => empty($errores)
        ]);
    }
    
    if ($action === 'verificar_mysqldump') {
        $comando = Config::MYSQLDUMP_PATH . ' --version 2>&1';
        exec($comando, $output, $returnCode);
        
        echo json_encode([
            'success' => $returnCode === 0,
            'disponible' => $returnCode === 0,
            'version' => $output[0] ?? 'No disponible',
            'error' => $returnCode !== 0 ? implode(', ', $output) : null
        ]);
    }
    
    if ($action === 'verificar_base_datos') {
        try {
            $conn = new mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASS);
            if ($conn->connect_error) {
                throw new Exception($conn->connect_error);
            }
            
            // Verificar si la base de datos existe
            $result = $conn->query("SHOW DATABASES LIKE '" . Config::DB_NAME . "'");
            $db_existe = $result->num_rows > 0;
            
            $conn->close();
            
            echo json_encode([
                'success' => true,
                'base_datos_existe' => $db_existe,
                'conexion_exitosa' => true
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>