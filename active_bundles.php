<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;
use TTE\App\Model\Bundle;
use TTE\App\Model\Account;
// Define document (i.e. tab) title
$DOCUMENT_TITLE = "Active Bundles";

// Include page head
require_once 'partials/head.php';


if (!Authenticator::isLoggedIn()) {
    header('Location: /login.php');
    die('ERROR: Not logged in! <br> TODO: redirect to login page');
}

$acc = Authenticator::getCurrentUser();
if (!$acc) {
    header('Location: /login.php');
    die('');
}

if(!Seller::existsWithID($acc->getUserID())) {
    header('Location: /dashboard.php');
    die('');
}



// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';

$bundles = Seller::getAllBundlesForUser($acc->getUserID());


?>




<div class="active-bundles-wrapper">
    <nav class="active-bundles-nav">
        <ul class="active-bundles-nav-left">
            <li>
                <a class="button button--rounded" href="/dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                    <span>Home</span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="active-bundles"></div>
    <?php if (!$bundles): ?>
        <h1>No Active Bundles</h1>
    <?php else: ?>
    <h1>Active bundles</h1>   
    <div class="active-bundles-list-wrapper">
        <ul class="active-bundles-list">
            <?php foreach ($bundles as $b): ?>
                <?php
                if($b['bundleStatus'] != 'available' && $b['bundleStatus'] != 'reserved') {
                    continue;
                }
                $bundle = Bundle::load($b['bundleID']);
                ?>
            <li>
                <h1 class="active-bundles-bundle-name"><?php echo $bundle->getTitle() ?></h1>
                <p class="active-bundles-bundle-description">Bundle Status: <?php echo $bundle->getStatus()->value ?></p>
                <p class="active-bundles-bundle-description">Bundle description: <?php echo $bundle->getDetails() ?></p>
<!--                <p class="active-bundles-bundle-date"><i>Bundle Date posted</i></p>-->

                <nav class="active-bundles-bundle-nav">
                    <ul>
                        <li><h2>£<?php echo number_format($bundle->getDiscountedPriceGBX() / 100, 2); ?></h2></li>
                        <li><a class="active-bundles-bundle-nav-view" href="/view_bundle.php?id=<?php echo $bundle->getID() ?>">View</a></li>
                        <li><a class="active-bundles-bundle-nav-view" href="/edit_bundle.php?id=<?php echo $bundle->getID() ?>">Edit</a></li>
                        <li><a class="active-bundles-bundle-nav-cancel" data-bndle-id="<?php echo $bundle->getID() ?>">Delete</a></li>
                    </ul>
                </nav>

            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

</div>


<script>
    $('.active-bundles-bundle-nav-cancel').on('click', function(e) {
        e.preventDefault();
        const bundleID = $(this).data('bndle-id');
        console.log(bundleID);
        $.ajax({
            type: 'DELETE',
            url: '/backend/API/Model/bundle.php',
            data: {bundleID: bundleID},
            success: function() {
                // redirect
                window.location.href = '/active_bundles.php';
            },
            error: function(err) {
                console.log('Failed to Cancel: ' + err.status);
            }
        });

    });

    

</script>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

