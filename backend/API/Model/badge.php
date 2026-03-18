<?php

/*
 * Handles API 'badge' request
 */

// Import badge from Model directory
use TTE\App\Helpers\CurrencyTools;
use TTE\App\Model\Badge;
use TTE\App\Auth\Authenticator;
use TTE\App\Auth\RBACManager;
use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Model\BadgeTier;
use TTE\App\Model\Customer;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchBadgeException;
use TTE\App\Model\FailedOwnershipAuthException;
use TTE\App\Model\NoSuchCustomerException;
use TTE\App\Model\NoSuchSellerException;
use TTE\App\Model\BadgeAlreadyExistsException;

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
    // Handling GET request by calling the appropriate loading method for badges

    try {

        // Get current user ID
        $userID = Authenticator::getCurrentUser()->getUserID();

        // Check if permissions exist for method
        if (!RBACManager::isCurrentUserPermitted("badge_load")) {
            throw new NoSuchPermissionException("User does not have permission to load badges for customer with ID $userID");
        }

        // Calling the load() method for badges
        $badges = Customer::loadBadges($userID);

        // Form an array of badges to return to frontend
        $badge_array = array();
        foreach ($badges as $badge) {
            // Get title of badge
            $current_badge = Badge::load($badge["badgeID"]);

            $description = null;
            $subtitle = null;

            if ($current_badge->getTitle() == "Bargain Hunter") {
                // Set message depending on current tier
                switch ($badge["tier"]) {
                    case BadgeTier::Bronze:
                        $subtitle = str_replace("{x}", $current_badge->getXBronze(), $current_badge->getSubtitle());
                        $description = str_replace("{x}", strval($current_badge->getXSilver()), $current_badge->getBadgeDescription());
                        break;
                    case BadgeTier::Silver:
                        $subtitle = str_replace("{x}", $current_badge->getXSilver(), $current_badge->getSubtitle());
                        $description = str_replace("{x}", strval($current_badge->getXGold()), $current_badge->getBadgeDescription());
                        break;
                    case BadgeTier::Gold:
                        $subtitle = str_replace("{x}", $current_badge->getXGold(), $current_badge->getSubtitle());
                        $description = "You have achieved the utmost grade for this badge!";
                        break;
                }
            } else {
                // Set message depending on current tier
                switch ($badge["tier"]) {
                    case BadgeTier::Bronze:
                        $subtitle = str_replace("{x}", $current_badge->getXBronze(), $current_badge->getSubtitle());
                        $description = str_replace("{x}", strval($current_badge->getXSilver() - $badge["progress"]), $current_badge->getBadgeDescription());
                        break;
                    case BadgeTier::Silver:
                        $subtitle = str_replace("{x}", $current_badge->getXSilver(), $current_badge->getSubtitle());
                        $description = str_replace("{x}", strval($current_badge->getXGold() - $badge["progress"]), $current_badge->getBadgeDescription());
                        break;
                    case BadgeTier::Gold:
                        $subtitle = str_replace("{x}", $current_badge->getXGold(), $current_badge->getSubtitle());
                        $description = "You have achieved the utmost grade for this badge!";
                        break;
                }
            }

            if ($badge["tier"] == null) {
                // Form the URL for the given badge to be presented
                $iconURL = strtolower(trim($current_badge->getTitle()) . "locked" . "png");

            } else {
                // Form the URL for the given badge to be presented
                $iconURL = strtolower(trim($current_badge->getTitle()) . $badge["tier"] . "png");

            }

            $badge_contents = array(
                "badgeID" => $badge["badgeID"],
                "badgeDescription" => $description,
                "badgeSubtitle" => $subtitle,
                "badgeIconURL" => $iconURL,
            );

            // Adding to array, pointed at by title
            $badges[$current_badge->getTitle()] = $badge_contents;
        }

        // Return the badge through a JSON-encoded message
        echo json_encode($badge_array);
        die();



    } catch (NoSuchPermissionException $e) {
        echo json_encode(http_response_code(403));
        die();
    } catch (DatabaseException $e) {
        echo json_encode(http_response_code(500));
        die();
    } catch (NoSuchBadgeException $e) {
        echo json_encode(http_response_code(404));
        die();
    } catch (NoSuchCustomerException $e) {
        echo json_encode(http_response_code(400));
    }

} else {
    // JSON response if no permitted request is made
    echo json_encode(http_response_code(405));
    die();
}