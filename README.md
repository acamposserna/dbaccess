# DBAccess - Clase PHP para ODBC

Clase PHP 8.1+ para gestionar el acceso a bases de datos mediante ODBC de forma segura y eficiente.

## Características

- ✅ Conexión y desconexión de base de datos
- ✅ Ejecución de consultas SELECT con resultados
- ✅ Ejecución de sentencias INSERT, UPDATE, DELETE
- ✅ Consultas preparadas con parámetros (prevención de SQL injection)
- ✅ Gestión de transacciones (begin, commit, rollback)
- ✅ Manejo completo de excepciones
- ✅ Compatible con PHP 8.1+
- ✅ PSR-4 autoloading

## Requisitos

- PHP >= 8.1
- Extensión PHP ODBC habilitada
- Driver ODBC instalado (ej: ODBC Driver 17 for SQL Server)

## Instalación

```bash
composer install
```

## Uso

### Conexión básica

```php
use DbAccess\OdbcDatabase;

$db = new OdbcDatabase(
    server: 'localhost',
    port: '1433',
    database: 'mi_base_datos',
    username: 'usuario',
    password: 'contraseña'
);

$db->connect();
```

### Ejecutar consultas SELECT

```php
// Sin parámetros
$resultados = $db->executeSQL("SELECT * FROM usuarios");

// Con parámetros
$resultados = $db->executeSQL(
    "SELECT * FROM usuarios WHERE edad > ? AND ciudad = ?",
    [25, 'Madrid']
);

foreach ($resultados as $fila) {
    echo $fila['nombre'];
}
```

### Ejecutar INSERT, UPDATE, DELETE

```php
// INSERT
$filasAfectadas = $db->executeCUD(
    "INSERT INTO usuarios (nombre, edad) VALUES (?, ?)",
    ['Juan', 30]
);

// UPDATE
$filasAfectadas = $db->executeCUD(
    "UPDATE usuarios SET edad = ? WHERE nombre = ?",
    [31, 'Juan']
);

// DELETE
$filasAfectadas = $db->executeCUD(
    "DELETE FROM usuarios WHERE edad < ?",
    [18]
);
```

### Transacciones

```php
$db->beginTransaction();

try {
    $db->executeCUD("INSERT INTO usuarios (nombre) VALUES (?)", ['María']);
    $db->executeCUD("UPDATE cuentas SET saldo = saldo - 100 WHERE id = ?", [1]);
    $db->executeCUD("UPDATE cuentas SET saldo = saldo + 100 WHERE id = ?", [2]);

    $db->commitTransaction();
} catch (Exception $e) {
    $db->rollbackTransaction();
    throw $e;
}
```

### Cerrar conexión

```php
$db->close();
```

## Métodos disponibles

### Constructor

```php
__construct(
    string $server,
    string $port,
    string $database,
    string $username,
    string $password,
    string $driver = 'ODBC Driver 17 for SQL Server'
)
```

### Métodos públicos

- `connect()`: Establece la conexión con la base de datos
- `close()`: Cierra la conexión con la base de datos
- `executeSQL(string $sql, array $parameters = []): array`: Ejecuta consultas SELECT
- `executeCUD(string $sql, array $parameters = []): int`: Ejecuta INSERT, UPDATE, DELETE
- `beginTransaction()`: Inicia una transacción
- `commitTransaction()`: Confirma la transacción
- `rollbackTransaction()`: Revierte la transacción
- `isConnected(): bool`: Verifica si hay conexión activa
- `isInTransaction(): bool`: Verifica si hay transacción activa

## Manejo de errores

Todos los métodos lanzan excepciones `Exception` con mensajes descriptivos en caso de error:

```php
try {
    $db->connect();
    $db->executeSQL("SELECT * FROM tabla_inexistente");
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Ejemplo completo

Consulta el archivo `example.php` para ver un ejemplo completo de uso con todos los métodos disponibles.

## Licencia

MIT