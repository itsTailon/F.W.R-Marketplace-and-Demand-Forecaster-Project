<?php

/*
 * Handles API 'bundle' request
 */

// Import bundle from Model directory
use TTE\App\Model\Bundle;
use TTE\App\Auth\Authenticator;
use TTE\App\Auth\RBACManager;
use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Model\BundleStatus;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchBundleException;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\FailedOwnershipAuthException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\NoSuchSellerException;

include '../../../vendor/autoload.php';

session_start();

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    http_response_code(401); //TODO: Add JSON encoding
    die();
}

// Check raw request data to see if PUT is used as no native support
elseif ($_SERVER["REQUEST_METHOD"] == "PUT") {

    // Handling response depending on output on update() of Bundle.php
    try {

        // Get input (bundleID) and parse it
        $input = file_get_contents("php://input");
        $bundle_ID = json_decode($input, true);

        // Ensure $bundle_ID is of the right type
        if (!is_array($bundle_ID)) {
            throw new InvalidArgumentException("Bundle ID must be an array");
        }

        // check data is set and of the right form before using
        if (!isset($bundle_ID['bundleID']) || !is_numeric($bundle_ID['bundleID'])) {
            throw new InvalidArgumentException("Invalid bundle ID");
        }


        // Get current user logged in
        $owner_id = Authenticator::getCurrentUser()->getUserID();

        // Consider whether current user has permissions for update()
        if (!RBACManager::isCurrentuserPermitted("bundle_update")) {
            throw new NoSuchPermissionException("Seller $owner_id");
        }

        // Retrieve right Bundle using bundleID
        $bundle = Bundle::load($bundle_ID['bundleID']);

        // Ensure seller for which the method is called has ownership of said bundle
        if ($bundle->getSellerID() != $owner_id) {
            throw new FailedOwnershipAuthException("Seller $owner_id is not allowed to update bundle");
        }

        // Calling update() method as checks have been fulfilled
        $bundle->update();

        // Explicitly give "OK" HTTP response code
        http_response_code(200);
        // TODO: Check if need a die() or keep as is

        //TODO: JSON handling messages and verify response codes
    } catch (NoSuchPermissionException $perm_e) {
        // Handling exception produced if user doesn't have required permission
        http_response_code(403);
        die();
    } catch (DatabaseException $db_e) {
        // Handling exception produced due to database error
        http_response_code(500);
        die();
    } catch (NoSuchBundleException $sb_e) {
        // Handling exception if bundle attempted to update does not exist
        http_response_code(404);
        die();
    } catch (\PDOException $pdo_e) {
        // Handling exception produced by failed PDO request
        http_response_code(500);
        die();
    } catch (FailedOwnershipAuthException $no_e) {
        // Handling exception produced failure to authenticate seller for updating specified bundle
        http_response_code(403);
        die();
    }

}

// Handle POST request for creating bundle
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {

        // POST input
        $fields = filter_input_array(INPUT_POST);

        // Checking that current user has permissions to create a Bundle
        if (!RBACManager::isCurrentuserPermitted("bundle_create")) {
            throw new NoSuchPermissionException("Seller " . $fields['sellerID'] . " is not allowed to create bundle");
        }
        //TODO: Might want to add check that all arrays are included (though check for missing values is done in create() so consider)

        // Array of valid fields
        $valid_fields =
            array(
                "bundleStatus"
            , "title"
            , "details"
            , "rrp"
            , "discountedPrice"
            , "sellerID"
            , "purchaserID"
            );

        // Checking passed fields are valid fields
        foreach ($fields as $field=>$value) {
            if (!in_array($field, $valid_fields)) {
                throw new InvalidArgumentException("Invalid field $field");
            }

            //TODO: FIX TYPE CHECKS

            // Switch-case confirming field type
            switch ($field) {
                case "bundleStatus":
                    if (gettype($field) != BundleStatus::Available || gettype($field) != BundleStatus::Reserved || gettype($field) != BundleStatus::Collected || gettype($field) != BundleStatus::Cancelled ) {
                        throw new InvalidArgumentException("Invalid field type for $field");
                    }
                    break;case "title":
                case "details":
                    if (gettype($field) != "string") {
                        throw new InvalidArgumentException("Invalid field type for $field");
                    }
                    break;
                case "rrp":
                case "discountedPrice":
                case "sellerID":
                    if (gettype($field) != "integer") {
                        throw new InvalidArgumentException("Invalid field type for $field");
                    }
                    break;
                case "purchaserID":
                    if (gettype($field) != "integer" && gettype($field) != "NULL") {
                        throw new InvalidArgumentException("Invalid field type for $field");
                    }
                    break;

            }

        }

        // Calling create() method, storing Bundle object produced as $bundle
        $bundle = Bundle::create($fields);

        // TODO: maybe success in creating bundle should give 201 instead of 200


    } catch (NoSuchPermissionException $nsp_e) {
        // Permission denied thus "forbidden" to access content
        http_response_code(403);
    } catch (DatabaseException $e) {
        // Internal server error caused by failed database query
        http_response_code(500);
    } catch (MissingValuesException $mv_e) {
        // Bad request not in the form required as input
        http_response_code(400);
    } catch (NoSuchCustomerException $nsc_e) {
        // Customer not found
        http_response_code(404);
    } catch (NoSuchSellerException $nss_e) {
        // Seller not found
        http_response_code(404);
    }

}

else {
    // Response if no permitted request is made
    http_response_code(405);
}
