<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta Desactivada - MarketHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Barra de navegación -->
    <nav class="bg-gray-900 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold">MarketHub</a>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="container mx-auto px-4 py-12">
        <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-md overflow-hidden p-8 text-center">
            <div class="flex justify-center mb-6">
                <div class="bg-red-100 p-4 rounded-full">
                    <i class="fas fa-user-slash text-red-500 text-4xl"></i>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Cuenta Desactivada</h1>
            <p class="text-gray-600 mb-8">
                Tu cuenta ha sido desactivada exitosamente. Lamentamos verte partir, pero respetamos tu decisión.
            </p>
            
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-8 text-left">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>¿Cambiaste de opinión?</strong> Si deseas reactivar tu cuenta en el futuro, 
                            por favor contacta a nuestro equipo de soporte.
                        </p>
                    </div>
                </div>
            </div>
            
            <a href="index.html" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-home mr-2"></i> Volver al Inicio
            </a>
        </div>
    </main>

    <!-- Pie de página -->
    <footer class="bg-gray-900 text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="text-center text-sm text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> MarketHub. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>
