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
        if($_GET["type"] == "MovingAverage") {
            if($_GET["dataNeeded"] == "Forecast") {
                // Check if values that need to be set are set
                if (!isset($_GET["startTime"]) || !isset($_GET["endTime"]) || !isset($_GET["minDiscount"])
                    || !isset($_GET["maxDiscount"])) {
                    throw new InvalidArgumentException("Missing parameters");
                }

                // Get current seller's id
                $sellerID = Authenticator::getCurrentUser()->getUserID();

                // Check if they have permissions to view forecasts
                if (!RBACManager::isCurrentUserPermitted("forecast_view")) {
                    throw new NoSuchPermissionException("Seller $sellerID does not have permission to view forecasts");
                }

                // Get the forecast
                $data = \TTE\App\Model\Reservation::getAllReservationsForUser($sellerID, "seller");
                $weeklyForecast = Forecast::movingAverage($_GET["startTime"], $_GET["endTime"], $_GET["minDiscount"], $_GET["maxDiscount"], $data);
                echo json_encode($weeklyForecast);

                exit();
            } elseif ($_GET["dataNeeded"] == "Compare") {
                // Get current seller's id
                $sellerID = Authenticator::getCurrentUser()->getUserID();

                // Check if they have permissions to view forecasts
                if (!RBACManager::isCurrentUserPermitted("forecast_view")) {
                    throw new NoSuchPermissionException("Seller $sellerID does not have permission to view forecasts");
                }

                echo json_encode(Forecast::compareWithGroundTruth($sellerID, "MovingAverage"));
            }
        } elseif ($_GET["type"] == "Seasonal") {
            if($_GET["dataNeeded"] == "Forecast") {
                // Check if values that need to be set are set
                if (!isset($_GET["startTime"]) || !isset($_GET["endTime"]) || !isset($_GET["minDiscount"])
                    || !isset($_GET["maxDiscount"]) || !isset($_GET["filterCategory"]) || !isset($_GET["filterWeatherCondition"])) {
                    throw new InvalidArgumentException("Missing parameters");
                }

                // Get current seller's id
                $sellerID = Authenticator::getCurrentUser()->getUserID();

                // Check if they have permissions to view forecasts
                if (!RBACManager::isCurrentUserPermitted("forecast_view")) {
                    throw new NoSuchPermissionException("Seller $sellerID does not have permission to view forecasts");
                }

                $data = Forecast::getLastWeeksReservations($sellerID);
                $weeklyForecast = Forecast::forecastNextWeekSeasonal($_GET["filterCategory"],$_GET["startTime"], $_GET["endTime"], $_GET["minDiscount"], $_GET["maxDiscount"], $_GET["filterWeatherCondition"], $data);
                echo json_encode($weeklyForecast);

                exit();
            } elseif ($_GET["dataNeeded"] == "Compare") {
                // Get current seller's id
                $sellerID = Authenticator::getCurrentUser()->getUserID();

                // Check if they have permissions to view forecasts
                if (!RBACManager::isCurrentUserPermitted("forecast_view")) {
                    throw new NoSuchPermissionException("Seller $sellerID does not have permission to view forecasts");
                }

                echo json_encode(Forecast::compareWithGroundTruth($sellerID, "Seasonal"));
            }
        } elseif ($_GET["type"] == "ProductionRec") {
            
        }
    } catch (InvalidArgumentException $e) {
        echo json_encode(http_response_code(400));
        die();
    } catch (NoSuchPermissionException $e) {
        echo json_encode(http_response_code(403));
        die();
    } catch (\TTE\App\Model\DatabaseException $e) {
        echo json_encode(http_response_code(500));
        die();
    }
} else {
    // JSON-encoded response if no permitted request is made
    echo json_encode(http_response_code(405));
    die();
}
