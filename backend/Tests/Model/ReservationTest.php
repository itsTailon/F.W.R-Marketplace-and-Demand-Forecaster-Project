<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\Customer;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchReservationException;
use TTE\App\Model\Reservation;
use TTE\App\Model\ReservationStatus;
use TTE\App\Model\Seller;

class ReservationTest extends TestCase
{
    /**
     * Deletes all data from the tables: account, seller, customer and reservation.
     * Will return true if successful and false if not
     *
     * @return bool
     */
    private function emptyTables() : bool {
        try {
            $stmt = DatabaseHandler::getPDO()->prepare("SET FOREIGN_KEY_CHECKS = 0;");
            $stmt->execute();

            $stmt3 = DatabaseHandler::getPDO()->prepare("TRUNCATE account;");
            $stmt3->execute();

            $stmt1 = DatabaseHandler::getPDO()->prepare("TRUNCATE seller");
            $stmt1->execute();

            $stmt2 = DatabaseHandler::getPDO()->prepare("TRUNCATE customer");
            $stmt2->execute();

            $stmt4 = DatabaseHandler::getPDO()->prepare("TRUNCATE reservation");
            $stmt4->execute();

            $stmt5 = DatabaseHandler::getPDO()->prepare("TRUNCATE bundle");
            $stmt5->execute();

            $stmt = DatabaseHandler::getPDO()->prepare("SET FOREIGN_KEY_CHECKS = 0;");
            $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
        return true;
    }

    /**
     * Test the updating of reservations
     *
     * @return void
     *
     * @throws MissingValuesException
     * @throws \TTE\App\Model\DatabaseException
     * @throws \TTE\App\Model\NoSuchCustomerException
     * @throws \TTE\App\Model\NoSuchSellerException
     */
    public function testUpdateReservation(){
        // Ensure all used tables are fresh
        self::emptyTables();

        // Create customer to get customer ID to create reservation
        $purchaser = Customer::create([
            'email' => 'tEmail@email.com',
            'password' =>  'password123',
            'username' => 'egUsername'
        ]);

        // Create seller to get a seller ID to create a bundle
        $seller = Seller::create([
            'email' => 'test@test.com',
            'password' => 'password',
            'name' => 'sampleShop',
            'address' => '2 Example Avenue',
        ]);

        // Create bundle for the reservation to reference
        $bundle = Bundle::create([
            'bundleStatus' => BundleStatus::Available,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        // Create test reservation
        $reservation = Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle->getID(),
            'status' => ReservationStatus::Active,
            'claimCode' => 'abcdabcdabcdabcd'
        ]);

        // Make a copy of the reservation before changes are made
        $preChangesReservation = clone $reservation;

        // Make a different bundle
        $bundle2 = Bundle::create([
            'bundleStatus' => BundleStatus::Available,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        // Make a different customer
        $purchaser2 = Customer::create([
            'email' => 'test@email.com',
            'password' =>  'password123',
            'username' => 'egUsername'
        ]);

        // Make changes to the reservation object
        $reservation->setBundleID($bundle2->getID());
        $reservation->setPurchaserID($purchaser2->getUserID());
        $reservation->setStatus(ReservationStatus::Completed);
        $reservation->setClaimCode('efghefghefghefgh');

        // Attempt to update the reservation with the new details
        try {
            $reservation->update();
        } catch (\Exception $e) {
            //  Clean up and fail the test
            self::emptyTables();
            $this->fail($e->getMessage());
        }

        $postChangesReservation = $reservation->load($reservation->getID());

        // Check if any values have not been changes when they should have
        self::assertFalse($postChangesReservation == $preChangesReservation);
        self::assertTrue($preChangesReservation->getID() == $postChangesReservation->getID());
        self::assertFalse($postChangesReservation->getBundleID() == $preChangesReservation->getBundleID());
        self::assertFalse($postChangesReservation->getPurchaserID() == $preChangesReservation->getPurchaserID());
        self::assertFalse($postChangesReservation->getStatus() == $preChangesReservation->getStatus());
        self::assertFalse($postChangesReservation->getClaimCode() == $preChangesReservation->getClaimCode());

        // cleans up all tables
        self::emptyTables();
    }

    /**
     * Test creation of reservations
     *
     * @return void
     *
     * @throws MissingValuesException
     * @throws \TTE\App\Model\DatabaseException
     * @throws \TTE\App\Model\NoSuchCustomerException
     * @throws \TTE\App\Model\NoSuchSellerException
     */
    public function testCreateReservation(){
        /*
         * Test:
         * - MissingValuesException is thrown when information is missing
         * - A record is made in the database if no error
         * - The record has the right information
         * - Returned reservation object information matches with database record
         */

        // Ensure all used tables are fresh
        self::emptyTables();

        // Create customer to get customer ID to create reservation
        $purchaser = Customer::create([
            'email' => 'tEmail@email.com',
            'password' =>  'password123',
            'username' => 'egUsername'
        ]);

        // Create seller to get a seller ID to create a bundle
        $seller = Seller::create([
            'email' => 'test@test.com',
            'password' => 'password',
            'name' => 'sampleShop',
            'address' => '2 Example Avenue',
        ]);

        // Create bundle for the reservation to reference
        $bundle = Bundle::create([
            'bundleStatus' => BundleStatus::Available,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        // Test creating reservation with missing values
        $thrown = false;
        try {
            Reservation::create([
                'purchaserID' => $purchaser->getUserID(),
                'bundleID' => $bundle->getID(),
                'claimCode' => 'abcdabcdabcdabcd'
            ]);
        } catch (MissingValuesException $e) {
            $thrown  = true;
        }
        self::assertTrue($thrown);

        // Test creating reservation with no missing vales
        try {
            $reservation = Reservation::create([
                'purchaserID' => $purchaser->getUserID(),
                'bundleID' => $bundle->getID(),
                'status' => ReservationStatus::Active,
                'claimCode' => 'abcdabcdabcdabcd'
            ]);
        } catch (\Exception $e) {
            // Clean up and fail the test
            self::emptyTables();

            self::fail($e->getMessage());
        }

        // Test that making a reservation without a claim code will cause a claim code to be generated
        try {
            $reservationClaimCodeTest = Reservation::create([
                'purchaserID' => $purchaser->getUserID(),
                'bundleID' => $bundle->getID(),
                'status' => ReservationStatus::Active,
            ]);
            $claimCode = $reservationClaimCodeTest->getClaimCode();
            self::assertTrue(isset($claimCode));
        } catch (DatabaseException $e) {
            // If it is not there, fail test and clean up
            self::emptyTables();

            self::fail($e->getMessage());
        }

        // Try load reservation from database
        try {
            $loadedReservation = Reservation::load($reservation->getID());
        } catch (NoSuchReservationException $e) {
            // If it is not there, fail test and clean up
            self::emptyTables();

            self::fail($e->getMessage());
        }

        // Check database record data matches reservation information
        self::assertTrue($loadedReservation->getBundleID() == $bundle->getID());
        self::assertTrue($loadedReservation->getPurchaserID() == $purchaser->getUserID());
        self::assertTrue($loadedReservation->getStatus() == ReservationStatus::Active);
        self::assertTrue($loadedReservation->getClaimCode() == 'abcdabcdabcdabcd');

        // Check returned reservation object data matches database data
        self::assertTrue($reservation->getID() == $loadedReservation->getID());
        self::assertTrue($reservation->getBundleID() == $loadedReservation->getBundleID());
        self::assertTrue($reservation->getPurchaserID() == $loadedReservation->getPurchaserID());
        self::assertTrue($reservation->getStatus() == $loadedReservation->getStatus());
        self::assertTrue($reservation->getClaimCode() == $loadedReservation->getClaimCode());

        // Clean up
        self::emptyTables();
    }

    /**
     * Tests loading of reservations
     *
     * @return void
     *
     * @throws MissingValuesException
     * @throws NoSuchReservationException
     * @throws \TTE\App\Model\DatabaseException
     * @throws \TTE\App\Model\NoSuchCustomerException
     * @throws \TTE\App\Model\NoSuchSellerException
     */
    public function testLoadReservation(){
        /*
         * Tests:
         * - check if loaded reservation equals the original reservation
         * - check NoSuchReservationException is thrown when loading non-existing reservation
         */

        // Ensure all used tables are fresh
        self::emptyTables();

        // Create customer to get customer ID to create reservation
        $purchaser = Customer::create([
            'email' => 'tEmail@email.com',
            'password' =>  'password123',
            'username' => 'egUsername'
        ]);

        // Create seller to get a seller ID to create a bundle
        $seller = Seller::create([
            'email' => 'test@test.com',
            'password' => 'password',
            'name' => 'sampleShop',
            'address' => '2 Example Avenue',
        ]);

        // Create bundle for the reservation to reference
        $bundle = Bundle::create([
            'bundleStatus' => BundleStatus::Available,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        $reservation = Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle->getID(),
            'status' => ReservationStatus::Active,
            'claimCode' => 'abcdabcdabcdabcd'
        ]);

        // Check if loaded reservation matches the original reservation
        self::assertEquals($reservation, Reservation::load($reservation->getID()));

        // Attempt to load reservation that does not exist
        $thrown = false;
        try{
            $reservation = Reservation::load(-1);
        } catch (NoSuchReservationException $e) {
            $thrown  = true;
        }
        self::assertTrue($thrown);

        // clean up
        self::emptyTables();
    }

    /**
     * Tests existsWithID
     *
     * @return void
     *
     * @throws MissingValuesException
     * @throws \TTE\App\Model\DatabaseException
     * @throws \TTE\App\Model\NoSuchCustomerException
     * @throws \TTE\App\Model\NoSuchSellerException
     */
    public function testExistsWithID() {
        /*
         * Tests:
         * - Passing ID of a reservation that exists returns true
         * - Passing ID of reservation that does not exist returns flase
         */

        // Ensure all used tables are fresh
        self::emptyTables();

        // Create customer to get customer ID to create reservation
        $purchaser = Customer::create([
            'email' => 'tEmail@email.com',
            'password' =>  'password123',
            'username' => 'egUsername'
        ]);

        // Create seller to get a seller ID to create a bundle
        $seller = Seller::create([
            'email' => 'test@test.com',
            'password' => 'password',
            'name' => 'sampleShop',
            'address' => '2 Example Avenue',
        ]);

        // Create bundle for the reservation to reference
        $bundle = Bundle::create([
            'bundleStatus' => BundleStatus::Available,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        $reservation = Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle->getID(),
            'status' => ReservationStatus::Active,
            'claimCode' => 'abcdabcdabcdabcd'
        ]);

        // Test created reservation exists
        self::assertTrue(Reservation::existsWithID($reservation->getID()));

        // Test that reservation that does not exist does not exist
        self::assertFalse(Reservation::existsWithID(-1));

        // Clean up
        self::emptyTables();
    }

    /**
     * Test the deleting of reservations
     *
     * @return void
     *
     * @throws MissingValuesException
     * @throws NoSuchReservationException
     * @throws \TTE\App\Model\DatabaseException
     * @throws \TTE\App\Model\NoSuchCustomerException
     * @throws \TTE\App\Model\NoSuchSellerException
     */
    public function testDelete() {
        /*
         * Tests:
         * - Deleting reservation that does not exist will throw NoSuchReservationException
         * - Deleting a reservation removes it from the database
         */

        // Ensure all used tables are fresh
        self::emptyTables();

        // Create customer to get customer ID to create reservation
        $purchaser = Customer::create([
            'email' => 'tEmail@email.com',
            'password' =>  'password123',
            'username' => 'egUsername'
        ]);

        // Create seller to get a seller ID to create a bundle
        $seller = Seller::create([
            'email' => 'test@test.com',
            'password' => 'password',
            'name' => 'sampleShop',
            'address' => '2 Example Avenue',
        ]);

        // Create bundle for the reservation to reference
        $bundle = Bundle::create([
            'bundleStatus' => BundleStatus::Available,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        $reservation = Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle->getID(),
            'status' => ReservationStatus::Active,
            'claimCode' => 'abcdabcdabcdabcd'
        ]);

        // Check that when deleting a reservation that does not exist, NoSuchReservationException is thrown
        $thrown = false;
        try {
            Reservation::delete(-1);
        } catch (NoSuchReservationException $e) {
            $thrown  = true;
        }
        self::assertTrue($thrown);

        // Check that calling the delete method deletes a record from the database
        self::assertTrue(Reservation::existsWithID($reservation->getID()));
        try{
            Reservation::delete($reservation->getID());
        } catch (\PDOException $e) {
            // If it is not there, fail test and clean up
            self::emptyTables();

            self::fail($e->getMessage());
        }

        self::assertFalse(Reservation::existsWithID($reservation->getID()));

        // Clean up
        self::emptyTables();
    }

    public function testGenerateClaimCode() {
        /*
         * tests:
         * - Check that claim code is of correct length
         * - Check that same parameters will generate the same claim code
         */

        // Generate 3 claim codes
        $claimCode1 = Reservation::generateClaimCode(0, 0, "bundleTitle");
        $claimCode2 = Reservation::generateClaimCode(1, 6, "bundle with title");
        $claimCode3 = Reservation::generateClaimCode(50, 1, "pastry bundle");

        // Check lengths of claim codes
        self::assertTrue(strlen($claimCode1) == Reservation::LENGTH);
        self::assertTrue(strlen($claimCode2) == Reservation::LENGTH);
        self::assertTrue(strlen($claimCode3) == Reservation::LENGTH);

        // Generate new claim code using same parameters as claim code 1
        $claimCode4 = Reservation::generateClaimCode(0, 0, "bundleTitle");

        // Check if claim code 1 = claim code 2
        self::assertEquals($claimCode1, $claimCode4);
    }
}