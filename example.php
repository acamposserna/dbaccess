<?php

require_once __DIR__ . '/vendor/autoload.php';

use DbAccess\OdbcDatabase;

try {
    // Crear instancia de la clase con la configuración de la base de datos
    $db = new OdbcDatabase(
        server: 'localhost',
        port: '1433',
        database: 'mi_base_datos',
        username: 'mi_usuario',
        password: 'mi_contraseña',
        driver: 'ODBC Driver 17 for SQL Server'  // Opcional
    );

    // Conectar a la base de datos
    $db->connect();
    echo "Conexión establecida exitosamente\n";

    // --- Ejemplo 1: Ejecutar consulta SELECT simple ---
    $resultados = $db->executeSQL("SELECT * FROM usuarios");
    foreach ($resultados as $fila) {
        echo "Usuario: {$fila['nombre']}\n";
    }

    // --- Ejemplo 2: Ejecutar consulta SELECT con parámetros ---
    $sql = "SELECT * FROM usuarios WHERE edad > ? AND ciudad = ?";
    $parametros = [25, 'Madrid'];
    $resultados = $db->executeSQL($sql, $parametros);

    foreach ($resultados as $fila) {
        echo "Usuario: {$fila['nombre']}, Edad: {$fila['edad']}\n";
    }

    // --- Ejemplo 3: Ejecutar INSERT ---
    $sqlInsert = "INSERT INTO usuarios (nombre, edad, ciudad) VALUES (?, ?, ?)";
    $filasAfectadas = $db->executeCUD($sqlInsert, ['Juan', 30, 'Barcelona']);
    echo "Filas insertadas: {$filasAfectadas}\n";

    // --- Ejemplo 4: Ejecutar UPDATE ---
    $sqlUpdate = "UPDATE usuarios SET edad = ? WHERE nombre = ?";
    $filasAfectadas = $db->executeCUD($sqlUpdate, [31, 'Juan']);
    echo "Filas actualizadas: {$filasAfectadas}\n";

    // --- Ejemplo 5: Usar transacciones ---
    $db->beginTransaction();

    try {
        $db->executeCUD("INSERT INTO usuarios (nombre, edad) VALUES (?, ?)", ['María', 28]);
        $db->executeCUD("UPDATE cuentas SET saldo = saldo - ? WHERE usuario = ?", [100, 'Juan']);
        $db->executeCUD("UPDATE cuentas SET saldo = saldo + ? WHERE usuario = ?", [100, 'María']);

        // Si todo va bien, confirmar la transacción
        $db->commitTransaction();
        echo "Transacción completada exitosamente\n";
    } catch (Exception $e) {
        // Si hay algún error, revertir la transacción
        $db->rollbackTransaction();
        echo "Error en la transacción, se ha revertido: {$e->getMessage()}\n";
    }

    // --- Ejemplo 6: Ejecutar DELETE ---
    $sqlDelete = "DELETE FROM usuarios WHERE edad < ?";
    $filasAfectadas = $db->executeCUD($sqlDelete, [18]);
    echo "Filas eliminadas: {$filasAfectadas}\n";

    // Cerrar la conexión
    $db->close();
    echo "Conexión cerrada exitosamente\n";

} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}
