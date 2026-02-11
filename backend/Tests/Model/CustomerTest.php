<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Customer;

class CustomerTest extends TestCase
{
    public function testCreateCustomer()
    {
        $testCustomer = Customer::create(["email" => "testcreatecustomer@example.com", "username" => "Ex Customer Name", "password" => "password"]);
        $testLoadedCustomer = Customer::load($testCustomer->getUserID());

        $this->assertEquals($testCustomer->getUserID(), $testLoadedCustomer->getUserID());
        $this->assertEquals($testCustomer->getUsername(), $testLoadedCustomer->getUsername());
        $this->assertEquals($testCustomer->getEmail(), $testLoadedCustomer->getEmail());

        Customer::delete($testCustomer->getUserID());
    }
}