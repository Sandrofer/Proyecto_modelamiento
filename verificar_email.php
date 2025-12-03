<?php
// verificar_email.php
session_start();
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

// Obtener token de la URL
$token = isset($_GET['token']) ? $conexion->real_escape_string($_GET['token']) : '';

if ($token) {
    // Verificar token válido y no expirado
    $stmt = $conexion->prepare("SELECT * FROM usuario WHERE token_verificacion = ? AND token_verificacion_expiracion > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        
        // Marcar email como verificado y limpiar token
        $stmt = $conexion->prepare("UPDATE usuario SET email_verificado = TRUE, token_verificacion = NULL, token_verificacion_expiracion = NULL WHERE token_verificacion = ?");
        $stmt->bind_param("s", $token);
        
        if ($stmt->execute()) {
            $mensaje = "✅ ¡Email verificado correctamente! Ya puedes iniciar sesión.";
            $tipo = "success";
        } else {
            $mensaje = "❌ Error al verificar el email.";
            $tipo = "error";
        }
    } else {
        $mensaje = "❌ Enlace inválido o expirado.";
        $tipo = "error";
    }
} else {
    $mensaje = "❌ Token no proporcionado.";
    $tipo = "error";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Email - UTASHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6 text-center">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Verificación de Email</h1>
        
        <div class="<?php echo $tipo == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> p-4 rounded-lg mb-4">
            <?php echo $mensaje; ?>
        </div>
        
        <?php if ($tipo == 'success'): ?>
            <a href="index.html" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 inline-block">
                Iniciar Sesión
            </a>
        <?php else: ?>
            <a href="index.html" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 inline-block">
                Volver al Inicio
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conexion->close(); ?>