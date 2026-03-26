<?php
namespace TTE\App\Tests\Model;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchBundleException;
use TTE\App\Model\ReservationStatus;
use TTE\App\Model\Seller;
use TTE\App\Model\Reservation;
use TTE\App\Model\Customer;
use TTE\App\Helpers\CurrencyTools;

class SellerTest extends TestCase
{

    public function testUpdate() {

        // Seller fields for update() method
        $seller = Seller::create(array(
            "email" => "test@gmail.com",
            "password" => "testingPassword123",
            "name" => "Test Name",
            "address" => "34 Testing Street",
        ));

        // No email test as can't enter empty string as a valid email due to '@' check

        // Test erroneous update for name
        $prevValue = $seller->getName();
        $seller->setName("       ");

        $thrown = false;
        try {
            $seller->update();
        } catch (MissingValuesException $e) {
            $thrown = true;
        } catch (DatabaseException $e) {
            $this->fail($e->getMessage());
        }

        if (!$thrown) {
            $this->fail("Failed to throw MissingValuesException for empty name");
        }

        $seller->setName($prevValue);

        // Test updating address to an erroneous one
        $prevValue = $seller->getAddress();
        $seller->setAddress("       ");

        $thrown = false;
        try {
            $seller->update();
        } catch (MissingValuesException $e) {
            $thrown = true;
        } catch (DatabaseException $e) {
            $this->fail($e->getMessage());
        }

        if (!$thrown) {
            $this->fail("Failed to throw MissingValuesException for empty address");
        }

        $seller->setAddress($prevValue);

        // Attempting a valid update of seller account
        $seller->setEmail("secondTestEmail@test.com");
        $seller->setName("secondTestName");
        $seller->setAddress("34 Testing Street Second");

        try {
            $seller->update();
        } catch (DatabaseException|MissingValuesException $e) {
            $this->fail($e->getMessage());
        }

        // Checking currently held values to ensure they updated correctly
        $this->assertTrue($seller->getEmail() == "secondTestEmail@test.com");
        $this->assertTrue($seller->getName() == "secondTestName");
        $this->assertTrue($seller->getAddress() == "34 Testing Street Second");

        // Clean-up
        Seller::delete($seller->getUserID());


    }

    public function testCreateSeller()
    {
        $testSeller = Seller::create(["email" => "testcreateseller@example.com", "name" => "Ex Seller Name", "password" => "password", "address" => "Ex Seller Address"]);
        $testSellerLoaded = Seller::load($testSeller->getUserID());

        $this->assertEquals($testSeller->getUserID(), $testSellerLoaded->getUserID());
        $this->assertEquals($testSeller->getEmail(), $testSellerLoaded->getEmail());
        $this->assertEquals($testSeller->getName(), $testSellerLoaded->getName());
        $this->assertEquals($testSeller->getAddress(), $testSellerLoaded->getAddress());

        Seller::delete($testSeller->getUserID());
    }

    public function testGetSellThroughRate() {
        // Create users needed for the test
        $testSeller = Seller::create(["email" => "sellthroughrate@example.com", "name" => "Ex Seller Name", "password" => "password", "address" => "Ex Seller Address"]);
        $testPurchaser = Customer::create(["email" => "sellthroughratebuyer@example.com", "username" => "Joe Generic", "password" => "password"]);

        // Create DateTimeImmutables for a week before and a week after
        $weekBefore = DateTimeImmutable::createFromMutable(date_sub(new DateTime(), date_interval_create_from_date_string("7 days")));
        $weekAfter = DateTimeImmutable::createFromMutable(date_add(new DateTime(), date_interval_create_from_date_string("7 days")));

        // Create a cancelled bundle
        $testBundleCancelled = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::OffSale, "title" => "Ex Bundle Title (Expired)", "details" => "Ex Bundle Details (Expired)", "rrp" => 10.00, "discountedPrice" => 8.00, "expiryDate" => $weekBefore, "pickupWindow" => "00:00-01:00", "quantity" => 1]);

        // Create a collected bundle
        $testBundleCollected = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::OffSale, "title" => "Ex Bundle Title (Collected)", "details" => "Ex Bundle Details (Collected)", "rrp" => 10.00, "discountedPrice" => 8.00, "expiryDate" => $weekBefore, "pickupWindow" => "00:00-01:00", "quantity" => 1]);
        $testBundleCollectedReservation = Reservation::create(["bundleID" => $testBundleCollected->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Completed]);
        Reservation::markCollected($testBundleCollectedReservation->getID());

        // Create an active bundle
        $testBundleActive = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::OnSale, "title" => "Ex Bundle Title (Active)", "details" => "Ex Bundle Details (Active)", "rrp" => 10.00, "discountedPrice" => 8.00, "expiryDate" => $weekAfter, "pickupWindow" => "00:00-01:00", "quantity" => 1]);
        $testBundleActiveReservation = Reservation::create(["bundleID" => $testBundleActive->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Active]);

        // Do our assertion
        $this->assertEquals(50, $testSeller->getSellThroughRate());

        // Delete the users involved (will cause cascade)
        Customer::delete($testPurchaser->getUserID());
        Seller::delete($testSeller->getUserID());
    }

    public function testGetSellThroughRateByDiscountRate() {
        // Create users needed for the test
        $testSeller = Seller::create(["email" => "sellthroughrate@example.com", "name" => "Ex Seller Name", "password" => "password", "address" => "Ex Seller Address"]);
        $testPurchaser = Customer::create(["email" => "sellthroughratebuyer@example.com", "username" => "Joe Generic", "password" => "password"]);

        // Create DateTimeImmutables for a week before and a week after
        $weekBefore = DateTimeImmutable::createFromMutable(date_sub(new DateTime(), date_interval_create_from_date_string("7 days")));
        $weekAfter = DateTimeImmutable::createFromMutable(date_add(new DateTime(), date_interval_create_from_date_string("7 days")));

        // Create an expired bundle
        $testBundleExpired = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::OffSale, "title" => "Ex Bundle Title (Expired)", "details" => "Ex Bundle Details (Expired)", "rrp" => 10.00, "discountedPrice" => 8.00, "expiryDate" => $weekBefore, "pickupWindow" => "00:00-01:00", "quantity" => 1]);

        // Create a collected bundle
        $testBundleCollected = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::OffSale, "title" => "Ex Bundle Title (Collected)", "details" => "Ex Bundle Details (Collected)", "rrp" => 10.00, "discountedPrice" => 8.00, "expiryDate" => $weekBefore, "pickupWindow" => "00:00-01:00", "quantity" => 1]);
        $testBundleCollectedReservation = Reservation::create(["bundleID" => $testBundleCollected->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Completed]);
        Reservation::markCollected($testBundleCollectedReservation->getID());

        // Create an active bundle
        $testBundleActive = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::OnSale, "title" => "Ex Bundle Title (Active)", "details" => "Ex Bundle Details (Active)", "rrp" => 10.00, "discountedPrice" => 8.00, "expiryDate" => $weekAfter, "pickupWindow" => "00:00-01:00", "quantity" => 1]);
        $testBundleActiveReservation = Reservation::create(["bundleID" => $testBundleActive->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Active]);

        // Create a bundle that should be ignored
        $testBundleShouldBeIgnored = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::OffSale, "title" => "Ex Bundle Title (Should Be Ignored)", "details" => "Ex Bundle Details (Should Be Ignored)", "rrp" => 10, "discountedPrice" => 4, "expiryDate" => $weekAfter, "pickupWindow" => "00:00-01:00", "quantity" => 1]);
        $testBundleShouldBeIgnoredReservation = Reservation::create(["bundleID" => $testBundleShouldBeIgnored->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Completed]);

        // Do our assertion
        $this->assertEquals(50, $testSeller->getSellThroughRateByDiscountRate(10, 30));

        // Delete the users involved
        Customer::delete($testPurchaser->getUserID());
        Seller::delete($testSeller->getUserID());
    }

    public function testFilterBundlesByDiscountLevel() {
        $seller = Seller::create(["email" => "testfilterbundles@example.com", "name" => "Ex Seller Name", "password" => "password", "address" => "123 Testing Street"]);
        Bundle::create(["sellerID" => $seller->getUserID(), "bundleStatus" => BundleStatus::OnSale, "title" => "10% Discounted Bundle", "details" => "Bundle that is discounted by 10%", "rrp" => 10.00, "discountedPrice" => 9.00, "expiryDate" => new \DateTimeImmutable(), "pickupWindow" => "00:00-01:00", "quantity" => 1]);
        Bundle::create(["sellerID" => $seller->getUserID(), "bundleStatus" => BundleStatus::OnSale, "title" => "20% Discounted Bundle", "details" => "Bundle that is discounted by 20%", "rrp" => 10.00, "discountedPrice" => 8.00, "expiryDate" => new \DateTimeImmutable(), "pickupWindow" => "00:00-01:00", "quantity" => 1]);
        Bundle::create(["sellerID" => $seller->getUserID(), "bundleStatus" => BundleStatus::OnSale, "title" => "30% Discounted Bundle", "details" => "Bundle that is discounted by 30%", "rrp" => 10.00, "discountedPrice" => 7.00, "expiryDate" => new \DateTimeImmutable(), "pickupWindow" => "00:00-01:00", "quantity" => 1]);

        $bundles = Seller::getAllBundlesForUser($seller->getUserID());

        $this->assertCount(2, $seller->filterBundlesByDiscountLevel($bundles, 5, 25));
        $this->assertCount(2, $seller->filterBundlesByDiscountLevel($bundles, 15, 35));
        $this->assertCount(3, $seller->filterBundlesByDiscountLevel($bundles, 5, 35));
        $this->assertCount(1, $seller->filterBundlesByDiscountLevel($bundles, 15, 25));

        Seller::delete($seller->getUserID());
    }

    public function testGetBundlesByStatus() {
        $seller = Seller::create(["email" => "testbundlesbystatus@example.com", "name" => "Ex Seller Name", "password" => "password", "address" => "123 Testing Street"]);
        Bundle::create(["sellerID" => $seller->getUserID(), "bundleStatus" => BundleStatus::OnSale, "title" => "10% Discounted Bundle", "details" => "Bundle that is discounted by 10%", "rrp" => 10.00, "discountedPrice" => 9.00, "expiryDate" => new \DateTimeImmutable(), "pickupWindow" => "00:00-01:00", "quantity" => 1]);
        Bundle::create(["sellerID" => $seller->getUserID(), "bundleStatus" => BundleStatus::OffSale, "title" => "20% Discounted Bundle", "details" => "Bundle that is discounted by 20%", "rrp" => 10.00, "discountedPrice" => 8.00, "expiryDate" => new \DateTimeImmutable(), "pickupWindow" => "00:00-01:00", "quantity" => 1]);

        $available = $seller->getBundlesByStatus(BundleStatus::OnSale);
        $this->assertCount(1, $available);
        $this->assertEquals(CurrencyTools::decimalStringToGBX($available[0]["discountedPrice"]), 0.9 * CurrencyTools::decimalStringToGBX($available[0]["rrp"]));

        $expired = $seller->getBundlesByStatus(BundleStatus::OffSale);
        $this->assertCount(1, $expired);
        $this->assertEquals(CurrencyTools::decimalStringToGBX($expired[0]["discountedPrice"]), 0.8 * CurrencyTools::decimalStringToGBX($expired[0]["rrp"]));

        Seller::delete($seller->getUserID());
    }

    public function testDeleteSeller() {
    // Create a seller
    $seller = Seller::create([
        "email" => "testdeleteseller@example.com",
        "name" => "Seller Name",
        "password" => "password",
        "address" => "Seller Address"
    ]);

    // Ensure seller exists
    $loadedSeller = Seller::load($seller->getUserID());
    $this->assertEquals($seller->getUserID(), $loadedSeller->getUserID());

    // Delete seller
    Seller::delete($seller->getUserID());

    // Ensure seller no longer exists
    $this->expectException(\Exception::class);
    Seller::load($seller->getUserID());
    }
}
