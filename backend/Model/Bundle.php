<?php

namespace TTE\App\Model;

use http\Exception\InvalidArgumentException;
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

    /**
     * @param int $id of bundle to be updated.
     * @param array $fields holding field names and values to update record with.
     * @throws DatabaseException|NoSuchBundleException
     */
    public function update(int $id, array $fields): void
    {
        // Check validity of bundleID
        if (!Bundle::existsWithID($id)) {
            // Exception thrown if ID is invalid
            throw new NoSuchBundleException("No such bundle with ID $id");
        }

        // Ensuring that there is at least one field passed
        if (count($fields) < 1) {
            // Throwing exception
            throw new InvalidArgumentException("At least one field must be passed to update a bundle.");
        }

        // List of viable fields for editing
        $valid_fields = array("bundleStatus", "bundleTitle", "bundleDetails", "bundleRrpGBX", "purchaserID");

        // Enforcing field limits
        foreach (array_keys($fields) as $field) {
            if (!in_array($field, $valid_fields)) {
                throw new InvalidArgumentException("Field ". $field ." cannot be updated.");
            }
        }

        // Arrays to hold SET parameters and bindings for SQL query
        $setParams = array();
        $execBindings = array();

        // Building arrays
        foreach($fields as $field=>$value) {
            $setParams[] = "$field = :$field";
            $execBindings[":$field"] = $value;
        }
        // Adding the ID as a exec value as we know it will be present in query
        $execBindings[":bundleID"] = $id;

        // SQL query to be executed
        $sql_query = "Update bundles SET ".implode(", ", $setParams)."WHERE id = :id";
        // Prepare and execute query
        $stmt = DatabaseHandler::getPDO()->prepare($sql_query);

        // Try-catch block for handling potential database exceptions
        try {
            // Execute SQL command, establishing values of parameterised fields
            $stmt->execute($execBindings);
        } catch (\PDOException $e) {
            // Throw exception message aligning with output of database error
            throw new DatabaseException($e->getMessage());
        }

    }

    /**
     * Create a Bundle object and add entry to database.
     *
     * @param array $fields associative array of fields required for Bundle.
     * @throws MissingValuesException|NoSuchSellerException|NoSuchCustomerException|DatabaseException
     * @return Bundle object with fields holding values passed in at call of the function.
     */
    public static function create(array $fields): Bundle {

        // Presence check on all inputs - not on purchaserID as it is nullable
        if (!isset($fields['bundleStatus']) || !isset($fields['bundleTitle']) || !isset($fields['bundleDetails']) || !isset($fields['bundleRrpGBX']) ||
            !isset($fields['bundleDiscountedPriceGBX']) || !isset($fields['bundleSellerID']) || empty(trim($fields['bundleTitle'])) || empty(trim($fields['bundleDetails']))) {

            // Produce error message if field exists with no content
            throw new MissingValuesException("Missing information required to create a bundle");
        }

        // Creating new Bundle object
        $bundle = new Bundle();
        // Updating attributes in line with input
        $bundle->setStatus($fields['bundleStatus']);
        $bundle->setTitle($fields['bundleTitle']);
        $bundle->setDetails($fields['bundleDetails']);
        $bundle->setRrpGBX($fields['bundleRrpGBX']);
        $bundle->setDiscountedPriceGBX($fields['bundleDiscountedPriceGBX']);
        $bundle->setSellerID($fields['bundleSellerID']);
        $bundle->setPurchaserID($fields['bundlePurchaserID']);

        // Creating parameterised SQL command
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO bundle (bundleStatus, title, details, rrp, discountedPrice, sellerID, ) 
            VALUES (:bundleStatus, :title, :details, :rrp, :discountedPrice, :sellerID);");

        // Try-catch block for handling potential database exceptions
        try {
            // Execute SQL command, establishing values of parameterised fields
            $stmt->execute([":bundleStatus" => $bundle->getStatus(), ":title" => $bundle->getTitle(), ":details" => $bundle->getDetails(), ":rrp" => $bundle->getRrpGBX(),
                ":discountedPrice" => CurrencyTools::gbxToDecimalString($bundle->getDiscountedPriceGBX()), ":sellerID" => CurrencyTools::gbxToDecimalString($bundle->getSellerID())]);
        } catch (\PDOException $e) {
            // Throw exception message aligning with output of database error
            throw new DatabaseException($e->getMessage());
        }

        // Get query ID of the last record added to the database (i.e., the one just created)
        $lastId = (int) DatabaseHandler::getPDO()->lastInsertId();
        // Add ID to Bundle object
        $bundle->id = $lastId;


        // Return Bundle object as output once the database is successfully updated
        return $bundle;
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