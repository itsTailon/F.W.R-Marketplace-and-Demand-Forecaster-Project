<?php


namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Badge;
use TTE\App\Model\BadgeTier;
use TTE\App\Model\Bundle;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\Customer;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\ImpactMetric;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchBadgeException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\Reservation;
use TTE\App\Model\ReservationStatus;
use TTE\App\Model\Seller;

// Global for session to run test
$_SESSION = array();


// Class testing functions of the Customer class
class CustomerTest extends TestCase {

    public function testLoad(): void {
        // Create customer to test loading
        $customer1 = Customer::create([
            'username' => 'testLoadCustomer1',
            'password' => 'password',
            'email' => 'testLoadCustomer1@example.com',
        ]);

        // Test loading of actual customer record (i.e. valid ID)
        $this->assertEquals($customer1, Customer::load($customer1->getUserID()));

        // Cleanup
        Customer::delete($customer1->getUserID());

        // Ensure that the method throws a DatabaseException if no customer exists with the given ID
        $thrown = false;
        try {
            Customer::load($customer1->getUserID()); // $customer1 was deleted, so its ID is no longer valid
        } catch (DatabaseException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testExistsWithID(): void {
        // Create customer to test loading
        $customer1 = Customer::create([
            'username' => 'testLoadCustomer1',
            'password' => 'password',
            'email' => 'testLoadCustomer1@example.com',
        ]);

        // Test method on valid customer ID
        $this->assertTrue(Customer::existsWithID($customer1->getUserID()));

        // Cleanup
        Customer::delete($customer1->getUserID());

        // Test method on invalid (non-existent) customer ID
        $this->assertFalse(Customer::existsWithID($customer1->getUserID())); // $customer1 was deleted, so its ID is no longer valid
    }

    public function testDeleteCustomer(): void {
        // Create customer to test deletion
        $customer = Customer::create([
            'username' => 'testDeleteCustomer',
            'password' => 'password',
            'email' => 'testDeleteCustomer@example.com',
        ]);

        // Ensure customer exists before deletion
        $this->assertTrue(Customer::existsWithID($customer->getUserID()));

        // Delete the customer
        Customer::delete($customer->getUserID());

        // Ensure customer no longer exists
        $this->assertFalse(Customer::existsWithID($customer->getUserID()));

        // Ensure loading deleted customer throws exception
        $this->expectException(DatabaseException::class);
        Customer::load($customer->getUserID());
    }

    public function testGetImpactMetric() {
        // Create customer for testing
        $customer = Customer::create([
            'username' => 'testCustomer1',
            'password' => 'password',
            'email' => 'testCustomer1@example.com',
        ]);

        // Create seller for testing
        $seller = Seller::create(array(
            "email" => "testSeller1@example.com",
            "password" => "testingPassword123",
            "name" => "Test Name",
            "address" => "34 Testing Street",
        ));

        // Create bundle for testing
        $bundle = Bundle::create([
            "bundleStatus" => BundleStatus::Available,
            "title" => "Test Bundle Title",
            "details" => "Test Bundle Details",
            "rrp" => 599,
            "discountedPrice" => 299,
            "sellerID" => $seller->getUserID(),
            "quantity" => 1,
        ]);

        // Create reservation for testing
        $reservation = Reservation::create([
            'purchaserID' => $customer->getUserID(),
            'bundleID' => $bundle->getID(),
            'status' => ReservationStatus::Active,
            'claimCode' => 'abcxyz'
        ]);

        // Currently, both personal impact metric calculations should equal zero
        $this->assertEquals(0, $customer->getImpactMetric(ImpactMetric::Bundles_Collected));
        $this->assertEquals(0.0, $customer->getImpactMetric(ImpactMetric::CO2_Saved)); // Float

        // Mark reservation as completed (to accurately test calculations)
        Reservation::markCollected($reservation->getID());

        // Following a single bundle being collected, the metric calculations should have changed:
        //  - Expected Bundles_Collected = 1
        //  - Expected CO2_Saved > 0 (i.e. greater than prev. value)
        $this->assertEquals(1, $customer->getImpactMetric(ImpactMetric::Bundles_Collected));
        $this->assertGreaterThan(0.0, $customer->getImpactMetric(ImpactMetric::CO2_Saved));

        // Cleanup
        Reservation::delete($reservation->getID());
        Bundle::delete($bundle->getID());
        Seller::delete($seller->getUserID());
        Customer::delete($customer->getUserID());
    }

    /**
     * Method testing the loading of badges attached to Customer
     */
    public function testLoadBadges() {
        // Create customer for testing
        $customer = Customer::create([
            'username' => 'testCustomer1',
            'password' => 'password',
            'email' => 'testCustomer1@example.com',
        ]);

        // Badge fields to create badge
        $badgeFields= array("title" => "Test Badge",
            "subtitle" => "Test this badge {x} times.",
            "badgeDescription" => "Test this badge {x} times to earn badge.",
            "xBronze" => 1,
            "xSilver" => 5,
            "xGold" => 10,
        );

        // Create badge
        $badge = Badge::create($badgeFields);

        // Add badge to customer (manually) for tests
        try {
            $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO customer_badge (customerID, badgeID, tier, progress) VALUES (:customerID, :badgeID, :tier, :progress)");
            $stmt->execute([":customerID" => $customer->getUserID(), ":badgeID" => $badge->getId(), ":tier" => BadgeTier::Silver->value, ":progress" => 8]);
        } catch (\PDOException $e) {
            // Cleanup
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            // Fail test
            $this->fail($e->getMessage());
        }

        try {
            $badges = Customer::loadBadges($customer->getUserID());
        } catch (\Exception $e) {
            // Cleanup
            Badge::delete($badge->getId());
            Customer::delete($customer->getUserID());

            $this->fail($e->getMessage());
        }

        // Compare values within array returned for badge passed in (as core badges may or may not be present
        $testBadge = $badges[$badge->getTitle()];

        $this->assertTrue($testBadge["badgeID"] == $badge->getID());
        $this->assertTrue($testBadge["tier"] == BadgeTier::Silver->value);
        $this->assertTrue($testBadge["progress"] == 8);

        // Cleanup
        Badge::delete($badge->getId());
        Customer::delete($customer->getUserID());
    }
}
