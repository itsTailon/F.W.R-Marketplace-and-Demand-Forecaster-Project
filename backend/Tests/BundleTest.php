<?php

namespace TTE\App\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\Customer;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\Seller;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\NoSuchSellerException;
use TTE\App\Model\NoSuchBundleException;

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
            $this->fail();
        }

        // Change values for $bundle
        $bundle->setStatus(BundleStatus::Reserved);
        $bundle->setPurchaserID($customer->getUserID());
        $bundle->setTitle("Testing Updating Method");
        $bundle->setRrpGBX(700);

        // Attempting to update bundle
        $thrown = false;
        try {
            $bundle->update();
        } catch (DatabaseException|NoSuchBundleException $e) {
            $thrown = true;
        }
        if (!$thrown) {
            $this->fail();
        }

        // TODO: get fresh obj from DB and compare to new values (equality)

        // If successful update, confirm and do cleanup
        Seller::delete($seller->getUserID());
        Customer::delete($customer->getUserID());
        Bundle::delete($bundle->getID());

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
        for ($i = 0; $i < count($fields) - 1; $i++) {
            // Storing previous value of field and updating it
            $prevValue = $fields[$i]->getValue();
            $fields[$i]->setValue(null);

            // Test create() function
            $thrown = false;
            try {
                $bundle = Bundle::create($fields);

                // Add created bundle's ID to cleanup array
                $cleanupBundles[] = $bundle->getID();
            } catch (MissingValuesException $e) {
                $thrown = true;
            }
            if (!$thrown) {
                $this->fail();
            }

            // Return $fields to initial state
            $fields[$i]->setValue($prevValue);
        }

        // Test handling when it comes to strings for title and bundle details being empty spaces
        $prevValue = $fields['title']->getValue(); // Store value to return to
        $fields['title']->setValue("       "); // Set value to empty string filled with spaces

        $thrown = false;
        try {
            $bundle = Bundle::create($fields);
            $cleanupBundles[] = $bundle->getID();
        } catch (MissingValuesException $e) {
            $thrown = true;
        }
        if (!$thrown) {
            $this->fail();
        }

        $fields['title']->setValue($prevValue); // Return value to previous

        // Repeat for bundle details
        $prevValue = $fields['details']->getValue();
        $fields['details']->setValue("         ");

        $thrown = false;
        try {
            Bundle::create($fields);
            $cleanupBundles[] = $bundle->getID();
        } catch (MissingValuesException $e) {
            $thrown = true;
        }
        if (!$thrown) {
            $this->fail();
        }

        $fields['details']->setValue($prevValue);

        // Apply creation method and check that Bundle is produced
        $bundle = Bundle::create($fields);
        $cleanupBundles[] = $bundle->getID();
        $this->assertInstanceOf("Bundle", $bundle);

        // Check $bundle attributes and ensure all hold appropriate values
        foreach ($fields as $key => $value) {
            switch ($key) {
                case "bundleStatus":
                    $this->assertEquals($value, $bundle->getBundleStatus()->getValue());
                    break;
                case "title":
                    $this->assertEquals($value, $bundle->getBundleTitle());
                    break;
                case "details":
                    $this->assertEquals($value, $bundle->getBundleDetails());
                    break;
                case "rrp":
                    $this->assertEquals($value, $bundle->getBundleRrp());
                    break;
                case "discountedPrice":
                    $this->assertEquals($value, $bundle->getBundleDiscountedPrice());
                    break;
                case "sellerID":
                    $this->assertEquals($value, $bundle->getBundleSellerID());
                    break;
                case "purchaserID":
                    $this->assertEquals($value, $bundle->getBundlePurchaserID());
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
    public function testLoadBundle()
    {
    }

    // TODO: Create test case/s for the existsWithID() method
    public function testExistsWithID()
    {
    } // Not necessarily needed as should be tested through use in set...ID functions

    // TODO: Create test case/s for the delete() method
    public function testDeleteBundle()
    {
    }


}