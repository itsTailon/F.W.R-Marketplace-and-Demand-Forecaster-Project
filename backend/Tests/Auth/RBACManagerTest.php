<?php

namespace TTE\App\Tests\Auth;

use PHPUnit\Framework\TestCase;
use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Auth\NoSuchRoleException;
use TTE\App\Auth\RBACManager;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\NoSuchAccountException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\Seller;
use ValueError;

$_SESSION = array();

class RBACManagerTest extends TestCase
{

    // Note that RBACManager::isCurrentUserPermitted() is not tested, as
    // it can be seen as a mere alias for RBACManager::isUserPermitted()
    // with the userID parameter populated with the return value of
    // Authenticator::getCurrentUser()->getUserID(), which will already
    // be unit tested in isolation.

    public function testCreateRole(): void
    {
        /*
         * Things to test:
         * - if title too long, ValueError is thrown
         * - with valid title: role is actually inserted
         */

        // Test title length limit (invalid)
        $title = str_repeat("x", 129); // 128 should be the maximum permissible value, so 129+ is invalid
        $this->expectException(ValueError::class);
        RBACManager::createRole($title);

        // Test that role (with valid title) is inserted
        RBACManager::createRole("testRoleName");
        $this->assertTrue(RBACManager::roleExists("testRoleName"));

        // Cleanup
        RBACManager::deleteRole("testRoleName");
        RBACManager::deleteRole($title);
    }

    public function testDeleteRole(): void
    {
        /*
         * Things to test:
         * - if role exists, it is successfully deleted
         * - if role does not exist, a NoSuchRoleException is thrown.
         */

        // Setup (create role)
        $roleTitle = "aRoleWouldNeverExistWithThisTitle";
        RBACManager::createRole($roleTitle);

        // Skip test if role was not created
        if (!RBACManager::roleExists($roleTitle)) {
            $this->markTestSkipped('Test role could not be created, so this test must be skipped (deletion cannot be tested)');
        }

        // Test deletion
        RBACManager::deleteRole($roleTitle); // Naturally handles cleanup
        $this->assertFalse(RBACManager::roleExists($roleTitle));

        // Test for NoSuchRoleException (role is now deleted)
        $this->expectException(NoSuchRoleException::class);
        RBACManager::deleteRole($roleTitle);

    }

    public function testCreatePermission(): void
    {
        /*
         * Things to test:
         * - if title too long, ValueError is thrown
         * - with valid title: permission is actually inserted
         */

        // Test title length limit (invalid)
        $titleInvalid = str_repeat("x", 129); // 128 should be the maximum permissible value, so 129+ is invalid
        $this->expectException(ValueError::class);
        RBACManager::createPermission($titleInvalid);

        // Test that permission (with valid title) is inserted
        $titleValid = "aPermissionWouldNeverExistWithThisTitle";
        RBACManager::createPermission($titleValid);
        $this->assertTrue(RBACManager::roleExists($titleValid));

        // Cleanup
        RBACManager::deleteRole($titleValid);
        RBACManager::deleteRole($titleInvalid);
    }

    public function testDeletePermission(): void
    {
        /*
         * Things to test:
         * - if permission exists, it is successfully deleted
         * - if permission does not exist, a NoSuchPermissionException is thrown.
         */

        // Setup (create permission)
        $permissionTitle = "aPermissionWouldNeverExistWithThisTitle";
        RBACManager::createPermission($permissionTitle);

        // Skip test if permission was not created
        if (!RBACManager::permissionExists($permissionTitle)) {
            $this->markTestSkipped('Test permission could not be created, so this test must be skipped (deletion cannot be tested)');
        }

        // Test deletion
        RBACManager::deletePermission($permissionTitle); // Naturally handles cleanup
        $this->assertFalse(RBACManager::permissionExists($permissionTitle));

        // Test for NoSuchPermissionException (permission is now deleted)
        $this->expectException(NoSuchPermissionException::class);
        RBACManager::deletePermission($permissionTitle);
    }

    public function testRoleExists(): void
    {
        /*
         * Things to test:
         * - if role does not exist, it returns false
         * - if role does exist, it returns true
         */

        // Test for role that does not exist
        $this->assertFalse(RBACManager::roleExists("aRoleWouldNeverExistWithThisTitle"));

        // Test for role that does exist

        // Create role to test
        RBACManager::createRole("aRoleWouldNeverExistWithThisTitle");

        // Perform test
        $this->assertTrue(RBACManager::roleExists("aRoleWouldNeverExistWithThisTitle"));

        // Cleanup
        RBACManager::deleteRole("aRoleWouldNeverExistWithThisTitle");
    }

    public function testPermissionExists(): void
    {
        /*
         * Things to test:
         * - if permission does not exist, it returns false
         * - if permission does exist, it returns true
         */

        // Test for permission that does not exist
        $title = "aPermissionWouldNeverExistWithThisTitle";
        $this->assertFalse(RBACManager::permissionExists($title));

        // Test for permission that does exist

        // Create permission to test
        RBACManager::createPermission($title);

        // Perform test
        $this->assertTrue(RBACManager::permissionExists($title));

        // Cleanup
        RBACManager::deletePermission($title);
    }

    public function testAssignPermissionToRole(): void
    {
        /**
         * Things to test:
         * - if the role does not exist, a NoSuchRoleException is thrown
         * - if the permission does not exist, a NoSuchPermissionException is thrown
         * - if the permission was not already assigned, that it was successfully assigned and true was returned
         * - if the permission was already assigned, that false is returned
         */

        $roleTitle = "aRoleWouldNeverExistWithThisTitle";
        $permissionTitle = "aPermissionWouldNeverExistWithThisTitle";

        // Test with non-existent role
        RBACManager::createPermission($permissionTitle); // Add permission
        $thrown = false;
        try {
            RBACManager::assignPermissionToRole($roleTitle, $permissionTitle);
        } catch (NoSuchRoleException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Test with non-existent permission
        RBACManager::deletePermission($permissionTitle); // Delete permission
        RBACManager::createRole($roleTitle); // Create role
        $thrown = false;
        try {
            RBACManager::assignPermissionToRole($roleTitle, $permissionTitle);
        } catch (NoSuchPermissionException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Test assignment when not already assigned
        RBACManager::createPermission($permissionTitle); // Create permission
        $result = RBACManager::assignPermissionToRole($roleTitle, $permissionTitle); // Assign permission to role
        // Test that assignment was successful
        $this->assertTrue(RBACManager::isRolePermitted($roleTitle, $permissionTitle));
        // Test that the correct value (true) was returned.
        $this->assertTrue($result);

        // Test assignment when already assigned
        $this->assertFalse(RBACManager::assignPermissionToRole($roleTitle, $permissionTitle));

        // Cleanup
        RBACManager::removePermissionFromRole($roleTitle, $permissionTitle);
        RBACManager::deletePermission($permissionTitle);
        RBACManager::deleteRole($roleTitle);

    }

    public function testRemovePermissionFromRole(): void
    {
        /**
         * Things to test:
         * - if the role does not exist, a NoSuchRoleException is thrown
         * - if the permission does not exist, a NoSuchPermissionException is thrown
         * - if a permission-role assignment exists, it is removed, and true is returned
         * - if a permission-role assignment does not exist, false is returned
         */

        $roleTitle = "aRoleWouldNeverExistWithThisTitle";
        $permissionTitle = "aPermissionWouldNeverExistWithThisTitle";

        // Test with non-existent role
        RBACManager::createPermission($permissionTitle); // Create permission
        $thrown = false;
        try {
            RBACManager::removePermissionFromRole($roleTitle, $permissionTitle);
        } catch (NoSuchRoleException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
        RBACManager::deletePermission($permissionTitle); // Cleanup


        // Test with non-existent permission (permission is now deleted)
        RBACManager::createRole($roleTitle); // Create role
        $thrown = false;
        try {
            RBACManager::removePermissionFromRole($roleTitle, $permissionTitle);
        } catch (NoSuchPermissionException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Test that false is returned when the assignment does not exist
        RBACManager::createPermission($permissionTitle); // Create permission, so now both permission and role exist
        $this->assertFalse(RBACManager::removePermissionFromRole($roleTitle, $permissionTitle));

        // Test that (if the assignment does exist) the assignment is removed, and true is returned
        RBACManager::assignPermissionToRole($roleTitle, $permissionTitle); // Create assignment
        $result = RBACManager::removePermissionFromRole($roleTitle, $permissionTitle); // Naturally performs cleanup
        $this->assertTrue($result);
        $this->assertFalse(RBACManager::isRolePermitted($roleTitle, $permissionTitle));

        // Cleanup
        RBACManager::deleteRole($roleTitle);
        RBACManager::deletePermission($permissionTitle);
    }

    /**
     * @throws DatabaseException|NoSuchAccountException|NoSuchRoleException
     */
    public function testAssignRoleToUser(): void
    {
        /**
         * Things to test:
         * - if the role does not exist, a NoSuchRoleException is thrown
         * - if a user does not exist with the given ID, a NoSuchAccountException is thrown
         * - if the user-role assignment already exists, false is returned
         * - if the user-role assignment does not already exist, it is created, and true is returned
         */

        // Creating dummy seller to assign roles to
        $sellerFields = array(
            "email" => "test@gmail.com",
            "password" => "testingPassword123",
            "name" => "Test Name",
            "address" => "34 Testing Street",
        );
        $seller = Seller::create($sellerFields);

        $roleTitle = "aRoleWouldNeverExistWithThisTitle";

        // Test with non-existent account (user ID)
        RBACManager::createRole($roleTitle);
        $thrown = false;
        try {
            RBACManager::assignRoleToUser(-1, $roleTitle); // -1 will never relate to any user.
        } catch (NoSuchAccountException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Test with non-existent role
        RBACManager::deleteRole($roleTitle); // Delete role
        $thrown = false;
        try {
            RBACManager::assignRoleToUser($seller->getUserID(), $roleTitle);
        } catch (NoSuchRoleException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Test when role-permission assignment does not already exist
        RBACManager::createRole($roleTitle); // Create role
        $this->assertTrue(RBACManager::assignRoleToUser($seller->getUserID(), $roleTitle));
        $this->assertTrue(RBACManager::hasRole($seller->getUserID(), $roleTitle));

        // Test when assignment already exists
        $this->assertFalse(RBACManager::assignRoleToUser($seller->getUserID(), $roleTitle));

        // Cleanup
        RBACManager::removeRoleFromUser($seller->getUserID(), $roleTitle);
        RBACManager::deleteRole($roleTitle);
        Seller::delete($seller->getUserID());
    }

    /**
     * @throws DatabaseException
     * @throws NoSuchRoleException
     * @throws NoSuchAccountException
     */
    public function testRemoveRoleFromUser(): void
    {
        /*
         * Things to test:
         * - if the role does not exist, a NoSuchRoleException is thrown
         * - if a user does not exist with the given ID, a NoSuchAccountException is thrown
         * - if the user-role assignment does not exist, false is returned
         * - if the user-role assignment does exist, it is removed, and true is returned
         */

        // Creating dummy seller to assign roles to
        $sellerFields = array(
            "email" => "test@gmail.com",
            "password" => "testingPassword123",
            "name" => "Test Name",
            "address" => "34 Testing Street",
        );
        $seller = Seller::create($sellerFields);

        $roleTitle = "aRoleWouldNeverExistWithThisTitle";

        // Test with non-existent account (user ID)
        RBACManager::createRole($roleTitle); // Create role
        $thrown = false;
        try {
            RBACManager::removeRoleFromUser(-1, $roleTitle); // -1 will never relate to any user
        } catch (NoSuchAccountException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Test with non-existent role
        RBACManager::deleteRole($roleTitle); // Delete role
        $thrown = false;
        try {
            RBACManager::removeRoleFromUser($seller->getUserID(), $roleTitle);
        } catch (NoSuchRoleException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Test when user-role assignment does not exist
        RBACManager::createRole($roleTitle);
        $this->assertFalse(RBACManager::removeRoleFromUser($seller->getUserID(), $roleTitle));

        // Test when user-role assignment already exists
        RBACManager::assignRoleToUser($seller->getUserID(), $roleTitle);
        $result = RBACManager::removeRoleFromUser($seller->getUserID(), $roleTitle);
        $this->assertTrue($result);
        $this->assertFalse(RBACManager::hasRole($seller->getUserID(), $roleTitle));

        // Cleanup
        RBACManager::deleteRole($roleTitle);
        Seller::delete($seller->getUserID());
    }

    public function testIsRolePermitted(): void
    {
        /**
         * Things to test:
         * - if the role does not exist, a NoSuchRoleException is thrown
         * - if the permission does not exist, a NoSuchPermissionException is thrown
         * - if the role has the permission, true is returned
         * - if the role does not have the permission, false is returned
         */

        $roleTitle = "aRoleWouldNeverExistWithThisTitle";
        $permissionTitle = "aPermissionWouldNeverExistWithThisTitle";

        // Test with non-existent role
        RBACManager::createPermission($permissionTitle); // Add permission
        $thrown = false;
        try {
            RBACManager::isRolePermitted($roleTitle, $permissionTitle);
        } catch (NoSuchRoleException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);


        // Test with non-existent permission
        RBACManager::deletePermission($permissionTitle); // Delete permission
        RBACManager::createRole($roleTitle); // Create role
        $thrown = false;
        try {
            RBACManager::isRolePermitted($roleTitle, $permissionTitle);
        } catch (NoSuchPermissionException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);


        // Test when role does have permission
        RBACManager::createPermission($permissionTitle); // Add permission
        RBACManager::assignPermissionToRole($roleTitle, $permissionTitle); // Assign permission to role
        $this->assertTrue(RBACManager::isRolePermitted($roleTitle, $permissionTitle));

        // Test when role does not have permission
        RBACManager::removePermissionFromRole($roleTitle, $permissionTitle); // Remove assignment
        $this->assertFalse(RBACManager::isRolePermitted($roleTitle, $permissionTitle));

        // Cleanup
        RBACManager::deleteRole($roleTitle);
        RBACManager::deletePermission($permissionTitle);
    }

    /**
     * @throws DatabaseException
     * @throws NoSuchRoleException
     * @throws NoSuchAccountException
     */
    public function testHasRole(): void
    {
        /**
         * Things to test:
         * - if the role does not exist, a NoSuchRoleException is thrown
         * - if a user does not exist with the given ID, a NoSuchAccountException is thrown
         * - if the user has the role, true is returned
         * - if the user does not have the role, false is returned
         */

        // Creating dummy seller to assign roles to
        $sellerFields = array(
            "email" => "test@gmail.com",
            "password" => "testingPassword123",
            "name" => "Test Name",
            "address" => "34 Testing Street",
        );
        $seller = Seller::create($sellerFields);

        $roleTitle = "aRoleWouldNeverExistWithThisTitle";

        // Test with non-existent account (user ID)
        RBACManager::createRole($roleTitle);
        $thrown = false;
        try {
            RBACManager::hasRole(-1, $roleTitle); // -1 will never relate to any user.
        } catch (NoSuchAccountException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);


        // Test with non-existent role
        RBACManager::deleteRole($roleTitle); // Delete role
        $thrown = false;
        try {
            RBACManager::hasRole($seller->getUserID(), $roleTitle);
        } catch (NoSuchRoleException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Test when user does have role
        RBACManager::createRole($roleTitle);
        RBACManager::assignRoleToUser($seller->getUserID(), $roleTitle); // Assign role to user
        $this->assertTrue(RBACManager::hasRole($seller->getUserID(), $roleTitle));

        // Test when user does not have role
        RBACManager::removeRoleFromUser($seller->getUserID(), $roleTitle);
        $this->assertFalse(RBACManager::hasRole($seller->getUserID(), $roleTitle));

        // Cleanup
        RBACManager::deleteRole($roleTitle);
        Seller::delete($seller->getUserID());
    }

    /**
     * @throws DatabaseException
     * @throws NoSuchPermissionException
     * @throws NoSuchAccountException
     * @throws NoSuchRoleException
     */
    public function testIsUserPermitted(): void
    {
        /**
         * Things to test:
         * - if the permission does not exist, a NoSuchPermissionException is thrown
         * - if a user does not exist with the given ID, a NoSuchAccountException is thrown
         * - if the user has the permission, true is returned
         * - if the user does not have the permission, false is returned
         */

        // Creating dummy seller to assign roles to
        $sellerFields = array(
            "email" => "test@gmail.com",
            "password" => "testingPassword123",
            "name" => "Test Name",
            "address" => "34 Testing Street",
        );
        $seller = Seller::create($sellerFields);


        $roleTitle = "aRoleWouldNeverExistWithThisTitle";
        $permissionTitle = "aPermissionWouldNeverExistWithThisTitle";

        // Test with non-existent account (user ID)
        RBACManager::createPermission($permissionTitle);
        $thrown = false;
        try {
            RBACManager::isUserPermitted(-1, $permissionTitle); // -1 will never relate to any user.
        } catch (NoSuchAccountException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Test with non-existent permission
        RBACManager::deletePermission($permissionTitle); // Delete permission
        $thrown = false;
        try {
            RBACManager::isUserPermitted($seller->getUserID(), $permissionTitle);
        } catch (NoSuchPermissionException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        // Setup (create role and permission, and assignment between them)
        RBACManager::createRole($roleTitle);
        RBACManager::createPermission($permissionTitle);
        RBACManager::assignPermissionToRole($roleTitle, $permissionTitle);

        // Test when user does have permission
        RBACManager::assignRoleToUser($seller->getUserID(), $roleTitle);
        $this->assertTrue(RBACManager::isUserPermitted($seller->getUserID(), $permissionTitle));

        // Test when user does not have permission
        RBACManager::removePermissionFromRole($roleTitle, $permissionTitle);
        $this->assertFalse(RBACManager::isUserPermitted($seller->getUserID(), $permissionTitle));

        // Cleanup
        RBACManager::removeRoleFromUser($seller->getUserID(), $roleTitle);
        RBACManager::deletePermission($permissionTitle);
        RBACManager::deleteRole($roleTitle);
        Seller::delete($seller->getUserID());

    }

}