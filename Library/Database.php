<?php declare(strict_types = 1);

namespace Sketchspace\Library;

use PDO;
use PDOException;
use Sketchspace\Enum\Queries;

use Sketchspace\Object\User;
use Sketchspace\Object\Submission;

/**
 * Allows to easily access the database through a static class.
 */
class Database
{
    public static PDO $db;
    public static string $prefix = '';

    /**
     * Initializes the database object
     * @param  string $engine The DB engine to use
     * @param  string $server Server host or name
     * @param  string $db_name DB name
     * @param  string $user DB username
     * @param  string $pass DB user password
     * @param  string $prefix Prefix to use for generated tables. (optional)
     */
    public static function initDb (string $engine, string $server, string $db_name, string $user, string $pass, string $prefix = ''): void
    {
        // connect to our DB
        self::$db = new PDO (
            $engine .
            ':host=' . $server .
            ';dbname=' . $db_name,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        // if prefix matches safe criteria, set it
        if (preg_match('/^[A-z0-9]+$/', $prefix)) {
            self::$prefix = $prefix;
        }
    }

    /**
     * Shortcut for specifying table names mapped to objects.
     * Because spamming `Database::$prefix.Table::TABLE_NAME` looks fugly.
     * @param  string $table_class               [description]
     * @return string             [description]
     */
    public static function getTable(string $table_class): string
    {
        return self::$prefix . ('Sketchspace\\Object\\'.$table_class)::TABLE_NAME;
    }

    /**
     * Check if a table exists
     */
    public static function tableExists(string $table): int
    {
       return self::$db->query("show tables like '{$table}'")->rowcount();
    }
}
