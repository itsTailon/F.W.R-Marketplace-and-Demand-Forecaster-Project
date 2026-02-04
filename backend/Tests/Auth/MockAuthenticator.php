<?php

namespace TTE\App\Tests\Auth;

use TTE\App\Auth\Authenticator;
use TTE\App\Model\Account;

class MockAuthenticator extends Authenticator {

    private static array $mockSession;

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
            self::$mockSession['isLoggedIn'] = true;
            self::$mockSession['currentUser'] = $account;

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
        if (isset(self::$mockSession['isLoggedIn'])) {
            unset(self::$mockSession['isLoggedIn']);
        }
        if (isset(self::$mockSession['currentUser'])) {
            unset(self::$mockSession['currentUser']);
        }
    }

    /**
     * Checks if the website user is logged in.
     *
     * @return bool
     */
    public static function isLoggedIn() {
        if (isset(self::$mockSession['isLoggedIn']) && isset(self::$mockSession['currentUser'])) {
            return self::$mockSession['isLoggedIn'];
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

        return self::$mockSession['currentUser'];
    }

    /**
     * Returns an object which is a subclass of Account, corresponding to the current user and their account type — e.g., A Seller object, if the current user is a seller.
     *
     * If the user is not logged in, returns null.
     *
     * @return ?Account If logged in, a specialised (e.g., Seller) object representing the current user. Otherwise, null.
     */
    public static function getCurrentUserSubclass(): ?Account {
        // Check if user is actually logged in
        if (!self::isLoggedIn()) {
            return null;
        }

        return self::$mockSession['currentUser']->getSubclass();
    }
}