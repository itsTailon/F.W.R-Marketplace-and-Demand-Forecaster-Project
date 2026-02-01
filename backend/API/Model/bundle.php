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
use TTE\App\Model\FailedOwnershipAuthException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\NoSuchSellerException;

include '../../../vendor/autoload.php';

session_start();

// JSON heading for all JSON-encoded messages
header('Content-Type: application/json');

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    echo json_encode(http_response_code(401));
    die();
}

// JSON heading for all JSON-encoded messages
header('Content-Type: application/json');

// if-elseif...-else statement block branching on the basis of request method
if ($_SERVER["REQUEST_METHOD"] == "PUT") {
    // Handling PUT request that calls the update() method for the Bundle class
    try {

        // Get input (bundleID) and parse it
        $input = json_decode(file_get_contents("php://input"), true);
        $bundleID = $input["bundleID"];


        // check data is set and of the right form before using
        if (!isset($bundleID['bundleID']) || !ctype_digit((string)$bundleID['bundleID'])) {
            throw new InvalidArgumentException("Invalid bundle ID");
        }

        // Convert to int before using
        $bundleID = (int)$bundleID;


        // Get current user logged in
        $ownerID = Authenticator::getCurrentUser()->getUserID();

        // Consider whether current user has permissions for update()
        if (!RBACManager::isCurrentuserPermitted("bundle_update")) {
            throw new NoSuchPermissionException("Seller $ownerID doesn't have permissions to update a bundle");
        }

        // Retrieve right Bundle using bundleID
        $bundle = Bundle::load($bundleID);

        // Ensure seller for which the method is called has ownership of said bundle
        if ($bundle->getSellerID() != $ownerID) {
            throw new FailedOwnershipAuthException("Seller $ownerID is not allowed to update bundle");
        }

        // Calling update() method as checks have been fulfilled
        $bundle->update();

        // Explicitly give "OK" HTTP response code
        http_response_code(200);
        die();

    } catch (NoSuchPermissionException $perm_e) {
        // Handling exception produced if user doesn't have required permission and producing JSON-encoded response
        echo json_encode(http_response_code(403));
        die();
    } catch (DatabaseException $db_e) {
        // Handling exception produced due to database error and producing JSON-encoded response
        echo json_encode(http_response_code(500));
        die();
    } catch (NoSuchBundleException $sb_e) {
        // Handling exception if bundle attempted to update does not exist and producing JSON-encoded response
        echo json_encode(http_response_code(404));
        die();
    } catch (\PDOException $pdo_e) {
        // Handling exception produced by failed PDO request and producing JSON-encoded response
        echo json_encode(http_response_code(500));
        die();
    } catch (FailedOwnershipAuthException $no_e) {
        // Handling exception produced failure
        //to authenticate seller for updating specified bundle and producing JSON-encoded message
        echo json_encode(http_response_code(403));
        die();
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handling POST request method that calls create() method

    try {

        // Get input (bundleID) and parse it
        $fields = json_decode(file_get_contents("php://input"), true);

        // Ensuring input is, in fact, an array
        if (!is_array($fields)) {
            throw new InvalidArgumentException("Fields must be an array");
        }

        // Checking that current user has permissions to create a Bundle
        if (!RBACManager::isCurrentuserPermitted("bundle_create")) {
            throw new NoSuchPermissionException("Seller " . Authenticator::getCurrentUser()->getUserID() . " is not allowed to create bundle");
        }

        // Array of valid fields
        $valid_fields =
            array(
                "bundleStatus"
            , "title"
            , "details"
            , "rrp"
            , "discountedPrice"
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
                    if (!ctype_digit((string)$value) && (string)$value !== null) {
                        throw new InvalidArgumentException("Invalid field type for $field");
                    }

                    // Convert type and store if integer
                    if (ctype_digit((string)$value)) {
                        $fields[$field] = (int)$value;
                    }
                    break;

            }

        }

        // Calling create() method, storing Bundle object produced as $bundle
        $bundle = Bundle::create($fields);

        // If successfully created a Bundle, return that bundle
        echo json_encode($bundle);
        die();


    } catch (NoSuchPermissionException $nsp_e) {
        // Permission denied thus "forbidden" to access content and produce JSON-encoded message
        echo json_encode(http_response_code(403));
        die();
    } catch (DatabaseException $e) {
        // Internal server error caused by failed database query and produce JSON-encoded message
        echo json_encode(http_response_code(500));
        die();
    } catch (MissingValuesException $mv_e) {
        // Bad request not in the form required as input and produce JSON-encoded message
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchCustomerException $nsc_e) {
        // Customer not found and produce JSON-encoded message
        echo json_encode(http_response_code(404));
        die();
    } catch (NoSuchSellerException $nss_e) {
        // Seller not found and produce JSON-encoded message
        echo json_encode(http_response_code(404));
        die();
    } catch (InvalidArgumentException $ia_e) {
        // Argument passed to method not of right form and return JSON-encoded message
        echo json_encode(http_response_code(400));
        die();
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Handling GET request calling the load() method for the Bundle class

    try {

        // Handling GET request and storing input
        $bundleID = $_GET["bundleID"];

        // Checking validity of passed bandle ID
        if (!isset($bundleID['bundleID']) || !ctype_digit((string)$bundleID['bundleID'])) {
            throw new InvalidArgumentException("Invalid bundle ID");
        }

        // Convert to valid int type
        $bundleID = (int)$bundleID;

        // Get current user ID
        $userID = Authenticator::getCurrentUser()->getUserID();

        // Consider whether current user has permissions for load()
        if (!RBACManager::isCurrentuserPermitted("bundle_load")) {
            throw new NoSuchPermissionException("Seller $userID doesn't have permissions to load bundle");
        }


        // Calling load() and storing resultant Bundle under $bundle
        $bundle = Bundle::load($bundleID);

        // Return Bundle through a JSON-encoded message
        echo json_encode($bundle);
        die();

    } catch (DatabaseException $db_e) {
        // Internal server error caused by failed database query and produce JSON-encoded message
        echo json_encode(http_response_code(500));
        die();
    } catch (InvalidArgumentException $ia_e) {
        // Argument passed to method not of right form and return JSON-encoded message
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchPermissionException $nsp_e) {
        // Permission denied thus "forbidden" to access content and produce JSON-encoded message
        echo json_encode(http_response_code(403));
        die();
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "DELETE") {

    try {
        // Get input (bundleID) and parse it
        $input = json_decode(file_get_contents("php://input"), true);
        $bundleID = $input["bundleID"];

        // Check that bundle ID holds valid data
        if (!isset($bundleID['bundleID']) || !ctype_digit((string)$bundleID['bundleID'])) {
            throw new InvalidArgumentException("Invalid bundle ID");
        }

        // Convert to usable type
        $bundleID = (int)$bundleID;

        // Get current user ID
        $userID = Authenticator::getCurrentUser()->getUserID();

        // Consider whether current user has permissions for delete()
        if (!RBACManager::isCurrentuserPermitted("bundle_delete")) {
            throw new NoSuchPermissionException("Seller $userID doesn't have permissions to delete a bundle");
        }

        // Get Bundle with given bundleID
        $bundle = Bundle::load($bundleID);

        // Check seller owns the Bundle
        if ($bundle->getSellerID() != $userID) {
            throw new NoSuchPermissionException("Seller $userID is not allowed to delete bundle");
        }

        // Delete bundle with given ID
        Bundle::delete($bundleID);

        // Return success message
        echo json_encode(http_response_code(200));
        die();
    } catch (NoSuchPermissionException $nsp_e) {
        // Permission denied thus "forbidden" to access content and produce JSON-encoded message
        echo json_encode(http_response_code(403));
        die();
    } catch (DatabaseException $db_e) {
        // Internal server error caused by failed database query and produce JSON-encoded message
        echo json_encode(http_response_code(500));
        die();
    }
} else {
    // JSON-encoded response if no permitted request is made
    echo json_encode(http_response_code(405));
    die();
}
