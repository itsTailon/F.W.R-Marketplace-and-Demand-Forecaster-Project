


<?php 

use TTE\App\Auth\Authenticator;
use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Model\Notification;
use TTE\App\Model\NoSuchAccountException;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\NoSuchNotificationException;


include '../../../vendor/autoload.php';

session_start();

header('Content-Type: application/json');

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    echo json_encode(http_response_code(401));
    die();
}


if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {

        if (!isset($_GET["action"])) {
            throw new InvalidArgumentException("Action not provided.");
        }


        $currentUserID = Authenticator::getCurrentUser()->getUserID();

        switch($_GET["action"]) {
            case "all":
                if(isset($_GET["limit"])) {
                    echo json_encode(Notification::getForUser($currentUserID, (int) $_GET["limit"]));
                    exit();
                }
                echo json_encode(Notification::getForUser($currentUserID));
                exit();
            case "unread":

                if(isset($_GET["limit"])) {
                    echo json_encode(Notification::getUnreadForUser($currentUserID, (int) $_GET["limit"]));
                    exit();
                }

                echo json_encode(Notification::getUnreadForUser($currentUserID));
                exit();
            case "count":
                echo json_encode(Notification::getUnreadCount($currentUserID));
                exit();
            default:
                throw new InvalidArgumentException("Invalid action");
        }

    } catch(InvalidArgumentException $e) {
        http_response_code(400);
        die("IAE");
    } catch(NoSuchAccountException $e) {
        http_response_code(404);
        die("NSAE");
            
    } catch(DatabaseException $e) {
        http_response_code(500);
        die("DBE");
    }
}
else if($_SERVER["REQUEST_METHOD"] == "PUT") {
    try {
        
        $_PUT = array();
        parse_str(file_get_contents('php://input'), $_PUT);

        if (!isset($_PUT["action"])) {
            throw new InvalidArgumentException("Action not provided.");
        }

        $currentUserID = Authenticator::getCurrentUser()->getUserID();

        switch($_PUT["action"]) {
            case "read-all":
                Notification::markAllRead($currentUserID);
                http_response_code(200);
                echo json_encode([]);
                exit();
            case "read":
                if(!isset($_PUT["notificationID"])) {
                    throw new InvalidArgumentException("Missing notification ID");
                }
                $notification = Notification::load($_PUT["notificationID"]);

                if ($notification->getUserID() != $currentUserID) {
                    throw new NoSuchPermissionException("Not your notification");
                }
                $notification->setIsRead(true);
                $notification->update();
                exit();
            default:
                throw new InvalidArgumentException("Invalid action");
        }
    } catch(InvalidArgumentException $e) {
        http_response_code(400);
        die();
    } catch(NoSuchPermissionException $e) {
        http_response_code(403);
        die();
    } catch(NoSuchNotificationException $e) {
        http_response_code(404);
        die();
    } catch(DatabaseException $e) {
        http_response_code(500);
        die();
    }
}
else if($_SERVER["REQUEST_METHOD"] == "DELETE") {
    try {
        $_DELETE = array();
        parse_str(file_get_contents('php://input'), $_DELETE);

        if (!isset($_DELETE["notificationID"])) {
            throw new InvalidArgumentException("Missing notification ID");
        }

        $currentUserID = Authenticator::getCurrentUser()->getUserID();
        $notification = Notification::load($_DELETE["notificationID"]);

        if ($notification->getUserID() != $currentUserID) {
            throw new NoSuchPermissionException("Not your notification");
        }

        Notification::delete($notification->getID());
        exit();
    } catch(InvalidArgumentException $e) {
        http_response_code(400);
        die();
    } catch(NoSuchPermissionException $e) {
        http_response_code(403);
        die();
    } catch(NoSuchNotificationException $e) {
        http_response_code(404);
        die();
    } catch(DatabaseException $e) {
        http_response_code(500);
        die();
    }
}
else {
    http_response_code(405);
    die();
}

?>