<?php

namespace TTE\App\Model;

use TTE\App\Helpers\CurrencyTools;

class Bundle extends StoredObject {

    private int $id;

    private BundleStatus $status;

    private string $title;
    const MAX_LEN_TITLE = 128; // Maximum length of the 'title' field (as in DB schema)

    private string $details;

    private int $rrpGBX;

    private int $discountedPriceGBX;

    private int $sellerID;

    private int $purchaserID;

    public function update(): void {
        // TODO: (TGP-37) Implement update() method.

        // Notes for implementation:
        //  (1) Remember to be careful with rrp and discountedPrice, as these are denoted in this class as GBX (pence).
        //  (2) The underlying value of the $status enum should be stored. May need to use '->value' (see docs for PHP backed enums).
    }

    public static function create(): Bundle {
        // TODO: (TGP-36) Implement create() method.

        // Notes for implementation:
        //  (1) Remember to be careful with rrp and discountedPrice, as these are denoted in this class as GBX (pence).
        //  (2) The underlying value of the $status enum should be stored. May need to use '->value' (see docs for PHP backed enums).
        //  (3) Please use the class's setter functions, as these already encapsulate logic to enforce DB constraints on internal values.

        // TODO: Remove placeholder return
        return new Bundle();
    }

    /**
     * Loads a bundle from the database.
     *
     * @param int $id ID of the bundle to be loaded.
     *
     * @throws DatabaseException if no bundle exists with the given ID.
     * @return StoredObject a Bundle object representing the loaded bundle
     */
    public static function load(int $id): Bundle {
        // Attempt to retrieve bundle record with the given ID
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM bundle WHERE bundleID=:bundleID;");
        $stmt->execute(["bundleID" => $id]);

        // Fetch result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Throw exception if no bundle found with given ID
        if ($row === false) {
            throw new DatabaseException("No bundle found with ID $id");
        }

        // Construct Bundle object, performing any necessary data type/format conversions
        $bundle = new Bundle();
        $bundle->id = $row['bundleID'];
        $bundle->bundleStatus = BundleStatus::from($row['bundleStatus']); // Convert to enum representation
        $bundle->title = $row['title'];
        $bundle->details = $row['details'];
        // MySQL DECIMAL values are returned by PDO as strings, so convert to ints representing pence (ints to avoid FP errors)
        $bundle->rrpGBX = CurrencyTools::decimalStringToGBX($row['rrp']);
        $bundle->discountedPriceGBX = CurrencyTools::decimalStringToGBX($row['discountedPrice']);
        $bundle->sellerID = $row['sellerID'];
        $bundle->purchaserID = $row['purchaserID'];

        return $bundle;
    }

    /**
     * Checks if a bundle record exists with the given ID.
     *
     * @param int $id ID to check
     *
     * @return bool true, if such a bundle exists. Otherwise, false.
     */
    public static function existsWithID(int $id): bool {
        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM bundle WHERE bundleID=:bundleID;");

        // Execute statement with given bundle ID
        $stmt->execute(["bundleID" => $id]);

        // Get result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return true if a bundle exists with the given ID
        return !($row === false);
    }

    public function getID(): int {
        return $this->id;
    }

    public function getStatus(): BundleStatus {
        return $this->status;
    }

    public function setStatus(BundleStatus $status): void {
        $this->status = $status;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function setTitle(string $title): void {
        // Ensure length of new title complies with DB schema (128)
        if (strlen($title) > self::MAX_LEN_TITLE) {
            throw new \ValueError("Cannot set bundle title longer than " . self::MAX_LEN_TITLE . " characters");
        }

        $this->title = $title;
    }

    public function getDetails(): string {
        return $this->details;
    }

    public function setDetails(string $details): void {
        $this->details = $details;
    }

    public function getRrpGBX(): int {
        return $this->rrpGBX;
    }

    public function setRrpGBX(int $gbx): void {
        // Ensure value is non-negative
        if ($gbx < 0) {
            throw new \ValueError("Cannot set RRP to negative value");
        }

        $this->rrpGBX = $gbx;
    }

    public function getDiscountedPriceGBX(): int {
        return $this->discountedPriceGBX;
    }

    public function setDiscountedPriceGBX(int $gbx): void {
        // Ensure value is non-negative
        if ($gbx < 0) {
            throw new \ValueError("Cannot set discounted price to negative value");
        }

        $this->discountedPriceGBX = $gbx;
    }

    public function getSellerID(): int {
        return $this->sellerID;
    }

    /**
     * Sets the seller ID. Private, as this should only be used once, by the constructor.
     *
     * @param int $sellerID
     * @return void
     */
    private function setSellerID(int $sellerID): void {
        // Ensure that the given seller ID corresponds to an actual seller
        if (!Seller::existsWithID($sellerID)) {
            throw new NoSuchSellerException("Cannot set seller ID to non-existent seller (invalid seller ID $sellerID)");
        }

        $this->sellerID = $sellerID;
    }

    public function getPurchaserID(): int {
        return $this->purchaserID;
    }

    public function setPurchaserID(int $customerID): void {
        // Ensure that the given purchaser ID corresponds to an actual customer
        if (!Customer::existsWithID($customerID)) {
            throw new NoSuchCustomerException("Cannot set purchaser ID to non-existent customer (invalid customer ID $customerID)");
        }

        $this->purchaserID = $customerID;
    }

    public static function delete(int $id): void {
        // TODO: Implement delete() method.
    }
}