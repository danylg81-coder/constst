<?php
include("db/conexion.php");

// Crear usuarios de prueba
$usuarios = [
    [
        'usuario' => 'admin',
        'password' => 'admin123',
        'email' => 'admin@madreaguast.cu',
        'rol' => 'admin',
        'nombre_completo' => 'Administrador Principal'
    ],
    [
        'usuario' => 'usuario1',
        'password' => 'user123',
        'email' => 'usuario1@madreaguast.cu',
        'rol' => 'user',
        'nombre_completo' => 'Usuario Normal 1'
    ],
    [
        'usuario' => 'usuario2', 
        'password' => 'user123',
        'email' => 'usuario2@madreaguast.cu',
        'rol' => 'user',
        'nombre_completo' => 'Usuario Normal 2'
    ]
];

foreach ($usuarios as $user_data) {
    // Verificar si ya existe
    $check = $conn->query("SELECT id FROM usuarios WHERE usuario = '{$user_data['usuario']}'");
    if ($check->num_rows == 0) {
        $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (usuario, email, password, rol, nombre_completo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $user_data['usuario'], $user_data['email'], $password_hash, $user_data['rol'], $user_data['nombre_completo']);
        
        if ($stmt->execute()) {
            echo "Usuario <strong>{$user_data['usuario']}</strong> creado exitosamente<br>";
            echo "Contraseña: <strong>{$user_data['password']}</strong><br>";
            echo "Rol: <strong>{$user_data['rol']}</strong><br><br>";
        } else {
            echo "Error creando usuario {$user_data['usuario']}: " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        echo "El usuario <strong>{$user_data['usuario']}</strong> ya existe.<br><br>";
    }
}

echo "<hr><h3>Credenciales para probar:</h3>";
echo "<strong>Administrador:</strong> admin / admin123<br>";
echo "<strong>Usuario normal 1:</strong> usuario1 / user123<br>";
echo "<strong>Usuario normal 2:</strong> usuario2 / user123<br>";
?>