<?php

use TTE\App\Auth\Authenticator;
use TTE\App\Helpers\CurrencyTools;
use TTE\App\Model\Customer;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\Issue;
use TTE\App\Model\IssueAlreadyResolvedException;
use TTE\App\Model\IssueStatus;
use TTE\App\Model\MissingValuesException;
use TTE\App\Model\NoSuchReservationException;
use TTE\App\Model\Reservation;
use TTE\App\Model\Seller;

$DOCUMENT_TITLE = "Individual Issue View";

require_once 'partials/head.php';

// TODO: Replace this with graceful redirect to login page
// (Temporary code) Halt rendering if user not logged in
if (!Authenticator::isLoggedIn()) {
    header('Location: /login.php');
    die('ERROR: Not logged in!');
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';
require_once 'partials/dashboard/dashboard_sidebar.php';

$issue_id = intval($_GET['id']) ?? -1;

if ($issue_id == -1 || !Issue::existsWithID($issue_id)) {
    header('Location: /404.php');
    die('ERROR: Issue ID not specified or not present in our database!');
}

// Initialise all the things we'll need to null so that they can be in range later
$issue = null;
$bundle = null;
$reservation = null;
$seller = null;
$purchaser = null;

try {
    // Load the issue and the bundle
    $issue = Issue::load($issue_id);
    $bundle = $issue->getBundle();

    // Load the reservation
    $getReservationQuery = DatabaseHandler::getPDO()->prepare('SELECT `reservationID` FROM `reservation` WHERE `bundleID` = :bundleID');
    $getReservationQuery->execute(['bundleID' => $bundle->getID()]);
    $reservationID = $getReservationQuery->fetch()["reservationID"];
    $reservation = Reservation::load($reservationID);

    // Load the associated parties
    $seller = Seller::load($bundle->getSellerID());
    $purchaser = Customer::load($bundle->getPurchaserID());
} catch (DatabaseException $e) {
    die("Problems were encountered while loading data from the database. The error message provided was: " . $e->getMessage() . ".");
} catch (NoSuchReservationException $e) {
    die("We were unable to find the reservation associated with this issue. The error message provided was: " . $e->getMessage() . ".");
}
?>

<div id = "viewissue">
    <h1>Issue</h1>
    <table id = "issuedetails">
        <tr>
            <td>Bundle</td>
            <td><?php echo $bundle->getTitle() ?></td>
        </tr>
        <tr>
            <td>Seller</td>
            <td><?php echo $seller->getName() ?></td>
        </tr>
        <tr>
            <td>Price</td>
            <td><?php echo "£" . CurrencyTools::gbxToDecimalString($bundle->getDiscountedPriceGBX()) . " (£" . CurrencyTools::gbxToDecimalString($bundle->getRrpGBX()) . "RRP)" ?></td>
        </tr>
        <tr>
            <td>Bundle Status</td>
            <td><?php echo $bundle->getStatus()->value ?></td>
        </tr>
        <tr>
            <td>Issue Title</td>
            <td><?php echo $issue->getDescription() ?></td>
        </tr>
        <tr>
            <td>Issue Status</td>
            <td><?php echo ($issue->getStatus() == IssueStatus::Resolved) ? "Resolved" : "Awaiting seller response"; ?></td>
        </tr>
    </table>
</div>

<?php if ($issue->getStatus() == IssueStatus::Ongoing): ?>
    <?php if (Authenticator::getCurrentUser() == $purchaser->getUserID()): ?>
        <div id = "waitforresponse">
            <p>
                If the seller does not respond within three working days, please contact <?php echo "<a href = 'mailto:". $seller->getEmail() ."'>" .$seller->getEmail() . "</a>"; ?>
                from your registered email with the following information:<br>
                Username: <?php echo $purchaser->getUserID(); ?><br>
                Issue number: <?php echo $issue->getID(); ?>
            </p>
        </div>
    <?php elseif (Authenticator::getCurrentUser() == $seller->getUserID()): ?>
        <form id = "sellerresponseform" method = "post">
            <label for = "response" id = "responselabel">Choose a response to resolve this issue</label>
            <select id = "response" name = "response">
                <option value = "Refund will be issued in store.">Refund will be issued in store.</option>
                <option value = "Please contact the store directly to resolve this.">Please contact the store directly to resolve this.</option>
                <option value = "Issue closed as resolved.">Issue closed as resolved.</option>
                <option value = "Issue closed as spam.">Issue closed as spam.</option>
            </select>
            <input type="submit" value="Close Issue"/>
        </form>
    <?php
    if ($_POST["response"]) {
        try {
            $issue->markResolved(new DateTimeImmutable(), $_POST["response"]);
            $issue->update();
        } catch (IssueAlreadyResolvedException $e) { // No need to cause a fuss if the issue was already resolved
        } catch (DatabaseException $e) {
            echo "Problems were encountered with the database while marking the issue as resolved. The error message provided was: " . $e->getMessage() . ".";
        } catch (MissingValuesException $e) {
            echo "There were required values missing from the Issue object";
        }
    }
    endif; ?>
<?php endif; ?>
<?php if ($issue->getStatus() == IssueStatus::Resolved): ?>
<div id = "response">
    <b>Resolved on <?php echo $issue->getResolvedDate()->format("d/m/y"); ?> with message "<?php echo $issue->getSellerResponse(); ?>"</b>
    <?php if (Authenticator::getCurrentUser() == $purchaser->getUserID()): ?>
        <p>
            If this response is unsatisfactory, please contact <?php echo "<a href = 'mailto:". $seller->getEmail() ."'>" .$seller->getEmail() . "</a>"; ?>
            from your registered email (<?php echo $purchaser->getEmail(); ?>) with the following information: <br>
            Username: <?php echo $purchaser->getUsername(); ?><br>
            Issue number: <?php echo $issue->getID(); ?>
        </p>
    <?php endif; ?>
</div>
<?php endif;

// Include page footer and closing tags
require_once 'partials/footer.php';
?>
