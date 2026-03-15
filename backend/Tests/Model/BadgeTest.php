<?php

namespace TTE\App\Tests\Model;

// Global session array to run tests

// Class testing methods of the Badge class
use Exception;
use PHPUnit\Framework\TestCase;
use TTE\App\Model\Badge;
use TTE\App\Model\Bundle;
use TTE\App\Model\Customer;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchBadgeException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\NoSuchSellerException;

class BadgeTest extends TestCase{

    /**
     * Method for BadgeTest that tests the functionality of the create() of Badge
     */
    public function testCreateBadge() {

        // Badge fields as to test failed creation attempts prior to creating valid Badge
        $badgeFields= array("title" => "Test Badge",
                "iconURL" => "http://example.com/test.png",
                "subtitle" => "Test this badge {x} times.",
                "badgeDescription" => "Test this badge {x} times to earn badge.",
                "xBronze" => 1,
                "xSilver" => 5,
                "xGold" => 10,
        );

        // Iterate through fields and set to an invalid value to ensure Badge isn't created
        foreach($badgeFields as $fieldName => $fieldValue) {

            // Store previously held value
            $prevValue = $fieldValue;
            unset($badgeFields[$fieldName]);

            // Test create() method
            $thrown = false;
            try {
                $badge = Badge::create($badgeFields);
            } catch (MissingValuesException $e) {
                $thrown = true;
            }

            if (!$thrown) {
                // Check $badge holds a Badge object or not and confirm it did fail to create
                if (isset($badge)) {
                    Badge::delete($badge->getId());
                }


                // Fail test as no error was thrown
                $this->fail("Badge created with invalid input.");
            }

            // Return badge field to initial state
            $badgeFields[$fieldName] = $prevValue;
        }

        // Test string handling for title (with spaces)
        $prevValue = $badgeFields['title']; // Store value to return to
        $badgeFields['title'] = "       "; // Set value to empty string filled with spaces

        $thrown = false;
        try {
            $badge = Badge::create($badgeFields);
        } catch (MissingValuesException $e) {
            $thrown = true;
        } catch (DatabaseException|NoSuchCustomerException|NoSuchSellerException $e) {
            // Clean-up and give exception
            if (isset($badge)) {
                Badge::delete($badge->getId());
            }

            // Fail test but with message of wrong exception thrown than what expected
            $this->fail("Exception was thrown but wrong for test case.");

        }
        if (!$thrown) {
            // Clean-up
            if (isset($badge)) {
                Badge::delete($badge->getId());
            }

            // Fail test as no error was thrown
            $this->fail("Badge created with invalid input.");
        }

        $badgeFields['title'] = $prevValue; // Return value to previous


        // Test handling of empty strings passed for iconURL
        $prevValue = $badgeFields['iconURL']; // Store value to return to
        $badgeFields['iconURL'] = "       "; // Set value to empty string filled with spaces

        $thrown = false;
        try {
            $badge = Badge::create($badgeFields);
        } catch (MissingValuesException $e) {
            $thrown = true;
        } catch (DatabaseException|NoSuchCustomerException|NoSuchSellerException $e) {
            // Clean-up and give exception
            if (isset($badge)) {
                Badge::delete($badge->getId());
            }

            // Fail test but with message of wrong exception thrown than what expected
            $this->fail("Exception was thrown but wrong for test case.");

        }
        if (!$thrown) {
            // Clean-up
            if (isset($badge)) {
                Badge::delete($badge->getId());
            }

            // Fail test as no error was thrown
            $this->fail("Badge created with invalid input.");
        }

        $badgeFields['iconURL'] = $prevValue; // Return value to previous


        // Test passing of empty string to subtitle attribute
        $prevValue = $badgeFields['subtitle']; // Store value to return to
        $badgeFields['subtitle'] = "       "; // Set value to empty string filled with spaces

        $thrown = false;
        try {
            $badge = Badge::create($badgeFields);
        } catch (MissingValuesException $e) {
            $thrown = true;
        } catch (DatabaseException|NoSuchCustomerException|NoSuchSellerException $e) {
            // Clean-up and give exception
            if (isset($badge)) {
                Badge::delete($badge->getId());
            }

            // Fail test but with message of wrong exception thrown than what expected
            $this->fail("Exception was thrown but wrong for test case.");

        }
        if (!$thrown) {
            // Clean-up
            if (isset($badge)) {
                Badge::delete($badge->getId());
            }

            // Fail test as no error was thrown
            $this->fail("Badge created with invalid input.");
        }

        $badgeFields['subtitle'] = $prevValue; // Return value to previous


        // Testing passing of empty string into badge description attribute
        $prevValue = $badgeFields['badgeDescription']; // Store value to return to
        $badgeFields['badgeDescription'] = "       "; // Set value to empty string filled with spaces

        $thrown = false;
        try {
            $badge = Badge::create($badgeFields);
        } catch (MissingValuesException $e) {
            $thrown = true;
        } catch (DatabaseException|NoSuchCustomerException|NoSuchSellerException $e) {
            // Clean-up and give exception
            if (isset($badge)) {
                Badge::delete($badge->getId());
            }

            // Fail test but with message of wrong exception thrown than what expected
            $this->fail("Exception was thrown but wrong for test case.");

        }
        if (!$thrown) {
            // Clean-up
            if (isset($badge)) {
                Badge::delete($badge->getId());
            }

            // Fail test as no error was thrown
            $this->fail("Badge created with invalid input.");
        }

        $badgeFields['badgeDescription'] = $prevValue; // Return value to previous


        // Use valid fields for create()
        try {
            $badge = Badge::create($badgeFields);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        // Compare all fields of created badge in DB to those of the object
        $badge_db = Badge::load($badge->getId());
        foreach ($badgeFields as $fieldName => $fieldValue) {
            switch ($fieldName) {
                case "title":
                    $this->assertEquals($fieldValue, $badge_db->getTitle());
                    break;
                case "iconURL":
                    $this->assertEquals($fieldValue, $badge_db->getIconURL());
                    break;
                case "subtitle":
                    $this->assertEquals($fieldValue, $badge_db->getSubtitle());
                    break;
                case "badgeDescription":
                    $this->assertEquals($fieldValue, $badge_db->getBadgeDescription());
                    break;
                case "xBronze":
                    $this->assertEquals($fieldValue, $badge_db->getXBronze());
                    break;
                case "xSilver":
                    $this->assertEquals($fieldValue, $badge_db->getXSilver());
                    break;
                case "xGold":
                    $this->assertEquals($fieldValue, $badge_db->getXGold());
                    break;
            }
        }

        // Cleanup before ending test for create()
        Badge::delete($badge->getId());
    }

    /**
     * Testing method for updating attributes of badge in the DB
     */
    public function testUpdateBadge() {

        // Badge fields for creating badge
        $badgeFields= array("title" => "Test Badge",
            "iconURL" => "http://example.com/test.png",
            "subtitle" => "Test this badge {x} times.",
            "badgeDescription" => "Test this badge {x} times to earn badge.",
            "xBronze" => 1,
            "xSilver" => 5,
            "xGold" => 10,
        );

        // Create valid badge
        $badge = Badge::create($badgeFields);

        // Create customer for means of testings
        $customer = Customer::create(
            array("username" => "testingUser",
                "email" => "testingCust@gmail.com",
                "password" => "testingPassword!23")
        );


        // Test string handling for title (with spaces)
        $prevValue = $badge->getTitle(); // Store value to return to
        $badge->setTitle("       "); // Set value to empty string filled with spaces

        $thrown = false;
        try {
            $badge->update();
        } catch (MissingValuesException $e) {
            $thrown = true;
        } catch (DatabaseException|NoSuchCustomerException|NoSuchSellerException $e) {
            // Clean-up and give exception
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test but with message of wrong exception thrown than what expected
            $this->fail("Exception was thrown but wrong for test case.");

        }
        if (!$thrown) {
            // Clean-up
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test as no error was thrown
            $this->fail("Badge created with invalid input.");
        }

        $badge->setTitle($prevValue); // Return value to previous


        // Test handling of empty strings passed for iconURL
        $prevValue = $badge->getIconURL(); // Store value to return to
        $badge->setIconURL("       "); // Set value to empty string filled with spaces

        $thrown = false;
        try {
            $badge->update();
        } catch (MissingValuesException $e) {
            $thrown = true;
        } catch (DatabaseException|NoSuchCustomerException|NoSuchSellerException $e) {
            // Clean-up and give exception
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test but with message of wrong exception thrown than what expected
            $this->fail("Exception was thrown but wrong for test case.");

        }
        if (!$thrown) {
            // Clean-up
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test as no error was thrown
            $this->fail("Badge created with invalid input.");
        }

        $badge->setIconURL($prevValue); // Return value to previous


        // Test passing of empty string to subtitle attribute
        $prevValue = $badge->getSubtitle(); // Store value to return to
        $badge->setSubtitle("       "); // Set value to empty string filled with spaces

        $thrown = false;
        try {
            $badge->update();
        } catch (MissingValuesException $e) {
            $thrown = true;
        } catch (DatabaseException|NoSuchCustomerException|NoSuchSellerException $e) {
            // Clean-up and give exception
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test but with message of wrong exception thrown than what expected
            $this->fail("Exception was thrown but wrong for test case.");

        }
        if (!$thrown) {
            // Clean-up
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test as no error was thrown
            $this->fail("Badge created with invalid input.");
        }

        $badge->setSubtitle($prevValue); // Return value to previous


        // Testing passing of empty string into badge description attribute
        $prevValue = $badge->getBadgeDescription(); // Store value to return to
        $badge->setBadgeDescription("       "); // Set value to empty string filled with spaces

        $thrown = false;
        try {
            $badge->update();
        } catch (MissingValuesException $e) {
            $thrown = true;
        } catch (DatabaseException|NoSuchCustomerException|NoSuchSellerException $e) {
            // Clean-up and give exception
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test but with message of wrong exception thrown than what expected
            $this->fail("Exception was thrown but wrong for test case.");

        }
        if (!$thrown) {
            // Clean-up
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test as no error was thrown
            $this->fail("Badge created with invalid input.");
        }

        $badge->setBadgeDescription($prevValue);


        // Valid fields of different value to original badge fields
        $updatedBadgeFields = array(
            "title" => $badge->getTitle(),
            "iconURL" => $badge->getIconURL(),
            "subtitle" => "Change Subtitle",
            "badgeDescription" => "Change Badge Description",
            "xBronze" => $badge->getXBronze(),
            "xSilver" => $badge->getXSilver(),
            "xGold" => $badge->getXGold(),
        );

        // Set badge fields to updated badge fields
        $badge->setTitle($updatedBadgeFields["title"]);
        $badge->setIconURL($updatedBadgeFields["iconURL"]);
        $badge->setSubtitle($updatedBadgeFields["subtitle"]);
        $badge->setBadgeDescription($updatedBadgeFields["badgeDescription"]);
        $badge->setXBronze($updatedBadgeFields["xBronze"]);
        $badge->setXSilver($updatedBadgeFields["xSilver"]);
        $badge->setXGold($updatedBadgeFields["xGold"]);

        // Attempt update (with valid changes made)
        try {
            $badge->update();
        } catch (Exception $e) {
            $this->fail("Badge failed to create when should have.");
        }

        // Compare all fields of created badge in DB to those of the object
        $badge_db = Badge::load($badge->getId());
        foreach ($updatedBadgeFields as $fieldName => $fieldValue) {
            switch ($fieldName) {
                case "title":
                    $this->assertEquals($fieldValue, $badge_db->getTitle());
                    break;
                case "iconURL":
                    $this->assertEquals($fieldValue, $badge_db->getIconURL());
                    break;
                case "subtitle":
                    $this->assertEquals($fieldValue, $badge_db->getSubtitle());
                    break;
                case "badgeDescription":
                    $this->assertEquals($fieldValue, $badge_db->getBadgeDescription());
                    break;
                case "xBronze":
                    $this->assertEquals($fieldValue, $badge_db->getXBronze());
                    break;
                case "xSilver":
                    $this->assertEquals($fieldValue, $badge_db->getXSilver());
                    break;
                case "xGold":
                    $this->assertEquals($fieldValue, $badge_db->getXGold());
                    break;
            }
        }

        // Cleanup before ending test for update()
        Badge::delete($badge->getId());
        Customer::delete($customer->getUserID());

    }

    /**
     * Method that tests the load() method of the Badge class
     */
    public function testLoadBadge() {

        // Badge fields to create badge
        $badgeFields= array("title" => "Test Badge",
            "iconURL" => "http://example.com/test.png",
            "subtitle" => "Test this badge {x} times.",
            "badgeDescription" => "Test this badge {x} times to earn badge.",
            "xBronze" => 1,
            "xSilver" => 5,
            "xGold" => 10,
        );

        // Create valid badge
        $badge = Badge::create($badgeFields);

        // Create customer for means of testings
        $customer = Customer::create(
            array("username" => "testingUser",
                "email" => "testingCust@gmail.com",
                "password" => "testingPassword!23")
        );

        // Try loading non-existent badge (ID of -1 will never exist)
        $thrown = false;
        try {
            $attempted_badge = Badge::load(-1);
        } catch (NoSuchBadgeException $e) {
            $thrown = true;
        }
        if (!$thrown) {
            //Cleanup
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test
            $this->fail('Badge "successfully" loaded but badge ID was false.');
        }

        // Load valid badge and compare to ensure it is correct
        try {
            $db_badge = Badge::load($badge->getId());
        } catch (DatabaseException $e) {
            // Cleanup
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test
            $this->fail('Database Exception was thrown.');
        }

        $this->assertEquals($badge->getTitle(), $db_badge->getTitle());
        $this->assertEquals($badge->getIconURL(), $db_badge->getIconURL());
        $this->assertEquals($badge->getSubtitle(), $db_badge->getSubtitle());
        $this->assertEquals($badge->getBadgeDescription(), $db_badge->getBadgeDescription());
        $this->assertEquals($badge->getXBronze(), $db_badge->getXBronze());
        $this->assertEquals($badge->getXSilver(), $db_badge->getXSilver());
        $this->assertEquals($badge->getXGold(), $db_badge->getXGold());

        // Cleanup
        Badge::delete($badge->getId());
        Customer::delete($customer->getUserID());
    }

    /**
     * Test method existsWithID() for Badge object
     */
    public function testExistsWithID() {

        // Badge fields to create badge
        $badgeFields= array("title" => "Test Badge",
            "iconURL" => "http://example.com/test.png",
            "subtitle" => "Test this badge {x} times.",
            "badgeDescription" => "Test this badge {x} times to earn badge.",
            "xBronze" => 1,
            "xSilver" => 5,
            "xGold" => 10,
        );

        // Create badge
        $badge = Badge::create($badgeFields);

        // Create customer for means of testings
        $customer = Customer::create(
            array("username" => "testingUser",
                "email" => "testingCust@gmail.com",
                "password" => "testingPassword!23")
        );

        // Confirm "True" output if badge does exist
        $this->assertTrue(Badge::existsWithID($badge->getId()));

        // Delete and then test again for the same ID
        Badge::delete($badge->getId());
        $this->assertFalse(Badge::existsWithID($badge->getId()));

        // Final cleanup
        Customer::delete($customer->getUserID());
    }

    /**
     * Test method existsWithIDByTitle() for Badge object
     */
    public function testExistsWithIDByTitle() {

        // Badge fields to create badge
        $badgeFields= array("title" => "Test Badge",
            "iconURL" => "http://example.com/test.png",
            "subtitle" => "Test this badge {x} times.",
            "badgeDescription" => "Test this badge {x} times to earn badge.",
            "xBronze" => 1,
            "xSilver" => 5,
            "xGold" => 10,
        );

        // Create badge
        $badge = Badge::create($badgeFields);

        // Create customer for means of testings
        $customer = Customer::create(
            array("username" => "testingUser",
                "email" => "testingCust@gmail.com",
                "password" => "testingPassword!23")
        );

        try {
            // Confirm "True" output if badge does exist
            $this->assertTrue(Badge::existsWithIDByTitle($badge->getTitle()));

            // Delete and then test again for the same ID
            Badge::delete($badge->getId());
            $this->assertFalse(Badge::existsWithIDByTitle($badge->getTitle()));

        } catch (Exception $e) {
            Badge::delete($badge->getId());
        }
        // Final cleanup
        Customer::delete($customer->getUserID());
    }

    /**
     * Method that tests the loadByTitle() method of the Badge class
     */
    public function testLoadByTitle() {

        // Badge fields to create badge
        $badgeFields= array("title" => "Test Badge",
            "iconURL" => "http://example.com/test.png",
            "subtitle" => "Test this badge {x} times.",
            "badgeDescription" => "Test this badge {x} times to earn badge.",
            "xBronze" => 1,
            "xSilver" => 5,
            "xGold" => 10,
        );

        // Create valid badge
        $badge = Badge::create($badgeFields);

        // Create customer for means of testings
        $customer = Customer::create(
            array("username" => "testingUser",
                "email" => "testingCust@gmail.com",
                "password" => "testingPassword!23")
        );

        // Try loading non-existent badge
        $thrown = false;
        try {
            $attempted_badge = Badge::loadByTitle("Doesn't Exist");
        } catch (NoSuchBadgeException $e) {
            $thrown = true;
        }
        if (!$thrown) {
            //Cleanup
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test
            $this->fail('Badge "successfully" loaded by title that was false.');
        }

        try {
            $db_badge = Badge::loadByTitle($badge->getTitle());
        } catch (DatabaseException $e) {
            // Cleanup
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail
            $this->fail('Database Exception was thrown.');
        }

        // Load valid badge and compare to ensure it is correct
        $this->assertEquals($badge->getTitle(), $db_badge->getTitle());
        $this->assertEquals($badge->getIconURL(), $db_badge->getIconURL());
        $this->assertEquals($badge->getSubtitle(), $db_badge->getSubtitle());
        $this->assertEquals($badge->getBadgeDescription(), $db_badge->getBadgeDescription());
        $this->assertEquals($badge->getXBronze(), $db_badge->getXBronze());
        $this->assertEquals($badge->getXSilver(), $db_badge->getXSilver());
        $this->assertEquals($badge->getXGold(), $db_badge->getXGold());

        // Cleanup
        Badge::delete($badge->getId());
        Customer::delete($customer->getUserID());
    }

    /**
     * Method testing deleteByTitle() for Badge class
     */
    public function testBadgeDeleteByTitle() {
        // Badge fields to create badge
        $badgeFields= array("title" => "Test Badge",
            "iconURL" => "http://example.com/test.png",
            "subtitle" => "Test this badge {x} times.",
            "badgeDescription" => "Test this badge {x} times to earn badge.",
            "xBronze" => 1,
            "xSilver" => 5,
            "xGold" => 10,
        );

        // Create badge
        $badge = Badge::create($badgeFields);

        // Create customer for means of testings
        $customer = Customer::create(
            array("username" => "testingUser",
                "email" => "testingCust@gmail.com",
                "password" => "testingPassword!23")
        );

        // Confirm badge exists within the database
        $this->assertTrue(Badge::existsWithID($badge->getId()));

        // Run the deletion method for given badge and confirm it now doesn't exist
        Badge::deleteByTitle($badge->getTitle());
        $this->assertFalse(Badge::existsWithID($badge->getId()));

        // Attempting deleting badge that doesn't exist
        $thrown = false;
        try {
            Badge::delete($badge->getId());
        } catch (NoSuchBadgeException $e) {
            $thrown = true;
        } catch (DatabaseException $e) {
            $this->fail("Database Exception thrown when shouldn't have.");
        }
        if (!$thrown) {
            // Cleanup before failing test
            Badge::deleteByTitle($badge->getTitle());
            Customer::delete($customer->getUserID());

            $this->fail("Badge deletion method given a false ID yet didn't throw Exception.");
        }

        // Remaining cleanup (Customer)
        Customer::delete($customer->getUserID());

    }

    /**
     * Method testing delete() for Badge class
     */
    public function testBadgeDelete() {

        // Badge fields to create badge
        $badgeFields= array("title" => "Test Badge",
            "iconURL" => "http://example.com/test.png",
            "subtitle" => "Test this badge {x} times.",
            "badgeDescription" => "Test this badge {x} times to earn badge.",
            "xBronze" => 1,
            "xSilver" => 5,
            "xGold" => 10,
        );

        // Create badge
        $badge = Badge::create($badgeFields);

        // Create customer for means of testings
        $customer = Customer::create(
            array("username" => "testingUser",
                "email" => "testingCust@gmail.com",
                "password" => "testingPassword!23")
        );

        // Confirm badge exists within the database
        $this->assertTrue(Badge::existsWithID($badge->getId()));

        // Run the deletion method for given badge and confirm it now doesn't exist
        Badge::delete($badge->getId());
        $this->assertFalse(Badge::existsWithID($badge->getId()));

        // Attempting deleting badge that doesn't exist
        $thrown = false;
        try {
            Badge::delete($badge->getId());
        } catch (NoSuchBadgeException $e) {
            $thrown = true;
        } catch (DatabaseException $e) {
            $this->fail("Database Exception thrown when shouldn't have.");
        }
        if (!$thrown) {
            // Cleanup before failing test
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            $this->fail("Badge deletion method given a false ID yet didn't throw Exception.");
        }

        // Remaining cleanup (Customer)
        Customer::delete($customer->getUserID());

    }
}

