<?php

namespace TTE\App\Tests\Model;

use Exception;
use PHPUnit\Framework\TestCase;
use TTE\App\Helpers\CurrencyTools;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\Customer;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\Seller;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\NoSuchSellerException;
use TTE\App\Model\NoSuchBundleException;

// Global for session to run test
$_SESSION = array();


// Class testing functions of the Bundle class
class BundleTest extends TestCase
{

    /**
     * @throws NoSuchCustomerException|DatabaseException
     */
    public function testUpdateBundle()
    {

        // Seller and customer fields for update() methods
        $sellerFields = array(
            "email" => "test@gmail.com",
            "password" => "testingPassword123",
            "name" => "Test Name",
            "address" => "34 Testing Street",
        );

        $customerFields = array(
            "username" => "testingUser",
            "email" => "testCust@gmail.com",
            "password" => "testingPassword123",
            "name" => "Name Test",
            "address" => "32 Testing Street",
        );

        // Creating seller/customer objects
        $seller = Seller::create($sellerFields);
        $customer = Customer::create($customerFields);

        // Create associative array with fields required as parameter for update()
        $fields =
            array(
                "bundleStatus" => BundleStatus::Available,
                "title" => "Test Bundle Title",
                "details" => "Test Bundle Details",
                "rrp" => 599,
                "discountedPrice" => 299,
                "sellerID" => $seller->getUserID(),
                "purchaserID" => $customer->getUserID(),
            );

        // Creating bundle that is to then be updated
        try {
            $bundle = Bundle::create($fields);
        } catch (Exception $e) {
            Seller::delete($seller->getUserID());
            Customer::delete($customer->getUserID());

            // fail the test
            $this->fail($e->getMessage());
        }

        // Iterate through $fields array and update different values to null to test functionality
        foreach($fields as $key => $value) {

            // Storing previous value of field and updating it
            $prevValue = $value;
            unset($fields[$key]);

            // Test update() function
            $thrown = false;
            try {
                $bundle->update();
            } catch (Exception $e) {
                $thrown = true;
                if (!$thrown) {
                    // Cleanup if bundle fails to update
                    $bundle->delete($bundle->getID());
                    $customer->delete($customer->getUserID());
                    $seller->delete($seller->getUserID());

                    // Force failure as error not thrown as should
                    $this->fail($e->getMessage());
                }

                // Return $fields to initial state
                $fields[$key] = $prevValue;
            }


            // Change values for $bundle to a set of valid values
            $bundle->setStatus(BundleStatus::Reserved);
            $bundle->setPurchaserID($customer->getUserID());
            $bundle->setTitle("Testing Updating Method");
            $bundle->setRrpGBX(700);

            // Attempting to update bundle
            try {
                $bundle->update();
            } catch (DatabaseException|NoSuchBundleException $e) {
                // Cleanup prior to throwing failure
                Bundle::delete($bundle->getID());
                Seller::delete($seller->getUserID());
                Customer::delete($customer->getUserID());

                // Fail test
                $this->fail($e->getMessage());
            }

            // Get fresh object from the database
            $db_bundle = Bundle::load($bundle->getID());

            // Comparing values of object stored in DB to what should be
            $this->assertEquals($bundle->getStatus(), $db_bundle->getStatus());
            $this->assertEquals($bundle->getTitle(), $db_bundle->getTitle());
            $this->assertEquals($bundle->getDetails(), $db_bundle->getDetails());
            $this->assertEquals($bundle->getRrpGBX(), $db_bundle->getRrpGBX());
            $this->assertEquals($bundle->getDiscountedPriceGBX(), $db_bundle->getDiscountedPriceGBX());
            $this->assertEquals($bundle->getSellerID(), $db_bundle->getSellerID());
            $this->assertEquals($bundle->getPurchaserID(), $db_bundle->getPurchaserID());

            // If successful update, confirm and do cleanup
            Bundle::delete($bundle->getID());
            Seller::delete($seller->getUserID());
            Customer::delete($customer->getUserID());
        }

    }

    /**
     * Method that tests that all appropriate exceptions are thrown and Bundle creation works on code and db front
     * @throws DatabaseException|NoSuchCustomerException|NoSuchSellerException
     * @throws MissingValuesException
     */
    public function testCreateBundle()
    {
        /*
         * Test:
         * - If invalid inputs are passed, MissingValuesException thrown
         * - If valid inputs, entry is made into the MySQL database
         * - SQL injection-proof SQL query
         * - If SQL query produces error, appropriate DatabaseException thrown
         *  - Final Bundle object returned by function is
         */

        // Seller and customer fields for create() methods
        $sellerFields = array(
            "email" => "test@gmail.com",
            "password" => "testingPassword123",
            "name" => "Test Name",
            "address" => "34 Testing Street",
        );

        $customerFields = array(
            "username" => "testingUser",
            "email" => "testCust@gmail.com",
            "password" => "testingPassword123",
            "name" => "Name Test",
            "address" => "32 Testing Street",
        );

        // Creating seller/customer objects
        $seller = Seller::create($sellerFields);
        $customer = Customer::create($customerFields);

        // Array to track IDs of bundles created for cleanup
        $cleanupBundles = array();

        // Create associative array with fields required as parameter for create()
        $fields =
            array(
                "bundleStatus" => BundleStatus::Available,
                "title" => "Test Bundle Title",
                "details" => "Test Bundle Details",
                "rrp" => 599,
                "discountedPrice" => 299,
                "sellerID" => $seller->getUserID(),
                "purchaserID" => $customer->getUserID(),
            );

        // Iterate through $fields array and update different values to null to test functionality (ignore purchaserID as nullable)
        foreach($fields as $key => $value) {
            if($key == "purchaserID") {
                continue;
            }

            // Storing previous value of field and updating it
            $prevValue = $value;
            unset($fields[$key]);

            // Test create() function
            $thrown = false;
            try {
                $bundle = Bundle::create($fields);

                // Add created bundle's ID to cleanup array
                array_push($cleanupBundles, $bundle->getID());
            } catch (MissingValuesException $e) {
                $thrown = true;
            }
            if (!$thrown) {
                // Checking if there are bundles to delete
                if (!empty($cleanupBundles)) {
                    foreach($cleanupBundles as $bundleID) {
                        Bundle::delete($bundleID);
                    }
                }

                // Cleanup if bundle fails to create
                $customer->delete($customer->getUserID());
                $seller->delete($seller->getUserID());

                // Force failure as error not thrown as should
                $this->fail();
            }

            // Return $fields to initial state
            $fields[$key] = $prevValue;
        }

        // Test handling when it comes to strings for title and bundle details being empty spaces
        $prevValue = $fields['title']; // Store value to return to
        $fields['title'] = "       "; // Set value to empty string filled with spaces

        $thrown = false;
        try {
            $bundle = Bundle::create($fields);
            array_push($cleanupBundles, $bundle->getID());
        } catch (MissingValuesException $e) {
            $thrown = true;
        }
        if (!$thrown) {
            // Checking if there are bundles to delete
            if (!empty($cleanupBundles)) {
                foreach($cleanupBundles as $bundleID) {
                    Bundle::delete($bundleID);
                }
            }

            // Cleanup prior to failure of test
            $customer->delete($customer->getUserID());
            $seller->delete($seller->getUserID());

            // Forcing test failure
            $this->fail();
        }

        $fields['title'] = $prevValue; // Return value to previous

        // Repeat for bundle details
        $prevValue = $fields['details'];
        $fields['details'] = "         ";

        $thrown = false;
        try {
            $bundle = Bundle::create($fields);
            array_push($cleanupBundles, $bundle->getID());
        } catch (MissingValuesException $e) {
            $thrown = true;
        }
        if (!$thrown) {
            // Checking if there are bundles to delete
            if (!empty($cleanupBundles)) {
                foreach($cleanupBundles as $bundleID) {
                    Bundle::delete($bundleID);
                }
            }

            // Cleanup prior to deletion
            $customer->delete($customer->getUserID());
            $seller->delete($seller->getUserID());

            // Forcing failure of test
            $this->fail();
        }

        $fields['details'] = $prevValue;

        // Apply creation method and check that Bundle is produced
        $bundle = Bundle::create($fields);
        array_push($cleanupBundles, $bundle->getID());

        // Check $bundle attributes and ensure all hold appropriate values
        foreach ($fields as $key => $value) {
            switch ($key) {
                case "bundleStatus":
                    $this->assertEquals($value, $bundle->getStatus());
                    break;
                case "title":
                    $this->assertEquals($value, $bundle->getTitle());
                    break;
                case "details":
                    $this->assertEquals($value, $bundle->getDetails());
                    break;
                case "rrp":
                    $this->assertEquals($value, $bundle->getRrpGBX());
                    break;
                case "discountedPrice":
                    $this->assertEquals($value, $bundle->getDiscountedPriceGBX());
                    break;
                case "sellerID":
                    $this->assertEquals($value, $bundle->getSellerID());
                    break;
                case "purchaserID":
                    $this->assertEquals($value, $bundle->getPurchaserID());
                    break;
            }
        }

        // Cleanup bundles
        foreach ($cleanupBundles as $bundleID) {
            Bundle::delete($bundleID);
        }
        // Remove test users
        $customer->delete($customer->getUserID());
        $seller->delete($seller->getUserID());
    }

    // TODO: Create test case/s for the load() method
    public function testLoadBundle() {
        // Create seller to get a seller ID to create a bundle
        $seller = Seller::create([
            'email' => 'sellertest@example.com',
            'password' => 'password',
            'name' => 'sampleShop',
            'address' => '2 Example Avenue',
        ]);

        // Create bundle for testing
        $bundle = Bundle::create([
            'bundleStatus' => BundleStatus::Available,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        // Load bundle and compare to existing bundle object (both should be equal)
        self::assertTrue($bundle == Bundle::load($bundle->getID()));

        // Try loading non-existent bundle (ID of -1 will never exist)
        // Ensure that such results in a DatabaseException being thrown
        $thrown = false;
        try {
            Bundle::load(-1);
        } catch (DatabaseException $e) {
            $thrown = true;
        }
        if (!$thrown) {
            $this->fail();
        }

        // Cleanup
        Bundle::delete($bundle->getID());
        Seller::delete($seller->getUserID());
    }

    // TODO: Create test case/s for the existsWithID() method
    public function testExistsWithID() {
        // Create seller to get a seller ID to create a bundle
        $seller = Seller::create([
            'email' => 'sellertest@example.com',
            'password' => 'password',
            'name' => 'sampleShop',
            'address' => '2 Example Avenue',
        ]);

        // Create bundle for testing
        $bundle = Bundle::create([
            'bundleStatus' => BundleStatus::Available,
            'title' => 'TestBundle',
            'details' => 'A test bundle',
            'rrp' => 1000,
            'discountedPrice' => 500,
            'sellerID' => $seller->getUserID(),
        ]);

        // Bundle should exist as it has just been created
        self::assertTrue(Bundle::existsWithID($bundle->getID()));

        // Delete bundle
        Bundle::delete($bundle->getID());
        // Should now be false, as bundle has been deleted
        self::assertFalse(Bundle::existsWithID($bundle->getID()));

        // Cleanup (delete seller)
        Seller::delete($seller->getUserID());
    } // Not necessarily needed as should be tested through use in set...ID functions

    // TODO: Create test case/s for the delete() method
    public function testDeleteBundle()
    {
    }

}