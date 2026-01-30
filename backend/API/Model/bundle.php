<?php

/*
 * Handles API 'bundle' request
 */

// Import bundle from Model directory
use TTE\App\Model\Bundle;
use TTE\App\Auth\Authenticator;
use TTE\App\Auth\RBACManager;
use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\NoSuchBundleException;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\FailedOwnershipAuthException;

include '../../../vendor/autoload.php';

session_start();

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    http_response_code(401); //TODO: Add JSON encoding
    die();
}

// Handling response depending on output on update() of Bundle.php
try {
    // Consider whether current user has permissions for update()
    if (RBACManager::isCurrentuserPermitted("bundle_update")) {

        // Required GETs
        $owner_id = Authenticator::getCurrentUser()->getUserID();
        $bundle_id = $_GET['bundleID'];
        $fields = $_GET['update_fields'];

        // SQL query for confirming bundle ownership
        $sql_query = "SELECT bundleID FROM bundle WHERE bundleID = :bundleID AND sellerID = :sellerID";
        // Prepare and execute query
        $stmt = DatabaseHandler::getPDO()->prepare($sql_query);

        // Try-catch block for handling potential database exceptions
        try {
            // Execute SQL command, establishing values of parameterised fields
            $stmt->execute([":bundleID" => $bundle_id, ":sellerID" => $owner_id]);
        } catch (\PDOException $e) {
            // Throw exception message aligning with output of database error
            throw new DatabaseException($e->getMessage());
        }

        // Confirm output of SQL query and throw exception if not the owner
        $sql_result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$sql_result) {
            // Throwing error fitting for failed authentication of ownership
            throw new FailedOwnershipAuthException("Seller with ID $owner_id doesn't own a bundle with ID $bundle_id");
        }

        //TODO: Fix how bundle is being accessed after correcting implementation
        // Running update() method for specified bundle
        Bundle::update($_GET['bundleID'], $_GET['update_fields']);

        // Explicitly give "OK" HTTP response code
        http_response_code(200);
        die();
    }
    //TODO: JSON handling messages
} catch (NoSuchPermissionException $perm_e) {
    // Handling exception produced if user doesn't have required permission
} catch (DatabaseException $db_e) {
    // Handling exception produced due to database error
} catch (NoSuchBundleException $sb_e) {
    // Handling exception if bundle attempted to update does not exist
} catch (PDOException $pdo_e) {
    // Handling exception produced by failed PDO request
} catch (FailedOwnershipAuthException $no_e) {
    // Handling exception produced failure to authenticate seller for updating specified bundle
}
