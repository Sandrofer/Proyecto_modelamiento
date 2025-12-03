<?php
// Asegurarse de que no hay salida antes de session_start()
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mostrar errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

if ($conexion->connect_error) {
    die("No tiene conexión: " . $conexion->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibimos los datos del formulario
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];

    // Consulta preparada - Aseguramos que seleccionamos el campo 'activo'
    $stmt = $conexion->prepare("SELECT id, nombre_usuario, contraseña, nombre, apellido, correo_electronico, rol, email_verificado, activo FROM usuario WHERE nombre_usuario=?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute(); 
    $resultado = $stmt->get_result();

    // Verificar si el usuario existe
    if ($resultado->num_rows > 0) {
        // Recuperar el usuario de la base de datos
        $usuario_bd = $resultado->fetch_assoc();
        
        // Verificar la contraseña
        if (password_verify($clave, $usuario_bd['contraseña'])) {
            
            // Verificar si la cuenta está activa
            if (isset($usuario_bd['activo']) && $usuario_bd['activo'] == 0) {
                // Cuenta desactivada
                echo "<script>
                    alert('Esta cuenta ha sido desactivada. Por favor, contacta al administrador para más información.');
                    window.location.href = 'index.html';
                </script>";
                exit();
            }
            
            // VERIFICAR SI EL EMAIL ESTÁ CONFIRMADO
            if (!$usuario_bd['email_verificado']) {
                // Guardar datos en sesión para reenvío
                $_SESSION['usuario_pendiente'] = $usuario_bd['nombre_usuario'];
                $_SESSION['correo_pendiente'] = $usuario_bd['correo_electronico'];
                $_SESSION['nombre_pendiente'] = $usuario_bd['nombre'];
                
                // Redirigir a página de reenvío
                header("Location: reenviar_verificacion.php");
                exit();
            }
           
            // Si está verificado, crear sesión normal
            $_SESSION['usuario_id'] = $usuario_bd['nombre_usuario'];
            $_SESSION['username'] = $usuario_bd['nombre_usuario'];
            $_SESSION['nombre'] = $usuario_bd['nombre'];
            $_SESSION['apellido'] = $usuario_bd['apellido'];
            $_SESSION['correo'] = $usuario_bd['correo_electronico'];
            $_SESSION['rol'] = $usuario_bd['rol'] ?? 'usuario';  // Añadir el rol a la sesión

            // Redirigir al usuario según su rol
            if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'moderador') {
                // Depuración
                error_log("Redirigiendo a panel_moderador.php");
                header("Location: panel_moderador.php");
                exit();
            } else {
                // Depuración
                error_log("Redirigiendo a pagina_principal.php");
                header("Location: pagina_principal.php");
                exit();
            }
            
        } else {
            // Contraseña incorrecta
            echo "<script>alert('Usuario o contraseña incorrectos.'); window.history.back();</script>";
        }
    } else {
        // Usuario no encontrado
        echo "<script>alert('Usuario o contraseña incorrectos.'); window.history.back();</script>";
    }

    // Cerrar la consulta y la conexión
    $stmt->close();
    $conexion->close();
}
?>