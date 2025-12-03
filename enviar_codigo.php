<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

if ($conexion->connect_error) {
    die("No tiene conexión: " . $conexion->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'];

    // Verificar si el correo existe en la tabla USUARIO (singular)
    $stmt = $conexion->prepare("SELECT * FROM usuario WHERE correo_electronico = ?");
    $stmt->bind_param("s", $correo);    
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        // Recuperar el usuario
        $usuario = $resultado->fetch_assoc();
        $nombre_usuario = $usuario['nombre_usuario'];

        // Generar un token único
        $token = bin2hex(random_bytes(16));

        // Guardar el token en la tabla USUARIO
        $expiracion = date("Y-m-d H:i:s", strtotime('+1 hour'));
        $stmt = $conexion->prepare("UPDATE usuario SET token_recuperacion = ?, token_expiracion = ? WHERE correo_electronico = ?");
        $stmt->bind_param("sss", $token, $expiracion, $correo);
        
        if ($stmt->execute()) {
            // Enviar el enlace de restablecimiento
            $reset_link = "http://localhost/Proyecto/restablecer.php?token=$token";
            $subject = "Recuperación de Contraseña - UTASHOP";
            
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                    .email-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; }
                    .header { background: #2c5aa0; color: white; padding: 20px; text-align: center; }
                    .content { padding: 30px; color: #333; }
                    .reset-button { display: inline-block; background: #2c5aa0; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 15px 0; }
                    .footer { background: #f4f4f4; padding: 15px; text-align: center; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='header'>
                        <h2>UTASHOP - Recuperación de Contraseña</h2>
                    </div>
                    <div class='content'>
                        <p>Hola <strong>$nombre_usuario</strong>,</p>
                        <p>Has solicitado restablecer tu contraseña en UTASHOP.</p>
                        <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
                        <p><a href='$reset_link' class='reset-button'>Restablecer Contraseña</a></p>
                        <p><strong>Este enlace expirará en 1 hora.</strong></p>
                        <p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2024 UTASHOP - Todos los derechos reservados</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            // Configurar PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Configuración del servidor SMTP (usa Gmail que es más confiable)
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ferdex24@gmail.com';  // CAMBIA ESTO
                $mail->Password = 'gelc dxwm wuud jomm';  // CAMBIA ESTO
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Remitente y destinatario
                $mail->setFrom('ferdex24@gmail.com', 'UTASHOP');
                $mail->addAddress($correo);

                // Contenido
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;

                // Enviar correo
                $mail->send();
                echo "<script>alert('Se ha enviado un enlace de recuperación a tu correo.'); window.location.href='index.html';</script>";
            } catch (Exception $e) {
                echo "<script>alert('Error al enviar el correo: " . $e->getMessage() . "'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Error al generar el token. Intenta nuevamente.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('No encontramos un usuario con ese correo electrónico.'); window.history.back();</script>";
    }

    $conexion->close();
}
?>