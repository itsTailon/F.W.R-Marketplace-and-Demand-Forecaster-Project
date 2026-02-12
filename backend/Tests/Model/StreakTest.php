<?php

namespace TTE\App\Tests\Model;

use Exception;
use PHPUnit\Framework\TestCase;
use \DateTimeImmutable;
use TTE\App\Model\Customer;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchStreakException;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\NoSuchSellerException;
use TTE\App\Model\Streak;
use TTE\App\Model\StreakStatus;
use function PHPUnit\Framework\assertSame;

// Global for session to run test
$_SESSION = array();


// Class testing functions of the Streak class
class StreakTest extends TestCase
{

    /**
     * @throws DatabaseException|NoSuchCustomerException
     */
    public function testUpdateStreak()
    {

        // Fields for Customer object for the purpose of the test
        $customerFields = array(
            "username" => "testingUser",
            "email" => "testCust@gmail.com",
            "password" => "testingPassword123",
        );

        // Creating required Customer
        $customer = Customer::create($customerFields);

        // Create associative array with fields required as parameter for update()
        $fields =
            array(
                "streakStatus" => streakStatus::Active,
                "customerID" => $customer->getUserID(),
            );

        // Creating Streak that is to then be updated
        try {
            $streak = Streak::create($fields);
        } catch (Exception $e) {
            Customer::delete($customer->getUserID());

            // fail the test
            $this->fail($e->getMessage());
        }


        // Change values for $streak to a set of valid values
        $streak->setStatus(StreakStatus::Inactive);
        $endDate = new DateTimeImmutable("now");
        $streak->setEndDate($endDate);

        // Attempting to update streak
        try {
            $streak->update();
        } catch (DatabaseException|NoSuchStreakException $e) {
            // Cleanup prior to throwing failure
            Streak::delete($streak->getID());
            Customer::delete($customer->getUserID());

            // Fail test
            $this->fail($e->getMessage());
        }

        // Get fresh object from the database
        $db_streak = Streak::load($streak->getID());

        // Comparing values of object stored in DB to what should be
        $this->assertEquals($streak->getStatus(), $db_streak->getStatus());
        $this->assertEquals($streak->getCustomerID(), $db_streak->getCustomerID());
        $this->assertEquals($streak->getStartDate()->format("Y-m-d H:i:s"), $db_streak->getStartDate()->format("Y-m-d H:i:s"));
        $this->assertEquals($streak->getEndDate()->format("Y-m-d H:i:s"), $db_streak->getEndDate()->format("Y-m-d H:i:s"));

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

        // Create associative array with fields required as parameter for create()
        $fields =
            array(
                "streakStatus" => streakStatus::Active,
                "customerID" => $customer->getUserID(),
            );

        // Create required streak and get DB version
        $streak = Streak::create($fields);
        $db_streak = Streak::load($streak->getID());

        try {
            // Load streak and compare to existing streak object
            self::assertEquals($streak->getStatus(), $streak->getStatus());
            self::assertEquals($streak->getCustomerID(), $db_streak->getCustomerID());
            self::assertEquals($streak->getStartDate()->format("Y-m-d H:i:s"), $db_streak->getStartDate()->format("Y-m-d H:i:s"));

            if($streak->getEndDate() !== null) {
                self::assertEquals($streak->getEndDate()->format("Y-m-d H:i:s"), $db_streak->getEndDate()->format("Y-m-d H:i:s"));
            } else {
                self::assertEquals($streak->getEndDate(), $db_streak->getEndDate());
            }
        } catch (DatabaseException|NoSuchStreakException $e) {
            // Cleanup
            Streak::delete($streak->getID());
            Customer::delete($customer->getUserID());

            $this->fail($e->getMessage());
        }

        // Try loading non-existent streak (ID of -1 will never exist)
        // Ensure that such results in a DatabaseException being thrown
        $thrown = false;
        try {
            Streak::load(-1);
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
     * @throws DatabaseException|NoSuchCustomerException|MissingValuesException|NoSuchSellerException
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

        // Create associative array with fields required as parameter for create()
        $fields =
            array(
                "streakStatus" => streakStatus::Active,
                "customerID" => $customer->getUserID(),
            );

        // Create required streak
        $streak = Streak::create($fields);

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

        // Create associative array with fields required as parameter for create()
        $fields =
            array(
                "streakStatus" => streakStatus::Active,
                "customerID" => $customer->getUserID(),
            );

        // Create required streak
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

        // Delete test streak and check that the streak no longer exists
        Streak::delete($streak->getID());
        $this->assertFalse(Streak::existsWithID($streak->getID()));
    }

}
