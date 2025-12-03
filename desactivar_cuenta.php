<?php
session_start();
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}

// Verificar que la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: perfil.php");
    exit();
}

// Obtener datos del usuario
$nombre_usuario = $_SESSION['usuario_id'];
$contrasena_actual = $_POST['contrasena_actual'] ?? '';
$confirmar_desactivar = isset($_POST['confirmar_desactivar']);

// Verificar que se haya confirmado la desactivación
if (!$confirmar_desactivar) {
    $_SESSION['mensaje'] = "Debes confirmar que entiendes que esta acción no se puede deshacer.";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: perfil.php#desactivar-cuenta");
    exit();
}

// Obtener información del usuario
$sql_usuario = "SELECT id, contraseña FROM usuario WHERE nombre_usuario = ?";
$stmt = $conexion->prepare($sql_usuario);
$stmt->bind_param("s", $nombre_usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    $_SESSION['mensaje'] = "Error: Usuario no encontrado.";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: perfil.php#desactivar-cuenta");
    exit();
}

$usuario = $resultado->fetch_assoc();
$stmt->close();

// Verificar la contraseña
if (!password_verify($contrasena_actual, $usuario['contraseña'])) {
    $_SESSION['mensaje'] = "La contraseña actual es incorrecta.";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: perfil.php#desactivar-cuenta");
    exit();
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Marcar la cuenta como inactiva
    $sql_desactivar = "UPDATE usuario SET activo = FALSE WHERE id = ?";
    $stmt = $conexion->prepare($sql_desactivar);
    $stmt->bind_param("i", $usuario['id']);
    $stmt->execute();
    $stmt->close();
    
    // 2. Marcar todos los productos del usuario como inactivos
    $sql_productos = "UPDATE productos SET estado = 'inactivo' WHERE usuario_vendedor = ?";
    $stmt = $conexion->prepare($sql_productos);
    $stmt->bind_param("s", $nombre_usuario);
    $stmt->execute();
    $stmt->close();
    
    // Confirmar la transacción
    $conexion->commit();
    
    // Cerrar la sesión
    session_destroy();
    
    // Redirigir a la página de confirmación de cuenta desactivada
    header("Location: cuenta_desactivada.php");
    exit();
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conexion->rollback();
    
    $_SESSION['mensaje'] = "Error al desactivar la cuenta. Por favor, intenta de nuevo más tarde.";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: perfil.php#desactivar-cuenta");
    exit();
}

$conexion->close();
?>
