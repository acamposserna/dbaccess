<?php

declare(strict_types=1);

namespace DbAccess;

use Exception;

/**
 * Clase para gestionar el acceso a base de datos con ODBC
 *
 * Proporciona métodos para conectar, ejecutar consultas y gestionar transacciones
 * usando la extensión ODBC de PHP.
 *
 * @package DbAccess
 * @author  Claude
 * @version 1.0.0
 */
class OdbcDatabase
{
    /**
     * Recurso de conexión ODBC
     *
     * @var resource|null
     */
    private $connection = null;

    /**
     * Cadena de conexión ODBC
     *
     * @var string
     */
    private string $connectionString;

    /**
     * Nombre de usuario para la base de datos
     *
     * @var string
     */
    private string $username;

    /**
     * Contraseña para la base de datos
     *
     * @var string
     */
    private string $password;

    /**
     * Indica si hay una transacción activa
     *
     * @var bool
     */
    private bool $inTransaction = false;

    /**
     * Constructor de la clase
     *
     * @param string $server   Nombre o IP del servidor de base de datos
     * @param string $port     Puerto del servidor
     * @param string $database Nombre de la base de datos
     * @param string $username Usuario de la base de datos
     * @param string $password Contraseña del usuario
     * @param string $driver   Driver ODBC a utilizar (opcional)
     *
     * @throws Exception Si los parámetros son inválidos
     */
    public function __construct(
        string $server,
        string $port,
        string $database,
        string $username,
        string $password,
        string $driver = 'ODBC Driver 17 for SQL Server'
    ) {
        if (empty($server) || empty($database) || empty($username)) {
            throw new Exception('Los parámetros servidor, base de datos y usuario son obligatorios');
        }

        // Construir la cadena de conexión ODBC
        $this->connectionString = sprintf(
            'DRIVER={%s};SERVER=%s,%s;DATABASE=%s;',
            $driver,
            $server,
            $port,
            $database
        );

        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Establece la conexión con la base de datos
     *
     * @return void
     * @throws Exception Si no se puede establecer la conexión
     */
    public function connect(): void
    {
        if ($this->connection !== null) {
            throw new Exception('Ya existe una conexión activa');
        }

        $connection = @odbc_connect(
            $this->connectionString,
            $this->username,
            $this->password
        );

        if ($connection === false) {
            $error = odbc_errormsg();
            throw new Exception("Error al conectar con la base de datos: {$error}");
        }

        $this->connection = $connection;
    }

    /**
     * Cierra la conexión con la base de datos
     *
     * @return void
     * @throws Exception Si hay una transacción activa o si falla el cierre
     */
    public function close(): void
    {
        if ($this->connection === null) {
            throw new Exception('No hay ninguna conexión activa para cerrar');
        }

        if ($this->inTransaction) {
            throw new Exception('No se puede cerrar la conexión con una transacción activa. Ejecute commit o rollback primero');
        }

        if (!@odbc_close($this->connection)) {
            $error = odbc_errormsg($this->connection);
            throw new Exception("Error al cerrar la conexión: {$error}");
        }

        $this->connection = null;
    }

    /**
     * Ejecuta una consulta SQL SELECT con o sin parámetros
     *
     * @param string $sql        Consulta SQL a ejecutar
     * @param array  $parameters Parámetros para la consulta preparada (opcional)
     *
     * @return array Array asociativo con los resultados
     * @throws Exception Si no hay conexión o si la consulta falla
     */
    public function executeSQL(string $sql, array $parameters = []): array
    {
        $this->ensureConnection();

        if (empty($parameters)) {
            $result = @odbc_exec($this->connection, $sql);
        } else {
            $result = @odbc_prepare($this->connection, $sql);

            if ($result === false) {
                $error = odbc_errormsg($this->connection);
                throw new Exception("Error al preparar la consulta: {$error}");
            }

            if (!@odbc_execute($result, $parameters)) {
                $error = odbc_errormsg($this->connection);
                throw new Exception("Error al ejecutar la consulta preparada: {$error}");
            }
        }

        if ($result === false) {
            $error = odbc_errormsg($this->connection);
            throw new Exception("Error al ejecutar la consulta SQL: {$error}");
        }

        $rows = [];
        while ($row = odbc_fetch_array($result)) {
            $rows[] = $row;
        }

        odbc_free_result($result);

        return $rows;
    }

    /**
     * Ejecuta sentencias CUD (Create, Update, Delete)
     *
     * @param string $sql        Sentencia SQL (INSERT, UPDATE, DELETE)
     * @param array  $parameters Parámetros para la sentencia preparada (opcional)
     *
     * @return int Número de filas afectadas
     * @throws Exception Si no hay conexión o si la sentencia falla
     */
    public function executeCUD(string $sql, array $parameters = []): int
    {
        $this->ensureConnection();

        if (empty($parameters)) {
            $result = @odbc_exec($this->connection, $sql);

            if ($result === false) {
                $error = odbc_errormsg($this->connection);
                throw new Exception("Error al ejecutar la sentencia CUD: {$error}");
            }
        } else {
            $result = @odbc_prepare($this->connection, $sql);

            if ($result === false) {
                $error = odbc_errormsg($this->connection);
                throw new Exception("Error al preparar la sentencia CUD: {$error}");
            }

            if (!@odbc_execute($result, $parameters)) {
                $error = odbc_errormsg($this->connection);
                throw new Exception("Error al ejecutar la sentencia CUD preparada: {$error}");
            }
        }

        $affectedRows = odbc_num_rows($result);
        odbc_free_result($result);

        return $affectedRows;
    }

    /**
     * Inicia una transacción
     *
     * @return void
     * @throws Exception Si no hay conexión, ya hay una transacción activa o falla al iniciar
     */
    public function beginTransaction(): void
    {
        $this->ensureConnection();

        if ($this->inTransaction) {
            throw new Exception('Ya existe una transacción activa');
        }

        if (!@odbc_autocommit($this->connection, false)) {
            $error = odbc_errormsg($this->connection);
            throw new Exception("Error al iniciar la transacción: {$error}");
        }

        $this->inTransaction = true;
    }

    /**
     * Confirma y finaliza la transacción activa
     *
     * @return void
     * @throws Exception Si no hay transacción activa o falla el commit
     */
    public function commitTransaction(): void
    {
        $this->ensureConnection();

        if (!$this->inTransaction) {
            throw new Exception('No hay ninguna transacción activa para confirmar');
        }

        if (!@odbc_commit($this->connection)) {
            $error = odbc_errormsg($this->connection);
            throw new Exception("Error al confirmar la transacción: {$error}");
        }

        if (!@odbc_autocommit($this->connection, true)) {
            $error = odbc_errormsg($this->connection);
            throw new Exception("Error al restaurar autocommit: {$error}");
        }

        $this->inTransaction = false;
    }

    /**
     * Revierte y finaliza la transacción activa
     *
     * @return void
     * @throws Exception Si no hay transacción activa o falla el rollback
     */
    public function rollbackTransaction(): void
    {
        $this->ensureConnection();

        if (!$this->inTransaction) {
            throw new Exception('No hay ninguna transacción activa para revertir');
        }

        if (!@odbc_rollback($this->connection)) {
            $error = odbc_errormsg($this->connection);
            throw new Exception("Error al revertir la transacción: {$error}");
        }

        if (!@odbc_autocommit($this->connection, true)) {
            $error = odbc_errormsg($this->connection);
            throw new Exception("Error al restaurar autocommit: {$error}");
        }

        $this->inTransaction = false;
    }

    /**
     * Verifica que existe una conexión activa
     *
     * @return void
     * @throws Exception Si no hay conexión activa
     */
    private function ensureConnection(): void
    {
        if ($this->connection === null) {
            throw new Exception('No hay conexión activa. Ejecute connect() primero');
        }
    }

    /**
     * Obtiene el estado de la conexión
     *
     * @return bool True si hay una conexión activa, false en caso contrario
     */
    public function isConnected(): bool
    {
        return $this->connection !== null;
    }

    /**
     * Obtiene el estado de la transacción
     *
     * @return bool True si hay una transacción activa, false en caso contrario
     */
    public function isInTransaction(): bool
    {
        return $this->inTransaction;
    }

    /**
     * Destructor de la clase
     * Cierra la conexión si está activa al destruir el objeto
     */
    public function __destruct()
    {
        if ($this->connection !== null && !$this->inTransaction) {
            @odbc_close($this->connection);
        }
    }
}
