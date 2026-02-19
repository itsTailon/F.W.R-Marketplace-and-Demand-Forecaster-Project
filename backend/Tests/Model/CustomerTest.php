<?php


namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Customer;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\MissingValuesException;

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
}
