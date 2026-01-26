<?php

namespace TTE\App\Model;

class Seller extends Account {

    private string $name;

    private string $address;

    public function update(): void {
        // TODO: Implement update() method.
    }

    public static function create(): Seller {
        // TODO: Implement create() method.

        // Perhaps make a call to super class, then get ID and create seller record (?) — i.e., create account, then specialise?

        // TODO: Remove placeholder return
        return new Seller();
    }

    /**
     * Returns a Seller object representing the seller with the given seller ID.
     *
     * @param int $id ID of seller to load
     *
     * @throws DatabaseException if no seller exists with the given ID.
     * @return Seller
     */
    public static function load(int $id): Seller {
        // Prepare parameterised statement to get seller record
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM seller WHERE sellerID=:sellerID;");

        // Execute statement with given ID
        $stmt->execute(["sellerID" => $id]);

        // Get result (seller)
        $sellerRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Throw exception if no seller was found with the given ID
        if ($sellerRow === false) {
            throw new DatabaseException("No seller found with ID $id");
        }

        // Get Account object
        $account = Account::load($id);

        // Construct Seller object
        $seller = new Seller();
        $seller->userID = $account->userID;
        $seller->email = $account->email;
        $seller->accountType = $account->accountType;
        $seller->name = $sellerRow['sellerName'];
        $seller->address = $sellerRow['sellerAddress'];

        return $seller;
    }

    /**
     * Checks if a seller record exists with the given ID.
     *
     * @param int $id ID to check
     *
     * @return bool true, if such a seller exists. Otherwise, false.
     */
    public static function existsWithID(int $id): bool {
        // Prepare parameterised statement to get seller record
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM seller WHERE sellerID=:sellerID;");

        // Execute statement with given ID
        $stmt->execute(["sellerID" => $id]);

        // Get result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return true if an account exists with the given ID
        return !($row === false);
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getAddress(): string {
        return $this->address;
    }

    public function setAddress(string $address): void {
        $this->address = $address;
    }


}