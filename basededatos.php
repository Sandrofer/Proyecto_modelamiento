<?php
session_start();    
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

if ($conexion->connect_error) {
    die("No tiene conexión: " . $conexion->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibimos los datos del formulario
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];

    // Consulta preparada CORREGIDA
    $stmt = $conexion->prepare("SELECT * FROM usuario WHERE nombre_usuario=?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute(); 

    $resultado = $stmt->get_result();

    // Verificar si el usuario existe
    if ($resultado->num_rows > 0) {
        // Recuperar el usuario de la base de datos
        $usuario_bd = $resultado->fetch_assoc();
        
        // Verificar la contraseña CORREGIDA
        if (password_verify($clave, $usuario_bd['contraseña'])) {
           // Usamos nombre_usuario como identificador en lugar de id
           $_SESSION['usuario_id'] = $usuario_bd['nombre_usuario']; // Guardamos el username
           $_SESSION['username'] = $usuario_bd['nombre_usuario'];
           $_SESSION['nombre'] = $usuario_bd['nombre'];
           $_SESSION['apellido'] = $usuario_bd['apellido'];
           $_SESSION['correo'] = $usuario_bd['correo_electronico'];
           
            // Contraseña correcta, redirigir al usuario
            header("Location: pagina_principal.php");
            exit();
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