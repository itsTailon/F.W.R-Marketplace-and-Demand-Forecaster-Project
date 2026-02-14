<?php
namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\ReservationStatus;
use TTE\App\Model\Seller;
use TTE\App\Model\Reservation;
use TTE\App\Model\Customer;

class SellerTest extends TestCase
{
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

        // Create a cancelled bundle
        $testBundleExpired = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Expired, "title" => "Ex Bundle Title (Cancelled)", "details" => "Ex Bundle Details (Cancelled)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleExpiredReservation = Reservation::create(["bundleID" => $testBundleExpired->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Cancelled]);

        // Create a collected bundle
        $testBundleCollected = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Collected, "title" => "Ex Bundle Title (Collected)", "details" => "Ex Bundle Details (Collected)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleCollectedReservation = Reservation::create(["bundleID" => $testBundleCollected->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Completed]);

        // Create an active bundle
        $testBundleActive = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Reserved, "title" => "Ex Bundle Title (Active)", "details" => "Ex Bundle Details (Active)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleActiveReservation = Reservation::create(["bundleID" => $testBundleActive->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Active]);

        // Do our assertion
        $this->assertEquals(50, $testSeller->getSellThroughRate());

        // Delete the reservations
        Reservation::delete($testBundleExpiredReservation->getID());
        Reservation::delete($testBundleCollectedReservation->getID());
        Reservation::delete($testBundleActiveReservation->getID());

        // Delete the bundles themselves
        Bundle::delete($testBundleExpired->getID());
        Bundle::delete($testBundleCollected->getID());
        Bundle::delete($testBundleActive->getID());

        // Delete the users involved
        Customer::delete($testPurchaser->getUserID());
        Seller::delete($testSeller->getUserID());
    }

    public function testGetSellThroughRateByDiscountRate() {
        // Create users needed for the test
        $testSeller = Seller::create(["email" => "sellthroughrate@example.com", "name" => "Ex Seller Name", "password" => "password", "address" => "Ex Seller Address"]);
        $testPurchaser = Customer::create(["email" => "sellthroughratebuyer@example.com", "username" => "Joe Generic", "password" => "password"]);

        // Create a cancelled bundle
        $testBundleExpired = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Expired, "title" => "Ex Bundle Title (Cancelled)", "details" => "Ex Bundle Details (Cancelled)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleExpiredReservation = Reservation::create(["bundleID" => $testBundleExpired->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Cancelled]);

        // Create a collected bundle
        $testBundleCollected = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Collected, "title" => "Ex Bundle Title (Collected)", "details" => "Ex Bundle Details (Collected)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleCollectedReservation = Reservation::create(["bundleID" => $testBundleCollected->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Completed]);

        // Create an active bundle
        $testBundleActive = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Reserved, "title" => "Ex Bundle Title (Active)", "details" => "Ex Bundle Details (Active)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleActiveReservation = Reservation::create(["bundleID" => $testBundleActive->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Active]);

        // Create a bundle that should be ignored
        $testBundleShouldBeIgnored = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Collected, "title" => "Ex Bundle Title (Should Be Ignored)", "details" => "Ex Bundle Details (Should Be Ignored)", "rrp" => 10.00, "discountedPrice" => 6.00]);
        $testBundleShouldBeIgnoredReservation = Reservation::create(["bundleID" => $testBundleActive->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Completed]);

        // Do our assertion
        $this->assertEquals(50, $testSeller->getSellThroughRateByDiscountRate(10, 30));

        // Delete the reservations
        Reservation::delete($testBundleExpiredReservation->getID());
        Reservation::delete($testBundleCollectedReservation->getID());
        Reservation::delete($testBundleActiveReservation->getID());
        Reservation::delete($testBundleShouldBeIgnoredReservation->getID());

        // Delete the bundles themselves
        Bundle::delete($testBundleExpired->getID());
        Bundle::delete($testBundleCollected->getID());
        Bundle::delete($testBundleActive->getID());
        Bundle::delete($testBundleShouldBeIgnored->getID());

        // Delete the users involved
        Customer::delete($testPurchaser->getUserID());
        Seller::delete($testSeller->getUserID());
    }
}