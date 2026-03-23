<?php

namespace TTE\App\Model;

use DateTime;

use DateTimeImmutable;

class Reservation extends StoredObject
{
    private int $id;

    private int $bundleID;

    private int $purchaserID;

    private  ReservationStatus $status;

    private string $claimCode;
    const LENGTH = 16;

    private DateTime $reservationDate;

    private string $weatherCondition;

    public function getWeatherCondition(): string
    {
        return $this->weatherCondition;
    }

    public function setWeatherCondition(string $weatherCondition): void
    {
        $this->weatherCondition = $weatherCondition;
    }

    public function getReservationDate(): DateTime {
        return $this->reservationDate;
    }

    public function setReservationDate(DateTime $reservationDate): void {
        $this->reservationDate = $reservationDate;
    }

    public function getID(): int {
        return $this->id;
    }

    public function setBundleID(int $bundleID): void{
        $this->bundleID = $bundleID;
    }

    public function getBundleID(): int
    {
        return $this->bundleID;
    }

    public function setPurchaserID(int $purchaserID): void{
        $this->purchaserID = $purchaserID;
    }

    public function getPurchaserID(): int{
        return $this->purchaserID;
    }

    public function setStatus(ReservationStatus $status): void{
        // If the status is being set to 'no show' or 'cancelled', update the bundle quantity (+1).
        if ($status == ReservationStatus::NoShow || $status == ReservationStatus::Cancelled) {
            $bundle = Bundle::load($this->getBundleID());
            $bundle->setQuantity($bundle->getQuantity() + 1);
        }

        $this->status = $status;
    }

    public function getStatus(): ReservationStatus{
        return $this->status;
    }

    public function setClaimCode(string $claimCode): void{
        $this->claimCode = $claimCode;
    }

    public function getClaimCode(): string{
        return $this->claimCode;
    }

    /**
     * Updates the database with the values stored in the current instance of the reservation object
     *
     * @return void
     * @throws DatabaseException|NoSuchReservationException|NoSuchCustomerException|NoSuchBadgeException|NoSuchStreakException|MissingValuesException|NoSuchBundleException
     */
    public function update(): void {
        // Throw error if reservation with given id does not exist
        if(!self::existsWithID($this->id)) {
            throw new NoSuchReservationException("No such reservation with ID $this->id");
        }

        // Create SQL statement to update reservation record
        $stmt = DatabaseHandler::getPDO()->prepare("UPDATE reservation 
            SET bundleID = :bundleID, purchaserID = :purchaserID, reservationStatus = :reservationStatus, claimCode = :claimCode, reservationDate = :reservationDate, weatherCondition = :weatherCondition WHERE reservationID = :id");

        // Attempt to execute the statement
        try{
            $stmt->execute([":bundleID" => $this->bundleID, ":purchaserID" => $this->purchaserID, ":reservationStatus" => $this->status->value, ":claimCode" => $this->claimCode, ":id" => $this->id, ":reservationDate" => $this->reservationDate, ":weatherCondition" => $this->weatherCondition]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // If reservation is cancelled/no-show, increase quantity of bundle
        if ($this->status == ReservationStatus::Cancelled || $this->status == ReservationStatus::NoShow) {
            // Update quantity for bundle
            $bundle = Bundle::load($this->getBundleID());
            $bundle->setQuantity($bundle->getQuantity() + 1);
            $bundle->update();
        }

        // Check value of reservation status
        if ($this->status == ReservationStatus::Completed) {
            // Check if customer has an ongoing streak and create one if not
            $streak = Customer::load($this->getPurchaserID())->getStreak();
            if ($streak == null) {
                // Create streak
                $streak = Streak::create(["customerID" => $this->getPurchaserID()]);
                // Get current day and time
                $currentDate = new DateTimeImmutable("now");
                // Set appropriate values for fields
                $streak->setStartDate($currentDate);
                $streak->setCurrentWeekStart($currentDate->modify("+1 week"));
                $streak->setEndDate($currentDate->modify("+1 week"));
                $streak->update();
            } else {

                // Start new streak if "current" streak has already ended
                if ($streak->getEndDate() < new DateTimeImmutable("now")) {
                    // Get current date
                    $currentDate = new DateTimeImmutable("now");
                    $streak->setStartDate($currentDate);
                    $streak->setCurrentWeekStart($currentDate);
                    $streak->setEndDate($currentDate->modify("+1 week"));
                    // Update streak
                    $streak->update();
                } else {
                    // Check if a bundle has already been collected to continue the streak
                    if ($streak->getCurrentWeekStart() < new DateTimeImmutable("now")) {
                        // Changing currentWeekStart and endDate to a weeks time signifying update of streak
                        $streak->setCurrentWeekStart($streak->getCurrentWeekStart()->modify("+1 week"));
                        $streak->setEndDate($streak->getCurrentWeekStart()->modify("+1 week"));
                        // Applying update
                        $streak->update();
                    }
                }
            }

            // Get how many weeks have elapsed since start of week
            $start = $streak->getStartDate();
            $now = new DateTimeImmutable("now");

            $diff = $start->diff($now);

            $weeksElapsed = intdiv($diff->days, 7);

            // Default tier value
            $tier = null;

            // Compare to required values for each tier
            if ($weeksElapsed >= 3 && $weeksElapsed < 10 ) {
                $tier = BadgeTier::Bronze;
            } else if ($weeksElapsed >= 10 && $weeksElapsed < 20 ) {
                $tier = BadgeTier::Silver;
            } else if ($weeksElapsed >= 20) {
                $tier = BadgeTier::Gold;
            }

            // Get Dedicated Save badge
            $dedicatedSaver = Badge::loadByTitle("Dedicated Saver");

            // Update information in database
            try {
                $stmt = DatabaseHandler::getPDO()->prepare("UPDATE customer_badge SET tier = :tier, progress = :progress WHERE customerID = :customerID AND badgeID = :badgeID;");
                $stmt->execute([":tier" => $tier, ":progress" => $weeksElapsed, ":customerID" => $this->getPurchaserID(), ":badgeID" => $dedicatedSaver->getId()]);
            } catch (\PDOException $e) {
                throw new DatabaseException($e->getMessage());
            }


            // Retrieve bundle for given reservation
            $bundle = Bundle::load($this->bundleID);

            // Get difference in RRP and discountedPrice
            $discount = $bundle->getRrpGBX() - $bundle->getDiscountedPriceGBX();

            // Get badge details for Bargain Hunter relating to customer
            $badges = Customer::loadBadges($this->purchaserID);
            $bargainHunter = Badge::loadByTitle("Bargain Hunter");
            $bargainHunterCustomer = $badges[$bargainHunter->getId()];

            // Switch-case to assign right value depending on current tier
            switch ($bargainHunterCustomer["tier"]) {
                case null:
                    // Check if discount was £5 to meet requirement
                    if (500 >= $discount && $discount < 1000) {
                            $tier = BadgeTier::Bronze;
                            $progress = 500;
                            break;
                    }

                    // Otherwise, set to current values
                    $tier = null;
                    $progress = 0;
                    break;
                case BadgeTier::Bronze:
                    // Check if discount was £10 to meet requirement
                    if (1000 >= $discount && $discount < 1500) {
                        $tier = BadgeTier::Silver;
                        $progress = 1000;
                        break;
                    }

                    // Otherwise, set to current values
                    $tier = BadgeTier::Bronze;
                    $progress = 500;
                    break;
                case BadgeTier::Silver:
                    // Check if discount was £15 to meet requirement
                    if (1500 >= $discount) {
                        $tier = BadgeTier::Gold;
                        $progress = 1500;
                        break;
                    }

                    // Otherwise, set to current values
                    $tier = BadgeTier::Silver;
                    $progress = 1000;
                    break;
                default:
                    $tier = null;
                    $progress = 0;
            }

            // Update progression and tier for badge
            try {
                $stmt = DatabaseHandler::getPDO()->prepare("UPDATE customer_badge SET tier = :tier, progress = :progress WHERE badgeID = :badgeID AND customerID = :customerID;");
                $stmt->execute([":tier" => $tier?->value, ":progress" => $progress, ":badgeID" => $bargainHunterCustomer["badgeID"], ":customerID" => $bargainHunterCustomer["customerID"]]);
            } catch (\PDOException $e) {
                throw new DatabaseException($e->getMessage());
            }
        }
    }

    /**
     * Creates and returns a reservation object, and add a record to the database
     *
     * @param array $fields
     *
     * @return StoredObject
     *
     * @throws DatabaseException|MissingValuesException|NoSuchBundleException
     */
    public static function create(array $fields): StoredObject {
        // Check that required fields have values
        if(!isset($fields['bundleID']) || !isset($fields['purchaserID']) || !isset($fields['status'])) {
            throw new MissingValuesException("Missing information to create reservation");
        }

        $bundle = Bundle::load($fields['bundleID']);
        // Decrease quantity
        $bundle->setQuantity($bundle->getQuantity() - 1);
        // Update bundle
        $bundle->update();

        // Generate claim code for the bundle if the bundle has no claim code
        if(!isset($fields['claimCode'])) {
            $claimCode = self::generateClaimCode($fields['bundleID'], $fields['purchaserID'],$bundle->getTitle());
        } else {
            $claimCode = $fields['claimCode'];
        }

        // Create new reservation object and populate parameters with given/ generated data
        $thisReservation = new self();
        $thisReservation->bundleID = $fields['bundleID'];
        $thisReservation->purchaserID = $fields['purchaserID'];
        $thisReservation->status = $fields['status'];
        $thisReservation->claimCode = $claimCode;
        if($fields['reservationDate'] == null) {
            $today = new \DateTime();
            $today = getdate($today->getTimestamp())['yday'];
        } else {
            $today = getdate(strtotime($fields['reservationDate']))['yday'];
        }
        $weatherCon = self::getCurrentWeather($today);
        $thisReservation->weatherCondition = $weatherCon;

        // Create SQL statement to create reservation record
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO reservation (bundleID, purchaserID, reservationStatus, claimCode, weatherCondition, reservationDate) 
            VALUES (:bundleID, :purchaserID, :reservationStatus, :claimCode, :weatherCondition, :reservationDate);");

        // Attempt to execute the statement
        try{
            $stmt->execute([":bundleID" => $fields['bundleID'], ":purchaserID" => $fields['purchaserID']
                ,":reservationStatus" => $fields['status']->value, ":weatherCondition" => $weatherCon, ":claimCode" => $claimCode, ":reservationDate" => $fields['reservationDate']]);
        } catch (\PDOException $e){
            throw new DatabaseException($e->getMessage());
        }

        // Get the id of the created reservation and add to the bundle object
        $thisReservation->id = DatabaseHandler::getPDO()->lastInsertId();

        // Return the reservation object
        return $thisReservation;
    }

    private static function getCurrentWeather($index): string {
        $weathers = array_map('str_getcsv', file(__DIR__ . '/../Dataset/weatherCondition.csv'));
        return $weathers[$index][0];
    }

    /**
     * Creates and returns a random claim code, which is a 16 string of random characters in the alphabet
     *
     * @param int $reservationID
     * @param int $purchaserID
     * @param string $title
     * @return string
     */
    public static function generateClaimCode(int $reservationID, int $purchaserID, string $title): string {
        $validClaimCode = false;

        // repeat until generated unique claim code
        while (!$validClaimCode) {
            // generate claim code message
            $messg = $reservationID . $purchaserID . $title . date('m/d/Y h:i:s a', time());

            // hash had get value
            $claimCode = hash('sha512', $messg, false);
            $claimCode = substr($claimCode, 0, self::LENGTH);

            // Check if the claim code is unique
            $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM reservation WHERE claimCode = :claimCode");
            $stmt->execute([":claimCode" => $claimCode]);

            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if($row === false) {
                $validClaimCode = true;
            }
        }

        // return claim code
        return $claimCode;
    }

    /**
     * load reservation data from the database for a reservation with a given ID
     *
     * @param int $id
     *
     * @return Reservation
     *
     * @throws DatabaseException
     * @throws NoSuchReservationException
     */
    public static function load(int $id): Reservation {
        // Check if the bundle exists with given ID
        if(self::existsWithID($id)) {
            // Create SQL statement to load a record from the database
            $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM reservation WHERE reservationID = :id");

            // The result of the query will be stored in this variable
            $row = array();

            // Attempt to execute the statement
            try{
                $stmt->execute([":id" => $id]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e){
                throw new DatabaseException($e->getMessage());
            }

            // Create reservation object with fetched data
            $reservation = new self();
            $reservation->id = $id;
            $reservation->bundleID = $row["bundleID"];
            $reservation->purchaserID = $row["purchaserID"];
            $reservation->status = ReservationStatus::from($row["reservationStatus"]);
            $reservation->claimCode = $row["claimCode"];

            return $reservation;

        } else{
            // Throw error if no record exists
            throw new NoSuchReservationException("No such reservation with ID $id");
        }
    }

    /**
     * Checks weather a bundle of a given id exists
     *
     * @param int $id
     *
     * @return bool
     */
    public static function existsWithID(int $id): bool {
        // Prepare SQL statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM reservation WHERE reservationID=:id;");

        // Execute the statement
        $stmt->execute(["id" => $id]);

        // Fetch the result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return true if a reservation with the given id exists, and false if not
        return !($row === false);
    }

    /**
     * Deletes record from the database with a given ID
     *
     * @param int $id
     *
     * @return void
     *
     * @throws DatabaseException
     * @throws NoSuchReservationException
     */
    public static function delete(int $id): void {
        // Check if the bundle exists with given ID
        if(self::existsWithID($id)) {
            // Create SQL statement to delete record with given ID
            $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM reservation WHERE reservationID=:id;");

            //Attempt to execute statement
            try{
                $stmt->execute([":id" => $id]);
            } catch (\PDOException $e){
                throw new DatabaseException($e->getMessage());
            }
        } else{
            // Throw error if no record exists
            throw new NoSuchReservationException("No such reservation with ID $id");
        }
    }

    public static function getAllReservationsForUser (int $userID, string $accountType): array{
        if ($accountType === "seller") {
            // Prepare SQL statement to get all reservations a seller is involved in
            $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM reservation INNER JOIN bundle ON reservation.bundleID=bundle.bundleID WHERE sellerID=:id;");

            // Try to execute
            try {
                $stmt->execute([":id" => $userID]);
                // Load all reservation from query and return array
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                throw new DatabaseException($e->getMessage());
            }

        } else if ($accountType === "buyer") {
            // Prepare SQL statement to get all reservations a buyer has made
            $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM reservation WHERE purchaserID=:purchaserID;");

            //Try to execute
            try {
                $stmt->execute([":purchaserID" => $userID]);
                // Load all reservations and return them
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                throw new DatabaseException($e->getMessage());
            }
        } else {
            throw new \InvalidArgumentException("Invalid account type");
        }
    }

    /**
     * Claim bundle and update statuses
     *
     * @param string $claimCode
     *
     * @return void
     *
     * @throws DatabaseException
     * @throws NoSuchBundleException
     * @throws invalidClaimCodeExeption
     * @throws NoSuchCustomerException
     */
    public function claimReservation (string $claimCode): void {
        // Check if claim codes match
        if($claimCode != $this->claimCode) {
            throw new invalidClaimCodeExeption("Given claim code does not match with bundles claim code");
        }

        // Set and update reservation status
        $this->setStatus(ReservationStatus::Completed);
        $this->update();

        // Update bundle status
        $bundle = Bundle::load($this->bundleID);
        $bundle->setStatus(BundleStatus::OffSale);
        $bundle->setPurchaserID($this->getPurchaserID());
        $bundle->update();
    }

    /**
     * Marks the reservation as no show in the database
     *
     * @param int $id The ID of the reservation to be marked as no show
     * @throws DatabaseException
     * @throws NoSuchReservationException
     */
    public static function markNoShow(int $id): void {
        $reservation = Reservation::load($id);
        $reservation->setStatus(ReservationStatus::NoShow);
        $reservation->update();
    }

    /**
     * Marks the reservation as completed and the associated bundle as collected in the database
     *
     * @param int $id The ID of the reservation to marked as collected
     * @throws MissingValuesException
     * @throws NoSuchBundleException
     * @throws DatabaseException
     * @throws NoSuchCustomerException
     * @throws NoSuchReservationException
     * @throws NoSuchStreakException
     * @throws NoSuchBadgeException
     */
    public static function markCollected(int $id): void {
        $reservation = Reservation::load($id);
        $reservation->setStatus(ReservationStatus::Completed);
        $reservation->update();
    }
}