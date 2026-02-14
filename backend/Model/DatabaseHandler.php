<?php

namespace TTE\App\Model;

use TTE\App\Auth\RBACManager;
use TTE\App\Global\AppConfig;

/**
 * Provides a single point of access to the database
 */
class DatabaseHandler {

    /**
     * @var string Database name
     */
    private static string $DB_NAME = AppConfig::DB_NAME;

    /**
     * @var string Database port. Change for your local dev env., but don't commit changes. (consider adding this file to your gitignore)
     */
    private static string $DB_PORT = AppConfig::DB_PORT;

    /**
     * @var string Database user
     */
    private static string $DB_USERNAME = AppConfig::DB_USER;

    /**
     * @var string Database user password
     */
    private static string $DB_PASSWORD = AppConfig::DB_PASSWORD;

    /**
     * @var string Database host
     */
    private static string $DB_HOST = AppConfig::DB_HOST;


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
            self::$pdo = new \PDO("mysql:host=" . self::$DB_HOST . ";dbname=" . self::$DB_NAME . ";port=" . self::$DB_PORT, self::$DB_USERNAME, self::$DB_PASSWORD); // TODO: Add DSN

            // Have PDO errors communicated by means of exceptions being thrown
            self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Initialise database schema and add base data
            self::initSchema();
            self::initBaseData();
        }

        return self::$pdo;
    }

    /**
     * Initialise the database schema — i.e., creates necessary tables, etc. if they do not already exist (e.g., in a fresh installation).
     * @return void
     */
    private static function initSchema(): void {
        try {
            // Create tables for data model:

            self::$pdo->exec(
                <<<END
                 CREATE TABLE IF NOT EXISTS account ( -- formerly `user`
                    userID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(128) NOT NULL UNIQUE,
                    passwordHash VARCHAR(256) NOT NULL,
                    accountType ENUM('seller', 'customer') NOT NULL
                    );
                
                CREATE TABLE IF NOT EXISTS customer (
                    customerID INT NOT NULL PRIMARY KEY,
                    username VARCHAR(128) NOT NULL, -- non-identifying name
                    streak INT DEFAULT 0,
                    FOREIGN KEY (customerID) REFERENCES account(userID)
                    );
                
                CREATE TABLE IF NOT EXISTS seller (
                    sellerID INT NOT NULL PRIMARY KEY,
                    sellerName VARCHAR(128) NOT NULL, -- formerly `name`
                    sellerAddress VARCHAR(256) NOT NULL,
                    FOREIGN KEY (sellerID) REFERENCES account(userID)
                    );
                
                CREATE TABLE IF NOT EXISTS bundle (
                    bundleID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    bundleStatus ENUM('available', 'reserved', 'collected', 'cancelled') NOT NULL,
                    title VARCHAR(128) NOT NULL, -- formerly `name`
                    details TEXT NOT NULL, -- formerly `description`
                    rrp DECIMAL(8, 2) NOT NULL, -- recommended retail price
                    discountedPrice DECIMAL(8, 2) NOT NULL,
                    CHECK (rrp > discountedPrice), -- the discounted price should be less than the retail price
                    sellerID INT NOT NULL,
                    purchaserID INT DEFAULT NULL,
                    FOREIGN KEY (sellerID) REFERENCES seller(sellerID) ON DELETE CASCADE,
                    FOREIGN KEY (purchaserID) REFERENCES customer(customerID)
                    );
                
                CREATE TABLE IF NOT EXISTS allergen (
                    allergenName VARCHAR (64) NOT NULL PRIMARY KEY
                );
                
                CREATE TABLE IF NOT EXISTS bundle_allergen (
                    bundleID INT NOT NULL,
                    allergenName VARCHAR (64) NOT NULL,
                    PRIMARY KEY (bundleID, allergenName),
                    FOREIGN KEY (bundleID) REFERENCES bundle(bundleID) ON DELETE CASCADE,
                    FOREIGN KEY (allergenName) REFERENCES allergen(allergenName) ON DELETE CASCADE
                );
                
                CREATE TABLE IF NOT EXISTS reservation (
                    reservationID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    bundleID INT NOT NULL,
                    purchaserID INT NOT NULL,
                    reservationStatus ENUM ('active', 'completed', 'no-show', 'cancelled') NOT NULL,
                    claimCode VARCHAR (16) NOT NULL UNIQUE,
                    FOREIGN KEY (bundleID) REFERENCES bundle (bundleID) ON DELETE CASCADE,
                    FOREIGN KEY (purchaserID) REFERENCES customer (customerID) ON DELETE CASCADE
                );
                
                CREATE TABLE IF NOT EXISTS issue (
                    issueID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    customerID INT NOT NULL,
                    bundleID INT NOT NULL,
                    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
                    resolvedAt DATETIME DEFAULT NULL,
                    issueDescription TEXT NOT NULL,
                    sellerResponse TEXT,
                    issueStatus ENUM ('ongoing', 'resolved') NOT NULL,
                    FOREIGN KEY (customerID) REFERENCES customer (customerID) ON DELETE CASCADE,
                    FOREIGN KEY (bundleID) REFERENCES bundle (bundleID) ON DELETE CASCADE
                );
                
                CREATE TABLE IF NOT EXISTS streak (
                    streakID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    customerID INT NOT NULL, 
                    startDate DATETIME DEFAULT NULL,
                    endDate DATETIME DEFAULT NULL,
                    currentWeekStart DATETIME DEFAULT NULL, 
                    FOREIGN KEY (customerID) REFERENCES customer (customerID) ON DELETE CASCADE
                );
                END
            );


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

    /**
     * Initialises database by inserting 'base' data (i.e. RBAC roles, allergens, etc).
     * @return void
     */
    private static function initBaseData(): void {

        // Initialise database with allergen data
        $allergens = [
            "celery",
            "gluten",
            "crustaceans",
            "eggs",
            "fish",
            "lupin",
            "milk",
            "molluscs",
            "mustard",
            "nuts",
            "peanuts",
            "sesame-seeds",
            "soya",
            "sulphites"
        ];

        // Add each allergen if it does not already exist (IGNORE).
        foreach ($allergens as $allergen) {
            $stmt = DatabaseHandler::getPDO()->prepare("INSERT IGNORE INTO allergen (allergenName) VALUES (:allergenName);");
            $stmt->execute(["allergenName" => $allergen]);
        }


        // Initialise DB with RBAC roles and permissions
        $rbac = [
            "seller" => [
                "bundle_update",
                "bundle_create",
                "bundle_load",
                "bundle_delete",
            ],

            "customer" => [
                "bundle_load",
                "streak_load",
                "streak_delete",
            ],
        ];

        // Create roles and permissions
        foreach ($rbac as $role => $permissions) {
            // Create role if it does not exist
            if (!RBACManager::roleExists($role)) {
                RBACManager::createRole($role);
            }

            // Add permissions to role
            foreach ($permissions as $permission) {
                // If the permission does not already exist, create it
                if (!RBACManager::permissionExists($permission)) {
                    RBACManager::createPermission($permission);
                }

                // If the role does not already have the permission, add it
                if (!RBACManager::isRolePermitted($role, $permission)){
                    RBACManager::assignPermissionToRole($role, $permission);
                }
            }
        }



    }

}