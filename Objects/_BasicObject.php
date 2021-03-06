<?php declare(strict_types = 1);

namespace Sketchspace\Object;

use PDOStatement;

interface BasicObject
{
    /**
     * Error constants
     */
    public const FAILED = -1;
    public const NOT_MODIFIED = 0;
    public const UPDATED = 1;
    public const ADDED = 2;
    public const INITIALIZED = 3;

    /**
     * PDO handler for differently-named variables
     * @param mixed $name   key name
     * @param mixed $value  key contents
     */
    public function __set(mixed $name, mixed $value): void;

    /**
     * [Database]
     * Push the object into the database
     * @return int Status of the commit
     */
    public function commitToDatabase(): int;

    /**
     * Does the object reflect its actual state in the database?
     * @return bool whether or not the User is sync'd up
     */
    public function isObjectInSync(): bool;

    /**
     * Because ID's should be private
     * @return int
     */
    public function getId(): int;

    /**
     * Generate an object from an ID. Useful for quick fetching.
     * @param  int   ID of the requested resource
     * @return mixed The proper object from the ID
     */
    public static function fromId(int $id): mixed;

    /**
     * Creates an object using the result of a query.
     * This will create objects one fetch() at a time.
     * @param  PDOStatement $statement Query results
     * @return mixed
     */
    public static function fromStatement(PDOStatement $statement): mixed;

    /*
     * Set up any necessary tables
     */
    public static function initTables(): int;
}
