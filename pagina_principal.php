<?php
session_start();
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}

// Obtener productos activos de la base de datos
$sql_productos = "SELECT p.*, u.nombre as vendedor_nombre 
                 FROM productos p 
                 JOIN usuario u ON p.usuario_vendedor = u.nombre_usuario 
                 WHERE p.estado = 'activo' 
                 ORDER BY p.fecha_publicacion DESC 
                 LIMIT 8";
$productos_result = $conexion->query($sql_productos);

// Obtener categorías
$sql_categorias = "SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND estado = 'activo' LIMIT 6";
$categorias_result = $conexion->query($sql_categorias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTASHOP - Página Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .producto:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .carrito-icon {
            position: relative;
        }
        .carrito-contador {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #EF4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .btn-vender {
            background: linear-gradient(135deg, #28a745, #20c997);
            transition: all 0.3s ease;
        }
        .btn-vender:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Barra de navegación -->
    <nav class="bg-gray-900 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="pagina_principal.php" class="text-2xl font-bold">UTASHOP</a>
            </div>
            
            <div class="flex-1 max-w-xl mx-4">
                <div class="relative">
                    <input type="text" placeholder="Buscar productos..." class="w-full px-4 py-2 rounded-full text-gray-800 focus:outline-none">
                    <button class="absolute right-3 top-2 text-gray-500">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Botón VENDER destacado -->
                <a href="publicar_producto.php" class="btn-vender px-4 py-2 rounded-full font-semibold text-white">
                    <i class="fas fa-plus-circle mr-2"></i>VENDER
                </a>
                 <a href="mis_productos.php" class="hover:text-gray-300">
        <i class="fas fa-store mr-2"></i>Mis Productos
    </a>
    
                <a href="perfil.php" class="hover:text-gray-300">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                </a>
                
                <a href="productos_guardados.php" class="hover:text-gray-300">
                    <i class="fas fa-heart"></i> Guardados
                </a>
                
                <a href="#" class="carrito-icon hover:text-gray-300">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="carrito-contador">0</span>
                </a>
                
                <a href="cerrar_sesion.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-md">
                    Cerrar sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="container mx-auto px-4 py-8">
        <!-- Banner principal -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl p-8 text-white mb-12">
            <h1 class="text-4xl font-bold mb-4">Bienvenido a UTASHOP</h1>
            <p class="text-xl mb-6">Compra y vende de forma segura en tu comunidad</p>
            <div class="flex gap-4">
                <a href="#productos" class="bg-white text-blue-800 px-6 py-2 rounded-full font-semibold hover:bg-gray-100">
                    Explorar Productos
                </a>
                <a href="publicar_producto.php" class="bg-green-500 text-white px-6 py-2 rounded-full font-semibold hover:bg-green-600">
                    <i class="fas fa-plus-circle mr-2"></i>Publicar Producto
                </a>
            </div>
        </div>

        <!-- Categorías -->
        <h2 class="text-2xl font-bold mb-6">Categorías Populares</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-12">
            <?php if ($categorias_result->num_rows > 0): ?>
                <?php while($categoria = $categorias_result->fetch_assoc()): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-shadow cursor-pointer">
                        <i class="fas fa-tag text-4xl text-blue-600 mb-2"></i>
                        <h3 class="font-semibold"><?php echo htmlspecialchars($categoria['categoria']); ?></h3>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-8">
                    <p class="text-gray-500">No hay categorías disponibles aún.</p>
                    <a href="publicar_producto.php" class="text-blue-600 hover:underline">Sé el primero en publicar</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Productos destacados -->
        <h2 id="productos" class="text-2xl font-bold mb-6">Productos Recientes</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <?php if ($productos_result->num_rows > 0): ?>
                <?php while($producto = $productos_result->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden producto">
                        <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                            <?php if($producto['imagen']): ?>
                                <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-image text-4xl text-gray-400"></i>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                            <p class="text-gray-600 mb-2 text-sm"><?php echo htmlspecialchars(substr($producto['descripcion'], 0, 80)); ?>...</p>
                            <p class="text-sm text-gray-500 mb-3">Vendedor: <?php echo htmlspecialchars($producto['vendedor_nombre']); ?></p>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-lg text-green-600">$<?php echo number_format($producto['precio'], 2); ?></span>
                                <div class="flex gap-2">
                                    <button class="bg-gray-200 text-gray-700 p-2 rounded hover:bg-gray-300">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                    <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-500 mb-2">No hay productos publicados</h3>
                    <p class="text-gray-400 mb-4">Sé el primero en publicar un producto</p>
                    <a href="publicar_producto.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus-circle mr-2"></i>Publicar Producto
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sección para vendedores -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-8 text-white mb-12">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-6 md:mb-0">
                    <h2 class="text-3xl font-bold mb-2">¿Quieres Vender?</h2>
                    <p class="text-lg opacity-90">Publica tus productos y llega a más clientes</p>
                </div>
                <div class="flex gap-4">
                    <a href="publicar_producto.php" class="bg-white text-green-600 px-6 py-3 rounded-full font-semibold hover:bg-gray-100">
                        <i class="fas fa-plus-circle mr-2"></i>Publicar Producto
                    </a>
                    <a href="mis_productos.php" class="border-2 border-white text-white px-6 py-3 rounded-full font-semibold hover:bg-white hover:text-green-600">
                        <i class="fas fa-store mr-2"></i>Mis Productos
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Pie de página (se mantiene igual) -->
    <footer class="bg-gray-900 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">Sobre Nosotros</h3>
                    <p class="text-gray-400">UTASHOP es tu plataforma segura para comprar y vender productos.</p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Enlaces Rápidos</h3>
                    <ul class="space-y-2">
                        <li><a href="pagina_principal.php" class="text-gray-400 hover:text-white">Inicio</a></li>
                        <li><a href="publicar_producto.php" class="text-gray-400 hover:text-white">Vender</a></li>
                        
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Contacto</h3>
                    <p class="text-gray-400">Email: utashop@gmail.com</p>
                    
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 UTASHOP. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Funcionalidad del carrito (se mantiene igual)
        document.addEventListener('DOMContentLoaded', function() {
            const botonesAgregar = document.querySelectorAll('button:has(.fa-cart-plus)');
            const contadorCarrito = document.querySelector('.carrito-contador');
            let contador = 0;

            botonesAgregar.forEach(boton => {
                boton.addEventListener('click', function() {
                    contador++;
                    contadorCarrito.textContent = contador;
                    
                    const boton = this;
                    boton.innerHTML = '<i class="fas fa-check"></i>';
                    boton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    boton.classList.add('bg-green-500', 'hover:bg-green-600');
                    
                    setTimeout(() => {
                        boton.innerHTML = '<i class="fas fa-cart-plus"></i>';
                        boton.classList.remove('bg-green-500', 'hover:bg-green-600');
                        boton.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>
<?php $conexion->close(); ?>