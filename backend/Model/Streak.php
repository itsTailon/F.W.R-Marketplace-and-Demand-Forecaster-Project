<?php

namespace TTE\App\Model;

use Exception;
use TTE\App\Model\StreakStatus;
use DateTimeImmutable;

class Streak extends StoredObject {
    private int $id;

    private StreakStatus  $status;

    private DateTimeImmutable $startDate;

    private ?DateTimeImmutable $endDate;

    private int $customerID;

    /**
     * Method updating DB entry for streak to current values held by object it is called for
     * @throws NoSuchStreakException|DatabaseException
     */
    public function update(): void
    {
        // Check validity of streakID
        if (!Streak::existsWithID($this->id)) {
            // Exception thrown if ID is invalid
            throw new NoSuchStreakException("No such streak with ID $this->id");
        }

        // SQL query to be executed
        $sql_query = "UPDATE streak SET streakStatus = :streakStatus, startDate = :startDate, endDate = :endDate, customerID = :customerID WHERE streakID = :streakID;";
        // Prepare and execute query
        $stmt = DatabaseHandler::getPDO()->prepare($sql_query);

        // Try-catch block for handling potential database exceptions
        try {
            // Execute SQL command, establishing values of parameterised fields
            $stmt->execute([":streakID" => $this->id, ":streakStatus" => $this->getStatus()->value,":startDate" => $this->getStartDate()->format("Y-m-d H:i:s"), ":endDate" => $this->getEndDate()->format("Y-m-d H:i:s"), ":customerID" => $this->getCustomerID()]);
        } catch (\PDOException $e) {
            // Throw exception message aligning with output of database error
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * @param array $fields containing the current status of the streak and the customer it is for
     * @return Streak returns a created Streak object after a confirmed addition of an active streak in the DB
     *@throws DatabaseException|MissingValuesException|NoSuchCustomerException
     */
    public static function create(array $fields): StoredObject
    {

        // Presence check on all inputs
        if (!isset($fields["streakStatus"]) || !isset($fields["customerID"])) {

            // Produce error message if field exists with no content
            throw new MissingValuesException("Missing information required to create a streak");
        }

        // Creating new Streak object
        $streak = new Streak();
        // Updating attributes in line with input
        $streak->setStatus($fields["streakStatus"]);
        $streak->setStartDate(new DateTimeImmutable("now"));
        $streak->setEndDate(null);
        $streak->setCustomerID($fields["customerID"]);

        // Creating parameterised SQL command
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO streak (streakStatus, startDate, endDate, customerID) 
            VALUES (:streakStatus, :startDate, :endDate, :customerID);");

        // Try-catch block for handling potential database exceptions
        try {
            // Checking if endDate is set or not
            if ($streak->getEndDate() == null) {
                $appliedEndDate = null;
            } else{
                $appliedEndDate = $streak->getEndDate()->format("Y-m-d H:i:s");
            }

            // Execute SQL command, establishing values of parameterised fields
            $stmt->execute([":streakStatus" => $streak->getStatus()->value, ":startDate" => $streak->getStartDate()->format("Y-m-d H:i:s"), ":endDate" => $appliedEndDate, ":customerID" => $streak->getCustomerID()]);
        } catch (\PDOException $e) {
            // Throw exception message aligning with output of database error
            throw new DatabaseException($e->getMessage());
        }

        //TODO: Look into behaviour of lastInsertId() in terms of concurrency problems

        // Get query ID of the last record added to the database (i.e., the one just created)
        $lastId = DatabaseHandler::getPDO()->lastInsertId();
        // Add ID to Streak object
        $streak->id = $lastId;


        // Return Streak object as output once the database is successfully updated
        return $streak;
    }

    /**
     * @param int $id the ID of the Streak that is to be loaded
     * @return Streak object with given ID
     * @throws DatabaseException|NoSuchCustomerException|Exception
     */
    public static function load(int $id): StoredObject
    {
        // Forming and executing SQL query to retrieve streak data
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM streak WHERE streakID = :id;");
        $stmt->execute([":id" => $id]);

        // Fetching query results
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Throwing exception if streak with such ID doesn't exist
        if ($result === false) {
            throw new DatabaseException("No streak found with ID $id");
        }

        // Constructing new Streak object
        $streak = new Streak();
        $streak->id = $result["streakID"];
        $streak->setStatus(StreakStatus::from($result["streakStatus"]));
        $streak->setStartDate(new DateTimeImmutable($result["startDate"]));
        $streak->setCustomerID($result["customerID"]);

        // Assigning end date depending on retrieved value
        if ($result["endDate"] == null) {
            $streak->setEndDate(null);
        } else {
            $streak->setEndDate(new DateTimeImmutable($result["endDate"]));
        }

        return $streak;
    }

    /**
     * @param int $id ID to check
     * @return bool true, if such a streak exists. Otherwise, false.
     */
    public static function existsWithID(int $id): bool
    {
        // Preparing parameterised statement and executing
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM streak WHERE streakID = :streakID;");
        $stmt->execute([":streakID" => $id]);

        // Get result and return true/false depending
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return !($result === false);
    }

    /**
     * @param int $id of the streak to delete
     * @return void
     * @throws DatabaseException
     */
    public static function delete(int $id): void
    {
        // SQL command for streak deletion
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM streak WHERE streakID = :streakID;");

        // Try-catch for handling database exception
        try {
            $stmt->execute([":streakID" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    // Getters
    public function getID(): int{
        return $this->id;
    }

    public function getStatus(): StreakStatus {
        return $this->status;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getCustomerID(): int {
        return $this->customerID;
    }

    // Setters
    public function setStatus(StreakStatus $status): void {
        $this->status = $status;
    }

    public function setStartDate(DateTimeImmutable $startDate): void {
        $this->startDate = $startDate;
    }

    public function setEndDate(?DateTimeImmutable $endDate): void {
        $this->endDate = $endDate;
    }

    /**
     * @throws NoSuchCustomerException
     */
    public function setCustomerID(int $customerID): void {
        // Check customer with such ID exists
        if (!Customer::existsWithID($customerID)) {
            throw new NoSuchCustomerException("Can't find customer with ID $customerID to set streak to");
        }

        // Set if valid
        $this->customerID = $customerID;
    }
}
