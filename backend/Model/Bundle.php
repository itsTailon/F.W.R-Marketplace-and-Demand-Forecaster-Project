<?php

namespace TTE\App\Model;

use DateTimeImmutable;
use http\Exception\InvalidArgumentException;
use TTE\App\Helpers\CurrencyTools;
use TTE\App\Helpers\TimeTools;
use ValueError;

class Bundle extends StoredObject {

    private int $id;

    private BundleStatus $status;

    private string $title;
    const MAX_LEN_TITLE = 128; // Maximum length of the 'title' field (as in DB schema)

    private string $details;

    private int $quantity;

    private int $rrpGBX;

    private int $discountedPriceGBX;

    private DateTimeImmutable $expiryDate;

    private int $sellerID;

    private ?int $purchaserID;

    private string $pickupWindow;

    /**
     * Take current values of all attributes of the Bundle object
     * @throws DatabaseException|NoSuchBundleException|MissingValuesException
     */
    public function update(): void
    {

        // Check validity of bundleID
        if (!Bundle::existsWithID($this->id)) {
            // Exception thrown if ID is invalid
            throw new NoSuchBundleException("No such bundle with ID $this->id");
        }

        // Check if current object values are all set
        if (!isset($this->id) || !isset($this->status) || !isset($this->title) || !isset($this->details) || !isset($this->rrpGBX) ||
            !isset($this->discountedPriceGBX) || !isset($this->expiryDate) || !isset($this->pickupWindow) || empty(trim($this->getTitle())) || empty(trim($this->getDetails()))) {

            // Produce error message if field exists with no content
            throw new MissingValuesException("Missing information required to create a bundle");
        }

        // Update bundle status depending on quantity taken on
        if ($this->getQuantity() == 0) {
            $this->setStatus(BundleStatus::OffSale);
        }

        if  ($this->getQuantity() > 0) {
            $this->setStatus(BundleStatus::OnSale);
        }


        // SQL query to be executed
        $sql_query = "UPDATE bundle SET bundleStatus = :bundleStatus, title = :title, details = :details, quantity = :quantity, rrp = :rrp, discountedPrice = :discountedPrice, expiryDate = :expiryDate, sellerID = :sellerID, purchaserID = :purchaserID, pickupWindow = :pickupWindow WHERE bundleID = :id;";
        // Prepare and execute query
        $stmt = DatabaseHandler::getPDO()->prepare($sql_query);

        // Try-catch block for handling potential database exceptions
        try {
            // Execute SQL command, establishing values of parameterised fields
            $stmt->execute([":bundleStatus" => $this->getStatus()->value, ":expiryDate" => $this->getExpiryDate()->format("Y-m-d"), ":title" => $this->getTitle(), ":details" => $this->getDetails(), ":quantity" => $this->getQuantity() ,":rrp" => CurrencyTools::gbxToDecimalString($this->getRrpGBX()),
                ":discountedPrice" => CurrencyTools::gbxToDecimalString($this->getDiscountedPriceGBX()), ":sellerID" => $this->getSellerID(), ":purchaserID" => $this->getPurchaserID(), "pickupWindow" => $this->pickupWindow , ":id" => $this->id]);
        } catch (\PDOException $e) {
            // Throw exception message aligning with output of database error
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Create a Bundle object and add entry to database.
     *
     * @param array $fields associative array of fields required for Bundle.
     * @return Bundle object with fields holding values passed in at call of the function.
     * @throws MissingValuesException|NoSuchSellerException|NoSuchCustomerException|DatabaseException|ValueError
     */
    public static function create(array $fields): Bundle {

        // Presence check on all inputs - not on purchaserID as it is nullable
        if (!isset($fields['sellerID']) || !isset($fields['bundleStatus']) || !isset($fields['expiryDate']) || !isset($fields['title']) || !isset($fields['details']) || !isset($fields['rrp']) ||
            !isset($fields['discountedPrice']) || !isset($fields['pickupWindow']) || empty(trim($fields['title'])) || empty(trim($fields['details'])) || empty(trim($fields['quantity']))) {

            // Produce error message if field exists with no content
            throw new MissingValuesException("Missing information required to create a bundle");
        }

        // Verify time slot value validity
        if (!TimeTools::verifyTimeSlotStringFormat($fields['pickupWindow'])) {
            throw new ValueError("Invalid pickup window value '" . $fields['pickupWindow'] . "'.");
        }

        // Creating new Bundle object
        $bundle = new Bundle();
        // Updating attributes in line with input
        $bundle->setStatus($fields['bundleStatus']);
        $bundle->setTitle($fields['title']);
        $bundle->setDetails($fields['details']);
        $bundle->setRrpGBX($fields['rrp']);
        $bundle->setDiscountedPriceGBX($fields['discountedPrice']);
        $bundle->setExpiryDate($fields['expiryDate']);
        $bundle->setSellerID($fields['sellerID']);
        $bundle->setPurchaserID(isset($fields['purchaserID']) ? $fields['purchaserID'] : null);
        $bundle->setQuantity($fields['quantity']);
        $bundle->setPickupWindow($fields['pickupWindow']);

        // Creating parameterised SQL command
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO bundle (bundleStatus, title, details, quantity, rrp, discountedPrice, expiryDate, sellerID, purchaserID, pickupWindow) 
            VALUES (:bundleStatus, :title, :details, :quantity, :rrp, :discountedPrice, :expiryDate, :sellerID, :purchaserID, :pickupWindow);");

        // Try-catch block for handling potential database exceptions
        try {
            // Execute SQL command, establishing values of parameterised fields
            $stmt->execute([":bundleStatus" => $bundle->getStatus()->value, ":title" => $bundle->getTitle(), ":expiryDate" => $bundle->getExpiryDate()->format("Y-m-d"), ":details" => $bundle->getDetails(), ":quantity" => $bundle->getQuantity(), ":rrp" => CurrencyTools::gbxToDecimalString($bundle->getRrpGBX()),
                ":discountedPrice" => CurrencyTools::gbxToDecimalString($bundle->getDiscountedPriceGBX()), ":sellerID" => $bundle->getSellerID(), ":purchaserID" => $bundle->getPurchaserID(), "pickupWindow" => $bundle->getPickupWindow()]);
        } catch (\PDOException $e) {
            // Throw exception message aligning with output of database error
            throw new DatabaseException($e->getMessage());
        }

        // Get query ID of the last record added to the database (i.e., the one just created)
        $lastId = DatabaseHandler::getPDO()->lastInsertId();
        // Add ID to Bundle object
        $bundle->id = intval($lastId);


        // Return Bundle object as output once the database is successfully updated
        return $bundle;
    }

    /**
     * Loads a bundle from the database.
     *
     * @param int $id ID of the bundle to be loaded.
     *
     * @return Bundle a Bundle object representing the loaded bundle
     * @throws DatabaseException if no bundle exists with the given ID.
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
        $bundle->status = BundleStatus::from($row['bundleStatus']); // Convert to enum representation
        $bundle->setExpiryDate(DateTimeImmutable::createFromFormat("Y-m-d", $row['expiryDate'])); // Time is set to midnight by default
        $bundle->title = $row['title'];
        $bundle->details = $row['details'];
        // MySQL DECIMAL values are returned by PDO as strings, so convert to ints representing pence (ints to avoid FP errors)
        $bundle->rrpGBX = CurrencyTools::decimalStringToGBX($row['rrp']);
        $bundle->discountedPriceGBX = CurrencyTools::decimalStringToGBX($row['discountedPrice']);
        $bundle->sellerID = $row['sellerID'];
        $bundle->purchaserID = $row['purchaserID'];
        $bundle->setQuantity($row['quantity']);
        $bundle->pickupWindow = $row['pickupWindow'];

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

    /**
     * Method that retrieves all bundles that are currently still before their expiration date
     * @throws DatabaseException
     * @return array of bundle objects that are still active
     */
    public static function loadAllActiveBundles(): array {
        // SQL parameterised query that retrieves all bundles that have not expired
        try {
            $stmt = DatabaseHandler::getPDO()->prepare("SELECT bundleID FROM bundle WHERE expiryDate < :timestamp;");
            $stmt->execute([":timestamp" => (new DateTimeImmutable())->format("Y-m-d")]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Use bundleIDs to load all bundle objects with matching IDs and return them
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $bundles = array();
        foreach ($rows as $row) {
            $bundles[] = Bundle::load($row["bundleID"]);
        }

        return $bundles;
    }

    /**
     * @return array of bundles that are past their expiration date
     * @throws DatabaseException
     */
    public static function loadAllExpiredBundles(): array {
        // SQL parameterised query that retrieves all bundles that have not expired
        try {
            $stmt = DatabaseHandler::getPDO()->prepare("SELECT bundleID FROM bundle WHERE expiryDate >= :timestamp;");
            $stmt->execute([":timestamp" => (new DateTimeImmutable())->format("Y-m-d")]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Use bundleIDs to load all bundle objects with matching IDs and return them
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $bundles = array();
        foreach ($rows as $row) {
            $bundles[] = Bundle::load($row["bundleID"]);
        }

        return $bundles;
    }

    /**
     * Adds an allergen to the bundle.
     *
     * NOTE: Updates made by this method are applied immediately, even before Bundle::save() is called.
     *
     * @param string $allergenName the name of the allergen
     * @return void
     * @throws DatabaseException if a database issue occurs when trying to verify/add the allergen
     * @throws NoSuchAllergenException if the allergen name given does not correspond to a valid allergen
     */
    public function addAllergen(string $allergenName): void {
        // Ensure that allergen exists
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM allergen WHERE allergenName=:allergenName;");
        try {
            $stmt->execute(["allergenName" => $allergenName]);
        } catch (\PDOException  $e) {
            throw new DatabaseException("Could not load allergen with name '" . $allergenName . "'.");
        }
        if ($stmt->fetch() === false) {
            throw new NoSuchAllergenException("No allergen exists with name '" . $allergenName . "'.");
        }

        // Allergen does exist, so add it to the bundle
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO bundle_allergen (bundleID, allergenName) VALUES (:bundleID, :allergenName);");
        try {
            $stmt->execute([
               "bundleID" => $this->getID(),
               "allergenName" => $allergenName,
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException("Could not add allergen with name '" . $allergenName . "' to bundle with ID " . $this->getID() . ".");
        }
    }

    /**
     * @param string $categoryName name of category to be added to bundle
     * @throws DatabaseException|NoSuchCategoryException
     * @return void
     */
    public function addCategory(string $categoryName): void {
        // Check category exists
        if (!Category::categoryExists($categoryName)) {
            throw new NoSuchCategoryException("No category exists with name '" . $categoryName . "'.");
        }

        // If category exists, add to bundle
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO bundle_category (bundleID, categoryName) VALUES (:bundleID, :categoryName);)");
        try {
            $stmt->execute(["bundleID" => $this->getID(), "categoryName" => $categoryName]);
        } catch (\PDOException $e) {
            throw new DatabaseException("Could not add bundle category with name '" . $categoryName . "'.");
        }

    }

    /**
     * Removes an allergen from the bundle.
     *
     * NOTE: Updates made by this method are applied immediately, even before Bundle::save() is called.
     *
     * @param string $allergenName the name of the allergen
     * @return void
     * @throws DatabaseException if a database issue occurs when trying to verify/remove the allergen
     * @throws NoSuchAllergenException if the allergen name given does not correspond to a valid allergen
    */
    public function removeAllergen(string $allergenName): void {
        // Ensure that allergen exists
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM allergen WHERE allergenName=:allergenName;");
        try {
            $stmt->execute(["allergenName" => $allergenName]);
        } catch (\PDOException  $e) {
            throw new DatabaseException("Error attempting to load allergen with name '" . $allergenName . "'.");
        }
        if ($stmt->fetch() === false) {
            throw new NoSuchAllergenException("No allergen exists with name '" . $allergenName . "'.");
        }

        // Allergen does exist, so remove it from bundle
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM bundle_allergen WHERE bundleID=:bundleID AND allergenName=:allergenName;");
        try {
            $stmt->execute([
                "bundleID" => $this->getID(),
                "allergenName" => $allergenName,
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException("Could not remove allergen with name '" . $allergenName . "' from bundle with ID " . $this->getID() . " (it may not have been assigned to the bundle).");
        }
    }

    /**
     * Remove category attached to bundle
     * @return void
     * @throws DatabaseException|NoSuchCategoryException
     */
    public function removeCategory(): void {
        $categoryName = $this->getCategory();
        // Check if there is currently a category attached to bundle
        if ($categoryName == null) {
            throw new NoSuchCategoryException("No category is currently assigned to bundle with ID " . $this->getID());
        }

        // Else, remove category from bundle
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM bundle_category WHERE bundleID=:bundleID;");
        try {
            $stmt->execute(["bundleID" => $this->getID()]);
        } catch (\PDOException $e) {
            throw new DatabaseException("Could not remove bundle category with name '" . $categoryName . "' from bundle with ID " . $this->getID());
        }
    }

    /**
     * Returns an array of the names of all of the bundle's allergens.
     *
     * If the bundle has no allergens, an empty array is returned.
     *
     * @return array if the bundle has allergens, an array of strings (names of bundle's allergens). Otherwise, an empty array.
     */
    public function getAllergens(): array {
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT allergen.allergenName FROM allergen JOIN bundle_allergen ON bundle_allergen.bundleID=:bundleID WHERE allergen.allergenName=bundle_allergen.allergenName");
        $stmt->execute([
            "bundleID" => $this->getID(),
        ]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get category attached to bundle
     *
     * @throws DatabaseException
     */
    public function getCategory(): ?string {
        // Retrieve category attached to bundle
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT categoryName from bundle_category WHERE bundleID=:bundleID;");
        try {
            $stmt->execute(["bundleID" => $this->getID()]);
        } catch (\PDOException $e) {
            throw new DatabaseException("Could not retrieve category for bundle with bundle ID " . $this->getID());
        }
        // Get result (false if none)
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        } else {
            return $result["categoryName"];
        }
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
            throw new ValueError("Cannot set bundle title longer than " . self::MAX_LEN_TITLE . " characters");
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
            throw new ValueError("Cannot set RRP to negative value");
        }

        $this->rrpGBX = $gbx;
    }

    public function getExpiryDate(): DateTimeImmutable {
        return $this->expiryDate;
    }

    public function setExpiryDate(DateTimeImmutable $expiryDate): void {
        $this->expiryDate = $expiryDate;
    }

    public function getDiscountedPriceGBX(): int {
        return $this->discountedPriceGBX;
    }

    public function setDiscountedPriceGBX(int $gbx): void {
        // Ensure value is non-negative
        if ($gbx < 0) {
            throw new ValueError("Cannot set discounted price to negative value");
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
     * @throws NoSuchSellerException
     */
    private function setSellerID(int $sellerID): void {
        // Ensure that the given seller ID corresponds to an actual seller
        if (!Seller::existsWithID($sellerID)) {
            throw new NoSuchSellerException("Cannot set seller ID to non-existent seller (invalid seller ID $sellerID)");
        }

        $this->sellerID = $sellerID;
    }

    /**
     * Returns the quantity of currently available bundles within bundle listing
     * @return int quantity
     */
    public function getQuantity(): int {
        return $this->quantity;
    }

    public function getPurchaserID(): ?int {
        return $this->purchaserID;
    }

    public function setPurchaserID(?int $customerID): void {
        // Ensure that the given purchaser ID corresponds to an actual customer
        if ($customerID != null && !Customer::existsWithID($customerID)) {
            throw new NoSuchCustomerException("Cannot set purchaser ID to non-existent customer (invalid customer ID $customerID)");
        }

        $this->purchaserID = $customerID;
    }

    /**
     * Update bundle listing quantity
     * @param int $quantity value to be updated to
     * @return void
     */
    public function setQuantity(int $quantity): void {
        // Ensure quantity is valid
        if ($quantity < 0) {
            throw new ValueError("Cannot set bundle quantity to negative value");
        }

        // Update quantity
        $this->quantity = $quantity;
    }

    /**
     * Sets the pickup window from a string in the format 'hh:mm-hh:mm', validated using TimeTools::verifyTimeSlotStringFormat.
     *
     * @param string $pickupWindow
     * @throws ValueError if an invalid pickup window value is passed
     * @return void
     */
    public function setPickupWindow(string $pickupWindow): void {
        if (!TimeTools::verifyTimeSlotStringFormat($pickupWindow)) {
            throw new ValueError("Invalid pickup window value '$pickupWindow'.");
        }

        $this->pickupWindow = $pickupWindow;
    }

    public function getPickupWindow(): string {
        return $this->pickupWindow;
    }

    /**
     * @throws DatabaseException
     * @throws NoSuchBundleException
     */
    public static function delete(int $id): void {
        // Create SQL command to delete bundle of given ID
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM bundle WHERE bundleID=:bundleID;");

        // Check if bundle exists
        if (Bundle::existsWithID($id)) {
            // Attempt to run SQL statement
            try {
                $stmt->execute(["bundleID" => $id]);
            } catch (\PDOException $e) {
                throw new DatabaseException($e->getMessage());
            }
        } else {
            // If bundle does not exist, throw error
            throw new NoSuchBundleException("No bundle found with ID $id");
        }
    }

    /**
     * Searches the bundle table for bundles with the query string as a substring of their names
     * or descriptions
     *
     * @param string $withWhatQuery The query string
     * @return array The list of results, as an array of Bundle objects
     */
    public static function searchBundles(string $withWhatQuery) : array {
        $pattern = "%" . $withWhatQuery . "%";
        $query = "SELECT bundleID FROM bundle WHERE (title LIKE :pattern OR details LIKE :pattern) AND bundleStatus = :status";

        $stmt = DatabaseHandler::getPDO()->prepare($query);
        $stmt->execute([":pattern" => $pattern, ":status" => "onsale"]);

        $rowsRaw = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $rows = array();

        for ($i = 0; $i < count($rowsRaw); $i++)
        {
            try {
                $rows[$i] = Bundle::load($rowsRaw[$i]["bundleID"]);
            } catch (DatabaseException $e) {
                echo "Something went very wrong loading a bundle";
            }
        }

        return $rows;
    }

    /**
     * Displays the bundle in the style used in browse.php
     * @return void
     */
    public function display(): void {
        $format = "<div class = 'displayedbundle'><a href='view_bundle.php?id=%s' class = 'displayedbundlelink'><h2 class>%s</h2><p>%s</p></a></div>";
        echo sprintf($format, $this->id, htmlspecialchars($this->title), htmlspecialchars($this->details));
    }
}