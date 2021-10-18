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
     * Sets up the necessary tables
     */
    public static function setupAllTables(): void
    {
        // setup table checking function
        $tableExists = function(string $table){
            return self::$db->query("show tables like '{$table}'")->rowcount();
        };

        // create users table
        if (!$tableExists(self::$prefix.User::TABLE_NAME)){
            self::$db->query('
                create table '. self::getTable('User') . ' (
                    id int primary key auto_increment,
                    username varchar(32) unique not null,
                    password varchar(255) not null,
                    join_timestamp int default 0,
                    full_name varchar(64) not null,
                    email varchar(64) not null unique,
                    url varchar(64),
                    last_login int default null
                )
            ');
        }

        // create submissions table
        if (!$tableExists(self::$prefix . Submission::TABLE_NAME )){
            self::$db->query('
                create table '. self::getTable('Submission') . ' (
                    id int primary key auto_increment,
                    subject varchar(80) not null,
                    description text,
                    add_timestamp int default 0
                )
            ');
        }

        // create relations table
        if (!$tableExists(self::$prefix . Queries::R_SUBMISSION_USER)){
            self::$db->query('
                create table '. self::$prefix . Queries::R_SUBMISSION_USER . ' (
                    submission_id int,
                    user_id int,

                    foreign key (submission_id) references '. self::$prefix . Submission::TABLE_NAME .'(id),
                    foreign key (user_id) references '. self::$prefix . User::TABLE_NAME .'(id)
                )
            ');
        }
    }
}
