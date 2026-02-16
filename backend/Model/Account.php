<?php

namespace TTE\App\Model;

use TTE\App\Auth\NoSuchRoleException;
use TTE\App\Auth\RBACManager;

class Account extends StoredObject {

    protected int $userID;

    protected string $email;

    protected string $accountType;

    /**
     * Returns an Account object representing the account with the given ID.
     *
     * @param int $id ID of account to load
     *
     * @throws DatabaseException if no account exists with the given ID.
     * @return Account
     */
    public static function load(int $id): Account {
        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM account WHERE userID=:userID;");

        // Execute statement with given account ID
        $stmt->execute(["userID" => $id]);

        // Get result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Throw exception if no account was found with the given ID
        if ($row === false) {
            throw new DatabaseException("No account found with ID $id");
        }

        // Construct Account object
        $account = new Account();
        $account->userID = $row['userID'];
        $account->email = $row['email'];
        $account->accountType = $row['accountType'];

        return $account;
    }

    public function update(): void {
        // TODO: Implement update() method.
    }

    public static function create(array $fields): Account
    {
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO account(email, passwordHash, accountType) VALUES (:email, :passwordHash, :accountType);");
        $passwordHash = password_hash($fields['password'], PASSWORD_ARGON2ID);

        try {
            $stmt->execute([":email" => $fields['email'], ":passwordHash" => $passwordHash, ":accountType" => $fields['accountType']]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());//TODO: change msg
        }

        // We use the email of the user (which is unique) to find the user we just added to the database
        // TODO: Find a better approach
        $stmt2 = DatabaseHandler::getPDO()->prepare("SELECT userID FROM account WHERE email=:email;");
        $stmt2->execute([":email" => $fields['email']]);

        // Get the user ID (the one bit of info we don't have)
        $row = $stmt2->fetch(\PDO::FETCH_ASSOC);
        $userID = $row["userID"];

        // Create a new Account object
        $account = new Account();
        $account->userID = $userID;
        $account->email = $fields['email'];
        $account->accountType = $fields['accountType'];

        try {
            RBACManager::assignRoleToUser($account->getUserID(), "customer");
        } catch (NoSuchRoleException $e) {
            die("There is no such role");
        } catch (NoSuchAccountException $e) {
            die("There is no such account");
        }

        return $account;
    }

    /**
     * Checks if an account record exists with the given ID.
     *
     * @param int $id ID to check
     *
     * @return bool true, if such an account exists. Otherwise, false.
     */
    public static function existsWithID(int $id): bool {
        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM account WHERE userID=:userID;");

        // Execute statement with given account ID
        $stmt->execute(["userID" => $id]);

        // Get result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return true if an account exists with the given ID
        return !($row === false);
    }

    public function getUserID(): int {
        return $this->userID;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getAccountType(): string {
        return $this->accountType;
    }

    /**
     * Setter for e-mail field
     *
     * @param string $email new e-mail address
     *
     * @throws \InvalidArgumentException if $email is not a valid e-mail address
     * @return void
     */
    public function setEmail(string $email): void {
        // Validate e-mail value
        $newEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (!$newEmail) {
            // Invalid e-mail address
            throw new \InvalidArgumentException("$email is not a valid e-mail address");
        }

        $this->email = $email;
    }

    /**
     * Returns an instance of a specialised subclass of Account (e.g., Seller), relating to the account type.
     *
     * @return Account
     */
    public function getSubclass(): Account {
        switch ($this->accountType) {
            case 'seller':
                return Seller::load($this->userID);
                break;

            case 'customer':
                return Customer::load($this->userID);
                break;

            default:
                return $this;
                break;
        }
    }

    public static function delete(int $id): void {
        // Create SQL command to delete account of given ID
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM account WHERE userID=:userID;");

        // Check if account exists
        if (Account::existsWithID($id)) {
            // Attempt to run SQL statement
            try {
                $stmt->execute(["userID" => $id]);
            } catch (\PDOException $e) {
                throw new DatabaseException($e->getMessage());
            }
        } else {
            // If account does not exist, throw error
            throw new DatabaseException("No account found with ID $id");
        }
    }
}