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
        <a href="/backend/API/Auth/logout.php" class="button dashboard-header__account_logout">Log Out</a>
    </div>
</header>