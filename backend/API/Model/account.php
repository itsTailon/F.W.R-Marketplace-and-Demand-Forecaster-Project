<?php

use TTE\App\Auth\Authenticator;
use TTE\App\Auth\RBACManager;
use TTE\App\Model\Account;

include '../../../vendor/autoload.php';

session_start();

// JSON header for all JSON-encoded messages
header('Content-Type: application/json');

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    echo json_encode(http_response_code(401));
    die();
}

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    // Get input
    $_PUT = array();
    parse_str(file_get_contents('php://input'), $_PUT);

    // Ensure that required data was included in the request
    if (!isset($_PUT['email']) || !isset($_PUT['userID'])) {
        http_response_code(400); // Bad request
        echo json_encode([
           'error' => 'Request missing required data.',
        ]);
        die();
    }

    // Ensure that new e-mail given is valid
    $email = filter_var($_PUT['email'], FILTER_VALIDATE_EMAIL);
    if ($email === false) {
        http_response_code(400); // Bad request
        echo json_encode([
            'error' => 'E-mail address given is not a valid e-mail address.',
        ]);
        die();
    }

    // Ensure that the user ID given is an integer
    $userID = filter_var($_PUT['userID'], FILTER_VALIDATE_INT);
    if (!is_int($userID)) {
        http_response_code(400); // Bad request
        echo json_encode([
            'error' => 'Invalid user ID (format/type).',
        ]);
        die();
    }

    // Ensure that the userID given corresponds to an actual account
    if (!Account::existsWithID($userID)) {
        http_response_code(400); // Bad request
        echo json_encode([
            'error' => 'Invalid user ID (no such account).',
        ]);
        die();
    }

    try {
        // Ensure that user has sufficient permissions to update the account specified
        if (Authenticator::getCurrentUser()->getUserID() != $userID && !RBACManager::isCurrentUserPermitted('update_others_account')) {
            // User is attempting to update somebody else's account without permission
            http_response_code(403); // Forbidden
            echo json_encode([
                'error' => 'Permission denied.',
            ]);
            die();
        }
        // Validate for case where the user is trying to update their own account
        if (Authenticator::getCurrentUser()->getUserID() == $userID && !RBACManager::isCurrentUserPermitted('account_update')) {
            // User does not have permission to update their own account
            http_response_code(403); // Forbidden
            echo json_encode([
                'error' => 'Permission denied.',
            ]);
            die();
        }

    } catch (Exception $e) { // Multiple exceptions could potentially 'bubble up' from methods called by RBACManager::isCurrentUserPermitted, and any exception should give the same API error
        http_response_code(500);
        echo json_encode([
            'error' => 'Server error occurred during authorisation.',
        ]);
        die();
    }
    // User is permitted to update the account, so continue...

    // Update account
    try {
        $account = Account::load($userID);
        $account->setEmail($email);
        $account->update();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Error when attempting to update account.',
        ]);
        die();
    }

    // Success!
    http_response_code(200);
    echo json_encode([]);
    die();
}
