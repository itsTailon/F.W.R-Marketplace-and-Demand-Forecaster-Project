<?php

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
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

    // TODO: If there exists a getter/setter not utilised within other methods, test here


}