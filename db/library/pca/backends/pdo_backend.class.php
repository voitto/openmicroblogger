<?php

/**
 * \brief Backend class using a database (mySQL; Postgres, ...).
 *
 * This backend uses PDO to store each (key, value, ttl) triplet in a row in a
 * database which has to be manually created (see data/pdo-scheme.sql).
 *
 * If mySQL is used as database server, some queries will be optimized. This
 * feature is also planned for other database servers.
 *
 * This backend requires garbage collection, please make sure to call gc()
 * periodically.
 *
 * List of options:
 *  - pdo: A PDO connection.
 *  - dsn: The dsn of the database (only if pdo is not specified).
 *  - username: The username for the database server (optional).
 *  - password: The password for the database server (optional).
 *  - table_name: The table where cached values will be stored. The default is
 *  'cache'.
 *  - optimize_sql: Boolean value. When set to false, queries won't be
 *  optimized for a specific database server. This option should only be used
 *  internally for unit testing.
 */

class PDOBackend extends BackendSkeleton
{

    /**
     * \brief The PDO connection.
     */
    private $pdo;

    /**
     * \brief The table where cached values will be stored.
     */
    private $table_name = 'cache';

    /**
     * The driver for which queries will be optimized. If set to null, generic
     * queries will be used.
     */
    private $driver = null;

    public function __construct($options)
    {
        if(isset($options['pdo']) && $options['pdo'] instanceof PDO)
            $this->pdo = $options['pdo'];
        else
            $this->pdo = new PDO($options['dsn'], isset($options['username']) ? $options['username'] : null, isset($options['password']) ? $options['password'] : null);
        if(isset($options['table_name']))
            $this->table_name = $options['table_name'];
        if(!isset($options['optimize_sql']) || $options['optimize_sql'])
            $this->driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function exists($key)
    {
        $bind_values = array(
            ':key' => $key,
            ':now' => time()
        );
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM ' . $this->table_name . ' WHERE cache_key = :key AND (cache_expires IS NULL OR cache_expires >=:now)');
        $stmt->execute($bind_values);
        return ($stmt->fetchColumn() == 1);
    }

    public function add($key, $value, $ttl = 0)
    {
        $this->gc();
        $bind_values = array(
            ':key' => $key,
            ':value' => serialize($value),
            ':expires' => ($ttl == 0 ? null : (time() + $ttl))
        );
        $stmt = $this->pdo->prepare('INSERT INTO ' . $this->table_name . ' (cache_key, cache_value, cache_expires) VALUES (:key, :value, :expires)');
        return $stmt->execute($bind_values);
    }

    public function set($key, $value, $ttl = 0)
    {
        $bind_values = array(
            ':key' => $key,
            ':value' => serialize($value),
            ':expires' => ($ttl == 0 ? null : (time() + $ttl))
        );
        switch($this->driver)
        {
            case 'mysql':
                $stmt = $this->pdo->prepare('REPLACE INTO ' . $this->table_name . ' (cache_key, cache_value, cache_expires) VALUES (:key, :value, :expires)');
                return $stmt->execute($bind_values);
            default:
                $stmt = $this->pdo->prepare('INSERT INTO ' . $this->table_name . ' (cache_key, cache_value, cache_expires) VALUES (:key, :value, :expires)');
                if(!$stmt->execute($bind_values))
                {
                    $stmt = $this->pdo->prepare('UPDATE ' . $this->table_name . ' SET cache_value = :value, cache_expires = :expires WHERE cache_key = :key');
                    return $stmt->execute($bind_values);
                }
                return true;
        }
    }

    public function get($key)
    {
        $stmt = $this->pdo->prepare('SELECT cache_value FROM ' . $this->table_name . ' WHERE cache_key = :key AND (cache_expires IS NULL OR cache_expires >= :now)');
        $stmt->bindValue(':key', $key);
        $stmt->bindValue(':now', time());
        $stmt->execute();
        if(($data = $stmt->fetchColumn()) !== false)
            return unserialize($data);
        else
            return null;
    }

    public function delete($key)
    {
        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->table_name . ' WHERE cache_key = :key AND (cache_expires IS NULL OR cache_expires >= :now)');
        $stmt->bindValue(':key', $key);
        $stmt->bindValue(':now', time());
        $stmt->execute();
        return ($stmt->rowCount() == 1);
    }

    public function flush()
    {
        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->table_name);
        return $stmt->execute();
    }

    public function gc()
    {
        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->table_name . ' WHERE cache_expires IS NOT NULL AND cache_expires < :now');
        $stmt->bindValue(':now', time());
        return $stmt->execute();
    }

}
