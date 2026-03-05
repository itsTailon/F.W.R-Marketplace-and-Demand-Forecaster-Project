<?php

namespace TTE\App\Model;

use TTE\App\Model\StoredObject;

class SellerRegistrationRequest extends StoredObject {

    private int $id;

    private SellerRegistrationRequestStatus $status;

    private string $sellerName;

    private string $sellerAddress;

    private string $sellerEmail;

    private string $details;

    /**
     * @inheritDoc
     */
    public function update(): void {
        // TODO: Implement update() method.
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
     *  - status        (SellerRegistrationRequestStatus)
     *
     * @param $fields an array of fields of the request
     * @throws DatabaseException
     */
    public static function create(array $fields): StoredObject {
        $requiredFields = [
            "sellerName",
            "sellerAddress",
            "sellerEmail",
            "password",
            "details",
            "status",
        ];

        // Ensure that all required fields were passed
        if (count(array_intersect_key($fields, $requiredFields)) != count($requiredFields)) {
            throw new \ValueError("Missing required field(s).");
        }

        // Insert database record
        try {
            $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO seller_registration_request (sellerName, sellerAddress, sellerEmail, passwordHash, details, status) VALUES (:sellerName, :sellerAddress, :sellerEmail, :passwordHash, :details, :status);");
            $stmt->execute([
                "sellerName"    => $fields["sellerName"],
                "sellerAddress" => $fields["sellerAddress"],
                "sellerEmail"   => $fields["sellerEmail"],
                "passwordHash"  => password_hash($fields["password"], PASSWORD_ARGON2ID),
                "details"       => $fields["details"],
                "status"        => $fields["status"]->value,
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
        $registrationRequest->status = $fields["status"];
        $registrationRequest->sellerName = $fields["sellerName"];
        $registrationRequest->sellerEmail = $fields["sellerEmail"];
        $registrationRequest->sellerAddress = $fields["sellerAddress"];
        $registrationRequest->details = $fields["details"];

        return $registrationRequest;
    }

    /**
     * @inheritDoc
     */
    public static function load(int $id): StoredObject {
        // TODO: Implement load() method.
        return new SellerRegistrationRequest(); // TODO: remove placeholder return
    }

    /**
     * @inheritDoc
     */
    public static function existsWithID(int $id): bool {
        // TODO: Implement existsWithID() method.
        return false; // TODO: remove placeholder return
    }

    /**
     * @inheritDoc
     */
    public static function delete(int $id): void {
        // TODO: Implement delete() method.
    }
}