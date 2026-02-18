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

    public function testFilterBundlesByDiscountLevel() {
        $seller = Seller::create(["email" => "testfilterbundles@example.com", "name" => "Ex Seller Name", "password" => "password", "address" => "123 Testing Street"]);
        Bundle::create(["sellerID" => $seller->getUserID(), "bundleStatus" => BundleStatus::Available, "title" => "10% Discounted Bundle", "details" => "Bundle that is discounted by 10%", "rrp" => 10.00, "discountedPrice" => 9.00]);
        Bundle::create(["sellerID" => $seller->getUserID(), "bundleStatus" => BundleStatus::Available, "title" => "20% Discounted Bundle", "details" => "Bundle that is discounted by 20%", "rrp" => 10.00, "discountedPrice" => 8.00]);
        Bundle::create(["sellerID" => $seller->getUserID(), "bundleStatus" => BundleStatus::Available, "title" => "30% Discounted Bundle", "details" => "Bundle that is discounted by 30%", "rrp" => 10.00, "discountedPrice" => 7.00]);

        $bundles = Seller::getAllBundlesForUser($seller->getUserID());

        $this->assertCount(2, $seller->filterBundlesByDiscountLevel($bundles, 5, 25));
        $this->assertCount(2, $seller->filterBundlesByDiscountLevel($bundles, 15, 35));
        $this->assertCount(3, $seller->filterBundlesByDiscountLevel($bundles, 5, 35));
        $this->assertCount(1, $seller->filterBundlesByDiscountLevel($bundles, 15, 25));

        Seller::delete($seller->getUserID());
    }

    public function testGetBundlesByStatus() {
        $seller = Seller::create(["email" => "testbundlesbystatus@example.com", "name" => "Ex Seller Name", "password" => "password", "address" => "123 Testing Street"]);
        Bundle::create(["sellerID" => $seller->getUserID(), "bundleStatus" => BundleStatus::Available, "title" => "10% Discounted Bundle", "details" => "Bundle that is discounted by 10%", "rrp" => 10.00, "discountedPrice" => 9.00]);
        Bundle::create(["sellerID" => $seller->getUserID(), "bundleStatus" => BundleStatus::Expired, "title" => "20% Discounted Bundle", "details" => "Bundle that is discounted by 20%", "rrp" => 10.00, "discountedPrice" => 8.00]);
        Bundle::create(["sellerID" => $seller->getUserID(), "bundleStatus" => BundleStatus::Collected, "title" => "30% Discounted Bundle", "details" => "Bundle that is discounted by 30%", "rrp" => 10.00, "discountedPrice" => 7.00]);

        $available = $seller->getBundlesByStatus(BundleStatus::Available);
        $this->assertCount(1, $available);
        $this->assertEquals(CurrencyTools::decimalStringToGBX($available[0]["discountedPrice"]), 0.9 * CurrencyTools::decimalStringToGBX($available[0]["rrp"]));

        $expired = $seller->getBundlesByStatus(BundleStatus::Expired);
        $this->assertCount(1, $expired);
        $this->assertEquals(CurrencyTools::decimalStringToGBX($expired[0]["discountedPrice"]), 0.8 * CurrencyTools::decimalStringToGBX($expired[0]["rrp"]));

        $collected = $seller->getBundlesByStatus(BundleStatus::Collected);
        $this->assertCount(1, $collected);
        $this->assertEquals(CurrencyTools::decimalStringToGBX($collected[0]["discountedPrice"]), 0.7 * CurrencyTools::decimalStringToGBX($collected[0]["rrp"]));

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

    $sellerID = $seller->getUserID();

    // Ensure seller exists
    $loadedSeller = Seller::load($sellerID);
    $this->assertEquals($sellerID, $loadedSeller->getUserID());

    // Delete seller
    Seller::delete($sellerID);

    // Ensure seller no longer exists
    $this->expectException(\Exception::class);
    Seller::load($sellerID);
  }
}
