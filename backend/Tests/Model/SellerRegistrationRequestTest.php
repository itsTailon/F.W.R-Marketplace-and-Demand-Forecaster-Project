<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Seller;
use TTE\App\Model\SellerRegistrationRequest;

class SellerRegistrationRequestTest extends TestCase
{
    public function testProcessRequest() {
        $willBeRejected = SellerRegistrationRequest::create(["sellerName" => "Ex Seller Request (Will Be Rejected)", "sellerAddress" => "123 Testing Street", "sellerEmail" => "willberejected@test.com", "password" => "password", "details" => "A seller created for testing purposes that will be rejected"]);
        $willBeAccepted = SellerRegistrationRequest::create(["sellerName" => "Ex Seller Request (Will Be Accepted)", "sellerAddress" => "123 Testing Street", "sellerEmail" => "willbeaccepted@test.com", "password" => "password", "details" => "A seller created for testing purposes that will be accepted"]);

        $rejectedSeller = $willBeRejected->processRequest(false);
        $acceptedSeller = $willBeAccepted->processRequest(true);

        // We must check that willBeAccepted has been created with all the right details
        $this->assertEquals("Ex Seller Request (Will Be Accepted)", $acceptedSeller->getName());
        $this->assertEquals("123 Testing Street", $acceptedSeller->getAddress());
        $this->assertEquals("willbeaccepted@test.com", $acceptedSeller->getEmail());
        $this->assertTrue(Seller::existsWithID($acceptedSeller->getUserID()));

        // We must check that willBeRejected has not been created
        $this->assertNull($rejectedSeller);

        Seller::delete($acceptedSeller->getUserID());
        SellerRegistrationRequest::delete($willBeAccepted->getID());
        SellerRegistrationRequest::delete($willBeRejected->getID());
    }
}