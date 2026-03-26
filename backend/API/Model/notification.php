


<?php 

use TTE\App\Auth\Authenticator;
use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Model\Notification;
use TTE\App\Model\NoSuchAccountException;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\NoSuchNotificationException;


include '../../../vendor/autoload.php';

session_start();

// JSON heading for all JSON-encoded messages
header('Content-Type: application/json');

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    echo json_encode(http_response_code(401));
    die();
}

// GET REQUEST (Get notifications)
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {

        // check if action provided.
        if (!isset($_GET["action"])) {
            throw new InvalidArgumentException("Action not provided.");
        }


        // get current user id.
        $currentUserID = Authenticator::getCurrentUser()->getUserID();

        
        switch($_GET["action"]) {
            case "all":
                // get all notifications(both unread and read)
                if(isset($_GET["limit"])) {
                    // if specified a limit only return limited amount.
                    echo json_encode(Notification::getForUser($currentUserID, (int) $_GET["limit"]));
                    exit();
                }

                // return all notifications
                echo json_encode(Notification::getForUser($currentUserID));
                exit();
            case "unread":
                // get only unread notifications

                if(isset($_GET["limit"])) {
                    // get unread notifications up to specified limit. 
                    echo json_encode(Notification::getUnreadForUser($currentUserID, (int) $_GET["limit"]));
                    exit();
                }

                // return all unread notifications.
                echo json_encode(Notification::getUnreadForUser($currentUserID));
                exit();
            case "count":
                // get number of unread notifications.
                echo json_encode(Notification::getUnreadCount($currentUserID));
                exit();
            default:
                // throw error as incorrect action value.
                throw new InvalidArgumentException("Invalid action");
        }

    } catch(InvalidArgumentException $e) {
        // return HTTP 400 bad request.
        http_response_code(400);
        die("IAE");
    } catch(NoSuchAccountException $e) {
        // return HTTP 404 Account not found.
        http_response_code(404);
        die("NSAE");
            
    } catch(DatabaseException $e) {
        // return HTTP 500 Database error.
        http_response_code(500);
        die("DBE");
    }
}
// PUT REQUEST (mark notification/s as read)
else if($_SERVER["REQUEST_METHOD"] == "PUT") {
    try {
        
        $_PUT = array();
        parse_str(file_get_contents('php://input'), $_PUT);


        // check if action argument is set.
        if (!isset($_PUT["action"])) {
            throw new InvalidArgumentException("Action not provided.");
        }

        $currentUserID = Authenticator::getCurrentUser()->getUserID();

        
        switch($_PUT["action"]) {
            case "read-all":

                // mark all notifications as read for user.
                Notification::markAllRead($currentUserID);
                http_response_code(200);
                echo json_encode([]);
                exit();
            case "read":
                // mark specific notification as read if found.
                if(!isset($_PUT["notificationID"])) {
                    throw new InvalidArgumentException("Missing notification ID");
                }
                $notification = Notification::load($_PUT["notificationID"]);

                if ($notification->getUserID() != $currentUserID) {
                    throw new NoSuchPermissionException("Not your notification");
                }
                $notification->setIsRead(true);

                // update database.
                $notification->update();
                exit();
            default:
                throw new InvalidArgumentException("Invalid action");
        }
    } catch(InvalidArgumentException $e) {
        // HTTP 400 bad request.
        http_response_code(400);
        die();
    } catch(NoSuchPermissionException $e) {
        // HTTP 400 user doesnt have permission e.g. isnt owner of notification
        http_response_code(403);
        die();
    } catch(NoSuchNotificationException $e) {
        // HTTP 404 notification not found with provided id.
        http_response_code(404);
        die();
    } catch(DatabaseException $e) {
        // HTTP 500 database error.
        http_response_code(500);
        die();
    }
}

// DELETE REQUEST (delete a notification)
else if($_SERVER["REQUEST_METHOD"] == "DELETE") {
    try {
        $_DELETE = array();
        parse_str(file_get_contents('php://input'), $_DELETE);

        // check if notification ID argument is set.
        if (!isset($_DELETE["notificationID"])) {
            throw new InvalidArgumentException("Missing notification ID");
        }

        // get current user.
        $currentUserID = Authenticator::getCurrentUser()->getUserID();

        // load notification
        $notification = Notification::load($_DELETE["notificationID"]);

        // check if current user is the owner of the notification.
        if ($notification->getUserID() != $currentUserID) {
            throw new NoSuchPermissionException("Not your notification");
        }

        // delete the notification.
        Notification::delete($notification->getID());
        echo json_encode([]);
        exit();
    } catch(InvalidArgumentException $e) {

        // HTTP 400 bad request.
        http_response_code(400);
        die();
    } catch(NoSuchPermissionException $e) {
        // HTTP 403 user does not have permission to delete notification.
        http_response_code(403);
        die();
    } catch(NoSuchNotificationException $e) {
        // HTTP 404 notification does not exist.
        http_response_code(404);
        die();
    } catch(DatabaseException $e) {
        // HTTP 500 data base error.
        http_response_code(500);
        die();
    }
}
else {
    http_response_code(405);
    die();
}

?>