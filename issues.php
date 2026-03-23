<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;
use TTE\App\Model\Issue;
use TTE\App\Model\DatabaseException;
use TTE\App\Model\NoSuchIssueException;
use TTE\App\Model\DatabaseHandler;
use TTE\App\Model\IssueStatus;

$DOCUMENT_TITLE = "Customer Issues";

require_once 'partials/head.php';
if (!Authenticator::isLoggedIn()) {
    header("Location: /login.php");
    die('You are not logged in. If you are not redirected automatically, please click <a href="/login.php">here</a>.');
}

$acc = Authenticator::getCurrentUserSubclass();
if (!($acc instanceof Seller)) {
    header('Location: /dashboard.php');
    die('You must be a seller to view this page.');
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';
require_once 'partials/dashboard/dashboard_sidebar.php';

if (isset($_GET['all']) && $_GET['all'] == "1") {
    $all = True;
} else {
    $all = False;
}

$stmt = DatabaseHandler::getPDO()->prepare(
    "SELECT issue.issueID
    FROM issue 
    INNER JOIN reservation on issue.reservationID = reservation.reservationID 
    INNER JOIN bundle on reservation.bundleID = bundle.bundleID 
    WHERE bundle.sellerID = :id"
    . ($all?
        ";"
      : " AND issueStatus <> 'resolved';")
);

try {
    $stmt->execute([":id" => $acc->getUserID()]);
    $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\PDOException $e){
    echo "<script>alert('Error fetching issues.');</script>
        <span>
            Error fetching issues. Please try again.
        </span>";
    $issues = array();
}
?>


<div class="issues-list-wrapper">
    <nav class="issues-list-nav">
        <a class="button button--rounded" href="/dashboard.php">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
            <span>Home</span>
        </a>
    </nav>

    <h1 class="issues-list-title">Customer Issues</h1>

    <?php if (!$all): ?>
        <nav class="issues-list-nav">
            <a class="button button--rounded issues-list-viewall-button" href="/issues.php?all=1">Include Resolved Issues</a>
        </nav>
    <?php endif; ?>

    <?php if (!$data): ?>
        <span class="issues-list-text-none">No Customer Issues</span>
    
    <?php else: ?>
        <div class="issues-list-list">
            <?php foreach ($data as $issue): ?>
                <?php
                    try {
                        $issueObj = Issue::Load($issue["issueID"]);
                        $bundleObj = $issueObj->getBundle();
                        $customerObj = $issueObj->getCustomer();
                    } catch (DatabaseException | NoSuchIssueException $e) {
                        echo "Error loading this bundle, please try refreshing the page.";
                        continue;
                    }
                    
                    $bundleTitle = $bundleObj->getTitle();
                    $customerName = $customerObj->getUsername();
                    $issueTitle = $issueObj->getTitle(); // NYI
                    $issueOpenDate = $issueObj->getCreationDate();
                    $issueStatus = $issueObj->getStatus();

                    $now = new \DateTimeImmutable();
                    if ($now->format('Y-m-d') == $issueOpenDate->format('Y-m-d')) {
                        $issueDateText = "Today at " . $issueOpenDate->format('H:i');
                    } else {
                        $issueDateText = $issueOpenDate->format('Y-m-d');
                    }
                ?>
                <div class="issues-list-issue">
                    <div>
                        <h2 class="issues-list-issue-bundle-title">Bundle: <?php echo $bundleTitle ?></h2>
                        <p class="issues-list-issue-title">Issue: <?php echo $issueTitle; ?></p>
                        <p class="issues-list-issue-customer">Customer: <?php echo $customerName; ?></p>
                        <p class="issues-list-issue-time"><i><?php echo $issueDateText; ?></i></p>
                        <?php if ($issueStatus != IssueStatus::Resolved): ?>
                            <p class="issues-list-issue-status-unresolved">Not Resolved</p>
                        <?php else: ?>
                            <p class="issues-list-issue-status-responded">Responded</p>
                        <?php endif; ?>
                    </div>
                    <nav class="issues-list-issue-nav">
                        <a class="button button--rounded issues-list-issue-nav-button-clickable" href="/view_issue.php?id=<?php echo $issue['issueID']; ?>">View</a>
                    </nav>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

