<?php
namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\ReservationStatus;
use TTE\App\Model\Seller;
use TTE\App\Model\Reservation;
use TTE\App\Model\Customer;
use TTE\App\Helpers\CurrencyTools;

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

        // Create an expired bundle
        $testBundleExpired = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Expired, "title" => "Ex Bundle Title (Expired)", "details" => "Ex Bundle Details (Expired)", "rrp" => 10.00, "discountedPrice" => 8.00]);

        // Create a collected bundle
        $testBundleCollected = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Collected, "title" => "Ex Bundle Title (Collected)", "details" => "Ex Bundle Details (Collected)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleCollectedReservation = Reservation::create(["bundleID" => $testBundleCollected->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Completed]);
        Reservation::markCollected($testBundleCollectedReservation->getID());

        // Create an active bundle
        $testBundleActive = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Reserved, "title" => "Ex Bundle Title (Active)", "details" => "Ex Bundle Details (Active)", "rrp" => 10.00, "discountedPrice" => 8.00]);
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

        // Create an expired bundle
        $testBundleExpired = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Expired, "title" => "Ex Bundle Title (Expired)", "details" => "Ex Bundle Details (Expired)", "rrp" => 10.00, "discountedPrice" => 8.00]);

        // Create a collected bundle
        $testBundleCollected = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Collected, "title" => "Ex Bundle Title (Collected)", "details" => "Ex Bundle Details (Collected)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleCollectedReservation = Reservation::create(["bundleID" => $testBundleCollected->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Completed]);
        Reservation::markCollected($testBundleCollectedReservation->getID());

        // Create an active bundle
        $testBundleActive = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Reserved, "title" => "Ex Bundle Title (Active)", "details" => "Ex Bundle Details (Active)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleActiveReservation = Reservation::create(["bundleID" => $testBundleActive->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Active]);

        // Create a bundle that should be ignored
        $testBundleShouldBeIgnored = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Collected, "title" => "Ex Bundle Title (Should Be Ignored)", "details" => "Ex Bundle Details (Should Be Ignored)", "rrp" => 10, "discountedPrice" => 4]);
        $testBundleShouldBeIgnoredReservation = Reservation::create(["bundleID" => $testBundleShouldBeIgnored->getID(), "purchaserID" => $testPurchaser->getUserID(), "status" => ReservationStatus::Completed]);

        // Do our assertion
        $this->assertEquals(50, $testSeller->getSellThroughRateByDiscountRate(10, 30));

        // Delete the users involved
        Customer::delete($testPurchaser->getUserID());
        Seller::delete($testSeller->getUserID());
    }
}