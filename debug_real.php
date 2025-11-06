<?php
session_start();

echo "<h2>🔍 DEBUG COMPLETO DEL SISTEMA</h2>";

// 1. Estado de la sesión
echo "<h3>📋 SESIÓN ACTUAL:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// 2. Conectar a la BD y verificar carrito
try {
    $pdo = new PDO("mysql:host=localhost;dbname=supermercado;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>🛒 CARRITO EN BD:</h3>";
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM carrito WHERE id_usuario = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $carrito = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($carrito)) {
            echo "<p style='color: red;'>❌ NO HAY PRODUCTOS EN EL CARRITO PARA USER_ID: {$_SESSION['user_id']}</p>";
        } else {
            echo "<pre>";
            print_r($carrito);
            echo "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ NO HAY USER_ID EN LA SESIÓN</p>";
    }
    
    echo "<h3>👥 TODOS LOS CARRITOS:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM carrito");
    $stmt->execute();
    $todosCarritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($todosCarritos);
    echo "</pre>";
    
} catch(PDOException $e) {
    echo "Error BD: " . $e->getMessage();
}

echo "<hr>";
echo "<a href='login_rapido.php'>IR A LOGIN RÁPIDO</a> | ";
echo "<a href='direcciones/direcciones.php'>IR A CHECKOUT</a> | ";
echo "<a href='carrito/obtener_carrito.php'>PROBAR obtener_carrito.php</a>";
?>