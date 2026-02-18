<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase {
    /**
     * @throws DatabaseException|NoSuchAccountException|MissingValuesException
     */
    public function testDeleteAccount()
    {
        // Account fields for create() methods
        $accountFields = array(
            "userID" => "2020",
            "email" => "testAcc@gmail.com",
            "accountType" => "customer",
        );

        // Creating required Account object
        $account = Account::create($accountFields);

        // Delete account
        Account::delete($account->getUserID());
    }
}
