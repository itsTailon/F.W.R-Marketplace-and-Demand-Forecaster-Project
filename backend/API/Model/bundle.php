<?php

/*
 * Handles API 'bundle' request
 */

// Import bundle from Model directory
use TTE\App\Helpers\CurrencyTools;
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

// if-elseif...-else statement block branching on the basis of request method
if ($_SERVER["REQUEST_METHOD"] == "PUT") {
    // Handling PUT request that calls the update() method for the Bundle class
    try {

        // Getting fields from input and storing under $_PUT to use as you would a superglobal
        $_PUT = array();
        parse_str(file_get_contents('php://input'), $_PUT);

        // Get bundleID from input
        $bundleID = $_PUT["bundleID"];

        // check data is set and of the right form before using
        if (!isset($bundleID) || !ctype_digit($bundleID)) {
            throw new InvalidArgumentException("Invalid bundle ID");
        }

        // Convert to int before using
        $bundleID = intval($bundleID);

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

        // Apply changes to bundle
        $bundle->setStatus(BundleStatus::from($_PUT["bundleStatus"]));
        $bundle->setTitle($_PUT["title"]);
        $bundle->setDetails($_PUT['details']);
        $bundle->setRrpGBX(CurrencyTools::decimalStringToGBX($_PUT['rrp']));
        $bundle->setDiscountedPriceGBX(CurrencyTools::decimalStringToGBX($_PUT['discountedPrice']));

        if(isset($_PUT['purchaserID'])) {
            $bundle->setPurchaserID(intval($_PUT['purchaserID']));
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
    } catch (NoSuchCustomerException $e) {
        // Handling failure to customer ID and producing JSON-encoded message
        echo json_encode(http_response_code(400));
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handling POST request method that calls create() method
    try {

        // Ensuring all required values are set
        if (!isset($_POST["bundleStatus"]) || !isset($_POST["title"]) || !isset($_POST["details"])
            || !isset($_POST["rrp"]) || !isset($_POST["discountedPrice"]) || !isset($_POST["purchaserID"])) {
            // Throwing exception if field isn't present in retrieve data
            throw new MissingValuesException("Missing fields");
        }

        // Get array of fields for bundle to create
        $fields = array(
            $_POST["bundleStatus"],
            $_POST["title"],
            $_POST["details"],
            $_POST["rrp"],
            $_POST["discountedPrice"],
            $_POST["purchaserID"]
        );

        // Checking that current user has permissions to create a Bundle
        if (!RBACManager::isCurrentuserPermitted("bundle_create")) {
            throw new NoSuchPermissionException("Seller " . Authenticator::getCurrentUser()->getUserID() . " is not allowed to create bundle");
        }

        // Checking passed fields are valid fields
        foreach ($fields as $field=>$value) {
            // Switch-case confirming field type
            // No case for title and details as either are strings or are caught within create() anyway
            switch ($field) {
                case "bundleStatus":
                    // Switch-case checking value to additionally update it to non-string
                    $fields["bundleStatus"] = match ($value) {
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
                    if (!ctype_digit($value)) {
                        throw new InvalidArgumentException("Invalid field type for $field");
                    }
                    // Convert string to integer
                    $fields[$field] = intval($value);
                    break;

                case "purchaserID":
                    // If not [0,9] or null, throw exception
                    if (!empty($value) || !ctype_digit($value)) {
                        throw new InvalidArgumentException("Invalid field type for $field");
                    }

                    // Convert type and store if integer
                    if (ctype_digit($value)) {
                        $fields[$field] = intval($value);
                    } else {
                        // If empty then store as null
                        $fields[$field] = null;
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
        if (!isset($bundleID['bundleID']) || !ctype_digit($bundleID['bundleID'])) {
            throw new InvalidArgumentException("Invalid bundle ID");
        }

        // Convert to valid int type
        $bundleID = intval($bundleID);

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
        $_DELETE = array();
        parse_str(file_get_contents('php://input'), $_DELETE);

        // Get input (bundleID) and parse it
        $bundleID = $_DELETE["bundleID"];

        // Check that bundle ID holds valid data
        if (!isset($bundleID) || !ctype_digit($bundleID)) {
            throw new InvalidArgumentException("Invalid bundle ID");
        }

        // Convert to usable type
        $bundleID = intval($bundleID);

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
    } catch (InvalidArgumentException $ia_e) {
        echo json_encode(http_response_code(400));
        die();
    }
} else {
    // JSON-encoded response if no permitted request is made
    echo json_encode(http_response_code(405));
    die();
}
