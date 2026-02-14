<?php
namespace TTE\App\Model;

use TTE\App\Helpers\CurrencyTools;

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
        // Check seller with ID exists
        if (Seller::existsWithID($id) === false) {
            throw new DatabaseException("Seller with ID $id does not exist");
        }

        // Create SQL command to delete seller and corresponding account instance of given ID
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM seller WHERE sellerID=:sellerID;");
        try {
            $stmt->execute(["sellerID" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM account WHERE userID=:userID;");
        try {
            $stmt->execute(["userID" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

    }

    private function filterBundlesByDiscountLevel(array $dbRows, int $minDiscount, int $maxDiscount): array {
        $dbRowsFiltered = array();
        $j = 0;

        for ($i = 0; $i < count($dbRows); $i++) {
            $dbRow = $dbRows[$i];
            $rrp = CurrencyTools::decimalStringToGBX($dbRow['rrp']);
            $discountedPrice = CurrencyTools::decimalStringToGBX($dbRow['discountedPrice']);
            $discountPercentage = 100 * (($rrp - $discountedPrice) / $rrp);

            if ($discountPercentage <= $maxDiscount && $discountPercentage >= $minDiscount) {
                $dbRowsFiltered[$j++] = $dbRow;
            }
        }

        return $dbRowsFiltered;
    }

    public function getSellThroughRate() : float {
        $queryText = "SELECT rrp, discountedPrice FROM bundle INNER JOIN reservation ON bundle.bundleID = reservation.bundleID WHERE sellerID = :sellerID ";

        $query1 = $queryText . "AND reservationStatus = 'completed';";
        $stmt1 = DatabaseHandler::getPDO()->prepare($query1);
        $stmt1->execute([":sellerID" => $this->userID]);
        $completedRows = $stmt1->fetchAll(\PDO::FETCH_ASSOC);

        $query2 = $queryText . "AND reservationStatus != 'active';";
        $stmt2 = DatabaseHandler::getPDO()->prepare($query2);
        $stmt2->execute([":sellerID" => $this->userID]);
        $notActiveRows = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

        return 100 * (count($completedRows) / count($notActiveRows));
    }

    public function getSellThroughRateByDiscountRate(int $minDiscount, int $maxDiscount) : float {
        $queryText = "SELECT rrp, discountedPrice FROM bundle LEFT JOIN reservation ON bundle.bundleID = reservation.bundleID WHERE sellerID = :sellerID ";

        $query1 = $queryText . "AND reservationStatus = 'completed';";
        $stmt1 = DatabaseHandler::getPDO()->prepare($query1);
        $stmt1->execute([":sellerID" => $this->userID]);
        $completedRows = $this->filterBundlesByDiscountLevel($stmt1->fetchAll(\PDO::FETCH_ASSOC), $minDiscount, $maxDiscount);

        $query2 = $queryText . "AND reservationStatus != 'active' AND bundleStatus != 'available';";
        $stmt2 = DatabaseHandler::getPDO()->prepare($query2);
        $stmt2->execute([":sellerID" => $this->userID]);
        $notActiveRows = $this->filterBundlesByDiscountLevel($stmt2->fetchAll(\PDO::FETCH_ASSOC), $minDiscount, $maxDiscount);

        return 100 * (count($completedRows) / count($notActiveRows));
    }
}