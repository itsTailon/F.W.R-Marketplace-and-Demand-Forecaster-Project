<?php

namespace TTE\App\Model;

/**
 * Provides a single point of access to the database
 */
class DatabaseHandler {

    /**
     * @var string Database name
     */
    private static string $DB_NAME = "TeamProjectApp";

    /**
     * @var string Database port. Change for your local dev env., but don't commit changes. (consider adding this file to your gitignore)
     */
    private static string $DB_PORT = "8889";

    /**
     * @var string Database user
     */
    private static string $DB_USERNAME = "root";

    /**
     * @var string Database user password
     */
    private static string $DB_PASSWORD = "root";


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
            self::$pdo = new \PDO("mysql:host=127.0.0.1;dbname=" . self::$DB_NAME . ";port=" . self::$DB_PORT, self::$DB_USERNAME, self::$DB_PASSWORD); // TODO: Add DSN

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
    private static function initSchema() {
        try {
            // Create tables for data model:

            // Create 'account' table
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS account (userID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, email VARCHAR(128) NOT NULL UNIQUE, passwordHash VARCHAR(256) NOT NULL, accountType VARCHAR(64) NOT NULL);");

            // Create 'customer' table
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS customer (customerID INT NOT NULL PRIMARY KEY, username VARCHAR(128) NOT NULL, streak INT DEFAULT 0, FOREIGN KEY (customerID) REFERENCES account(userID));");

            // Create 'seller' table
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS seller (sellerID INT NOT NULL PRIMARY KEY, sellerName VARCHAR(128) NOT NULL, sellerAddress VARCHAR(256) NOT NULL, FOREIGN KEY (sellerID) REFERENCES account(userID));");

            // Create 'bundle' table
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS bundle (bundleID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, bundleStatus ENUM('available', 'reserved', 'collected', 'cancelled') NOT NULL, title VARCHAR(128) NOT NULL,  details TEXT NOT NULL,  rrp DECIMAL(8, 2) NOT NULL, discountedPrice DECIMAL(8, 2) NOT NULL, CHECK (rrp > discountedPrice), sellerID INT NOT NULL, purchaserID INT DEFAULT NULL, FOREIGN KEY (sellerID) REFERENCES seller(sellerID), FOREIGN KEY (purchaserID) REFERENCES customer(customerID));");


            // Create supporting tables for RBAC:

            // Create RBAC roles table
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS rbac_roles (title VARCHAR(128) PRIMARY KEY);");

            // Create RBAC permissions table
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS rbac_permissions (title VARCHAR(128) PRIMARY KEY);");

            // Create RBAC PA table
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS rbac_pa (roleTitle VARCHAR(128) NOT NULL, permissionTitle VARCHAR(128) NOT NULL, PRIMARY KEY (roleTitle, permissionTitle), FOREIGN KEY (roleTitle) REFERENCES rbac_roles(title), FOREIGN KEY (permissionTitle) REFERENCES rbac_permissions(title));");

            // Create RBAC UA table
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS rbac_ua (userID INT NOT NULL, roleTitle VARCHAR(128) NOT NULL, PRIMARY KEY (userID, roleTitle), FOREIGN KEY (userID) REFERENCES account(userID), FOREIGN KEY (roleTitle) REFERENCES rbac_roles(title));");

        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

}