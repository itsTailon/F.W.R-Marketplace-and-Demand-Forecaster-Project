$("#active-tab").click(() => { // If active reservation tab clicked
    location.href = "/active_reservations.php?status=active"; // Reload page with status set to active
});

$("#completed-tab").click(() => { // If completed reservation tab clicked
    location.href = "/active_reservations.php?status=completed"; // Reload page with status set to completed
});

$("#no-show-tab").click(() => { // If no-show reservation tab clicked
    location.href = "/active_reservations.php?status=no-show"; // Reload page with status set to no-show
});

$("#cancelled-tab").click(() => { // If cancelled reservation tab clicked
    location.href = "/active_reservations.php?status=cancelled"; // Reload page with status set to cancelled
});

$('.active-reservations-bundle-nav-cancel').on('click', function(e) {
    e.preventDefault();
    const reservationID = $(this).data('res-id');
    console.log(reservationID);
    $.ajax({
        type: 'DELETE',
        url: '/backend/API/Model/sellerReservation.php',
        data: {reservationID: reservationID},
        success: function() {
            // redirect
            alert("Reservation successfully cancelled!");
            window.location.href = '/active_reservations.php';
        },
        error: function(err) {
            console.log('Failed to Cancel: ' + err.status);
        }
    });

});
