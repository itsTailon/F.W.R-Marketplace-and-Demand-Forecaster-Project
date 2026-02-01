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
    // JSON-encoded response
    header('ContentType: application/json');
    echo json_encode(http_response_code(401));
    die();
}

// Check raw request data to see if PUT or POST were used for update() and create() respectively
if ($_SERVER["REQUEST_METHOD"] == "PUT") {
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
        if (!isset($bundle_ID['bundleID']) || !ctype_digit((string)$bundle_ID['bundleID'])) {
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
        die();

        //TODO: JSON handling messages and verify response codes
    } catch (NoSuchPermissionException $perm_e) {
        // Handling exception produced if user doesn't have required permission and producing JSON-encoded response
        header('ContentType: application/json');
        echo json_encode(http_response_code(403));
        die();
    } catch (DatabaseException $db_e) {
        // Handling exception produced due to database error and producing JSON-encoded response
        header('ContentType: application/json');
        echo json_encode(http_response_code(500));
        die();
    } catch (NoSuchBundleException $sb_e) {
        // Handling exception if bundle attempted to update does not exist and producing JSON-encoded response
        header('ContentType: application/json');
        echo json_encode(http_response_code(404));
        die();
    } catch (\PDOException $pdo_e) {
        // Handling exception produced by failed PDO request and producing JSON-encoded response
        header('ContentType: application/json');
        echo json_encode(http_response_code(500));
        die();
    } catch (FailedOwnershipAuthException $no_e) {
        // Handling exception produced failure
        //to authenticate seller for updating specified bundle and producing JSON-encoded message
        header('ContentType: application/json');
        echo json_encode(http_response_code(403));
        die();
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {

        // POST input
        $fields = filter_input_array(INPUT_POST);

        // Ensuring input is, in fact, an array
        if (!is_array($fields)) {
            throw new InvalidArgumentException("Fields must be an array");
        }

        // Checking that current user has permissions to create a Bundle
        if (!RBACManager::isCurrentuserPermitted("bundle_create")) {
            throw new NoSuchPermissionException("Seller " . $fields['sellerID'] . " is not allowed to create bundle");
        }

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

            // Switch-case confirming field type
            // No case for title and details as either are strings or are caught within create() anyway
            switch ($field) {
                case "bundleStatus":
                    // Switch-case checking value to additionally update it to non-string
                    $fields["bundleStatus"] = match ((string)$value) {
                        "Available" => BundleStatus::Available,
                        "Reserved" => BundleStatus::Reserved,
                        "Collected" => BundleStatus::Collected,
                        "Cancelled" => BundleStatus::Cancelled,
                        default => throw new InvalidArgumentException("Invalid field type for $field"),
                    };
                    break;

                case "rrp":
                case "discountedPrice":
                case "sellerID":
                    // Check string contains only [0,9] digits and no '.'
                    if (!ctype_digit((string)$value)) {
                        throw new InvalidArgumentException("Invalid field type for $field");
                    }
                    // Convert string to integer
                    $fields[$field] = (int)$value;
                    break;

                case "purchaserID":
                    // If not [0,9] or null, throw exception
                    if (!ctype_digit((string)$value) && (string)$value !== "NULL") {
                        throw new InvalidArgumentException("Invalid field type for $field");
                    }

                    // Convert type and store depending on whether there is ID or it is NULL
                    if ((string)$value === "NULL") {
                        $fields[$field] = null;
                    } elseif (ctype_digit((string)$value)) {
                        $fields[$field] = (int)$value;
                    }
                    break;

            }

        }

        // Calling create() method, storing Bundle object produced as $bundle
        $bundle = Bundle::create($fields);

        // TODO: JSON message to add
        http_response_code(201);
        die();


    } catch (NoSuchPermissionException $nsp_e) {
        // Permission denied thus "forbidden" to access content and produce JSON-encoded message
        header('ContentType: application/json');
        echo json_encode(http_response_code(403));
        die();
    } catch (DatabaseException $e) {
        // Internal server error caused by failed database query and produce JSON-encoded message
        header('ContentType: application/json');
        echo json_encode(http_response_code(500));
        die();
    } catch (MissingValuesException $mv_e) {
        // Bad request not in the form required as input and produce JSON-encoded message
        header('ContentType: application/json');
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchCustomerException $nsc_e) {
        // Customer not found and produce JSON-encoded message
        header('ContentType: application/json');
        echo json_encode(http_response_code(404));
        die();
    } catch (NoSuchSellerException $nss_e) {
        // Seller not found and produce JSON-encoded message
        header('ContentType: application/json');
        echo json_encode(http_response_code(404));
        die();
    }

} else {
    // JSON-encoded response if no permitted request is made
    header('ContentType: application/json');
    echo json_encode(http_response_code(405));
    die();
}
