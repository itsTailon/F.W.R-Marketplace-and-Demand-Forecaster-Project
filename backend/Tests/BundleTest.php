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
    public function testCreateBundle() {
        /*
         * Test:
         * - If invalid inputs are passed, MissingValuesException thrown
         * - If valid inputs, entry is made into the MySQL database
         * - SQL injection-proof SQL query
         * - If SQL query produces error, appropriate DatabaseException thrown
         *  - Final Bundle object returned by function is
         */

        // Create associative array with fields required as parameter for create()
        $fields =
            array(
                "bundleStatus" => BundleStatus::Available,
                "bundleTitle" => "Test Bundle Title",
                "bundleDetails" => "Test Bundle Details",
                "bundleRrpGBX" => 599,
                "bundleDiscountedPriceGBX" => 299,
                "bundleSellerID" => 1394757, // Need to update before running test
                "bundlePurchaserID" => null,
            );

        // Iterate through $fields array and update different values to null to test functionality (ignore purchaserID as nullable)
        for ($i = 0; $i < count($fields)-1; $i++) {
            // Storing previous value of field and updating it
            $prevValue = $fields[$i]->getValue();
            $fields[$i]->setValue(null);

            // Test create() function
            $this->expectException(MissingValuesException::class);
            Bundle::create($fields[$i]);

            // Return $fields to initial state
            $fields[$i]->setValue($prevValue);
        }

        //


    }

    // TODO: Create test case/s for the load() method
    public function testLoadBundle() {}

    // TODO: Create test case/s for the existsWithID() method
    public function testExistsWithID() {} // Not necessarily needed as should be tested through use in set...ID functions

    // TODO: Create test case/s for the delete() method
    public function testDeleteBundle() {}

    // TODO: If there exists a getter/setter not utilised within other methods, test here


}