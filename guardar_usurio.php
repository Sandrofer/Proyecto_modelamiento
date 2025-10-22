<?php 
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener y limpiar los datos del formulario
$usuario   = isset($_POST['usuario']) ? $conexion->real_escape_string(trim($_POST['usuario'])) : '';
$clave     = isset($_POST['clave']) ? $_POST['clave'] : '';
$correo    = isset($_POST['correo']) ? $conexion->real_escape_string(trim($_POST['correo'])) : '';
$nombre    = isset($_POST['nombre']) ? $conexion->real_escape_string(trim($_POST['nombre'])) : '';
$apellido  = isset($_POST['apellido']) ? $conexion->real_escape_string(trim($_POST['apellido'])) : '';

// ✅ Validar seguridad de la contraseña
$patron_contraseña_segura = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/';
if (!preg_match($patron_contraseña_segura, $clave)) {
    die("<script>alert('La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula, un número y un símbolo especial.'); window.history.back();</script>");
}

// Verificar si el nombre de usuario ya existe
$check = $conexion->prepare("SELECT nombre_usuario FROM usuario WHERE nombre_usuario = ?");
$check->bind_param("s", $usuario);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    die("<script>alert('El nombre de usuario ya está en uso'); window.history.back();</script>");
}
$check->close();

// Verificar si el correo ya existe
$checkCorreo = $conexion->prepare("SELECT correo_electronico FROM usuario WHERE correo_electronico = ?");
$checkCorreo->bind_param("s", $correo);
$checkCorreo->execute();
$checkCorreo->store_result();
if ($checkCorreo->num_rows > 0) {
    die("<script>alert('Ya existe una cuenta registrada con ese correo'); window.history.back();</script>");
}
$checkCorreo->close();

// Hashear la clave antes de guardarla
$clave_encriptada = password_hash($clave, PASSWORD_DEFAULT);

// Insertar nuevo usuario usando consulta preparada
$stmt = $conexion->prepare("INSERT INTO usuario (nombre, apellido, nombre_usuario, correo_electronico, contraseña) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $nombre, $apellido, $usuario, $correo, $clave_encriptada);

if ($stmt->execute()) {
    echo "<script>alert('¡Registro exitoso! Ahora puedes iniciar sesión.'); window.location.href='index.html';</script>";
    exit();
} else {
    echo "<script>alert('Error al registrar: " . addslashes($stmt->error) . "'); window.history.back();</script>";
}

$stmt->close();
$conexion->close();
?>