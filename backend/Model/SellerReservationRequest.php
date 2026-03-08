<?php

namespace TTE\App\Model;

use function PHPUnit\Framework\throwException;

class SellerReservationRequest
{


    public function update(): void {

    }

    public static function load(int $id): SellerReservationRequest {
        return new SellerReservationRequest();
    }

    public static function existsWithID(int $id): bool {
        // Prepare SQL statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM seller_registration_request WHERE requestID=:id;");

        // Execute the statement
        $stmt->execute(["id" => $id]);

        // Fetch the result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return true if a request with the given id exists, and false if not
        return !($row === false);
    }

    public function grant(int $id): void {
        $sellerRegReq = SellerReservationRequest::load($id);

        if ($sellerRegReq->getStatus() != SellerRegestrationRequestStatus::Pending) {
            Seller::create([
                "email" => $sellerRegReq->getEmail(),
                "password" => $sellerRegReq->getPassword(),
                "name" => $sellerRegReq->getName(),
                "address" => $sellerRegReq->getAddress(),
            ]);

            $sellerRegReq->setStatus(SellerRegestrationRequestStatus::Closed);
            $sellerRegReq->update();
        } else {
            throw new InvalidRegestrationRequest("Request already handled");
        }
    }

    public function deny(int $id): void {
        $sellerRegReq = SellerReservationRequest::load($id);

        if ($sellerRegReq->getStatus() != SellerRegestrationRequestStatus::Pending) {
            $sellerRegReq->setStatus(SellerRegestrationRequestStatus::Closed);
            $sellerRegReq->update();
        } else {
            throw new InvalidRegestrationRequest("Request already handled");
        }
    }

    private function getEmail()
    {
    }

    private function getPassword()
    {
    }

    private function getName()
    {
    }

    private function getAddress()
    {
    }
}