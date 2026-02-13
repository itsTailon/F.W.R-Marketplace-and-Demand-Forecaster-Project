<?php

namespace TTE\App\Tests\Model;

use Exception;
use PHPUnit\Framework\TestCase;
use \DateTimeImmutable;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\Customer;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchBundleException;
use TTE\App\Model\NoSuchStreakException;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\NoSuchSellerException;
use TTE\App\Model\Seller;
use TTE\App\Model\Streak;
use TTE\App\Model\StreakStatus;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEquals;
use function PHPUnit\Framework\assertSame;

// Global for session to run test
$_SESSION = array();


// Class testing functions of the Streak class
class StreakTest extends TestCase
{

    /**
     * @throws DatabaseException|NoSuchCustomerException|MissingValuesException
     * @throws NoSuchSellerException
     */
    public function testUpdateStreak()
    {
        // Fields for Seller object for the purpose of the test
        $sellerFields = array(
            "email" => "test@gmail.com",
            "password" => "testingPassword123",
            "name" => "Test Name",
            "address" => "34 Testing Street",
        );

        // Fields for Customer object for the purpose of the test
        $customerFields = array(
            "username" => "testingUser",
            "email" => "testCust@gmail.com",
            "password" => "testingPassword123",
        );

        // Creating required Seller
        $seller = Seller::create($sellerFields);
        // Creating required Customer
        $customer = Customer::create($customerFields);

        // Get streak for given customer that should have been created when customer was made
        $streak = $customer->getStreak();

        if ($streak == null) {
            Customer::delete($customer->getUserID());

            // fail the test
            $this->fail();
        }

        // Create associative array with fields required as parameter for create() of bundle
        $fields =
            array(
                "bundleStatus" => BundleStatus::Available,
                "title" => "Test Bundle Title",
                "details" => "Test Bundle Details",
                "rrp" => 599,
                "discountedPrice" => 299,
                "sellerID" => $seller->getUserID(),
                "purchaserID" => null
            );

        // Create successful purchase of bundle ot extend/update streak
        $bundle = Bundle::create($fields);

        // Update bundle to show that it has been purchased
        $bundle->setStatus(BundleStatus::Collected);
        $bundle->setPurchaserID($customer->getUserID());

        try {
            $bundle->update();
        } catch (DatabaseException|MissingValuesException|NoSuchBundleException|NoSuchCustomerException|NoSuchStreakException $e) {
            Streak::delete($customer->getStreak()->getID());
            Customer::delete($customer->getUserID());
        }

        // Get streak from database
        $db_streak = $customer->getStreak();

        // Check that streak has been changed
        $this->assertNotEquals($db_streak->getStartDate(), $streak->getStartDate());
        $this->assertNotEquals($db_streak->getCurrentWeekStart(), $streak->getCurrentWeekStart());
        $this->assertNotEquals($db_streak->getEndDate(), $streak->getEndDate());

        // If successful update, confirm and do cleanup
        Streak::delete($streak->getID());
        Customer::delete($customer->getUserID());
    }

    /**
     * Method that tests that all appropriate exceptions are thrown and Streak creation works on code and db front
     * @throws DatabaseException|NoSuchCustomerException|MissingValuesException
     */
    public function testCreateStreak()
    {

        // Customer fields for create() methods
        $customerFields = array(
            "username" => "testingUser",
            "email" => "testCust@gmail.com",
            "password" => "testingPassword123",
        );

        // Creating required Customer object
        $customer = Customer::create($customerFields);

        // Create associative array with fields required as parameter for create()
        $fields =
            array(
                "streakStatus" => streakStatus::Active,
                "customerID" => $customer->getUserID(),
            );

        // Iterate through $fields array and unset different values to test functionality
        foreach($fields as $key => $value) {
            // Storing previous value of field and updating it
            $prevValue = $value;
            unset($fields[$key]);

            // Test create() function
            $thrown = false;
            try {
                $streak = Streak::create($fields);

            } catch (MissingValuesException $e) {
                $thrown = true;
            }
            if (!$thrown) {
                // Cleanup if streak fails to create
                Customer::delete($customer->getUserID());

                // Force failure as error not thrown as should
                $this->fail();
            }

            // Return $fields to initial state
            $fields[$key] = $prevValue;
        }

        // Apply creation method and check that Streak is produced
        $streak = Streak::create($fields);

        // Check $streak attributes and ensure all hold appropriate values
        foreach ($fields as $key => $value) {
            switch ($key) {
                case "streakStatus":
                    $this->assertEquals($value, $streak->getStatus());
                    break;
                case "customerID":
                    $this->assertEquals($value, $streak->getCustomerID());
                    break;
            }
        }

        // Cleanup streak
        Streak::delete($streak->getID());
        // Remove test user
        Customer::delete($customer->getUserID());
    }

    /**
     * @throws DatabaseException|NoSuchCustomerException|MissingValuesException
     */
    public function testLoadStreak() {
        // Customer fields for create() methods
        $customerFields = array(
            "username" => "testingUser",
            "email" => "testCust@gmail.com",
            "password" => "testingPassword123",
        );

        // Creating required Customer object
        $customer = Customer::create($customerFields);

        // Check that streak was, in turn, created
        $streak = $customer->getStreak();
        if ($streak == null) {
            Customer::delete($customer->getUserID());

            $this->fail();
        }

        // Try to also load the streak using its own ID
        try {
            $db_streak = Streak::load($streak->getID());
        } catch (Exception $e) {
            Streak::delete($streak->getID());
            Customer::delete($customer->getUserID());

            $this->fail();
        }

        // Compare values within each loaded streak
        self:assertEquals($db_streak->getID(), $streak->getID());
        self::assertEquals($db_streak->getStartDate(), $streak->getStartDate());
        self::assertEquals($db_streak->getCurrentWeekStart(), $streak->getCurrentWeekStart());
        self::assertEquals($db_streak->getEndDate(), $streak->getEndDate());

        // Try loading non-existent streak (ID of -1 will never exist)
        // Ensure that such results in a DatabaseException being thrown
        $thrown = false;
        try {
            $failed_streak = Streak::load(-1);
        } catch (Exception $e) {
            $thrown = true;
        }
        if (!$thrown) {
            $this->fail();
        }

        // Cleanup
        Streak::delete($streak->getID());
        Customer::delete($customer->getUserID());
    }

    /**
     * @throws DatabaseException|NoSuchCustomerException|MissingValuesException
     */
    public function testExistsWithID() {
        // Customer fields for create() methods
        $customerFields = array(
            "username" => "testingUser",
            "email" => "testCust@gmail.com",
            "password" => "testingPassword123",
        );

        // Creating required Customer object
        $customer = Customer::create($customerFields);

        // Create required streak
        $streak = $customer->getStreak();

        // Streak should exist as it has just been created
        self::assertTrue(Streak::existsWithID($streak->getID()));

        // Delete streak
        Streak::delete($streak->getID());
        // Should now be false, as streak has been deleted
        self::assertFalse(Streak::existsWithID($streak->getID()));

        // Cleanup (delete customer)
        Customer::delete($customer->getUserID());
    }

    /**
     * @throws DatabaseException|NoSuchCustomerException|MissingValuesException
     */
    public function testDeleteStreak()
    {
        // Customer fields for create() methods
        $customerFields = array(
            "username" => "testingUser",
            "email" => "testCust@gmail.com",
            "password" => "testingPassword123",
        );

        // Creating required Customer object
        $customer = Customer::create($customerFields);

        // Create required streak
        $streak = $customer->getStreak();

        // Delete test streak and check that the streak no longer exists
        Streak::delete($streak->getID());
        $this->assertFalse(Streak::existsWithID($streak->getID()));
    }

}
