<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - UTASHOP</title>
    <link rel="stylesheet" href="style.css">
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
            max-width: 1200px;
            margin: 0 auto;
            gap: 60px;
            position: relative;
            z-index: 2;
        }
        
        .formulario {
            flex: 1;
            max-width: 500px;
            background: white;
            padding: 60px;
            border-radius: 12px;
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.1);
        }
        
        .formulario h1 {
            text-align: left;
            margin: 0 0 15px 0;
            color: #333;
            font-size: 36px;
            font-weight: 700;
        }
        
        .formulario h2 {
            text-align: left;
            margin: 0 0 40px 0;
            color: #666;
            font-size: 22px;
            font-weight: 400;
        }
        
        .form-group {
            position: relative;
            margin: 25px 0;
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
        }
        
        .form-group input:focus {
            border-color: #333;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
        }
        
        .recordar {
            margin: 15px 0 25px;
            font-size: 14px;
            text-align: right;
        }
        
        .recordar a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .recordar a:hover {
            color: #000;
            text-decoration: underline;
        }
        
        button[type="submit"] {
            width: 100%;
            height: 60px;
            border: none;
            background: #000;
            color: white;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        button[type="submit"]:hover {
            background: #333;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            font-size: 15px;
            color: #666;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
            color: #000;
        }
        
        .image-container {
            display: none; /* Hide the image container as we're using it as background */
        }
        
        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
                gap: 30px;
            }
            
            .formulario {
                width: 100%;
                max-width: 500px;
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="formulario">
            <h1>UTASHOP</h1>
            <h2>Recuperar Contraseña</h2>
            
            <form method="post" action="enviar_codigo.php">
                <div class="form-group">
                    <input 
                        type="email" 
                        name="correo" 
                        placeholder="correo@ejemplo.com" 
                        required
                    >
                </div>
                
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> Enviar Código
                </button>
                
                <div class="recordar">
                    <a href="index.html"><i class="fas fa-arrow-left"></i> Volver al inicio de sesión</a>
                </div>
            </form>
        </div>
        
        <div class="image-container">
            <!-- Image is now used as background -->
        </div>
    </div>






</body>
</html>
