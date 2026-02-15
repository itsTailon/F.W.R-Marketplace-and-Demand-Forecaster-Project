<?php

use TTE\App\Auth\Authenticator;
use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Auth\RBACManager;
use TTE\App\Model\Forecast;

include '../../../vendor/autoload.php';

session_start();

// JSON heading for all JSON-encoded messages
header('Content-Type: application/json');

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    echo json_encode(http_response_code(401));
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try{
        // Check if category is specified
        if(!isset($_GET["category"])){
            $category = "";
        } else {
            $category = $_GET["category"];
        }

        // Check if weather is specified
        if(!isset($_GET["weather"])){
            $weather = "";
        } else {
            $weather = $_GET["weather"];
        }

        // Check if values that need to be set are set
        if(!isset($_GET["startTime"]) || !isset($_GET["endTime"]) || !isset($_GET["minDiscount"])
            || !isset($_GET["maxDiscount"])){
            throw new InvalidArgumentException("Missing parameters");
        }

        // Get current seller's id
        $sellerID = Authenticator::getCurrentUser()->getUserID();

        // Check if they have permissions to view forecasts
        if(!RBACManager::isCurrentUserPermitted("forecast_view")){
            throw new NoSuchPermissionException("Seller $sellerID does not have permission to view forecasts");
        }

        // Get the forecast
        $weeklyForecast = Forecast::sellerWeeklyForecast($sellerID, $category, $weather, $_GET["startTime"], $_GET["endTime"], $_GET["minDiscount"], $_GET["maxDiscount"]);
        echo json_encode($weeklyForecast);

        exit();
    } catch (InvalidArgumentException $e) {
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchPermissionException $e) {
        echo json_encode(http_response_code(403));
        die();
    }
} else {
    // JSON-encoded response if no permitted request is made
    echo json_encode(http_response_code(405));
    die();
}
