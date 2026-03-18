

function getTimeSince(timestamp) {

    const then = new Date(timestamp);
    const now = new Date()

    const timeDiff = now - then;

    const sec = Math.floor(timeDiff / 1000)
    const mins = Math.floor(sec / 60);
    const hours = Math.floor(mins / 60);
    const days = Math.floor(hours /24);
    const weeks = Math.floor(days / 7);
    const months = Math.floor(days / 30);
    const years = Math.floor(days / 365);


    
    if(years > 0) {
        return years + (years === 1 ? ' year ago' : ' years ago');
    }
    else if(months > 0){
        return months + (months === 1 ? ' month ago' : ' months ago');
    }
    else if(weeks > 0){
        return weeks + (weeks === 1 ? ' week ago' : ' weeks ago');
    }
    else if(days > 0){
        return days + (days === 1 ? ' day ago' : ' days ago');
    }
    else if(hours > 0){
        return hours + (hours === 1 ? ' hour ago' : ' hours ago');
    }
    else if(mins > 0){
        return mins + (mins === 1 ? ' min ago' : ' mins ago');
    }
    else if(sec > 0){
        return sec + (sec === 1 ? ' second ago' : ' seconds ago');
    }
    else {
        return 'error';
    }

}




document.getElementById('notificationBell').addEventListener("click", () => {
    const dropDown = document.getElementById('notificationDropdown');
    dropDown.hidden = !dropDown.hidden;

    if (!dropDown.hidden) {
        $.ajax({
            type: 'GET',
            url: '/backend/API/Model/notification.php',
            data: {
                action: 'all', 
                limit: 3 
            },
            success: function(response) {
                const list = document.getElementById('notificationList');
                list.innerHTML = '';

                if (response.length === 0) {
                    list.innerHTML = '<li class="notification-dropdown__empty">No notifications</li>';
                    return;
                }

                response.forEach(function(ntfc) {
                    const li = document.createElement('li');
                    li.className = 'notification-dropdown__item';
                    li.dataset.id = ntfc.notificationID;
                    li.innerHTML = `
                        ${ntfc.isRead == 0 ? '<span class="notification-dropdown__item__dot"></span>' : ''}
                        <span class="notification-dropdown__item__title">${ntfc.title}</span>
                        <span class="notification-dropdown__item__message">${ntfc.message}</span>
                        <span class="notification-dropdown__item__time">${getTimeSince(ntfc.createdAt)}</span>
                    `;
                    list.appendChild(li);
                });
            },
            error: function() {
                console.log("Error getting notifications");
            }
        });
    }
});



document.getElementById('markAllRead').addEventListener("click", () => {

    // mark all as read.

    $.ajax({
        type: 'PUT',
        url: '/backend/API/Model/notification.php',
        data: {
            action: "read-all"
        },
    success: function() {
        console.log("here");
        const dropDown = document.getElementById('notificationDropdown');
        dropDown.hidden = true;
        checkNotifications();
    },
    error: function(err) {
        console.log("error marking all notifications as read");
    }

    });
});




// close when click on anything but notification dropdown.
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dashboard-header__notifications')) {
        document.getElementById('notificationDropdown').hidden = true;
    }
});

// close on scroll
window.addEventListener('scroll', function() {
    document.getElementById('notificationDropdown').hidden = true;
});





function checkNotifications() {
    $.ajax({
        type: 'GET',
        url: '/backend/API/Model/notification.php',
        data: {
            action: 'count',
        },
    success: function(response) {
        const count = parseInt(response,10);
        const dot = document.getElementById('notificationDot');

        if(count === 0){
            dot.hidden = true;
            return;
        }
        dot.hidden = false;
    },
    error: function (e) {
        console.log("Error getting number of unread notifications.");
    }
    });
}



checkNotifications();

setInterval(checkNotifications, 30000);