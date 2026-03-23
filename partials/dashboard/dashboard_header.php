<header class="dashboard-header">
    <div class="dashboard-header__menu">
        <button class="dashboard-header__menu__button" id="dashboard-header__menu__buttonid">
            <svg width="35px" height="35px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6H20M4 12H20M4 18H20" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
    <div class="dashboard-header__branding">
        <span class="dashboard-header__branding__title">Team Project App</span>
    </div>

    <div class="dashboard-header__account">
        <span class="dashboard-header__account__name">
            <?php
                // Get current user object
                $user = \TTE\App\Auth\Authenticator::getCurrentUserSubclass();

                // Display 'seller name' or 'customer username' depending on account type.
                if ($user instanceof \TTE\App\Model\Seller) {
                    echo $user->getName();
                } else if ($user instanceof \TTE\App\Model\Customer) {
                    echo $user->getUsername();
                }
            ?>
        </span>

        <div class="dashboard-header__notifications">
            <button class="dashboard-header__notifications__bell" id="notificationBell">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1f1f1f"><path d="M160-200v-80h80v-280q0-83 50-147.5T420-792v-28q0-25 17.5-42.5T480-880q25 0 42.5 17.5T540-820v28q80 20 130 84.5T720-560v280h80v80H160Zm320-300Zm0 420q-33 0-56.5-23.5T400-160h160q0 33-23.5 56.5T480-80ZM320-280h320v-280q0-66-47-113t-113-47q-66 0-113 47t-47 113v280Z"/></svg>
                <span class="notification-dot" id="notificationDot" hidden></span>
            </button>

             <div class="notification-dropdown" id="notificationDropdown" hidden>
                <div class="notification-dropdown__header">
                    <h3>Notifications</h3>
                    <button class="notification-dropdown__mark-all" id="markAllRead">Mark all Read</button>
                </div>
                <ul class="notification-dropdown__list" id="notificationList">
                </ul>
                <div class="notification-dropdown__footer">
                    <a href="/notifications.php">View all</a>
                </div>
            </div>
        </div>
        <a href="/backend/API/Auth/logout.php" class="button dashboard-header__account_logout">Log Out</a>
    </div>
</header>

