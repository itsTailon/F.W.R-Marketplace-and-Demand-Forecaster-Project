<?php

use TTE\App\Auth\Authenticator;
use TTE\App\Model\Category;
use TTE\App\Model\DatabaseException;

include '../../../vendor/autoload.php';

session_start();

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    echo json_encode(http_response_code(401));
    die();
}

// Check that user is permitted to manage categories
if (!\TTE\App\Auth\RBACManager::isCurrentUserPermitted("manage_categories")) {
    echo json_encode(http_response_code(403));
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ensure that the required field was passed
    if (!isset($_POST['categories'])) {
        http_response_code(400);
        die();
    }

    // Decode submitted categories
    $submittedCategories = json_decode($_POST['categories'], true);
    if (!is_array($submittedCategories)) {
        http_response_code(400);
        die();
    }

    // Ensure that at least one category was submitted
    if (count($submittedCategories) < 1 || count($submittedCategories) == 1 && $submittedCategories[0] == "") {
        http_response_code(400);
        die();
    }

    // Get existing categories
    $currentCategories = Category::getCategoryList();

    // Determine whether categories are actually being changed
    if (count($currentCategories) == count($submittedCategories) && array_diff($currentCategories, $submittedCategories) == array_diff($submittedCategories, $currentCategories)) {
        // No changes to be made
        http_response_code(200);
        die();
    }

    // (if applicable) determine which categories are being removed
    $categoriesToDelete = array_diff($currentCategories, $submittedCategories);

    // Delete any categories no longer wanted (as per user input)
    foreach ($categoriesToDelete as $category) {
        try {
            Category::delete($category);
        } catch (DatabaseException $e) {
            http_response_code(500);
            die();
        }
    }

    // Get (after potential deletions) existing categories, and determine which categories to add
    $currentCategories = Category::getCategoryList();
    $categoriesToAdd = array_diff($submittedCategories, $currentCategories);

    // Add new categories
    foreach ($categoriesToAdd as $category) {
        try {
            Category::create($category);
        } catch (DatabaseException $e) {
            http_response_code(500);
            die();
        }
    }

    // Success!
    http_response_code(200);
    die();
}