<?php

namespace TTE\App\Model;

class Customer extends Account {

    private string $username;

    private ?int $streak;

    public function update(): void {
        // TODO: Implement update() method.
    }

    public static function create(array $fields): Customer {
        // Create the account in the database
        $account = parent::create([
            'email' => $fields['email'],
            'accountType' => 'customer',
            'password' => $fields['password']
        ]);

        // Create the customer in the database
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO customer(customerID, username) VALUES (:id, :username);");
        $stmt->execute(["id" => $account->getUserID(), "username" => $fields['username']]);

        // Create and return a customer object
        $customer = new Customer();
        $customer->username = $fields['username'];
        $customer->streak = 0;
        $customer->userID = $account->getUserID();
        $customer->setEmail($fields['email']);
        $customer->accountType = "customer";
        return $customer;
    }

    /**
     * Returns a Customer object representing the customer with the given seller ID.
     *
     * @param int $id ID of customer to load
     *
     * @throws DatabaseException if no customer exists with the given ID.
     * @return Customer
     */
    public static function load(int $id): Customer {
        // Prepare parameterised statement to get customer record
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM customer WHERE customerID=:customerID;");

        // Execute statement with given ID
        $stmt->execute(["customerID" => $id]);

        // Get result (customer)
        $customerRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Throw exception if no customer was found with the given ID
        if ($customerRow === false) {
            throw new DatabaseException("No customer found with ID $id");
        }

        // Get Account object
        $account = Account::load($id);

        // Construct Customer object
        $customer = new Customer();
        $customer->userID = $account->userID;
        $customer->email = $account->email;
        $customer->accountType = $account->accountType;
        $customer->username = $customerRow['username'];
        $customer->streak = $customerRow['streak'];

        return $customer;
    }

    /**
     * Checks if a customer record exists with the given ID.
     *
     * @param int $id ID to check
     *
     * @return bool true, if such a customer exists. Otherwise, false.
     */
    public static function existsWithID(int $id): bool {
        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM customer WHERE customerID=:customerID;");

        // Execute statement with given account ID
        $stmt->execute(["customerID" => $id]);

        // Get result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return true if a customer exists with the given ID
        return !($row === false);
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getStreak(): ?int {
        return $this->streak;
    }

    public static function delete(int $id): void {
        // TODO: Implement delete() method.

        // Call superclass method
    }

}