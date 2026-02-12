<?php
namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\Seller;

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
        $testSeller = Seller::create(["email" => "testgetsellthroughrate@example.com", "name" => "Ex Seller Name", "password" => "password", "address" => "Ex Seller Address"]);
        $testBundleExpired = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Expired, "title" => "Ex Bundle Title (Expired)", "details" => "Ex Bundle Details (Expired)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleCollected = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Collected, "title" => "Ex Bundle Title (Collected)", "details" => "Ex Bundle Details (Collected)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleAvailable = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Available, "title" => "Ex Bundle Title (Available)", "details" => "Ex Bundle Details (Available)", "rrp" => 10.00, "discountedPrice" => 8.00]);

        $this->assertEquals(50, $testSeller->getSellThroughRate());

        Bundle::delete($testBundleExpired->getID());
        Bundle::delete($testBundleCollected->getID());
        Bundle::delete($testBundleAvailable->getID());
        Seller::delete($testSeller->getUserID());
    }

    public function testGetSellThroughRateByDiscountRate() {
        $testSeller = Seller::create(["email" => "testgetsellthroughrate@example.com", "name" => "Ex Seller Name", "password" => "password", "address" => "Ex Seller Address"]);
        $testBundleExpired = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Expired, "title" => "Ex Bundle Title (Expired)", "details" => "Ex Bundle Details (Expired)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleCollected = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Collected, "title" => "Ex Bundle Title (Collected)", "details" => "Ex Bundle Details (Collected)", "rrp" => 10.00, "discountedPrice" => 8.00]);
        $testBundleAvailable = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Available, "title" => "Ex Bundle Title (Available)", "details" => "Ex Bundle Details (Available)", "rrp" => 10.00, "discountedPrice" => 8.00]);

        $testBundleExpired = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Expired, "title" => "Ex Bundle Title (Expired)", "details" => "Ex Bundle Details (Expired)", "rrp" => 10.00, "discountedPrice" => 6.00]);
        $testBundleExpired = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Expired, "title" => "Ex Bundle Title (Expired)", "details" => "Ex Bundle Details (Expired)", "rrp" => 10.00, "discountedPrice" => 10.00]);

        $this->assertEquals(50, $testSeller->getSellThroughRateByDiscountRate(10, 30));

        Bundle::delete($testBundleExpired->getID());
        Bundle::delete($testBundleCollected->getID());
        Bundle::delete($testBundleAvailable->getID());
        Seller::delete($testSeller->getUserID());
    }
}