<?php

namespace TTE\App\Model;

class Reservation extends StoredObject
{
    private int $id;

    private int $bundleID;

    private int $purchaserID;

    private  ReservationStatus $status;

    private string $claimCode;
    const LENGTH = 16;

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
     *
     * @throws DatabaseException
     * @throws NoSuchReservationException
     */
    public function update(): void {
        // Throw error if reservation with given id does not exist
        if(!self::existsWithID($this->id)) {
            throw new NoSuchReservationException("No such reservation with ID $this->id");
        }

        // Create SQL statement to update reservation record
        $stmt = DatabaseHandler::getPDO()->prepare("UPDATE reservation 
            SET bundleID = :bundleID, purchaserID = :purchaserID, reservationStatus = :reservationStatus, claimCode = :claimCode WHERE reservationID = :id");

        // Attempt to execute the statement
        try{
            $stmt->execute([":bundleID" => $this->bundleID, ":purchaserID" => $this->purchaserID, ":reservationStatus" => $this->status->value, ":claimCode" => $this->claimCode, ":id" => $this->id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Creates and returns a reservation object, and add a record to the database
     *
     * @param array $fields
     *
     * @return StoredObject
     *
     * @throws DatabaseException
     * @throws MissingValuesException
     */
    public static function create(array $fields): StoredObject {
        // Check that required fields have values
        if(!isset($fields['bundleID']) || !isset($fields['purchaserID']) || !isset($fields['status'])) {
            throw new MissingValuesException("Missing information to create reservation");
        }

        $bundle = Bundle::load($fields['bundleID']);

        // Update bundle's method
        try {
            $bundle->setStatus(BundleStatus::Reserved);
            $bundle->update();
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        } catch (DatabaseException $e) {
        } catch (NoSuchBundleException $e) {
        }

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

        // Create SQL statement to create reservation record
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO reservation (bundleID, purchaserID, reservationStatus, claimCode) 
            VALUES (:bundleID, :purchaserID, :reservationStatus, :claimCode);");

        // Attempt to execute the statement
        try{
            $stmt->execute([":bundleID" => $fields['bundleID'], ":purchaserID" => $fields['purchaserID']
                ,":reservationStatus" => $fields['status']->value, ":claimCode" => $claimCode]);
        } catch (\PDOException $e){
            throw new DatabaseException($e->getMessage());
        }

        // Get the id of the created reservation and add to the bundle object
        $thisReservation->id = DatabaseHandler::getPDO()->lastInsertId();

        // Return the reservation object
        return $thisReservation;
    }

    /**
     * Creates and returns a random claim code, which is a 16 string of random characters in the alphabet
     *
     * @return string
     */
    public static function generateClaimCode(int $reservationID, int $purchaserID, string $title): string {
        // generate claim code
        $messg = $reservationID . $purchaserID . $title;

        // hash had get value
        $claimCode = hash('sha512', $messg, false);
        $claimCode = substr($claimCode, 0, self::LENGTH);



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
        $bundle->setStatus(BundleStatus::Collected);
        $bundle->update();
    }
}