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

        // check bundle ID is set and of the right form before using
        if (!isset($_PUT["bundleID"]) || !is_int(filter_var($_PUT["bundleID"], FILTER_VALIDATE_INT))) {
            throw new InvalidArgumentException("Invalid bundle ID");
        }

        // Presence check for fields
        if (!isset($_PUT["title"]) || !isset($_PUT["details"])
            || !isset($_PUT["rrp"]) || !isset($_PUT["discountedPrice"]) || !isset($_PUT['allergens'])) {

            // Throwing exception if field isn't present in retrieve data
            throw new MissingValuesException("Missing fields");
        }

        // Convert bundle ID to int before using
        $bundleID = intval($_PUT["bundleID"]);

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

        // Get allergens
        $allergens = json_decode($_PUT['allergens']);
        if ($allergens === null) {
            throw new InvalidArgumentException();
        } else {
            foreach ($allergens as $allergen) {
                // Check if allergen exists (convert to string explicitly, as JSON value could have been non-string)
                if (!\TTE\App\Model\Allergen::allergenExists(strval($allergen))) {
                    throw new InvalidArgumentException();
                }
            }
        }

        // Apply changes to bundle
        $bundle->setStatus(BundleStatus::from($_PUT["bundleStatus"]));
        $bundle->setTitle($_PUT["title"]);
        $bundle->setDetails($_PUT['details']);
        $bundle->setRrpGBX(CurrencyTools::decimalStringToGBX($_PUT['rrp']));
        $bundle->setDiscountedPriceGBX(CurrencyTools::decimalStringToGBX($_PUT['discountedPrice']));

        // Set allergens
        // TODO: Make more efficient (add 'setAllergens' method to Bundle, perhaps)
        foreach ($bundle->getAllergens() as $existingAllergen) {
            $bundle->removeAllergen($existingAllergen);
        }
        foreach ($allergens as $allergen) {
            $bundle->addAllergen($allergen);
        }

        if(isset($_PUT['purchaserID'])) {
            $bundle->setPurchaserID(intval($_PUT['purchaserID']));
        } else {
            $bundle->setPurchaserID(null);
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

    } catch (MissingValuesException $e) {
        http_response_code(400);
        die("MVE");
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        die("IAE");
    } catch (DatabaseException $db_e) {
        // Handling exception produced due to database error and producing JSON-encoded response
        echo json_encode(http_response_code(500));
        die("DBE");
    } catch (NoSuchBundleException $sb_e) {
        // Handling exception if bundle attempted to update does not exist and producing JSON-encoded response
        echo json_encode(http_response_code(404));
        die();
    } catch (\PDOException $pdo_e) {
        // Handling exception produced by failed PDO request and producing JSON-encoded response
        echo json_encode(http_response_code(500));
        die("DBE");
    } catch (FailedOwnershipAuthException $no_e) {
        // Handling exception produced failure
        //to authenticate seller for updating specified bundle and producing JSON-encoded message
        echo json_encode(http_response_code(403));
        die();
    } catch (NoSuchCustomerException $e) {
        // Handling failure to customer ID and producing JSON-encoded message
        echo json_encode(http_response_code(400));
        die();
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handling POST request method that calls create() method
    try {

        // Ensuring all required values are set
        if (!isset($_POST["title"]) || !isset($_POST["details"])
            || !isset($_POST["rrp"]) || !isset($_POST["discountedPrice"]) || !isset($_POST['allergens'])) {

            // Throwing exception if field isn't present in retrieve data
            throw new MissingValuesException("Missing fields");
        }


        // Get array of fields for bundle to create
        $fields = array(
            "title" => $_POST["title"],
            "details" => $_POST["details"],
            "rrp" => $_POST["rrp"],
            "discountedPrice" => $_POST["discountedPrice"],
            "sellerID" => Authenticator::getCurrentUser()->getUserID(),
            "bundleStatus" => BundleStatus::Available,
        );

        // Get allergens
        $allergens = json_decode($_POST['allergens']);
        if ($allergens === null) {
            throw new InvalidArgumentException();
        } else {
            foreach ($allergens as $allergen) {
                // Check if allergen exists (convert to string explicitly, as JSON value could have been non-string)
                if (!\TTE\App\Model\Allergen::allergenExists(strval($allergen))) {
                    throw new InvalidArgumentException();
                }
            }
        }

        // Checking that current user has permissions to create a Bundle
        if (!RBACManager::isCurrentuserPermitted("bundle_create")) {
            throw new NoSuchPermissionException("Seller " . Authenticator::getCurrentUser()->getUserID() . " is not allowed to create bundle");
        }

        // Checking passed fields are valid fields
        foreach ($fields as $field=>$value) {
            // Switch-case confirming field type
            // No case for title and details as either are strings or are caught within create() anyway
            switch ($field) {
                case "rrp":
                case "discountedPrice":
                case "sellerID":
                    // Check string contains only [0,9] digits and no '.'
                    if (!is_int(filter_var($value, FILTER_VALIDATE_INT))) {
                        throw new InvalidArgumentException("Invalid field type for $field");
                    }
                    // Convert string to integer
                    $fields[$field] = intval($value);
                    break;

            }

        }

        // Calling create() method, storing Bundle object produced as $bundle
        $bundle = Bundle::create($fields);

        // Add allergens to bundle
        foreach ($allergens as $allergen) {
            $bundle->addAllergen($allergen);
        }

        // If successfully created a Bundle, return that bundle
        echo json_encode($bundle);
        die();


    } catch (NoSuchPermissionException $nsp_e) {
        // Permission denied thus "forbidden" to access content and produce JSON-encoded message
        echo json_encode(http_response_code(403));
        die("NSP");
    } catch (DatabaseException $e) {
        // Internal server error caused by failed database query and produce JSON-encoded message
        echo json_encode(http_response_code(500));
        die("DBE");
    } catch (MissingValuesException $mv_e) {
        // Bad request not in the form required as input and produce JSON-encoded message
        echo json_encode(http_response_code(400));
        die("MVE");
    } catch (NoSuchCustomerException $nsc_e) {
        // Customer not found and produce JSON-encoded message
        echo json_encode(http_response_code(404));
        die("NCE");
    } catch (NoSuchSellerException $nss_e) {
        // Seller not found and produce JSON-encoded message
        echo json_encode(http_response_code(404));
        die("NSE");
    } catch (InvalidArgumentException $ia_e) {
        // Argument passed to method not of right form and return JSON-encoded message
        echo json_encode(http_response_code(400));
        die("IAE");
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Handling GET request calling the load() method for the Bundle class

    try {

        // Handling GET request and storing input
        $bundleID = $_GET["bundleID"];

        // Checking validity of passed bandle ID
        if (!isset($bundleID['bundleID']) || !!is_int(filter_var($bundleID, FILTER_VALIDATE_INT))) {
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

        // Create associative array to encode for return
        $bundle_fields = array(
            "id" => $bundle->getID(),
            "title" => $bundle->getTitle(),
            "details" => $bundle->getDetails(),
            "status" => $bundle->getStatus(),
            "rrpGBX" => $bundle->getRrpGBX(),
            "discountedPriceGBX" => $bundle->getDiscountedPriceGBX(),
            "sellerID" => $bundle->getSellerID(),
            "purchaserID" => $bundle->getPurchaserID(),
        );

        // Return Bundle through a JSON-encoded message
        echo json_encode($bundle_fields);
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
        if (!isset($bundleID) || !is_int(filter_var($bundleID, FILTER_VALIDATE_INT))) {
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
