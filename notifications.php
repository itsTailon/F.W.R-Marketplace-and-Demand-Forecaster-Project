<?php
use TTE\App\Auth\Authenticator;
use TTE\App\Model\Seller;
use TTE\App\Model\Bundle;
use TTE\App\Model\Account;
// Define document (i.e. tab) title
$DOCUMENT_TITLE = "My Notifications";

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





// Include dashboard header (i.e. 'title bar')
require_once 'partials/dashboard/dashboard_header.php';

// Include dashboard sidebar
require_once 'partials/dashboard/dashboard_sidebar.php';
?>





<div class="notifications-wrapper">
    <nav class="notifications-nav">
        <ul>
            <li>
                <a class="button button--rounded" href="/dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
                    <span>Home</span>
                </a>
            </li>
        </ul>
    </nav>
    <h1 class="notifications-title">My Notifications</h1>
    <div class="notifications-list-wrapper">
        <ul class="notifications-list">
        </ul>
        <div class="notifications-empty" id="notificationsEmpty" hidden>
            <p>No New Notifications</p>
        </div>
    </div>
</div>



<script>

function loadNotifications() {
    $.ajax({
        type: 'GET',
        url: '/backend/API/Model/notification.php',
        data: { action: 'all' },
        success: function(response) {
            const list = document.querySelector('.notifications-list');
            list.innerHTML = '';

            if (response.length === 0) {
                document.getElementById('notificationsEmpty').hidden = false;
                return;
            }
            document.getElementById('notificationsEmpty').hidden = true;

            response.forEach(function(ntfc) {
                const li = document.createElement('li');
                li.innerHTML = `<div class="notifications-notification">
                                    <h3 class="notifications-notification-title">
                                        ${ntfc.isRead == 0 ? '<span class="notifications-notification-unread-dot"></span>' : ''}
                                        ${ntfc.title}
                                    </h3>
                                    <p class="notifications-notification-message">${ntfc.message}</p>
                                    <span class="notifications-notification-time">${getTimeSince(ntfc.createdAt)}</span>
                                    <nav class="notifications-notification-nav">
                                        <ul>
                                            <li>
                                                <button class="button button--rounded">Mark as read</button>
                                            </li>
                                            <li>
                                                <button class="button button--rounded">Delete</button>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                `;
                list.appendChild(li);
            });
        }
    });
}

loadNotifications();

</script>



<?php
// Include page footer and closing tags
require_once 'partials/footer.php';
?>

