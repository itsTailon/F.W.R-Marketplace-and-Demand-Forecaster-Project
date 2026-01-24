<?php

namespace TTE\App\Model;

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

    public function create(): Account {
        // TODO: Implement create() method.

        // TODO: Remove placeholder return
        return new Account();
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

}