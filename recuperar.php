<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - UTASHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .form-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .form-header {
            background: linear-gradient(135deg, #2b6cb0 0%, #4299e1 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .form-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .form-logo img {
            max-width: 100%;
            max-height: 100%;
        }
        .form-body {
            padding: 30px;
        }
        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }
        .form-subtitle {
            color: #718096;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .form-input:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
            outline: none;
        }
        .btn {
            display: inline-block;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2b6cb0 0%, #4299e1 100%);
            color: white;
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #4a5568;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        .back-link:hover {
            color: #2b6cb0;
        }
        .back-link i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <div class="form-logo">
                <img src="Imagen.png" alt="UTASHOP Logo">
            </div>
            <h1 class="text-2xl font-bold">UTASHOP</h1>
            <p class="text-blue-100">Mercado Universitario</p>
        </div>
        
        <div class="form-body">
            <h2 class="form-title">Recuperar Contraseña</h2>
            <p class="form-subtitle">Ingresa tu correo electrónico para recibir un enlace de recuperación</p>
            
            <form method="post" action="enviar_codigo.php" class="space-y-6">
                <div class="form-group">
                    <div class="relative">
                        <input 
                            type="email" 
                            name="correo" 
                            class="form-input pl-10" 
                            placeholder="correo@ejemplo.com" 
                            required
                        >
                        <div class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane mr-2"></i> Enviar Código
                </button>
            </form>
            
            <div class="text-center mt-6">
                <a href="index.html" class="back-link">
                    <i class="fas fa-arrow-left"></i> Volver al inicio de sesión
                </a>
            </div>
        </div>
    </div>
</body>
</html>