<?php

namespace TTE\App\Model;

class Customer extends Account {

    private string $username;

    private ?int $streak;

    public function update(): void {
        // TODO: Implement update() method.
    }

    public function create(): Seller {
        // TODO: Implement create() method.

        // Perhaps make a call to super class, then get ID and create seller record (?) — i.e., create account, then specialise?

        // TODO: Remove placeholder return
        return new Seller();
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

    public function getUsername(): string {
        return $this->username;
    }

    public function getStreak(): ?int {
        return $this->streak;
    }

}