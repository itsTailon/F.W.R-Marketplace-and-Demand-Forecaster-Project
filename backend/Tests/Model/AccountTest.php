<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Account;
use TTE\App\Model\DatabaseException;

class AccountTest extends TestCase {

    public function testLoad(): void {
        // Create account to test loading
        $account = Account::create([
            'password' => 'password',
            'email' => 'testLoadAccount@example.com',
            'accountType' => 'seller', // Account type not important for the purposes of this test
        ]);

        // Test loading of actual account record (i.e. valid ID)
        $this->assertEquals($account, Account::load($account->getUserID()));

        // Cleanup
        Account::delete($account->getUserID());

        // Ensure that the method throws a DatabaseException if no account exists with the given ID
        $thrown = false;
        try {
            Account::load($account->getUserID()); // $account was deleted, so its ID is no longer valid
        } catch (DatabaseException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testExistsWithID(): void {
        // Create account to test loading
        $account = Account::create([
            'password' => 'password',
            'email' => 'testLoadAccount@example.com',
            'accountType' => 'seller', // Account type not important for the purposes of this test
        ]);

        // Test method on valid account ID
        $this->assertTrue(Account::existsWithID($account->getUserID()));

        // Cleanup
        Account::delete($account->getUserID());

        // Test method on invalid (non-existent) account ID
        $this->assertFalse(Account::existsWithID($account->getUserID())); // $account was deleted, so its ID is no longer valid
    }


}