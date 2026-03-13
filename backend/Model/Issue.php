<?php

namespace TTE\App\Model;

use TTE\App\Model\StoredObject;

class Issue extends StoredObject {

    private int $id;

    private int $customerID;

    private int $bundleID;

    private \DateTimeImmutable $createdAt;

    private ?\DateTimeImmutable $resolvedAt = null;

    private string $description;

    private string $sellerResponse;

    private IssueStatus $status;

    /**
     * Updates the issue's database record to match this object — i.e. saves (to the database) changes made to the object.
     *
     * @throws MissingValuesException if the object does not have all required fields set.
     * @throws DatabaseException if the database record could not be updated.
     */
    public function update(): void {
        // Ensure that all required object fields are set (note that $this->resolvedAt can be null, so is not checked)
        if (!isset($this->id) || !isset($this->customerID) || !isset($this->bundleID) || !isset($this->createdAt) || !isset($this->description) || !isset($this->sellerResponse) || !isset($this->status)) {
            throw new MissingValuesException("Cannot perform issue update operation (not all required object fields are set).");
        }

        // Prepare parameterised statement.
        // Note: createdAt, bundleID and customerID are not updated in the database, as they are intended to be immutable.
        $stmt = DatabaseHandler::getPDO()->prepare("UPDATE issue SET resolvedAt=:resolvedAt, issueDescription=:issueDescription, sellerResponse=:sellerResponse, issueStatus=:issueStatus WHERE issueID=:issueID;");

        // Attempt to update database record.
        try {
            $stmt->execute([
                "resolvedAt"        => $this->resolvedAt?->format("Y-m-d H:i:s"), // If not null (?->), convert to MySQL-compatible date/time string format ("YYYY-MM-DD hh:mm:ss").
                "issueDescription"  => $this->description,
                "sellerResponse"    => $this->sellerResponse,
                "issueStatus"       => $this->status->value,
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Creates an issue record in the database.
     *
     * Accepted fields (all are mandatory):
     *  - customerID:       int
     *  - bundleID:         int
     *  - description:      string
     *  - sellerResponse:   string
     *
     * Note: 'status' and 'created at' fields are populated automatically.
     *
     * @throws MissingValuesException if not all required fields are provided.
     * @throws NoSuchBundleException if the bundle ID given does not correspond to an existing bundle.
     * @throws NoSuchCustomerException if the customer ID given does not correspond to an existing customer.
     * @throws DatabaseException if the issue record cannot be created.
     */
    public static function create(array $fields): Issue {
        // Ensure that all required fields were passed
        if (!isset($fields["customerID"]) || !isset($fields["bundleID"]) || !isset($fields["description"]) || !isset($fields["sellerResponse"])) {
            throw new MissingValuesException("Missing required values in 'fields' parameter.");
        }

        // Ensure that bundle ID passed is valid
        if (!Bundle::existsWithID($fields["bundleID"])) {
            throw new NoSuchBundleException("Cannot create issue linked to non-existent bundle.");
        }

        // Ensure that customer ID passed is valid
        if (!Customer::existsWithID($fields["customerID"])) {
            throw new NoSuchCustomerException("Cannot create issue linked to non-existent customer.");
        }

        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO issue (customerID, bundleID, issueDescription, sellerResponse, issueStatus) VALUES (:customerID, :bundleID, :issueDescription, :sellerResponse, :issueStatus);");

        // Attempt to create database record
        try {
            $stmt->execute([
                "customerID"        => $fields["customerID"],
                "bundleID"          => $fields["bundleID"],
                "issueDescription"  => $fields["description"],
                "sellerResponse"    => "", // A new issue will not have a seller response yet, but an empty string is safer than NULL.
                "issueStatus"       => IssueStatus::Ongoing->value,
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Attempt to return Issue object corresponding to the new database record
        try {
            // Get query ID of the last record added to the database (i.e., the one just created)
            $id = DatabaseHandler::getPDO()->lastInsertId();

            // Return Issue object
            return self::load(intval($id));

        } catch (\PDOException $e) {
            throw new Exception("Issue record created, but object could be returned.");
        }
    }

    /**
     * @inheritDoc
     */
    public static function load(int $id): Issue {
        // Ensure that an issue exists with the given ID
        if (!self::existsWithID($id)) {
            throw new NoSuchIssueException("Cannot load non-existent issue (no issue exists with id $id).");
        }

        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM issue WHERE issueID=:issueID;");

        // Attempt to retrieve database record
        try {
            $stmt->execute(["issueID" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Get results
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Construct object
        $issue = new Issue();
        $issue->id = $row["issueID"];
        $issue->customerID = $row["customerID"];
        $issue->bundleID = $row["bundleID"];
        $issue->createdAt = new \DateTimeImmutable($row["createdAt"]);
        $issue->resolvedAt = $row["resolvedAt"] === null ? null : new \DateTimeImmutable($row["resolvedAt"]);
        $issue->description = $row["issueDescription"];
        $issue->sellerResponse = $row["sellerResponse"];
        $issue->status = IssueStatus::from($row["issueStatus"]);

        return $issue;
    }

    /**
     * Checks if an issue exists with a given ID.
     */
    public static function existsWithID(int $id): bool {
        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT issueID FROM issue WHERE issueID=:issueID;");

        // Execute statement with given ID
        $stmt->execute(["issueID" => $id]);

        // Get result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return true if an issue exists with the given ID
        return !($row === false);
    }

    /**
     * Removes an issue from the database.
     *
     * @throws NoSuchIssueException if no issue exists with the given ID
     * @throws DatabaseException if the issue fails to be deleted.
     *
     */
    public static function delete(int $id): void {
        // Ensure that an issue exists with the given ID
        if (!self::existsWithID($id)) {
            throw new NoSuchIssueException("Cannot delete non-existent issue (no issue exists with id $id).");
        }

        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM issue WHERE issueID=:issueID;");

        try {
            $stmt->execute(["issueID" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    public function getID(): int {
        return $this->id;
    }

    public function getCustomerID(): int {
        return $this->customerID;
    }

    /**
     * Returns a Customer object relating to this issue's parent customer.
     *
     * @throws DatabaseException if the customer record could not be loaded.
     */
    public function getCustomer(): Customer {
        return Customer::load($this->customerID);
    }

    public function getBundleID(): int {
        return $this->bundleID;
    }

    /**
     * Returns a Bundle object relating to this issue's associated bundle.
     *
     * @throws DatabaseException if the bundle could not be loaded.
     */
    public function getBundle(): Bundle {
        return Bundle::load($this->bundleID);
    }

    public function getCreationDate(): \DateTimeImmutable {
        return $this->createdAt;
    }

    public function getResolvedDate(): ?\DateTimeImmutable {
        return $this->resolvedAt;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function setDescription(string $description): void {
        $this->description = $description;
    }

    public function getSellerResponse(): string {
        return $this->sellerResponse;
    }

    /**
     * Marks the issue as resolved, setting the 'resolved at' time and seller response.
     *
     * Note: this method does not update the database automatically, so the 'update' method must still be called to commit changes.
     *
     * @throws IssueAlreadyResolvedException If the issue has already been resolved.
     */
    public function markResolved(\DateTimeImmutable $resolvedAt, string $sellerResponse): void {
        // Ensure that the issue has not already been resolved.
        if ($this->status == IssueStatus::Resolved) {
            throw new IssueAlreadyResolvedException("Cannot mark issue #$this->id as resolved, as it has already been resolved.");
        }

        $this->resolvedAt = $resolvedAt;
        $this->sellerResponse = $sellerResponse;
        $this->status = IssueStatus::Resolved;
    }

    public function getStatus(): IssueStatus {
        return $this->status;
    }

}