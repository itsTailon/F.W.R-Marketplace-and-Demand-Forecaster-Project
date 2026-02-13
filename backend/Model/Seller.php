<?php

namespace TTE\App\Model;

class Seller extends Account {

    private string $name;

    private string $address;

    public function update(): void {
        // TODO: Implement update() method.
    }

    public static function create(array $fields): Seller {
        $account = parent::create([
            'email' => $fields['email'],
            'accountType' => 'seller',
            'password' => $fields['password']
        ]);

        // Create the customer in the database
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO Seller(sellerID, sellerName, sellerAddress) VALUES (:id, :name, :address);");
        $stmt->execute(["id" => $account->getUserID(), "name" => $fields['name'], "address" => $fields['address']]);

        // Create and return a seller object
        $seller = new Seller();
        $seller->name = $fields['name'];
        $seller->setEmail($fields['email']);
        $seller->setAddress($fields['address']);
        $seller->accountType = "seller";
        $seller->userID = $account->getUserID();

        return $seller;
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

    public static function delete(int $id): void {
        // TODO: Implement delete() method.

        // Call superclass method
    }

    public function getSellThroughRate() {
        return $this->getSellThroughRateByDiscountRate(0, 100);
    }

    public function getSellThroughRateByDiscountRate(int $minDiscount, int $maxDiscount) {
        $queryText = "SELECT COUNT(*) FROM bundle INNER JOIN reservation ON bundle.bundleID = reservation.bundleID WHERE sellerID = :sellerID AND ((RRP - discountedPrice) / RRP) * 100 BETWEEN :minDiscount AND :maxDiscount ";

        $query1 = $queryText . "AND reservationStatus = 'completed';";
        $stmt1 = DatabaseHandler::getPDO()->prepare($query1);
        $stmt1->execute([":sellerID" => $this->userID, ":minDiscount" => $minDiscount, ":maxDiscount" => $maxDiscount]);
        $completedRow = $stmt1->fetch(\PDO::FETCH_ASSOC);
        $completedCount = $completedRow["COUNT(*)"];

        $query2 = $queryText . "AND reservationStatus != 'active';";
        $stmt2 = DatabaseHandler::getPDO()->prepare($query2);
        $stmt2->execute([":sellerID" => $this->userID, ":minDiscount" => $minDiscount, ":maxDiscount" => $maxDiscount]);
        $notActiveRow= $stmt2->fetch(\PDO::FETCH_ASSOC);
        $notActiveCount = $notActiveRow["COUNT(*)"];

        return 100 * ($completedCount / $notActiveCount);
    }
}