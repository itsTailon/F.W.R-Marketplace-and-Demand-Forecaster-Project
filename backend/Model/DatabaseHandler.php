<?php

namespace TTE\App\Model;

/**
 * Provides a single point of access to the database
 */
class DatabaseHandler {

    /**
     * @var \PDO|null Used to store PDO instance
     */
    private static ?\PDO $pdo = null;

    private function __construct() {
    }

    /**
     * Returns the PDO instance.
     * @return \PDO|null
     */
    public static function getPDO() {
        // Create PDO instance if one has not already been created
        if (self::$pdo === null) {
            // Connect to database
            self::$pdo = new \PDO(''); // TODO: Add DSN

            // Have PDO errors communicated by means of exceptions being thrown
            self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Initialise database schema
            self::initSchema();
        }

        return self::$pdo;
    }

    /**
     * Initialise the database schema — i.e., creates necessary tables, etc. if they do not already exist (e.g., in a fresh installation).
     * @return void
     */
    public static function initSchema() {
        try {
            // TODO: Create tables

        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

}