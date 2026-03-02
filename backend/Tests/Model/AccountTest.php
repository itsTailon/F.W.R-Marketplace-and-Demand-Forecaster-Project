<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Account;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\IncorrectPasswordException;

class AccountTest extends TestCase
{
    public function testUpdate(): void {
        // Create account for testing
        $account = Account::create(["email" => "testaccountcreate2@example.com", "password" => "password", "accountType" => "seller"]);

        // Change value of account
        $account->setEmail("testaccountcreate2Edited@example.com");

        // Test update method
        $account->update();

        // See if update worked by loading fresh Account object
        $accountAfterUpdate = Account::load($account->getUserID());
        $this->assertEquals($accountAfterUpdate->getEmail(), $account->getEmail());

        // Cleanup
        Account::delete($account->getUserID());
    }

    public function testUpdatePassword(): void {
        // Create account for testing
        $account = Account::create(["email" => "testaccountcreate3@example.com", "password" => "password", "accountType" => "seller"]);

        // Test with invalid current password
        $thrown = false;
        try {
            $account->updatePassword("wrongpassword", "newpassword");
        } catch (IncorrectPasswordException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Test with valid password
        $account->updatePassword("password", "newpassword");
        $this->assertTrue(Authenticator::authenticateUser($account->getEmail(), "newpassword", false));

        // Cleanup
        Account::delete($account->getUserID());
    }

    public function testCreate()
    {
        $account = Account::create(["email" => "testaccountcreate@example.com", "password" => "password", "accountType" => "seller"]);
        $accountLoaded = Account::load($account->getUserID());
        $this->assertEquals("testaccountcreate@example.com", $accountLoaded->getEmail());
        $this->assertEquals("seller", $accountLoaded->getAccountType());

        Account::delete($account->getUserID());
    }

    public function testLoad(): void
    {
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

    public function testExistsWithID(): void
    {
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

    public function testDeleteAccount(): void
    {
        // Create account to test deletion
        $account = Account::create([
            'password' => 'password',
            'email' => 'testDeleteAccount@example.com',
            'accountType' => 'seller',
        ]);

        // Ensure the account exists
        $this->assertTrue(Account::existsWithID($account->getUserID()));

        // Delete account
        Account::delete($account->getUserID());

        // Ensure it no longer exists
        $this->assertFalse(Account::existsWithID($account->getUserID()));

        // Ensure loading it now throws an exception
        $this->expectException(DatabaseException::class);
        Account::load($account->getUserID());
    }
}
