<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de usuario - UTASHOP</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .mensaje-validacion {
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        .form-group {
            position: relative;
            margin: 20px 0;
        }
        .form-group input {
            width: 100%;
            padding: 16px 20px;
            height: 56px;
            font-size: 16px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #f9f9f9;
            outline: none;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus {
            border-color: #333;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
        }
        .form-group label {
            position: absolute;
            top: -10px;
            left: 10px;
            color: #666;
            font-size: 13px;
            background: white;
            padding: 0 5px;
            pointer-events: none;
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
        /* Estilos para el modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-contenido {
            background-color: #fff;
            margin: 10% auto;
            padding: 30px;
            border-radius: 8px;
            width: 70%;
            max-width: 600px;
            position: relative;
            max-height: 70vh;
            overflow-y: auto;
        }
        .cerrar {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .terminos-link {
            color: #0066cc;
            text-decoration: underline;
            cursor: pointer;
            padding: 0 3px;
        }
        .terminos-link:hover {
            color: #004999;
        }
            position: absolute;
            top: -10px;
            left: 10px;
            color: #666;
            font-size: 13px;
            background: white;
            padding: 0 5px;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="formulario">
            <h1>UTASHOP</h1>
            <h2>Crear Cuenta</h2>
            <form method="post" action="guardar_usurio.php" id="registroForm">
                <div class="form-group">
                    <input type="text" name="nombre" placeholder="Nombre" required>
                    <label>Nombre</label>
                </div>

                <div class="form-group">
                    <input type="text" name="apellido" placeholder="Apellido" required>
                    <label>Apellido</label>
                </div>

                <div class="form-group">
                    <input type="text" name="usuario" placeholder="Nombre de usuario" required>
                    <label>Nombre de usuario</label>
                </div>

                <div class="form-group">
                    <input type="email" name="correo" placeholder="Correo electrónico" required>
                    <label>Correo electrónico</label>
                </div>

                <div class="form-group">
                    <input type="password" name="clave" id="clave" placeholder="Contraseña" required
                        pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}"
                        title="Debe tener mínimo 8 caracteres, una mayúscula, una minúscula, un número y un símbolo especial.">
                    <label>Contraseña</label>
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
                    </div>
                </div>

                <div class="form-group">
                    <input type="password" name="confirmar_clave" id="confirmar_clave" placeholder="Confirmar contraseña" required>
                    <label>Confirmar contraseña</label>
                    <div class="mensaje-validacion" id="mensajeConfirmarClave">Las contraseñas no coinciden.</div>
                </div>

                <div class="form-group" style="display: flex; align-items: center; margin: 20px 0;">
                    <input type="checkbox" id="terminos" name="terminos" required style="width: auto; margin-right: 10px;">
                    <div style="display: flex; align-items: center;">
                        <span style="color: #666; font-size: 14px;">Acepto los </span>
                        <a href="#" class="terminos-link" onclick="mostrarTerminos(event)" style="margin-left: 5px;">términos y condiciones</a>
                    </div>
                </div>

                <!-- Modal de Términos y Condiciones -->
                <div id="modalTerminos" class="modal">
                    <div class="modal-contenido">
                        <span class="cerrar" onclick="cerrarModal()">&times;</span>
                        <h2>Términos y Condiciones</h2>
                        <p>Por favor, lea atentamente los siguientes términos y condiciones antes de registrarse:</p>
                        <ol>
                            <li>El usuario se compromete a hacer un uso adecuado de la plataforma.</li>
                            <li>No se permite el uso de la plataforma con fines ilícitos.</li>
                            <li>El usuario es responsable de mantener la confidencialidad de su cuenta y contraseña.</li>
                            <li>Nos reservamos el derecho de modificar estos términos en cualquier momento.</li>
                            <li>El uso continuado de la plataforma después de dichas modificaciones constituirá su consentimiento a tales cambios.</li>
                        </ol>
                        <p>Al marcar la casilla de verificación, acepta estos términos y condiciones en su totalidad.</p>
                    </div>
                </div>

                <input type="submit" value="Registrarse">

                <div class="registrarse">
                    ¿Ya tienes cuenta? <a href="index.html">Inicia sesión</a>
                </div>
            </form>
        </div>
        <div class="image-container">
            <img src="Imagen.png" alt="UTASHOP" class="login-image">
        </div>
    </div>

    <video autoplay muted loop id="video-fondo">
        <source src="uta2.mp4" type="video/mp4">
    </video>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar variables globales
        let passwordValid = false;
        let confirmPasswordValid = false;
        // Validación de contraseña en tiempo real
        const passwordInput = document.getElementById('clave');
        const confirmPasswordInput = document.getElementById('confirmar_clave');
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
            passwordValid = tieneLongitud && tieneMayuscula && tieneMinuscula && tieneNumero && tieneEspecial;
            if (passwordValid) {
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
            const mensajeError = document.getElementById('mensajeConfirmarClave');
            
            if (password && confirmPassword) {
                if (password !== confirmPassword) {
                    mensajeError.textContent = 'Las contraseñas no coinciden';
                    mensajeError.style.color = '#e53e3e';
                    mensajeError.style.display = 'block';
                    confirmPasswordValid = false;
                    return false;
                } else {
                    const tieneLongitud = password.length >= 8;
                    const tieneMayuscula = /[A-Z]/.test(password);
                    const tieneMinuscula = /[a-z]/.test(password);
                    const tieneNumero = /\d/.test(password);
                    const tieneEspecial = /[^A-Za-z0-9]/.test(password);
                    
                    if (tieneLongitud && tieneMayuscula && tieneMinuscula && tieneNumero && tieneEspecial) {
                        mensajeError.textContent = 'Las contraseñas coinciden';
                        mensajeError.style.color = '#38a169';
                        mensajeError.style.display = 'block';
                        confirmPasswordValid = true;
                        return true;
                    } else {
                        mensajeError.textContent = 'La contraseña no cumple con los requisitos';
                        mensajeError.style.color = '#e53e3e';
                        mensajeError.style.display = 'block';
                        confirmPasswordValid = false;
                        return false;
                    }
                }
            } else {
                mensajeError.style.display = 'none';
                confirmPasswordValid = false;
                return false;
            }
        }
        
        // Validación del formulario
        document.getElementById('registroForm').addEventListener('submit', function(e) {
            // Validar nuevamente antes de enviar
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
        
        // Manejo del modal de términos y condiciones
        window.mostrarTerminos = function(event) {
            if (event) {
                event.preventDefault();
            }
            document.getElementById('modalTerminos').style.display = 'block';
            return false;
        }

        window.cerrarModal = function() {
            document.getElementById('modalTerminos').style.display = 'none';
        }

        // Cerrar el modal al hacer clic fuera del contenido
        window.onclick = function(event) {
            const modal = document.getElementById('modalTerminos');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        // Validar términos y condiciones
        const terminos = document.getElementById("terminos");
        if (!terminos.checked) {
            alert("Debes aceptar los términos y condiciones para continuar.");
            return false;
        }

        // Validar que las contraseñas coincidan
        if (clave !== confirmarClave) {
            mensajeConfirmar.style.display = "block";
            return false;
        }
        mensajeConfirmar.style.display = "none";

        if (!terminos.checked) {
            alert("Debes aceptar los términos y condiciones para continuar");
            return false;
        }

        return true;
    }
    </script>
</body>
</html>
