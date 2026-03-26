<?php

namespace TTE\App\Tests\Model;

use DateInterval;
use DateTimeImmutable;
use mysql_xdevapi\Exception;
use PHPUnit\Framework\TestCase;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\Category;
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

        $expDate = new DateTimeImmutable();

        // Create customer to get customer ID to create reservation
        try {
            $purchaser = Customer::create([
                'email' => 'tEmail@email.com',
                'password' => 'password123',
                'username' => 'egUsername'
            ]);
        } catch (DatabaseException $e) {
            $count = 0;
            $found = false;
            while (!$found) {
                if (Customer::existsWithID($count)) {
                    $purchaser = Customer::load($count);
                    $found = true;
                } else {
                    $count++;
                    if($count == 100){
                        $found = true;
                    }
                }
            }
        };


        // Create seller to get a seller ID to create a bundle
        $seller = Seller::create([
            'email' => 'test@test.com',
            'password' => 'password',
            'name' => 'sampleShop',
            'address' => '2 Example Avenue',
        ]);

        // Create bundle for the reservation to reference
        $bundle1 = Bundle::create([
            'bundleStatus' => BundleStatus::OffSale,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
            'expiryDate' => $expDate,
            'pickupWindow' => "14:00-15:00",
            'quantity' => 100
        ]);

        $bundle2 = Bundle::create([
            'bundleStatus' => BundleStatus::OffSale,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
            'expiryDate' => $expDate,
            'pickupWindow' => "12:00-13:00",
            'quantity' => 100
        ]);



        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::NoShow,
            'claimCode' => 'abcdabcdabcdaaad',
            'reservationDate' => $day2,
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

        $forecast = Forecast::movingAverage('any', 12, 12, 0, 100, 'any', $reservations);

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

        $today = new \DateTime();
        $lastWeekStart = new \DateTime();
        $lastWeekStart->sub(DateInterval::createFromDateString('+' . (getdate($lastWeekStart->getTimestamp())['wday'] + 6) . ' days'));

        $today = date_format($today, 'Y/m/d H:i:s');
        $lastWeekStart = date_format($lastWeekStart, 'Y/m/d H:i:s');

        $expDate = new DateTimeImmutable();

        // Create customer to get customer ID to create reservation
        try {
            $purchaser = Customer::create([
                'email' => 'tEmail@email.com',
                'password' => 'password123',
                'username' => 'egUsername'
            ]);
        } catch (DatabaseException $e) {
            $count = 0;
            $found = false;
            while (!$found) {
                if (Customer::existsWithID($count)) {
                    $purchaser = Customer::load($count);
                    $found = true;
                } else {
                    $count++;
                    if($count == 100){
                        $found = true;
                    }
                }
            }
        };

        // Create seller to get a seller ID to create a bundle
        $seller = Seller::create([
            'email' => 'test@test.com',
            'password' => 'password',
            'name' => 'sampleShop',
            'address' => '2 Example Avenue',
        ]);

        // Create bundle for the reservation to reference
        $bundle1 = Bundle::create([
            'bundleStatus' => BundleStatus::OffSale,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
            'expiryDate' => $expDate,
            'pickupWindow' => "14:00-15:00",
            'quantity' => 100
        ]);

        $bundle2 = Bundle::create([
            'bundleStatus' => BundleStatus::OffSale,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
            'expiryDate' => $expDate,
            'pickupWindow' => "12:00-13:00",
            'quantity' => 100
        ]);



        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::NoShow,
            'claimCode' => 'abcdabcdabcdaaad',
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdabce',
        ]);

        // Create test reservation
        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::NoShow,
            'claimCode' => 'abcdabcdabcdabcd',
            'reservationDate' => $lastWeekStart
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

        $todayDay = getdate(idate($today))['weekday'];

        if($todayDay == "Monday") {
            self::assertEquals($weeklyForecast['neededBundlesMonday'], 3);
            self::assertEquals($weeklyForecast['probabilityCollectedMonday'], 4 / 6);
        } else{
            self::assertEquals($weeklyForecast['neededBundlesMonday'], 3);
            self::assertEquals($weeklyForecast['probabilityCollectedMonday'], 0.75);
        }
    }

    public function testCompareWithGroundTruth(){
        self::cleanTables();

        $day1 = "2026-03-12 14:56:39.599705";
        $day2 = "2026-03-20 15:56:39.599705";
        $day3 = "2026-03-27 15:56:39.599705";
        $day4 = "2026-04-2 15:56:39.599705";

        $expDate = new DateTimeImmutable();

        // Create customer to get customer ID to create reservation

        try {
            $purchaser = Customer::create([
                'email' => 'tEmail@email.com',
                'password' => 'password123',
                'username' => 'egUsername'
            ]);
        } catch (DatabaseException $e) {
            $count = 0;
            $found = false;
            while (!$found) {
                if (Customer::existsWithID($count)) {
                    $purchaser = Customer::load($count);
                    $found = true;
                } else {
                    $count++;
                    if($count == 100){
                        $found = true;
                    }
                }
            }
        };

        // Create seller to get a seller ID to create a bundle
        $seller = Seller::create([
            'email' => 'test@test.com',
            'password' => 'password',
            'name' => 'sampleShop',
            'address' => '2 Example Avenue',
        ]);

        // Create bundle for the reservation to reference
        $bundle1 = Bundle::create([
            'bundleStatus' => BundleStatus::OffSale,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
            'expiryDate' => $expDate,
            'pickupWindow' => "14:00-15:00",
            'quantity' => 100
        ]);

        $bundle2 = Bundle::create([
            'bundleStatus' => BundleStatus::OffSale,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
            'expiryDate' => $expDate,
            'pickupWindow' => "12:00-13:00",
            'quantity' => 100
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::NoShow,
            'claimCode' => 'abcdabcdabcdaaad',
            'reservationDate' => $day1,
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::NoShow,
            'claimCode' => 'abcdabcdabcdabce',
            'reservationDate' => $day1
        ]);

        // Create test reservation
        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdabcd',
            'reservationDate' => $day2
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdaaaf',
            'reservationDate' => $day3
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdabcg',
            'reservationDate' => $day4
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdaaah',
            'reservationDate' => $day4
        ]);

        $graphs = Forecast::compareWithGroundTruth($seller->getUserID(), "Seasonal");

        self::assertEquals(count($graphs[0]), 3);
        self::assertEquals(count($graphs[0]), count($graphs[1]));

        $graphs = Forecast::compareWithGroundTruth($seller->getUserID(), "MovingAverage");

        self::assertEquals(count($graphs[0]), 3);
        self::assertEquals(count($graphs[0]), count($graphs[1]));
    }

    public function testPrediction(){
        self::cleanTables();

        if(!Category::categoryExists("meals")){
            Category::create("meals");
        }

        if(!Category::categoryExists("sweets")){
            Category::create("sweets");
        }

        $day1 = "2026-03-12 14:56:39.599705";
        $day2 = "2026-03-20 15:56:39.599705";
        $day3 = "2026-03-27 15:56:39.599705";
        $day4 = "2026-04-2 15:56:39.599705";

        $expDate = new DateTimeImmutable();

        // Create seller to get a seller ID to create a bundle
        $seller = Seller::create([
            'email' => 'test@test.com',
            'password' => 'password',
            'name' => 'sampleShop',
            'address' => '2 Example Avenue',
        ]);

        // Create customer to get customer ID to create reservation
        try {
            $purchaser = Customer::create([
                'email' => 'tEmail@email.com',
                'password' => 'password123',
                'username' => 'egUsername'
            ]);
        } catch (DatabaseException $e) {
            $count = 0;
            $found = false;
            while (!$found) {
                if (Customer::existsWithID($count)) {
                    $purchaser = Customer::load($count);
                    $found = true;
                } else {
                    $count++;
                    if($count == 100){
                        $found = true;
                    }
                }
            }
        };

        // Create bundle for the reservation to reference
        $bundle1 = Bundle::create([
            'bundleStatus' => BundleStatus::OffSale,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
            'expiryDate' => $expDate,
            'pickupWindow' => "17:00-18:00",
            'quantity' => 100
        ]);

        if(!$bundle1->getCategory() == null){
            $bundle1->removeCategory();
        }

        $bundle1->addCategory("meals");

        $bundle2 = Bundle::create([
            'bundleStatus' => BundleStatus::OffSale,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
            'expiryDate' => $expDate,
            'pickupWindow' => "12:00-13:00",
            'quantity' => 100
        ]);

        if(!$bundle2->getCategory() == null){
            $bundle2->removeCategory();
        }
        $bundle2->addCategory("meals");

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdaaad',
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdabce',
        ]);

        // Create test reservation
        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdabcd',
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdaaaf',
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle2->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdabcg',
        ]);

        Reservation::create([
            'purchaserID' => $purchaser->getUserID(),
            'bundleID' => $bundle1->getID(),
            'status' => ReservationStatus::Completed,
            'claimCode' => 'abcdabcdabcdaaah',
        ]);

        $bundle3 = Bundle::create([
            'bundleStatus' => BundleStatus::OffSale,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
            'expiryDate' => $expDate,
            'pickupWindow' => "14:00-15:00",
            'quantity' => 100
        ]);

        if(!$bundle3->getCategory() == null){
            $bundle3->removeCategory();
        }
        $bundle3->addCategory("meals");

        $bundle4 = Bundle::create([
            'bundleStatus' => BundleStatus::OffSale,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 10000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
            'expiryDate' => $expDate,
            'pickupWindow' => "02:00-03:00",
            'quantity' => 100
        ]);

        if(!$bundle4->getCategory() == null){
            $bundle4->removeCategory();
        }
        $bundle4->addCategory("sweets");

        $prediction = Forecast::getProductionRecommendation($bundle3);

        self::assertEquals($prediction, array(6,0,-94, "12:00-13:00"));

        $prediction = Forecast::getProductionRecommendation($bundle4);

        $e = 2;
        self::assertEquals($prediction, array(0,0,-100, "12:00-13:00"));

        $bundle1->removeCategory();
        $bundle2->removeCategory();
        $bundle3->removeCategory();
        $bundle4->removeCategory();
    }

    public static function cleanTables(): void {
        $stmt = DatabaseHandler::getPDO()->prepare("SET FOREIGN_KEY_CHECKS = 0;");
        $stmt->execute();

        $stmt3 = DatabaseHandler::getPDO()->prepare("TRUNCATE account;");
        $stmt3->execute();

        $stmt1 = DatabaseHandler::getPDO()->prepare("TRUNCATE seller;");
        $stmt1->execute();

        $stmt4 = DatabaseHandler::getPDO()->prepare("TRUNCATE reservation;");
        $stmt4->execute();

        $stmt5 = DatabaseHandler::getPDO()->prepare("TRUNCATE bundle;");
        $stmt5->execute();

        $stmt2 = DatabaseHandler::getPDO()->prepare("DELETE FROM customer");
        $stmt2->execute();

        $stmt = DatabaseHandler::getPDO()->prepare("SET FOREIGN_KEY_CHECKS = 1;");
        $stmt->execute();
    }
}