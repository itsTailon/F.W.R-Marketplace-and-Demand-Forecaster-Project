<?php

namespace TTE\App\Auth;

use TTE\App\Model\Account;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\NoSuchAccountException;

class RBACManager {

    /**
     * The maximum length of a role title, as specified in the DB schema
     */
    const MAX_LEN_ROLE_TITLE = 128;

    /**
     * The maximum length of a permission title, as specified in the DB schema
     */
    const MAX_LEN_PERMISSION_TITLE = 128;


    // Private constructor, as this class should not need to be instantiated (all methods should be static)
    private function __construct() {}

    /**
     * Registers a new role.
     *
     * Note: role titles cannot exceed 128 characters in length.
     *
     * @param string $title title of the role to be created
     * @throws \ValueError if the title is too long (> 128)
     * @return void
     */
    private static function createRole(string $title): void {
        // Ensure role title does not exceed max length (for DB)
        if (strlen($title) > self::MAX_LEN_ROLE_TITLE) {
            throw new \ValueError("Role title length cannot exceed " . self::MAX_LEN_ROLE_TITLE . " characters.");
        }

        // Prepare SQL statement
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO rbac_roles (title) VALUES (:title);");

        // Execute statement (insert role record)
        try {
            $stmt->execute(["title" => $title]);
        } catch (\PDOException $e) {
            // TODO: Handle PDOException
        }
    }

    /**
     * Registers a new permission.
     *
     * Note: permission titles cannot exceed 128 characters in length.
     *
     * @param string $title title of the permission to be created
     * @throws \ValueError if the title is too long (> 128)
     * @return void
     */
    private static function createPermission(string $title): void {
        // Ensure permission title does not exceed max length (for DB)
        if (strlen($title) > self::MAX_LEN_PERMISSION_TITLE) {
            throw new \ValueError("Permission title length cannot exceed " . self::MAX_LEN_ROLE_TITLE . " characters.");
        }

        // Prepare SQL statement
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO rbac_permissions (title) VALUES (:title);");

        // Execute statement (insert permission record)
        try {
            $stmt->execute(["title" => $title]);
        } catch (\PDOException $e) {
            // TODO: Handle PDOException
        }
    }

    /**
     * Checks if a role exists.
     *
     * @param string $roleTitle the role title
     * @return bool true, if such a role exists. Otherwise, false.
     */
    private static function roleExists(string $roleTitle): bool {
        // Prepare and execute SQL statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT title FROM rbac_roles WHERE title=:title;");
        $stmt->execute(["title" => $roleTitle]);

        // Return true/false depending on whether a result was found
        return !($stmt->fetch() === false);
    }

    /**
     * Checks if a permission exists.
     *
     * @param string $permissionTitle the permission title
     * @return bool true, if such a permission exists. Otherwise, false.
     */
    private static function permissionExists(string $permissionTitle): bool {
        // Prepare and execute SQL statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT title FROM rbac_permissions WHERE title=:title;");
        $stmt->execute(["title" => $permissionTitle]);

        // Return true/false depending on whether a result was found
        return !($stmt->fetch() === false);
    }


    /**
     * Assigns a permission to a role.
     *
     * If the specified role-permission association/combination already exists (or cannot otherwise be inserted into the DB), the function simply returns false, not raising an exception.
     *
     * @param string $roleTitle the title of the role
     * @param string $permissionTitle the title of the permission
     * @return bool true, if the permission was successfully newly assigned. false, if the permission failed to be assigned or was already assigned to the role.
     * @throws NoSuchPermissionException if the specified permission does not exist
     * @throws NoSuchRoleException if the specified role does not exist
     */
    private static function assignPermissionToRole(string $roleTitle, string $permissionTitle): bool {
        // Ensure role exists
        if (!self::roleExists($roleTitle)) {
            throw new NoSuchRoleException("Role '$roleTitle' does not exist.");
        }

        // Ensure permission exists
        if (!self::permissionExists($permissionTitle)) {
            throw new NoSuchPermissionException("Permission '$permissionTitle' does not exist.");
        }

        // Prepare SQL statement to assign permission to role
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO rbac_pa (roleTitle, permissionTitle) VALUES (:roleTitle, :permissionTitle);");

        // Execute SQL statement (insert PA record).
        try {
            $stmt->execute([
                "roleTitle" => $roleTitle,
                "permissionTitle" => $permissionTitle,
            ]);

            // Success, so return true.
            return true;

        } catch (\PDOException $e) {
            // If insertion fails, return false.

            // TODO: Consider logging
            return false;
        }
    }

    /**
     * Assigns a role to a user.
     *
     * If the specified role-user association/combination aleady exists (or cannot otherwise be inserted into the DB), the function simply returns false, not raising an exception.
     *
     * @param int $userID the user's ID
     * @param string $roleTitle the title of the role
     * @return bool true, if the role was successfully newly assigned. false, if the role failed to be assigned or was already assigned to the user.
     * @throws NoSuchAccountException if no account (user) exists with the given ID
     * @throws NoSuchRoleException if the specified role does not exist
     */
    public static function assignRoleToUser(int $userID, string $roleTitle): bool {
        // Ensure user exists
        if (!Account::existsWithID($userID)) {
            throw new NoSuchAccountException("Account with user ID '$userID' does not exist.");
        }

        // Ensure role exists
        if (!self::roleExists($roleTitle)) {
            throw new NoSuchRoleException("Role '$roleTitle' does not exist.");
        }

        // Prepare SQL statement to assign role to user
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO rbac_ua (userID, roleTitle) VALUES (:userID, :roleTitle);");

        // Execute SQL statement (insert UA record)
        try {
            $stmt->execute([
                "userID" => $userID,
                "roleTitle" => $roleTitle,
            ]);

            // Success, so return true
            return true;

        } catch (\PDOException $e) {
            // If insertion fails, return false.

            // TODO: Consider logging
            return false;
        }
    }

    /**
     * Checks if a role has a specific permission.
     *
     * @param string $roleTitle the role title
     * @param string $permissionTitle the permission title
     * @return bool true, if the role has the permission. Otherwise, false.
     * @throws NoSuchPermissionException if no such permission exists
     * @throws NoSuchRoleException if no such role exists
     */
    public static function isRolePermitted(string $roleTitle, string $permissionTitle): bool {
        // Ensure role exists
        if (!self::roleExists($roleTitle)) {
            throw new NoSuchRoleException("Role '$roleTitle' does not exist.");
        }

        // Ensure permission exists
        if (!self::permissionExists($permissionTitle)) {
            throw new NoSuchPermissionException("Permission '$permissionTitle' does not exist.");
        }

        // Prepare and execute SQL statement to see if a mapping exists between the role and permission
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT COUNT(*) FROM rbac_pa WHERE roleTitle=:roleTitle AND permissionTitle=:permissionTitle;");
        $stmt->execute([
            "roleTitle" => $roleTitle,
            "permissionTitle" => $permissionTitle,
        ]);

        // Get the number of results found (this will be either 1 or 0)
        $nRows = $stmt->fetchColumn();

        return $nRows > 0;
    }

    /**
     * Checks if a user is assigned to a specific role.
     *
     * @param int $userID the user's ID
     * @param string $roleTitle the role title
     * @return bool true, if the user is assigned to the role. Otherwise, false.
     * @throws NoSuchAccountException if no account exists with the given user ID
     * @throws NoSuchRoleException if no such role exists
     */
    public static function hasRole(int $userID, string $roleTitle): bool {
        // Ensure user exists
        if (!Account::existsWithID($userID)) {
            throw new NoSuchAccountException("Account with user ID '$userID' does not exist.");
        }

        // Ensure role exists
        if (!self::roleExists($roleTitle)) {
            throw new NoSuchRoleException("Role '$roleTitle' does not exist.");
        }

        // Prepare and execute SQL statement to see if a mapping exists between the role and user
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT COUNT(*) FROM rbac_ua WHERE roleTitle=:roleTitle AND userID=:userID;");
        $stmt->execute([
            "roleTitle" => $roleTitle,
            "userID" => $userID,
        ]);

        // Get the number of results found (this will be either 1 or 0)
        $nRows = $stmt->fetchColumn();

        return $nRows > 0;
    }

    /**
     * Checks if a user has a specific permission.
     *
     * @param int $userID the user's ID
     * @param string $permissionTitle the permission title
     * @return bool true, if the user has the permission. Otherwise, false.
     * @throws NoSuchAccountException if no account exists with the given user ID
     * @throws NoSuchPermissionException if no such permission exists
     */
    public static function isUserPermitted(int $userID, string $permissionTitle): bool {
        // Ensure user exists
        if (!Account::existsWithID($userID)) {
            throw new NoSuchAccountException("Account with user ID '$userID' does not exist.");
        }

        // Ensure permission exists
        if (!self::permissionExists($permissionTitle)) {
            throw new NoSuchPermissionException("Permission '$permissionTitle' does not exist.");
        }

        // Prepare and execute SQL statement to see if the user has permission
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT COUNT(rbac_pa.permissionTitle) FROM rbac_pa JOIN rbac_ua ON rbac_pa.roleTitle = rbac_ua.roleTitle WHERE rbac_ua.userID=:userID AND rbac_pa.permissionTitle=:permissionTitle;");
        $stmt->execute([
            "userID" => $userID,
            "permissionTitle" => $permissionTitle,
        ]);

        // Get the number of results found (if > 0, then the user has the permission)
        $nRows = $stmt->fetchColumn();

        return $nRows > 0;
    }

}