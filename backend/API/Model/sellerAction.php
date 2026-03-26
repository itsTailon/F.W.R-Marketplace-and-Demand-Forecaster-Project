


<?php 

use TTE\App\Auth\Authenticator;
use TTE\App\Auth\NoSuchPermissionException;
use TTE\App\Model\Notification;
use TTE\App\Model\Seller;
use TTE\App\Model\SellerAction;
use TTE\App\Model\NoSuchAccountException;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\NoSuchNotificationException;
use TTE\App\Model\NoSuchSellerActionException;

include '../../../vendor/autoload.php';

session_start();

header('Content-Type: application/json');

// Check that user is currently logged in
if (!Authenticator::isLoggedIn()) {
    echo json_encode(http_response_code(401));
    die();
}

// Check if user is a seller.
if(!(Authenticator::getCurrentUserSubclass() instanceof Seller)){
    echo json_encode(http_response_code(401));
    die();
}

// GET REQUEST ( get all seller actions for seller.)
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {

        // get current user id.
        $currentUserID = Authenticator::getCurrentUser()->getUserID();

        if(!Seller::existsWithID($currentUserID)){
            throw new NoSuchPermissionException("Only Sellers can use this.");
        }

        // get and return all seller actions for seller.
        echo json_encode(SellerAction::getForSeller($currentUserID));
        exit();

    } catch(NoSuchAccountException $e) {
        // http 404 account not found.
        http_response_code(404);
        die("NSAE");
    } catch(NoSuchPermissionException $e) {
        // HTTP 403 user does not have permission.
        http_response_code(403);
        die("NSPE");          
    } catch(DatabaseException $e) {
        // HTTP 500 database error.
        http_response_code(500);
        die("DBE");
    }
}
// POST REQUEST (create seller action)
else if($_SERVER["REQUEST_METHOD"] == "POST") {
    try {

        // check if action and reason are set.
        if (!isset($_POST["action"]) || !isset($_POST["reason"])) {
            throw new InvalidArgumentException("Missing fields");
        }

        // get current user id.
        $currentUserID = Authenticator::getCurrentUser()->getUserID();

        // check if seller account exists with id of user.
        if(!Seller::existsWithID($currentUserID)){
            throw new NoSuchPermissionException("Only Sellers can use this.");
        }

        // create a seller action with details.
        $sellerAction = SellerAction::create([
            "sellerID" => $currentUserID,
            "action" => $_POST["action"],
            "reason" => $_POST["reason"]
        ]);

        // return seller action.
        echo json_encode($sellerAction);
        exit();

    } catch(InvalidArgumentException $e) {
        // HTTP 400 bad request.
        http_response_code(400);
        die();
    } catch(NoSuchPermissionException $e) {
        // HTTP 403 no permission
        http_response_code(403);
        die();
    } catch(DatabaseException $e) {
        // HTTP 500 database error.
        http_response_code(500);
        die();
    }
}
// DELETE REQUEST (delete seller action)
else if($_SERVER["REQUEST_METHOD"] == "DELETE") {

    try {
        $_DELETE = array();
        parse_str(file_get_contents('php://input'), $_DELETE);

        // check if action ID is set.
        if (!isset($_DELETE["actionID"])) {
            throw new InvalidArgumentException("Missing actionID");
        }

        // get current user id.
        $currentUserID = Authenticator::getCurrentUser()->getUserID();
        
        // check if user is a seller.
        if(!Seller::existsWithID($currentUserID)){
            throw new NoSuchPermissionException("Only Sellers can you this.");
        }


        // load the seller action.
        $sellerAction = SellerAction::load($_DELETE["actionID"]);


        // check if seller action belongs to caller.
        if ($sellerAction->getSellerID() != $currentUserID) {
            throw new NoSuchPermissionException("Not your seller Action");
        }

        // delete seller action.
        SellerAction::delete($sellerAction->getID());


        echo json_encode([]);
        exit();
    } catch(InvalidArgumentException $e) {
        // HTTP 400 bad request.
        http_response_code(400);
        die();
    } catch(NoSuchPermissionException $e) {
        // HTTP 403 no permission.
        http_response_code(403);
        die();
    } catch(NoSuchSellerActionException $e) {
        // HTTP 404 no seller action found.
        http_response_code(404);
        die();
    } catch(DatabaseException $e) {
        // HTTP 500 database exception.
        http_response_code(500);
        die();
    }
}
else {
    http_response_code(405);
    die();
}

?>