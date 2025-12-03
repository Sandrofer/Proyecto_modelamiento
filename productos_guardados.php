<?php
session_start();
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}

// Obtener el ID del usuario actual
$nombre_usuario = $_SESSION['usuario_id'];
$sql_usuario_id = "SELECT id FROM usuario WHERE nombre_usuario = ?";
$stmt = $conexion->prepare($sql_usuario_id);
$stmt->bind_param("s", $nombre_usuario);
$stmt->execute();
$resultado_usuario = $stmt->get_result();
$usuario_data = $resultado_usuario->fetch_assoc();
$usuario_id = $usuario_data['id'];
$stmt->close();

// Procesar eliminar favoritos
if (isset($_POST['eliminar_favorito'])) {
    $producto_id = $_POST['producto_id'];
    
    $sql_eliminar = "DELETE FROM productos_favoritos WHERE usuario_id = ? AND producto_id = ?";
    $stmt = $conexion->prepare($sql_eliminar);
    $stmt->bind_param("ii", $usuario_id, $producto_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: productos_guardados.php");
    exit();
}

// Obtener productos favoritos del usuario
$sql_favoritos = "SELECT p.*, u.nombre as vendedor_nombre, pf.fecha_guardado 
                  FROM productos_favoritos pf 
                  JOIN productos p ON pf.producto_id = p.id 
                  JOIN usuario u ON p.usuario_vendedor = u.nombre_usuario 
                  WHERE pf.usuario_id = ? AND p.estado = 'activo' 
                  ORDER BY pf.fecha_guardado DESC";
$stmt = $conexion->prepare($sql_favoritos);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$favoritos_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos Guardados - UTASHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .producto:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .favorito-btn {
            transition: all 0.3s ease;
        }
        .favorito-btn:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Barra de navegación (igual a la página principal) -->
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
                <a href="publicar_producto.php" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-full font-semibold text-white">
                    <i class="fas fa-plus-circle mr-2"></i>VENDER
                </a>
                <a href="mis_productos.php" class="hover:text-gray-300">
                    <i class="fas fa-store mr-2"></i>Mis Productos
                </a>
                
                <a href="perfil.php" class="hover:text-gray-300">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                </a>
                
                <a href="productos_guardados.php" class="hover:text-gray-300 font-semibold text-red-300">
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
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Mis Productos Guardados</h1>
                <p class="text-gray-600 mt-2">Tus productos favoritos en un solo lugar</p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm">
                    <?php echo $favoritos_result->num_rows; ?> productos
                </span>
                <a href="pagina_principal.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al Inicio
                </a>
            </div>
        </div>

        <?php if ($favoritos_result->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php while($producto = $favoritos_result->fetch_assoc()): ?>
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
                            <p class="text-xs text-gray-400 mb-3">
                                Guardado el: <?php echo date('d/m/Y', strtotime($producto['fecha_guardado'])); ?>
                            </p>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-lg text-green-600">$<?php echo number_format($producto['precio'], 2); ?></span>
                                <div class="flex gap-2">
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                        <button type="submit" name="eliminar_favorito" class="favorito-btn bg-red-100 text-red-600 p-2 rounded hover:bg-red-200" title="Quitar de favoritos">
                                            <i class="fas fa-heart-broken"></i>
                                        </button>
                                    </form>
                                    <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16">
                <i class="fas fa-heart text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-500 mb-2">No tienes productos guardados</h3>
                <p class="text-gray-400 mb-6">Descubre productos increíbles y guárdalos para verlos después</p>
                <a href="pagina_principal.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i>Explorar Productos
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Pie de página -->
    <footer class="bg-gray-900 text-white py-8 mt-12">
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
                        <li><a href="productos_guardados.php" class="text-gray-400 hover:text-white">Productos Guardados</a></li>
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
</body>
</html>
<?php $conexion->close(); ?>