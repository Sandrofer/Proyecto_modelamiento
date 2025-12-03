<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Verificar que viene del login con cuenta no verificada
if (!isset($_SESSION['usuario_pendiente'])) {
    header("Location: index.html");
    exit();
}

$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

// Procesar reenvío de verificación
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reenviar'])) {
    $usuario = $_SESSION['usuario_pendiente'];
    $correo = $_SESSION['correo_pendiente'];
    $nombre = $_SESSION['nombre_pendiente'];
    
    // Generar NUEVO token
    $nuevo_token = bin2hex(random_bytes(16));
    $nueva_expiracion = date("Y-m-d H:i:s", strtotime('+24 hours'));
    
    // Actualizar token en la base de datos
    $stmt = $conexion->prepare("UPDATE usuario SET token_verificacion = ?, token_verificacion_expiracion = ? WHERE nombre_usuario = ?");
    $stmt->bind_param("sss", $nuevo_token, $nueva_expiracion, $usuario);
    
    if ($stmt->execute()) {
        // Enviar nuevo correo de verificación
        $verification_link = "http://localhost/Proyecto/verificar_email.php?token=$nuevo_token";
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ferdex24@gmail.com';
            $mail->Password = 'gelc dxwm wuud jomm';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('ferdex24@gmail.com', 'UTASHOP');
            $mail->addAddress($correo);

            $mail->isHTML(true);
            $mail->Subject = 'Nuevo enlace de verificación - UTASHOP';
            $mail->Body = "
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
                        <h2>UTASHOP - Nuevo Enlace de Verificación</h2>
                    </div>
                    <div class='content'>
                        <p>Hola <strong>$nombre</strong>,</p>
                        <p>Has solicitado un nuevo enlace de verificación para tu cuenta en UTASHOP.</p>
                        <p>Haz clic en el siguiente enlace para activar tu cuenta:</p>
                        <p><a href='$verification_link' class='verify-button'>Verificar Mi Email</a></p>
                        <p><strong>Este enlace expirará en 24 horas.</strong></p>
                        <p>Si no solicitaste este nuevo enlace, puedes ignorar este mensaje.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2024 UTASHOP - Todos los derechos reservados</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->send();
            $mensaje = "✅ Se ha enviado un nuevo enlace de verificación a tu correo.";
            $tipo_mensaje = "success";
            
        } catch (Exception $e) {
            $mensaje = "❌ Error al enviar el correo. Intenta nuevamente.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "❌ Error al generar el nuevo enlace.";
        $tipo_mensaje = "error";
    }

    $stmt->close();
}

// Obtener datos de la sesión
$nombre_usuario = $_SESSION['nombre_pendiente'];
$correo_usuario = $_SESSION['correo_pendiente'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de correo - UTASHOP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #333333;
            --primary-hover: #1a1a1a;
            --secondary-color: #555555;
            --secondary-hover: #333333;
            --success-color: #28a745;
            --error-color: #dc3545;
            --light-bg: #f8f9fa;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            position: relative;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            overflow-x: hidden;
        }

        .background-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('Imagen.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.1;
            z-index: 0;
        }

        .container {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
            padding: 30px;
            justify-content: center;
            align-items: center;
        }

        .formulario {
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .formulario::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), #4a90e2);
        }

        h1 {
            color: var(--primary-color);
            font-size: 2.2rem;
            margin-bottom: 5px;
            font-weight: 700;
        }

        h2 {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 30px;
            font-weight: 500;
        }

        .mensaje {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: var(--border-radius);
            text-align: center;
            font-weight: 500;
            animation: fadeIn 0.5s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .info-box {
            background: linear-gradient(135deg, #f0f7ff 0%, #e1f0ff 100%);
            border-radius: var(--border-radius);
            padding: 25px;
            margin: 25px 0;
            text-align: left;
            border: 1px solid rgba(44, 90, 160, 0.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .info-box p {
            margin: 10px 0;
            color: #333;
            line-height: 1.6;
        }

        .info-box strong {
            color: var(--primary-color);
        }

        .btn-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 25px 0;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 15px 25px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn i {
            font-size: 1.1em;
            transition: transform 0.2s ease;
        }

        .btn:hover i {
            transform: translateX(2px);
        }

        .btn-primary {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            background-color: #f8f9fa;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .btn-primary:active {
            transform: translateY(0);
            background-color: #f1f3f5;
        }

        .btn-secondary {
            background: white;
            color: var(--secondary-color);
            border: 2px solid var(--secondary-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            background-color: #f8f9fa;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary:active {
            transform: translateY(0);
        }

        .consejos {
            margin-top: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: var(--border-radius);
            text-align: left;
            border: 1px dashed rgba(0, 0, 0, 0.1);
        }

        .consejos p {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .consejos p i {
            font-size: 1.1em;
        }

        .consejos ul {
            padding-left: 25px;
            list-style-type: none;
        }

        .consejos li {
            margin: 12px 0;
            position: relative;
            padding-left: 25px;
            color: #555;
        }

        .consejos li:before {
            content: '•';
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.5em;
            position: absolute;
            left: 0;
            top: -3px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .formulario {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Fondo con imagen -->
        <div class="background-image"></div>
        
        <!-- Contenido del formulario -->
        <div class="formulario">
            <h1>UTASHOP</h1>
            <h2>Verificación de correo requerida</h2>
            
            <?php if (isset($mensaje)): ?>
                <div class="mensaje <?php echo $tipo_mensaje === 'success' ? 'success' : 'error'; ?>">
                    <i class="fas <?php echo $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <span><?php echo $mensaje; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <p>Hola <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>,</p>
                <p>Tu cuenta aún no ha sido verificada. Hemos enviado un enlace de verificación a:</p>
                <p><strong><?php echo htmlspecialchars($correo_usuario); ?></strong></p>
                <p>Por favor revisa tu bandeja de entrada y haz clic en el enlace para activar tu cuenta.</p>
            </div>
            
            <div class="btn-container">
                <form method="POST" class="w-full">
                    <button type="submit" name="reenviar" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        <span>Reenviar correo de verificación</span>
                    </button>
                </form>
                
                <a href="index.html" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span>Volver al inicio de sesión</span>
                </a>
            </div>
            
            <div class="consejos">
                <p><i class="fas fa-question-circle"></i> ¿No recibiste el correo?</p>
                <ul>
                    <li>Revisa tu carpeta de correo no deseado o spam</li>
                    <li>Verifica que la dirección de correo sea correcta</li>
                    <li>Espera unos minutos y solicita otro enlace</li>
                </ul>
            </div>
        </div>
    </div>

    <video autoplay muted loop id="video-fondo">
        <source src="uta2.mp4" type="video/mp4">
    </video>
</body>
</html>
<?php $conexion->close(); ?>