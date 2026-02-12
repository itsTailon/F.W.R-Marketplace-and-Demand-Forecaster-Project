<?php

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\Seller;
use TTE\App\Model\MissingValuesException;

// Class testing functions of the Bundle class
class BundleTest extends TestCase {

    // TODO: Create test case/s for the update() method
    public function testUpdateBundle() {}

    // TODO: Create test case/s for the create() method
    public function testCreateBundle() {}

    // TODO: Create test case/s for the load() method
    public function testLoadBundle() {}

    // TODO: Create test case/s for the existsWithID() method
    public function testExistsWithID() {} // Not necessarily needed as should be tested through use in set...ID functions

    // TODO: Create test case/s for the delete() method
    public function testDeleteBundle() {
        /*
         * Test:
         * - If a bundle is deleted, it is not in the database
         * - If a bundle that doesn't exist is tried to be deleted, error thrown
         */

        // Create associative array for test bundle
        $fields =
            array(
                "bundleStatus" => BundleStatus::Available,
                "bundleTitle" => "Delete Bundle Title",
                "bundleDetails" => "Delete Bundle Details",
                "bundleRrpGBX" => 0,
                "bundleDiscountedPriceGBX" => 0,
                "bundleSellerID" => 0,
                "bundlePurchaserID" => null,
            );

        // Create test bundle and check it exists
        $testDeleteBundle = Bundle::create($fields);
        $testDeleteID = $testDeleteBundle->getID();
        $this->assertTrue(Bundle::existsWithID($testDeleteID));

        // Delete test bundle and check that the bundle no longer exists
        Bundle::delete($testDeleteID);
        $this->assertFalse(Bundle::existsWithID($testDeleteID));

        // Test deleting a bundle that does not exist
        $thrown = false;
        try {
            Bundle::delete($testDeleteID);
        } catch (PDOException $e) {
            $thrown = true;
        }
        if (!$thrown) {
            $this->fail();
        }
    }

    public function testSearchBundle() {
        $testSeller = Seller::create(["email" => "testsearchbundle@example.com", "password" => "password",
            "name" => "ex name", "address" => "ex address"]);
        $testBundle = Bundle::create(["sellerID" => $testSeller->getUserID(), "bundleStatus" => BundleStatus::Available,
            "title" => "testSearchBundle() title", "details" => "testSearchBundle() details", "rrp" => 10.00,
            "discountedPrice" => 8.00]);

        $shouldFindFromTitle = Bundle::searchBundles($testBundle->getTitle());
        $shouldFindFromDetails = Bundle::searchBundles($testBundle->getDetails());
        $shouldNotFind = Bundle::searchBundles($testBundle->getTitle() . " except no");

        $this->assertCount(1, $shouldFindFromTitle);
        $this->assertCount(1, $shouldFindFromDetails);
        $this->assertCount(0, $shouldNotFind);

        Bundle::delete($testBundle->getID());
        Seller::delete($testSeller->getUserID());
    }

    // TODO: If there exists a getter/setter not utilised within other methods, test here


}