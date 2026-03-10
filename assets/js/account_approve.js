$("#approve-btn").click(function() {

    const requestID = $(this).data('request-id');

    $.ajax({
        url: "/backend/API/Model/sellerRegistrationRequest.php",
        type: 'POST',
        data: {
            action: 'grant',
            sellerRequestID: requestID,
        },
        statusCode: {
            200: () => {
                alert("Request approved!");
                window.location.href = "/requests.php";
            },
            400: () => {
                alert("Error — please try again. (Bad Request)");
                location.reload();
            },
            403: () => {
                alert("Permission denied.");
                location.reload();
            },
            404: () => {
                alert("Error — please try again. (Request not found)");
                location.reload();
            },
            500: () => {
                alert("Error — please try again. (Server Error)");
                location.reload();
            }
        }
    });

});

$("#deny-btn").click(function() {

    const requestID = $(this).data('request-id');

    $.ajax({
        url: "/backend/API/Model/sellerRegistrationRequest.php",
        type: 'POST',
        data: {
            action: 'deny',
            sellerRequestID: requestID,
        },
        statusCode: {
            200: () => {
                alert("Request denied!");
                window.location.href = "/requests.php";
            },
            400: () => {
                alert("Error — please try again. (Bad Request)");
                location.reload();
            },
            403: () => {
                alert("Permission denied.");
                location.reload();
            },
            404: () => {
                alert("Error — please try again. (Request not found)");
                location.reload();
            },
            500: () => {
                alert("Error — please try again. (Server Error)");
                location.reload();
            }
        }
    });

});