$("#reserve-btn").click(function() {

    var bundleID = $("#bundleID").val();
    var purchaserID = $("#purchaserID").val();

    $.ajax({
        url: "/backend/API/Model/consumerReservation.php",
        type: 'POST',
        data: {
            bundleID: bundleID,
            purchaserID: purchaserID,
        },
        statusCode: {
            200: () => {
                alert("Bundle successfully reserved!");
                location.reload();
            },
            400: () => {
                alert("Error — please try again. (Bad Request)");
            },
            403: () => {
                alert("Permission denied.");
            },
            404: () => {
                alert("Error — please try again. (Bundle not found)");
            },
            500: () => {
                alert("Error — please try again. (Server Error)");
            }
        }
    });

});