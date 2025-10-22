<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de usuario - UTASHOP</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .mensaje-validacion {
            font-size: 12px;
            color: #D32F2F;
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
            <form method="post" action="guardar_usurio.php" onsubmit="return validarFormulario()">
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
                    <div class="mensaje-validacion" id="mensajeClave">La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula, un número y un símbolo especial.</div>
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

    <script>
    function mostrarTerminos(event) {
        if (event) {
            event.preventDefault();
        }
        document.getElementById('modalTerminos').style.display = 'block';
        return false;
    }

    function cerrarModal() {
        document.getElementById('modalTerminos').style.display = 'none';
    }

    // Cerrar el modal al hacer clic fuera del contenido
    window.onclick = function(event) {
        const modal = document.getElementById('modalTerminos');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    function validarFormulario() {
        const clave = document.getElementById("clave").value;
        const confirmarClave = document.getElementById("confirmar_clave").value;
        const mensaje = document.getElementById("mensajeClave");
        const mensajeConfirmar = document.getElementById("mensajeConfirmarClave");
        const terminos = document.getElementById("terminos");
        const regex = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/;

        // Validar formato de contraseña
        if (!regex.test(clave)) {
            mensaje.style.display = "block";
            return false;
        }
        mensaje.style.display = "none";

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
