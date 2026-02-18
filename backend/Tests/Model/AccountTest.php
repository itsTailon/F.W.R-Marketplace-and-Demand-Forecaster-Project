<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Account;

class AccountTest extends TestCase
{
    public function testCreate() {
        $account = Account::create(["email" => "testaccountcreate@example.com", "password" => "password", "accountType" => "seller"]);
        $accountLoaded = Account::load($account->getUserID());

        $this->assertEquals("testaccountcreate@example.com", $accountLoaded->getEmail());
        $this->assertEquals("seller", $accountLoaded->getAccountType());

        Account::delete($account->getUserID());
    }
}