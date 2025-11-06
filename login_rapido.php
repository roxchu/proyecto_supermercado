<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Rápido - Supermercado</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 600px; 
            margin: 50px auto; 
            padding: 20px; 
            background: #f5f5f5; 
        }
        .login-container { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .user-option { 
            background: #f8f9fa; 
            border: 1px solid #ddd; 
            margin: 10px 0; 
            padding: 15px; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: all 0.3s; 
        }
        .user-option:hover { 
            background: #e9ecef; 
            border-color: #007bff; 
        }
        .user-option.selected { 
            background: #d4edda; 
            border-color: #28a745; 
        }
        .btn { 
            background: #007bff; 
            color: white; 
            padding: 12px 24px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px; 
        }
        .btn:hover { background: #0056b3; }
        .current-user { 
            background: #d1ecf1; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>🔐 Acceso Rápido al Supermercado</h2>
        
        <?php 
        session_start();
        require_once '../carrito/db.php';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_POST['user_id'] ?? null;
            
            if ($user_id) {
                // Obtener datos del usuario
                $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
                $stmt->execute([$user_id]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usuario) {
                    $_SESSION['user_id'] = $usuario['id_usuario'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['rol'] = 'client';
                    $_SESSION['nombre'] = $usuario['nombre_usuario'];
                    $_SESSION['dni'] = $usuario['DNI'];
                    
                    // Configurar carrito con productos para este usuario
                    $stmt = $pdo->prepare("DELETE FROM carrito WHERE id_usuario = ?");
                    $stmt->execute([$usuario['id_usuario']]);
                    
                    // Productos específicos por usuario
                    $productos = [];
                    switch ($usuario['id_usuario']) {
                        case 1: // Cliente 1
                            $productos = [
                                ['id' => 1, 'precio' => 2500.00, 'cantidad' => 2], // Manzanas
                                ['id' => 4, 'precio' => 1850.00, 'cantidad' => 1]  // Coca Cola
                            ];
                            break;
                        case 2: // Admin
                            $productos = [
                                ['id' => 2, 'precio' => 8900.00, 'cantidad' => 1], // Pollo
                                ['id' => 3, 'precio' => 1250.00, 'cantidad' => 2]  // Leche
                            ];
                            break;
                        case 3: // Empleado
                            $productos = [
                                ['id' => 5, 'precio' => 1680.00, 'cantidad' => 1], // Arroz
                                ['id' => 6, 'precio' => 2200.00, 'cantidad' => 1]  // Pan
                            ];
                            break;
                        case 5: // Cliente 5
                            $productos = [
                                ['id' => 1, 'precio' => 2500.00, 'cantidad' => 1], // Manzanas
                                ['id' => 5, 'precio' => 1680.00, 'cantidad' => 2]  // Arroz
                            ];
                            break;
                    }
                    
                    foreach ($productos as $producto) {
                        $stmt = $pdo->prepare("
                            INSERT INTO carrito (id_usuario, Id_Producto, Precio_Unitario_Momento, Cantidad) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $usuario['id_usuario'],
                            $producto['id'],
                            $producto['precio'],
                            $producto['cantidad']
                        ]);
                    }
                    
                    echo "<div style='color: green; margin: 15px 0; padding: 10px; background: #d4edda; border-radius: 5px;'>";
                    echo "✅ Conectado como: {$usuario['nombre_usuario']} | Carrito configurado con " . count($productos) . " productos";
                    echo "</div>";
                }
            }
        }
        
        if (isset($_SESSION['user_id'])) {
            echo "<div class='current-user'>";
            echo "<h3>👤 Usuario Actual</h3>";
            echo "<p><strong>Nombre:</strong> {$_SESSION['nombre']}</p>";
            echo "<p><strong>ID:</strong> {$_SESSION['user_id']}</p>";
            echo "<p><strong>DNI:</strong> {$_SESSION['dni']}</p>";
            echo "</div>";
            
            echo "<div style='text-align: center; margin: 20px 0;'>";
            echo "<a href='/proyecto_supermercado/index.html' class='btn' style='text-decoration: none; margin-right: 10px;'>🏠 Ir al Inicio</a>";
            echo "<a href='/proyecto_supermercado/direcciones/direcciones.php' class='btn' style='text-decoration: none; background: #28a745;'>🛒 Ir al Checkout</a>";
            echo "</div>";
            
            echo "<p style='text-align: center;'><a href='?logout=1' style='color: #dc3545;'>Cerrar Sesión</a></p>";
        } else {
            echo "<p>Selecciona un usuario para continuar:</p>";
            echo "<form method='post'>";
            
            // Obtener usuarios disponibles
            $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario IN (1,2,3,5) ORDER BY id_usuario");
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($usuarios as $usuario) {
                echo "<div class='user-option' onclick='selectUser({$usuario['id_usuario']})'>";
                echo "<input type='radio' name='user_id' value='{$usuario['id_usuario']}' id='user_{$usuario['id_usuario']}' style='margin-right: 10px;'>";
                echo "<label for='user_{$usuario['id_usuario']}' style='cursor: pointer;'>";
                echo "<strong>{$usuario['nombre_usuario']}</strong><br>";
                echo "<small>DNI: {$usuario['DNI']} | ID: {$usuario['id_usuario']}</small>";
                echo "</label>";
                echo "</div>";
            }
            
            echo "<div style='text-align: center; margin-top: 20px;'>";
            echo "<button type='submit' class='btn'>Ingresar al Supermercado</button>";
            echo "</div>";
            echo "</form>";
        }
        
        // Logout
        if (isset($_GET['logout'])) {
            session_destroy();
            echo "<script>window.location.href = window.location.pathname;</script>";
        }
        ?>
    </div>

    <script>
        function selectUser(userId) {
            // Desmarcar todos
            document.querySelectorAll('.user-option').forEach(el => el.classList.remove('selected'));
            document.querySelectorAll('input[name="user_id"]').forEach(el => el.checked = false);
            
            // Marcar seleccionado
            document.getElementById('user_' + userId).checked = true;
            document.getElementById('user_' + userId).closest('.user-option').classList.add('selected');
        }
    </script>
</body>
</html>