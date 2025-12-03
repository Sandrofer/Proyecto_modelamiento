<?php
// restablecer.php
session_start();
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

// Verificar si viene token por GET (desde el correo)
$token = isset($_GET['token']) ? $_GET['token'] : '';

if ($token) {
    // Verificar token válido y no expirado en tabla USUARIO
    $stmt = $conexion->prepare("SELECT * FROM usuario WHERE token_recuperacion = ? AND token_expiracion > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $token_valido = $resultado->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - UTASHOP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('Imagen.png') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            z-index: 1;
        }
        
        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 90%;
            max-width: 500px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        
        .formulario {
            width: 100%;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        
        .formulario h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .mensaje {
            background: #f8f9fa;
            border-left: 4px solid #2b6cb0;
            padding: 15px;
            margin-bottom: 25px;
            text-align: left;
            border-radius: 4px;
        }
        
        .mensaje.error {
            border-left-color: #e53e3e;
        }
        
        .mensaje.success {
            border-left-color: #38a169;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4a5568;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #2b6cb0;
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.2);
            outline: none;
        }
        
        .btn {
            display: inline-block;
            background: #000;
            color: white;
            padding: 14px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            border: none;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background: #4a5568;
            margin-top: 10px;
        }
        
        .enlace {
            margin-top: 20px;
            font-size: 14px;
        }
        
        .enlace a {
            color: #2b6cb0;
            text-decoration: none;
            font-weight: 500;
        }
        
        .enlace a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .formulario {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="formulario">
           
            <h1>Restablecer Contraseña</h1>
            
            <?php if (isset($token_valido) && $token_valido): ?>
                
                <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                    <?php
                    $nueva_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    
                    // Actualizar contraseña y limpiar token en tabla USUARIO
                    $stmt = $conexion->prepare("UPDATE usuario SET contraseña = ?, token_recuperacion = NULL, token_expiracion = NULL WHERE token_recuperacion = ?");
                    $stmt->bind_param("ss", $nueva_password, $token);
                    
                    if ($stmt->execute()) {
                        echo "<div class='mensaje success'>
                                <p><strong>¡Contraseña actualizada correctamente!</strong></p>
                                <p>Tu contraseña ha sido restablecida con éxito. Ahora puedes iniciar sesión con tu nueva contraseña.</p>
                                <div style='margin-top: 15px;'>
                                    <a href='index.html' class='btn'>Iniciar Sesión</a>
                                </div>
                              </div>";
                    } else {
                        echo "<div class='mensaje error'>
                                <p><strong>Error al actualizar la contraseña</strong></p>
                                <p>Ocurrió un error al intentar actualizar tu contraseña. Por favor, inténtalo de nuevo.</p>
                                <div style='margin-top: 15px;'>
                                    <a href='restablecer.php?token=" . htmlspecialchars($token) . "' class='btn'>Intentar de nuevo</a>
                                </div>
                              </div>";
                    }
                    ?>
                
                <?php else: ?>
                    <form method="post" action="" class="formulario" id="resetForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label for="password">Nueva Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control" required
                                   placeholder="Ingresa tu nueva contraseña">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmar Contraseña</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                                   placeholder="Confirma tu nueva contraseña">
                        </div>
                        
                        <button type="submit" class="btn">
                            <i class="fas fa-key"></i> Restablecer Contraseña
                        </button>
                        
                        <div class="enlace">
                            <a href="index.html"><i class="fas fa-arrow-left"></i> Volver al inicio de sesión</a>
                        </div>
                    </form>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="mensaje error">
                    <p><strong>Enlace inválido o expirado</strong></p>
                    <p>El enlace de recuperación no es válido o ha expirado. Por favor, solicita uno nuevo.</p>
                    <div style="margin-top: 15px;">
                        <a href="recuperar.php" class="btn">
                            <i class="fas fa-sync-alt"></i> Solicitar nuevo enlace
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .requisitos-contrasena {
            text-align: left;
            margin: 10px 0 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 13px;
            color: #4a5568;
        }
        .requisito {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }
        .requisito i {
            margin-right: 8px;
            font-size: 12px;
        }
        .requisito.cumplido {
            color: #38a169;
        }
        .requisito.incumplido {
            color: #e53e3e;
        }
        .requisito.cumplido i {
            color: #38a169;
        }
        .requisito.incumplido i {
            color: #e53e3e;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetForm');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            // Crear contenedor de requisitos
            const passwordContainer = passwordInput.parentNode;
            const requisitosHTML = `
                <div id="requisitos" style="margin-top: 10px; text-align: left; display: none;">
                    <div class="requisito no-cumplido" id="req-longitud">
                        <i class="fas fa-times"></i>
                        <span>Mínimo 8 caracteres</span>
                    </div>
                    <div class="requisito no-cumplido" id="req-mayuscula">
                        <i class="fas fa-times"></i>
                        <span>Al menos una mayúscula</span>
                    </div>
                    <div class="requisito no-cumplido" id="req-minuscula">
                        <i class="fas fa-times"></i>
                        <span>Al menos una minúscula</span>
                    </div>
                    <div class="requisito no-cumplido" id="req-numero">
                        <i class="fas fa-times"></i>
                        <span>Al menos un número</span>
                    </div>
                    <div class="requisito no-cumplido" id="req-especial">
                        <i class="fas fa-times"></i>
                        <span>Al menos un carácter especial</span>
                    </div>
                </div>
                <div id="mensajeClave" class="mensaje-validacion" style="color: #e53e3e; margin-top: 10px; display: none;">
                    La contraseña no cumple con todos los requisitos.
                </div>`;
            
            passwordContainer.insertAdjacentHTML('beforeend', requisitosHTML);
            const requisitos = document.getElementById('requisitos');
            const mensajeClave = document.getElementById('mensajeClave');
            
            // Validación en tiempo real de la contraseña
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const tieneLongitud = password.length >= 8;
                const tieneMayuscula = /[A-Z]/.test(password);
                const tieneMinuscula = /[a-z]/.test(password);
                const tieneNumero = /\d/.test(password);
                const tieneEspecial = /[^A-Za-z0-9]/.test(password);
                
                // Mostrar/ocultar contenedor de requisitos
                requisitos.style.display = password ? 'block' : 'none';
                
                // Actualizar iconos de requisitos
                actualizarRequisito('longitud', tieneLongitud);
                actualizarRequisito('mayuscula', tieneMayuscula);
                actualizarRequisito('minuscula', tieneMinuscula);
                actualizarRequisito('numero', tieneNumero);
                actualizarRequisito('especial', tieneEspecial);
                
                // Actualizar mensaje de validación
                const esValida = tieneLongitud && tieneMayuscula && tieneMinuscula && tieneNumero && tieneEspecial;
                if (esValida) {
                    mensajeClave.style.display = 'none';
                } else {
                    mensajeClave.style.display = 'block';
                }
                
                // Validar coincidencia si hay texto en confirmación
                if (confirmPasswordInput.value) {
                    validarCoincidencia(password, confirmPasswordInput.value);
                }
            });
            
            // Función para actualizar el estado de un requisito
            function actualizarRequisito(id, cumple) {
                const elemento = document.getElementById(`req-${id}`);
                const icono = elemento.querySelector('i');
                
                if (cumple) {
                    elemento.classList.remove('no-cumplido');
                    elemento.classList.add('cumplido');
                    icono.classList.remove('fa-times');
                    icono.classList.add('fa-check');
                } else {
                    elemento.classList.remove('cumplido');
                    elemento.classList.add('no-cumplido');
                    icono.classList.remove('fa-check');
                    icono.classList.add('fa-times');
                }
            }
            
            // Validación de coincidencia de contraseñas
            function validarCoincidencia(password, confirmPassword) {
                let mensaje = document.getElementById('mensaje-coincidencia');
                
                if (!mensaje) {
                    confirmPasswordInput.insertAdjacentHTML('afterend', 
                        '<div id="mensaje-coincidencia" class="mensaje-validacion"></div>');
                    mensaje = document.getElementById('mensaje-coincidencia');
                }
                
                if (password && confirmPassword) {
                    if (password !== confirmPassword) {
                        mensaje.textContent = 'Las contraseñas no coinciden';
                        mensaje.style.color = '#e53e3e';
                        mensaje.style.display = 'block';
                        return false;
                    } else {
                        const tieneLongitud = password.length >= 8;
                        const tieneMayuscula = /[A-Z]/.test(password);
                        const tieneMinuscula = /[a-z]/.test(password);
                        const tieneNumero = /\d/.test(password);
                        const tieneEspecial = /[^A-Za-z0-9]/.test(password);
                        
                        if (tieneLongitud && tieneMayuscula && tieneMinuscula && tieneNumero && tieneEspecial) {
                            mensaje.textContent = 'Las contraseñas coinciden';
                            mensaje.style.color = '#38a169';
                            mensaje.style.display = 'block';
                            confirmPasswordValid = true;
                            return true;
                        } else {
                            mensaje.textContent = 'La contraseña no cumple con los requisitos';
                            mensaje.style.color = '#e53e3e';
                            mensaje.style.display = 'block';
                            confirmPasswordValid = false;
                            return false;
                        }
                    }
                } else {
                    mensaje.style.display = 'none';
                    return false;
                }
            }
            
            // Variables para rastrear el estado de validación
            let passwordValid = false;
            let confirmPasswordValid = false;
            
            // Validación del formulario
            form.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                // Validar requisitos de la contraseña
                const tieneLongitud = password.length >= 8;
                const tieneMayuscula = /[A-Z]/.test(password);
                const tieneMinuscula = /[a-z]/.test(password);
                const tieneNumero = /\d/.test(password);
                const tieneEspecial = /[^A-Za-z0-9]/.test(password);
                
                passwordValid = tieneLongitud && tieneMayuscula && tieneMinuscula && tieneNumero && tieneEspecial;
                const coinciden = validarCoincidencia(password, confirmPassword);
                
                if (!passwordValid || !confirmPasswordValid) {
                    e.preventDefault();
                    mensajeClave.style.display = 'block';
                    requisitos.style.display = 'block';
                    
                    // Mostrar mensaje de error general si no hay errores específicos visibles
                    if (document.querySelectorAll('.no-cumplido:not([style*="display: none"])').length === 0) {
                        mensajeClave.textContent = 'Por favor, completa todos los campos requeridos correctamente.';
                        mensajeClave.style.display = 'block';
                    }
                    
                    // Desplazarse al primer error
                    const primerError = document.querySelector('.no-cumplido');
                    if (primerError) {
                        primerError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        // Si no hay errores visibles pero la validación falla, desplazarse al mensaje de error
                        mensajeClave.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
            
            // Validar confirmación de contraseña en tiempo real
            confirmPasswordInput.addEventListener('input', function() {
                validarCoincidencia(passwordInput.value, this.value);
            });
        });
    </script>
    <style>
        .mensaje-validacion {
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        .requisito {
            display: flex;
            align-items: center;
            margin: 5px 0;
            font-size: 13px;
            color: #666;
        }
        .requisito i {
            margin-right: 8px;
            font-size: 10px;
        }
        .requisito.cumplido {
            color: #38a169;
        }
        .requisito.cumplido i {
            color: #38a169;
        }
        .requisito.no-cumplido {
            color: #e53e3e;
        }
        .requisito.no-cumplido i {
            color: #e53e3e;
        }
    </style>
</body>
</html>
<?php $conexion->close(); ?>