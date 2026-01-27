

(() => {
    const menuButton = document.getElementById("dashboard-header__menu__buttonid");
    const overlay = document.getElementById("sidebarOverlay");
    const sidebar = document.querySelector(".sidebar");

    if(!menuButton || !overlay || !sidebar) {
        return;
    }

    const open = () => {
        document.body.classList.add("sidebar-open");
        overlay.hidden = false;
    }

    const close = () => {
        document.body.classList.remove("sidebar-open");
        overlay.hidden = true;
    }


    menuButton.addEventListener("click", open);
    overlay.addEventListener("click", close);


})();