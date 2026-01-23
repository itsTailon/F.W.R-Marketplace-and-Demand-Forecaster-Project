<?php

namespace TTE\App\Auth;

use TTE\App\Model\Account;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;

class Authenticator {

    /**
     * Authenticates a user.
     *
     * If successful, the user is logged in (data/info stored in $_SESSION).
     *
     * @param string $email
     * @param string $password
     * @return bool true, upon success. false, upon failure.
     */
    public static function authenticateUser(string $email, string $password): bool {
        // Use a prepared statement to protect against SQL injection
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT userID, passwordHash FROM account WHERE email=:email;");

        // Execute statement with email as parameter
        $stmt->execute(["email" => $email]);

        // Get result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // No user found with the given e-mail address, so return false.
        if ($row === false) {
            return false;
        }

        // Validate password
        if (!password_verify($password, $row['passwordHash'])) {
            // Invalid password.
            return false;
        }

        // Log user in. Returns true if success, false upon failure.
        return self::login($row['userID']);
    }

    /**
     * Logs in the user with the given account ID.
     *
     * NOTE: This function does NOT perform authentication.
     *
     * @return bool true, if successful. false otherwise.
     */
    private static function login(int $userID): bool {
        try {
            // Load account / create object
            $account = Account::load($userID);

            // Set session variables
            $_SESSION['isLoggedIn'] = true;
            $_SESSION['currentUser'] = $account;

            // Success
            return true;

        } catch (\Exception $e) {
            // TODO: implement logging?

            // Failure
            return false;
        }
    }

    /**
     * If the website user is logged in, log them out.
     * @return void
     */
    public static function logout() {
        // Unset session variables
        if (isset($_SESSION['isLoggedIn'])) {
            unset($_SESSION['isLoggedIn']);
        }
        if (isset($_SESSION['currentUser'])) {
            unset($_SESSION['currentUser']);
        }
    }

    /**
     * Checks if the website user is logged in.
     *
     * @return bool
     */
    public static function isLoggedIn() {
        if (isset($_SESSION['isLoggedIn']) && isset($_SESSION['currentUser'])) {
            return $_SESSION['isLoggedIn'];
        }

        return false;
    }

    /**
     * Returns an Account object representing the currently logged-in user.
     *
     * If the user is not logged in, returns null.
     *
     * @return ?Account If logged in, the Account object representing the current user. Otherwise, null.
     */
    public static function getCurrentUser(): ?Account {
        // Check if user is actually logged in
        if (!self::isLoggedIn()) {
            return null;
        }

        return $_SESSION['currentUser'];
    }

}