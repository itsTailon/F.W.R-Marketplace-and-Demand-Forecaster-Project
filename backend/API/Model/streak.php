<?php

/*
 * Handles API 'streak' request
 */

// Import streak from Model directory
use TTE\App\Helpers\CurrencyTools;
use TTE\App\Auth\Authenticator;
use TTE\App\Auth\RBACManager;
use TTE\App\Auth\NoSuchPermissionException;
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
if ($_SERVER["REQUEST_METHOD"] == "PUT") {
    // Handling PUT request that calls the update() method for the Streak class
    try {

        // Getting fields from input and storing under $_PUT to use as you would a superglobal
        $_PUT = array();
        parse_str(file_get_contents('php://input'), $_PUT);

        // Get streakID from input
        $streakID = $_PUT["streakID"];

        // check data is set and of the right form before using
        if (!isset($streakID) || !ctype_digit($streakID)) {
            throw new InvalidArgumentException("Invalid streak ID");
        }

        // Convert to int before using
        $streakID = intval($streakID);

        // Get current user logged in
        $ownerID = Authenticator::getCurrentUser()->getUserID();

        // Consider whether current user has permissions for update()
        if (!RBACManager::isCurrentuserPermitted("streak_update")) {
            throw new NoSuchPermissionException("Customer with ID $streakID doesn't have permissions regarding this streak");
        }

        // Retrieve right Streak using streakID
        $streak = Streak::load($streakID);

        // Ensure seller for which the method is called has ownership of said streak
        if ($streak->getCustomerID() != $ownerID) {
            throw new FailedOwnershipAuthException("Customer with ID $streakID isn't owner of this streak");
        }

        // Apply changes to streak
        $streak->setStatus(StreakStatus::from($_PUT["streakStatus"]));
        $streak->setCustomerID($_PUT["customerID"]);
        $streak->setStartDate($_PUT["startDate"]);
        $streak->setEndDate($_PUT["endDate"]);

        // Calling update() method as checks have been fulfilled
        $streak->update();

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
    } catch (Exception $e) {
        // Exception thrown by DateTimeImmutable
        echo json_encode(http_response_code(500));
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Handling POST request method that calls create() method
    try {
        // Make sure no streak is currently attached to user
        $curr_user_ID = Authenticator::getCurrentUser()->getUserID();

        // Preparing parameterised statement and executing
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM streak WHERE customerID = :customerID;");
        $stmt->execute([":streakID" => $curr_user_ID]);

        // Get result and return true/false depending
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result !== false) {
            throw new StreakAlreadyExistsException("There is already a streak associated with customer with ID $curr_user_ID");
        }

        // Ensuring all required values are set
        if (!isset($_POST["streakStatus"]) || !isset($_POST["customerID"]) || !ctype_digit($_POST["customerID"])) {
            throw new MissingValuesException("Missing mandatory parameter/s");
        }

        // Get array of fields for bundle to create
        $fields = array(
            $_POST["streakStatus"],
            $_POST["customerID"]
        );

        // Checking that current user can have a streak attached to them
        if (!RBACManager::isCurrentuserPermitted("streak_create")) {
            throw new NoSuchPermissionException("Customer with ID " . Authenticator::getCurrentUser()->getUserID() . " is not allowed to create streak");
        }

        // Checking passed fields are valid fields
        foreach ($fields as $field=>$value) {
            // Switch-case confirming field type
            switch ($field) {
                case "streakStatus":
                    // Switch-case checking value to additionally update it to non-string
                    $fields["streakStatus"] = match ($value) {
                        "active" => StreakStatus::Active,
                        "inactive" => StreakStatus::Inactive,
                        default => throw new InvalidArgumentException("Invalid field type for $field"),
                    };
                    break;

                case "customerID":
                    // If not [0,9] or null, throw exception
                    if (!empty($value) || !ctype_digit($value)) {
                        throw new InvalidArgumentException("Invalid field type for $field");
                    }

                    // Convert type and store as integer
                    $fields[$field] = intval($value);
                    break;

            }

        }

        // Calling create() method, storing Streak object produced as $streak
        $streak = Streak::create($fields);

        // If successfully created a Streak, return that streak
        echo json_encode($streak);
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
    } catch (StreakAlreadyExistsException $sta_e) {
        // Bad request as streak for given customer already exists
        echo json_encode(http_response_code(400));
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Handling GET request calling the load() method for the Streak class

    try {

        // Handling GET request and storing input
        $streakID = $_GET["streakID"];

        // Checking validity of passed streak ID
        if (!isset($streakID['streakID']) || !ctype_digit($streakID['streakID'])) {
            throw new InvalidArgumentException("Invalid streak ID $streakID");
        }

        // Convert to valid int type
        $streakID = intval($streakID);

        // Get current user ID
        $userID = Authenticator::getCurrentUser()->getUserID();

        // Consider whether current user has permissions for load()
        if (!RBACManager::isCurrentuserPermitted("streak_load")) {
            throw new NoSuchPermissionException("Customer $userID doesn't have permissions to load streak");
        }


        // Calling load() and storing resultant Streak under $streak
        $streak = Streak::load($streakID);

        // Return Streak through a JSON-encoded message
        echo json_encode($streak);
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
        // Get input (streakID) and parse it
        $input = json_decode(file_get_contents("php://input"), true);
        $streakID = $input["streakID"];

        // Check that streak ID holds valid data
        if (!isset($streakID) || !ctype_digit($streakID)) {
            throw new InvalidArgumentException("Invalid bundle ID");
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
    }
} else {
    // JSON-encoded response if no permitted request is made
    echo json_encode(http_response_code(405));
    die();
}
