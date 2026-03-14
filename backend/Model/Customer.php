<?php

namespace TTE\App\Model;

use MongoDB\BSON\PackedArray;
use PDOException;
use TTE\App\Auth\NoSuchRoleException;
use TTE\App\Auth\RBACManager;
use DateTimeImmutable;

class Customer extends Account {

    private string $username;

    public function update(): void {
        // TODO: Implement update() method.
    }

    /**
     * Creates a record for a customer in the database and then returns an object describing that customer
     *
     * @param array $fields The information inputted into the signup form
     * @return Customer The newly created customer
     * @throws DatabaseException|NoSuchCustomerException|MissingValuesException
     */
    public static function create(array $fields): Customer {
        // Create the account in the database
        $account = parent::create([
            'email' => $fields['email'],
            'accountType' => 'customer',
            'password' => $fields['password']
        ]);

        // Get creation date
        $creationDate = new DateTimeImmutable("now");


        try {
            // Create the customer in the database
            $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO customer(customerID, username, creationDate) VALUES (:id, :username, :creationDate);");
            $stmt->execute(["id" => $account->getUserID(), "username" => $fields['username'], "creationDate" => $creationDate]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Create and return a customer object
        $customer = new Customer();
        $customer->username = $fields['username'];
        $customer->userID = $account->getUserID();
        $customer->setEmail($fields['email']);
        $customer->accountType = "customer";

        $customerID = array("customerID" => $customer->getUserID());

        try {
            RBACManager::assignRoleToUser($customer->getUserID(), "customer");
        } catch (NoSuchRoleException $e) {
            die("There is no such role");
        } catch (NoSuchAccountException $e) {
            die("There is no such account");
        }

        // Create a streak attached to customer, that has null for all current date values
        Streak::create($customerID);

        // Get all existing badges that a customer can hold
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT badgeID FROM badge;");
        try {
            $stmt->execute();
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Check first output of executed query to ensure some result was returned
        $first_row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$first_row) {
            // Throw exception as no badges exist
            throw new DatabaseException("Missing badges to present to customer.");
        }

        // Else, add this first returned ID as one of the customer's badges
        try {
            $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO customer_badge (badgeID, customerID) VALUES (:badgeID, :customerID);");
            $stmt->execute([":badgeID" => $first_row["badgeID"], ":customerID" => $customer->getUserID()]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Add all other existing badges iteratively
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            try {
                $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO customer_badge (badgeID, customerID) VALUES (:badgeID, :customerID);");
                $stmt->execute([":badgeID" => $row["badgeID"], ":customerID" => $customer->getUserID()]);
            } catch (\PDOException $e) {
                throw new DatabaseException($e->getMessage());
            }
        }

        // Return required output
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

    /**
     * @param int $customerID
     * @throws DatabaseException|NoSuchCustomerException
     * @return Streak|null, where Streak is if there is a streak related to the customer, and null if not
     */
    public function getStreak(): ?Streak {
        // Get customer ID
        $customerID = $this->getUserID();

        // Check database for streak entry using this customer ID
        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM streak WHERE customerID=:customerID;");

        // Execute statement with given bundle ID
        $stmt->execute(["customerID" => $customerID]);

        // Get result
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        } else {
            // Get streakID and load streak object
            $streakID = $result["streakID"];
            return Streak::load($streakID);
        }
    }

    /**
     * @throws DatabaseException
     */
    public static function delete(int $id): void {
        // Check customer with given ID exists
        if (Customer::existsWithID($id) === false) {
            throw new DatabaseException("No customer found with ID $id");
        }

        // Remove all badges attaches to this customer in customer_badge
        try {
            $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM customer_badge WHERE customerID=:customerID;");
            $stmt->execute(["customerID" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Create SQL command to delete customer, corresponding account instance, and streak of given ID
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM streak WHERE customerID=:customerID;");
        try {
            $stmt->execute(["customerID" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM rbac_ua WHERE userID = :userID;");
        try {
            $stmt->execute(["userID" => $id]);
        } catch(\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM customer WHERE customerID=:customerID;");
        try {
            $stmt->execute(["customerID" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM account WHERE userID=:userID;");
        try {
            $stmt->execute(["userID" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Call superclass method
    }

    /**
     * Returns a personal impact metric calculation.
     *
     * Possible metrics are described in the source code for the ImpactMetric enum.
     *
     * @param ImpactMetric $metric
     * @return int|float
     */
    public function getImpactMetric(ImpactMetric $metric): int|float {
        switch ($metric) {
            case ImpactMetric::Bundles_Collected:
                return $this->calculateBundlesCollected();
                break;

            case ImpactMetric::CO2_Saved:
                return $this->calculateCO2KgSaved();
                break;

            default:
                return -1;
        }
    }

    /**
     * Returns the customer's total number of bundles collected (i.e. completed reservations).
     *
     * @return int the customer's total number of bundles collected (i.e. completed reservations)
     * @throws DatabaseException
     */
    private function calculateBundlesCollected(): int {
        // Prepare SQL statement to count the number of completed customer reservations (i.e. collected bundles)
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT COUNT(*) FROM reservation WHERE purchaserID=:purchaserID AND reservationStatus='completed';");

        // Attempt to execute statement
        try {
            $stmt->execute(["purchaserID" => $this->userID]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Return the output of COUNT(*) (i.e. the number of bundles collected)
        return $stmt->fetchColumn();
    }

    /**
     * Returns a customer's estimated C02(kg) savings.
     *
     * @return float the customer's estimated C02(kg) savings
     * @throws DatabaseException
     */
    private function calculateCO2KgSaved(): float {
        // Multiply total bundles collected by a constant, which is the baseline estimate of CO2(kg) savings per bundle collected.
        return (float)$this->calculateBundlesCollected() * 2.0;
    }

    /**
     * Method that retrieves all data relating to badges attached to current customer
     * @param int $customerID ID of the customer whose badges are being loaded
     * @throws DatabaseException|NoSuchCustomerException
     */
    public static function loadBadges(int $customerID): array{

        // Retrieving all badges of customer and iterating through them
        try {
            $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM customer_badges WHERE customerID=:id;");
            $stmt->execute([":id" => $customerID]);
        } catch (\PDOException $e) {
            throw new DatabaseException("Failed to load customer's badges.");
        }

        // Array that will contain arrays containing each badge's content
        $badges = array();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

            // Create array holding details of badge
            $badge = array(
                "badgeID" => intval($row["badgeID"]),
                "customerID" => intval($row["customerID"]),
                "tier" => BadgeTier::from($row["tier"]),
                "progress" => intval($row["progress"]),
            );

            // Get title of the badge  for given iteration
            $badgeDetails = Badge::load($badge["badgeID"]);

            // Check if badge is titled "Rescue Vet"
            if ($badgeDetails->getTitle() == "Rescue Vet") {
                // Get creation date of customer's account
                $creationDate = Customer::getCreationDate($customerID);
                // Get current date
                $currentDate = new DateTimeImmutable('now');

                // Compare dates and set progress value for Rescue Vet badge (even if no change to avoid excessive calls)
                $dateDiff = $currentDate->diff($creationDate);
                $monthDiff = ($dateDiff->y * 12) + $dateDiff->m;

                // Calculate tier given difference
                if ($monthDiff >= 3 && $monthDiff < 6) {
                    // Update tier and progress
                    $tier = BadgeTier::Bronze;
                } else if ($monthDiff >= 6 && $monthDiff < 12) {
                    $tier = BadgeTier::Silver;
                } else if ($monthDiff >= 12) {
                    $tier = BadgeTier::Gold;
                }

                try {
                    $stmt = DatabaseHandler::getPDO()->prepare("UPDATE customer_badges SET (progress=:progress AND tier=:tier) WHERE (customerID=:customerID AND badgeID=:rescVetTitle);");
                    $stmt->execute([":progress" => $monthDiff, ":tier" => $tier, ":rescVetTitle" => $badgeDetails->getTitle()]);

                } catch (\PDOException $e) {
                    throw new DatabaseException($e->getMessage());
                }

                // Update value of this $badge to match database
                $badge["progress"] = $monthDiff;
            }

            // Add array to $badges array
            $badges[] = $badge;
        }

        // Return array of array representing badges
        return $badges;
    }

    /**
     * @param int $id ID of the customer whose creation date is required
     * @throws NoSuchCustomerException|DatabaseException|\Exception
     * @return DateTimeImmutable of creation date of customer's account
     */
    public static function getCreationDate(int $id): DateTimeImmutable {
        // Check customer ID does exist
        if (!Customer::existsWithID($id)) {
            // Throw error
            throw new NoSuchCustomerException("No customer found with ID $id");
        }

        // Retrieve customer's creation date
        try {
            $stmt = DatabaseHandler::getPDO()->prepare("SELECT creationDate FROM customer WHERE customerID=:id;");
            $stmt->execute([":id" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Get output of SQL request
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Ensure output exists
        if (!$row) {
            throw new DatabaseException("Failed to retrieve creation date.");
        }

        // Return DateTimeImmutable of creation date
        return new DateTimeImmutable($row["creationDate"]);
    }
}