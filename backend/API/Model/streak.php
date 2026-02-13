<?php

/*
 * Handles API 'streak' request
 */

// Import streak from Model directory
use TTE\App\Helpers\CurrencyTools;
use TTE\App\Auth\Authenticator;
use TTE\App\Auth\RBACManager;
use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Model\Customer;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\FailedOwnershipAuthException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\NoSuchSellerException;
use TTE\App\Model\Streak;
use TTE\App\Model\StreakAlreadyExistsException;
use TTE\App\Model\StreakStatus;

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
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Handling GET request calling the load() method for the Streak class
    try {

        // Get customer currently logged in
        $customerID = Authenticator::getCurrentUser()->getUserID();


        // Consider whether current user has permissions for load()
        if (!RBACManager::isCurrentuserPermitted("streak_load")) {
            throw new NoSuchPermissionException("Customer $customerID doesn't have permissions to load streak");
        }

        // Load streak for given customer
        $streak = Customer::load($customerID)->getStreak();

        // Return Streak through a JSON-encoded message
        echo json_encode($streak);
        die();

    } catch (InvalidArgumentException $ia_e) {
        // Argument passed to method not of right form and return JSON-encoded message
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchPermissionException $nsp_e) {
        // Permission denied thus "forbidden" to access content and produce JSON-encoded message
        echo json_encode(http_response_code(403));
        die();
    } catch (Exception $ex) {
        echo json_encode(http_response_code(400));
        die();
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "DELETE") {

    try {
        $_DELETE = array();
        parse_str(file_get_contents('php://input'), $_DELETE);

        // Check that streak ID holds valid data
        if (!isset($streakID) || !ctype_digit($streakID)) {
            throw new InvalidArgumentException("Invalid streak ID");
    }

        // Convert to usable type
        $streakID = intval($streakID);

        // Get current user ID
        $userID = Authenticator::getCurrentUser()->getUserID();

        // Consider whether current user has permissions for delete()
        if (!RBACManager::isCurrentuserPermitted("streak_delete")) {
            throw new NoSuchPermissionException("Customer $userID doesn't have permissions to delete a streak");
        }

        // Get Streak with given streakID
        $streak = Streak::load($streakID);

        // Check Customer owns the Streak
        if ($streak->getCustomerID() != $userID) {
            throw new NoSuchPermissionException("Customer $userID is not allowed to delete streak");
        }

        // Delete streak with given ID
        Streak::delete($streakID);

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
    } catch (NoSuchCustomerException $e) {
        echo json_encode(http_response_code(403));
        die();
    } catch (Exception $e) {
        echo json_encode(http_response_code(400));
        die();
    }
} else {
    // JSON-encoded response if no permitted request is made
    echo json_encode(http_response_code(405));
    die();
}
