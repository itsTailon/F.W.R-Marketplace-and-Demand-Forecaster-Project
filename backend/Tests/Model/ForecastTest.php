<?php

namespace TTE\App\Tests\Model;

use DateInterval;
use PHPUnit\Framework\TestCase;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\Customer;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\Forecast;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\NoSuchSellerException;
use TTE\App\Model\Reservation;
use TTE\App\Model\ReservationStatus;
use TTE\App\Model\Seller;

class ForecastTest extends TestCase
{
    /**
     * @throws DatabaseException
     * @throws NoSuchCustomerException
     * @throws \DateInvalidOperationException
     * @throws MissingValuesException
     * @throws NoSuchSellerException
     */
    public function testMovingAverage(){
        self::cleanTables();

        $day1 = "2026-03-12 14:56:39.599705";
        $day2 = "2026-03-4 15:56:39.599705";

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
        $bundle1 = Bundle::create([
            'bundleStatus' => BundleStatus::Collected,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        $bundle2 = Bundle::create([
            'bundleStatus' => BundleStatus::Cancelled,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::NoShow,
            'claimCode' => 'abcdabcdabcdaaad',
            'reservationDate' => $day2
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::NoShow,
            'claimCode' => 'abcdabcdabcdabce',
            'reservationDate' => $day2
        ]);

        // Create test reservation
        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdabcd',
            'reservationDate' => $day1
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdaaaf',
            'reservationDate' => $day1
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdabcg',
            'reservationDate' => $day1
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdaaah',
            'reservationDate' => $day1
        ]);

        $reservations = Reservation::getAllReservationsForUser($seller->getUserID(), 'seller');

        // (string $filterCategory, string $startTime, string $endTime, int $minDiscount, int $maxDiscount,$filterWeatherConditions, $reservations)
        $forecast = Forecast::movingAverage('any', 0, 24, 0, 100, 'any', $reservations);

        self::assertEquals($forecast["AvgThursdayCollected"], 2);
        self::assertEquals($forecast["AvgWednesdayNoShow"], 1);

        self::cleanTables();
    }

    /**
     * @throws DatabaseException
     * @throws NoSuchCustomerException
     * @throws \DateInvalidOperationException
     * @throws MissingValuesException
     * @throws NoSuchSellerException
     */
    public function testForecastNextWeekSeasonal(){
        self::cleanTables();

        Forecast::getLastWeeksReservations(11);

        $today = new \DateTime();
        $lastWeekStart = new \DateTime();
        $lastWeekStart->sub(DateInterval::createFromDateString('+' . (getdate($lastWeekStart->getTimestamp())['wday'] + 6) . ' days'));

        $today = date_format($today, 'Y/m/d H:i:s');
        $lastWeekStart = date_format($lastWeekStart, 'Y/m/d H:i:s');

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
        $bundle1 = Bundle::create([
            'bundleStatus' => BundleStatus::Collected,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        $bundle2 = Bundle::create([
            'bundleStatus' => BundleStatus::Cancelled,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::NoShow,
            'claimCode' => 'abcdabcdabcdaaad',
            'reservationDate' => $today
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::NoShow,
            'claimCode' => 'abcdabcdabcdabce',
            'reservationDate' => $today
        ]);

        // Create test reservation
        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdabcd',
            'reservationDate' => $today
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdaaaf',
            'reservationDate' => $lastWeekStart
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdabcg',
            'reservationDate' => $lastWeekStart
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdaaah',
            'reservationDate' => $lastWeekStart
        ]);

        $data = Forecast::getLastWeeksReservations($seller->getUserID());
        $weeklyForecast = Forecast::forecastNextWeekSeasonal('any', 0, 24, 0, 100, 'any', $data);

        self::assertEquals($weeklyForecast['neededBundlesMonday'], 3);

        self::assertEquals($weeklyForecast['probabilityCollectedMonday'], 1);
    }

    public function testGetDataFormatWithNoShow() {
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
        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle->getID(),
            'status' => ReservationStatus::Active,
            'claimCode' => 'abcdabcdabcdabcd'
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle->getID(),
            'status' => ReservationStatus::Active,
            'claimCode' => 'abcdabcdabcdaaad'
        ]);

        $fortnite = array(
            'baller' => 2
        );

        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM reservation");

        // Attempt to execute the statement
        try{
            $stmt->execute();
            $reservations1 = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e){
            throw new DatabaseException($e->getMessage());
        }

        $fortnite['feet'] = 12;

        $day = idate($reservations1[0]['reservationDate']);

        $fitnit = $fortnite;

        Forecast::getLastWeeksReservations(1);
    }

    public static function cleanTables(){
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

        $stmt = DatabaseHandler::getPDO()->prepare("SET FOREIGN_KEY_CHECKS = 1;");
        $stmt->execute();
    }
}