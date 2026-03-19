


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


if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {

        $currentUserID = Authenticator::getCurrentUser()->getUserID();

        if(!Seller::existsWithID($currentUserID)){
            throw new NoSuchPermissionException("Only Sellers can use this.");
        }

        echo json_encode(SellerAction::getForSeller($currentUserID));
        exit();

    } catch(NoSuchAccountException $e) {
        http_response_code(404);
        die("NSAE");
    } catch(NoSuchPermissionException $e) {
        http_response_code(403);
        die("NSPE");          
    } catch(DatabaseException $e) {
        http_response_code(500);
        die("DBE");
    }
}
else if($_SERVER["REQUEST_METHOD"] == "POST") {
    try {

        if (!isset($_POST["action"]) || !isset($_POST["reason"])) {
            throw new InvalidArgumentException("Missing fields");
        }

        $currentUserID = Authenticator::getCurrentUser()->getUserID();

        if(!Seller::existsWithID($currentUserID)){
            throw new NoSuchPermissionException("Only Sellers can use this.");
        }

        $sellerAction = SellerAction::create([
            "sellerID" => $currentUserID,
            "action" => $_POST["action"],
            "reason" => $_POST["reason"]
        ]);

        echo json_encode($sellerAction);
        exit();

    } catch(InvalidArgumentException $e) {
        http_response_code(400);
        die();
    } catch(NoSuchPermissionException $e) {
        http_response_code(403);
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

        if (!isset($_DELETE["actionID"])) {
            throw new InvalidArgumentException("Missing actionID");
        }

        $currentUserID = Authenticator::getCurrentUser()->getUserID();
        
        if(!Seller::existsWithID($currentUserID)){
            throw new NoSuchPermissionException("Only Sellers can you this.");
        }



        $sellerAction = SellerAction::load($_DELETE["actionID"]);

        if ($sellerAction->getSellerID() != $currentUserID) {
            throw new NoSuchPermissionException("Not your seller Action");
        }

        SellerAction::delete($sellerAction->getID());

        echo json_encode([]);
        exit();
    } catch(InvalidArgumentException $e) {
        http_response_code(400);
        die();
    } catch(NoSuchPermissionException $e) {
        http_response_code(403);
        die();
    } catch(NoSuchSellerActionException $e) {
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