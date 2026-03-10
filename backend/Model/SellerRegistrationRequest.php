<?php

namespace TTE\App\Model;

use http\Exception\UnexpectedValueException;
use InvalidArgumentException;
use TTE\App\Model\StoredObject;

class SellerRegistrationRequest extends StoredObject {

    private int $id;

    private string $passwordHash;

    private SellerRegistrationRequestStatus $status;

    private string $sellerName;
    const MAX_LEN_SELLER_NAME = 128;

    private string $sellerAddress;
    const MAX_LEN_SELLER_ADDRESS = 256;

    private string $sellerEmail;
    const MAX_LEN_SELLER_EMAIL = 128;

    private string $details;

    const MAX_LEN_PASSWORD_HASH = 128;


    /**
     * Updates a seller registration request's corresponding database record.
     *
     * @throws NoSuchSellerRegistrationRequestException if the seller registration request represented by the object no longer exists
     * @throws DatabaseException if the database record fails to update
     * @throws MissingValuesException if one or more of the object's attributes are not set
     */
    public function update(): void {
        // Ensure that all object attributes are set
        if (!isset($this->id) || !isset($this->status) || !isset($this->sellerName) ||!isset($this->sellerAddress) || !isset($this->sellerEmail) || !isset($this->details)) {
            throw new MissingValuesException("Missing values required to update seller registration request.");
        }

        // Ensure that record still exists
        if (!self::existsWithID($this->id)) {
            throw new NoSuchSellerRegistrationRequestException("No seller registration request exists with ID $this->id.");
        }

        // Update database record
        $stmt = DatabaseHandler::getPDO()->prepare("UPDATE seller_registration_request SET status=:status, sellerName=:sellerName, sellerAddress=:sellerAddress, sellerEmail=:sellerEmail, details=:details WHERE id=:id;");
        try {
            $stmt->execute([
                "id"            => $this->id,
                "sellerName"    => $this->sellerName,
                "sellerAddress" => $this->sellerAddress,
                "sellerEmail"   => $this->sellerEmail,
                "details"       => $this->details,
                "status"        => $this->status->value,
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Create a new seller registration request.
     *
     * Required fields:
     *  - sellerName    (string)
     *  - sellerAddress (string)
     *  - sellerEmail   (string)
     *  - password      (string)
     *  - details       (string)
     *
     * Note that requests are created with a status of 'Pending'.
     *
     * @param array $fields an array of fields of the request
     *
     * @throws DatabaseException
     */
    public static function create(array $fields): StoredObject {
        // List required fields and max lengths (null if no max. length is imposed)
        $requiredFields = [
            "sellerName"    => self::MAX_LEN_SELLER_NAME,
            "sellerAddress" => self::MAX_LEN_SELLER_ADDRESS,
            "sellerEmail"   => self::MAX_LEN_SELLER_EMAIL,
            "password"      => self::MAX_LEN_PASSWORD_HASH,
            "details"       => null,
        ];

        // Ensure that all required fields were passed
        if (count(array_intersect_key($fields, $requiredFields)) != count($requiredFields)) {
            throw new \ValueError("Missing required field(s).");
        }

        // Ensure that field values passed are valid
        foreach ($requiredFields as $fieldName => $maxLen) {
            // Ensure that types are correct
            if (!is_string($fields[$fieldName])) {
                throw new \ValueError("Value of $fieldName should be of type string.");
            }

            // Ensure that values have acceptable lengths
            if ($maxLen != null && strlen($fields[$fieldName]) > $maxLen) {
                throw new \ValueError("Value of $fieldName should not exceed $maxLen characters.");
            }
        }

        $passwordHash = password_hash($fields['password'], PASSWORD_ARGON2ID);

        // Insert database record
        try {
            $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO seller_registration_request (sellerName, sellerAddress, sellerEmail, passwordHash, details, status) VALUES (:sellerName, :sellerAddress, :sellerEmail, :passwordHash, :details, :status);");
            $stmt->execute([
                "sellerName"    => $fields["sellerName"],
                "sellerAddress" => $fields["sellerAddress"],
                "sellerEmail"   => $fields["sellerEmail"],
                "passwordHash"  => $passwordHash,
                "details"       => $fields["details"],
                "status"        => SellerRegistrationRequestStatus::Pending->value,
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Get ID
        $stmt2 = DatabaseHandler::getPDO()->prepare("SELECT id FROM seller_registration_request WHERE sellerEmail=:sellerEmail;");
        $stmt2->execute(["sellerEmail" => $fields["sellerEmail"]]);
        $id = $stmt2->fetch(\PDO::FETCH_ASSOC)["id"];

        // Construct object
        $registrationRequest = new SellerRegistrationRequest();
        $registrationRequest->id = $id;
        $registrationRequest->status = SellerRegistrationRequestStatus::Pending;
        $registrationRequest->sellerName = $fields["sellerName"];
        $registrationRequest->sellerEmail = $fields["sellerEmail"];
        $registrationRequest->sellerAddress = $fields["sellerAddress"];
        $registrationRequest->details = $fields["details"];
        $registrationRequest->passwordHash = $passwordHash;

        return $registrationRequest;
    }

    /**
     * @inheritDoc
     */
    public static function load(int $id): StoredObject {
        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT sellerName, sellerEmail, sellerAddress, status, details FROM seller_registration_request WHERE id=:id;");

        // Execute statement with given ID
        $stmt->execute(["id" => $id]);

        // Get result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Throw exception if no account was found with the given ID
        if ($row === false) {
            throw new DatabaseException("No seller registration request found with ID $id.");
        }

        // Construct object
        $registrationRequest = new SellerRegistrationRequest();
        $registrationRequest->id = $id;
        $registrationRequest->status = SellerRegistrationRequestStatus::from($row["status"]);
        $registrationRequest->sellerName = $row["sellerName"];
        $registrationRequest->sellerEmail = $row["sellerEmail"];
        $registrationRequest->sellerAddress = $row["sellerAddress"];
        $registrationRequest->details = $row["details"];
        $registrationRequest->passwordHash = $row["passwordHash"];

        return $registrationRequest;
    }

    /**
     * @inheritDoc
     */
    public static function existsWithNameAndAddress(string $name, string $address) {
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT id FROM seller_registration_request WHERE sellerName=:name AND sellerAddress=:address;");

        $stmt->execute(params: ["name" => $name, "address" => $address]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return !($row === false);
    }

    /**
     * @inheritDoc
     */
    public static function existsWithID(int $id): bool {
        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT id FROM seller_registration_request WHERE id=:id;");

        // Execute statement with given ID
        $stmt->execute(["id" => $id]);

        // Get result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return true if a seller registration request exists with the given ID
        return !($row === false);
    }

    /**
     * Deletes a seller registration request
     *
     * @param int $id ID of the seller registration request to delete
     * @throws NoSuchSellerRegistrationRequestException
     * @throws DatabaseException
     */
    public static function delete(int $id): void {
        // Check if seller registration request exists
        if (!self::existsWithID($id)) {
            throw new NoSuchSellerRegistrationRequestException("No seller registration request found with ID $id.");
        }

        // Prepare SQL deletion statement
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM seller_registration_request WHERE id=:id;");

        // Attempt to delete database record
        try {
            $stmt->execute(["id" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    public function setStatus(SellerRegistrationRequestStatus $status): void {
        $this->status = $status;
    }

    public function markClosed(): void {
        $this->status = SellerRegistrationRequestStatus::Closed;
    }

    public function setSellerName(string $sellerName): void {
        // Ensure length of new value complies with DB schema
        if (strlen($sellerName) > self::MAX_LEN_SELLER_NAME) {
            throw new \ValueError("Cannot set sellerName longer than " . self::MAX_LEN_SELLER_NAME . " characters");
        }

        $this->sellerName = $sellerName;
    }

    public function setSellerAddress(string $sellerAddress): void {
        // Ensure length of new value complies with DB schema
        if (strlen($sellerAddress) > self::MAX_LEN_SELLER_ADDRESS) {
            throw new \ValueError("Cannot set sellerAddress longer than " . self::MAX_LEN_SELLER_ADDRESS . " characters");
        }

        $this->sellerAddress = $sellerAddress;
    }

    public function setSellerEmail(string $sellerEmail): void {
        // Ensure length of new value complies with DB schema
        if (strlen($sellerEmail) > self::MAX_LEN_SELLER_EMAIL) {
            throw new \ValueError("Cannot set sellerEmail longer than " . self::MAX_LEN_SELLER_EMAIL . " characters");
        }

        $this->sellerEmail = $sellerEmail;
    }

    public function setDetails(string $details) {
        $this->details = $details;
    }

    public function getStatus(): SellerRegistrationRequestStatus {
        return $this->status;
    }

    public function getSellerName(): string {
        return $this->sellerName;
    }

    public function getSellerAddress(): string {
        return $this->sellerAddress;
    }

    public function getSellerEmail(): string {
        return $this->sellerEmail;
    }

    public function getDetails(): string {
        return $this->details;
    }

    public function getID(): int {
        return $this->id;
    }

    /**
     * Marks the request as approved and creates a Seller based on the request details
     *
     * @throws DatabaseException
     * @throws MissingValuesException
     * @throws NoSuchSellerRegistrationRequestException
     * @returns ?Seller The created Seller object, or null if the request was rejected
     */
    public function processRequest(bool $approved) : ?Seller {
        $this->setStatus(SellerRegistrationRequestStatus::Closed);
        $this->update();

        if ($approved) return Seller::create(["email" => $this->sellerEmail, "passwordHash" => $this->passwordHash, "name" => $this->sellerName, "address" => $this->sellerAddress]);
        return null;
    }

    /**
     * @throws DatabaseException
     */
    public function grant(): Seller {
        if ($this->getStatus() == SellerRegistrationRequestStatus::Pending) {
            return Seller::create([
                "email" => $this->getSellerEmail(),
                "password" => $this->passwordHash,
                "name" => $this->getSellerName(),
                "address" => $this->getSellerAddress(),
            ]);

            $this->setStatus(SellerRegistrationRequestStatus::Closed);
            $this->update();
        } else {
            throw new InvalidArgumentException("Request already handled");
        }
        throw new UnexpectedValueException("Invalid Status");
    }

    /**
     * @throws DatabaseException
     */
    public function deny(): void {
        if ($this->getStatus() == SellerRegistrationRequestStatus::Pending) {
            $this->setStatus(SellerRegistrationRequestStatus::Closed);
            $this->update();
        } else {
            throw new InvalidArgumentException("Request already handled");
        }
    }
}