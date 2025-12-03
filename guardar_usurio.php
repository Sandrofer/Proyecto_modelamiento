<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

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

// Generar token de verificación
$token_verificacion = bin2hex(random_bytes(16));
$expiracion = date("Y-m-d H:i:s", strtotime('+24 hours'));

// Insertar nuevo usuario CON EMAIL NO VERIFICADO
$stmt = $conexion->prepare("INSERT INTO usuario (nombre, apellido, nombre_usuario, correo_electronico, contraseña, token_verificacion, token_verificacion_expiracion, email_verificado) VALUES (?, ?, ?, ?, ?, ?, ?, FALSE)");
$stmt->bind_param("sssssss", $nombre, $apellido, $usuario, $correo, $clave_encriptada, $token_verificacion, $expiracion);

if ($stmt->execute()) {
    // ✅ USAR EXACTAMENTE LA MISMA CONFIGURACIÓN QUE enviar_codigo.php
    $verification_link = "http://localhost/Proyecto/verificar_email.php?token=$token_verificacion";
    
    $subject = "Verifica tu email - UTASHOP";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
            .email-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; }
            .header { background: #2c5aa0; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; color: #333; }
            .verify-button { display: inline-block; background: #2c5aa0; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 15px 0; }
            .footer { background: #f4f4f4; padding: 15px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h2>UTASHOP - Verificación de Email</h2>
            </div>
            <div class='content'>
                <p>Hola <strong>$nombre</strong>,</p>
                <p>¡Bienvenido a UTASHOP!</p>
                <p>Para activar tu cuenta, por favor verifica tu dirección de email haciendo clic en el siguiente enlace:</p>
                <p><a href='$verification_link' class='verify-button'>Verificar Mi Email</a></p>
                <p><strong>Este enlace expirará en 24 horas.</strong></p>
                <p>Si no creaste esta cuenta, puedes ignorar este mensaje.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 UTASHOP - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // ✅ CONFIGURACIÓN IDÉNTICA A enviar_codigo.php
    $mail = new PHPMailer(true);
    try {
        // MISMA CONFIGURACIÓN SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ferdex24@gmail.com';  // MISMO CORREO
        $mail->Password = 'gelc dxwm wuud jomm';  // MISMA CONTRASEÑA
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // MISMO REMITENTE
        $mail->setFrom('ferdex24@gmail.com', 'UTASHOP');
        $mail->addAddress($correo);

        // MISMO CONTENIDO HTML
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        // ENVIAR CORREO
        $mail->send();
        echo "<script>
            alert('✅ Registro exitoso. Se ha enviado un enlace de verificación a tu correo electrónico.');
            window.location.href = 'index.html';
        </script>";
        
    } catch (Exception $e) {
        echo "<script>
            alert('❌ Error al enviar el correo de verificación: " . $e->getMessage() . "');
            window.history.back();
        </script>";
    }
    
} else {
    echo "<script>alert('Error al registrar: " . addslashes($stmt->error) . "'); window.history.back();</script>";
}

$stmt->close();
$conexion->close();
?>