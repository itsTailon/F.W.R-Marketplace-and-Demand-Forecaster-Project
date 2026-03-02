<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;

// Define document (i.e. tab) title
$DOCUMENT_TITLE = "Account Settings";

// Include page head
require_once 'partials/head.php';

if (!Authenticator::isLoggedIn()) {
    header('Location: /login.php');
    die('You are not logged in. If you are not redirected automatically, please click <a href="/login.php">here</a>.');
}

// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard">
        <div class="dashboard-buttons">
            <!-- TODO: Add symbols  -->
            <a href="dashboard.php" class="button button--rounded">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"></path></svg>
                Home
            </a>
        </div>

        <h1>Account Settings</h1>

        <div class="account-update-group">
            <h2>Update Email</h2>
            <table class="account-update-inputs">
                <tr>
                    <td>Current email</td>
                    <td><?php echo Authenticator::getCurrentUser()->getEmail(); ?></td>
                </tr>
                <tr>
                    <td>New email</td>
                    <td><div class="textbox textbox--size-fill" data-type="email" data-label="New email" data-id="new-email" id="new-email-textbox" ></div></td>
                </tr>
                <tr>
                    <td><button class="button button--rounded" id="update-email-button">Update</button></td>
                    <td></td>
                </tr>
            </table>
        </div>

        <div class="account-update-group">
            <h2>Update Password</h2>
            <table class="account-update-inputs">
                <tr>
                    <td>Current password</td>
                    <td><div class="textbox textbox--size-fill" data-type="password" data-label="Current password" data-id="current-password" id="current-password-textbox"></div></td>
                </tr>
                <tr>
                    <td>New password</td>
                    <td><div class="textbox textbox--size-fill" data-type="password" data-label="New password" data-id="new-password" id="new-password-textbox"></div></td>
                </tr>
                <tr>
                    <td>Confirm new password</td>
                    <td><div class="textbox textbox--size-fill" data-type="password" data-label="Confirm new password" data-id="confirm-new-password" id="confirm-new-password-textbox"></div></td>
                </tr>

                <tr>
                    <td><button class="button button--rounded" id="update-password-button">Update</button></td>
                    <td></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

