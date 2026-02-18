<?php
namespace TTE\App\Model;

use TTE\App\Auth\NoSuchRoleException;
use TTE\App\Auth\RBACManager;
use TTE\App\Helpers\CurrencyTools;

class Seller extends Account {

    private string $name;

    private string $address;

    public function update(): void {
        // TODO: Implement update() method.
    }

    /**
     * Creates a record for a seller in the database and then returns an object describing that seller
     *
     * @param array $fields An associative array of fields, which must contain the email, password, name, and address of the seller
     * @return Seller The newly created seller
     * @throws DatabaseException
     */
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

        try {
            RBACManager::assignRoleToUser($seller->getUserID(), "customer");
        } catch (NoSuchRoleException $e) {
            die("There is no such role");
        } catch (NoSuchAccountException $e) {
            die("There is no such account");
        }

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


    /**
     * Summary of getAllBundlesForUser
     * @param int $id ID of seller 
     * @throws DatabaseException
     * @return array Returns array of Bundles that are owned by Seller provided.
     */
    public static function getAllBundlesForUser(int $id) {
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM bundle WHERE sellerID=:id;");

        // Try to execute
        try {
            $stmt->execute([":id" => $id]);
            // Load all bundles from query and return array
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
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

        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM rbac_ua WHERE userID = :userID;");
        try {
            $stmt->execute(["userID" => $id]);
        } catch(\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
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

    /**
     * A helper function used in getSellThroughRateByDiscountRate to filter bundles SELECTed from the bundles table
     * by discount level. Public only so it can be tested with PHPUnit; do not use.s
     *
     * @param array $dbRows The rows returned from a database SELECT request on the bundles table
     * @param int $minDiscount The minimum discount, expressed as a percentage discount
     * @param int $maxDiscount The maximum discount, expressed aa a percentage discount
     * @return array
     */
    public function filterBundlesByDiscountLevel(array $dbRows, int $minDiscount, int $maxDiscount): array {
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

    /**
     * A helper function used by analytics functions to SELECT bundles associated with the seller in question
     * with a particular status. Public only so it can be tested with PHPUnit; do not use.
     *
     * @param BundleStatus $status
     * @return array
     */
    public function getBundlesByStatus(BundleStatus $status) : array {
        $queryText = "SELECT rrp, discountedPrice FROM bundle WHERE sellerID = :sellerID AND bundleStatus = :status;";
        $stmt = DatabaseHandler::getPDO()->prepare($queryText);
        $stmt->execute([":sellerID" => $this->getUserID(), ":status" => $status->value]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Calculates sell-through, or the percentage of past (collected or expired) bundles that are collected
     * rather than expired, for all bundles associated with the seller
     *
     * @return float The sell-through rate
     */
    public function getSellThroughRate() : float {
        $collected = count($this->getBundlesByStatus(BundleStatus::Collected));
        $expired = count($this->getBundlesByStatus(BundleStatus::Expired));

        return 100 * ($collected / ($collected + $expired));
    }

    /**
     * Calculates sell-through, or the percentage of past (collected or expired) bundles that are collected
     * rather than expired, for all bundles associated with the seller within a certain range of discount
     * level
     *
     * @param int $minDiscount The bottom of the discount range, expressed as a discount percentage
     * @param int $maxDiscount The top of the discount range, expressed as a discount percentage
     * @return float The sell-through rate for that discount range
     */
    public function getSellThroughRateByDiscountRate(int $minDiscount, int $maxDiscount) : float {
        $collected = count($this->filterBundlesByDiscountLevel($this->getBundlesByStatus(BundleStatus::Collected), $minDiscount, $maxDiscount));
        $expired = count($this->filterBundlesByDiscountLevel($this->getBundlesByStatus(BundleStatus::Expired), $minDiscount, $maxDiscount));

        return 100 * ($collected / ($collected + $expired));
    }
}