// Get reservaton ID
const params = new URLSearchParams(document.location.search);
const reservationID = params.get("id");

// When submit button clicked, send create issue request
$("#submit-btn").click(() => {
    // Get title and description from form
    const issueTitle = $("#issue-title").val();
    const issueText = $("#issue-text").val();

    $(".error-text").text(""); // Reset error text

    // Issue must have a title
    if (issueTitle.length == 0) {
        $(".error-text").text("Issue must have a title.");
        return false;
    }

    // Issue description must be minimum 25 characters
    if (issueText.length < 25) {
        $(".error-text").text("Issue description must be at least 25 characters in length.");
        return false;
    }

    // Reservaton ID must be a number
    if (isNaN(reservationID)) {
        $(".error-text").text("Reservation ID is not a number.");
        return false;
    }

    // Send API request
    $.ajax({
        url: "/backend/API/Model/issue.php",
        type: "POST", // POST to indicate issue creation
        data: { // Attach data
            issueTitle: issueTitle,
            issueText: issueText,
            reservationID: reservationID
        },
        // Handle outcome
        statusCode: {
            200: () => { // Success
                // Go back to viewing reservation
                location.href = `/view_reservation.php?id=${reservationID}`
            },
            400: () => { // Bad Request
                $(".error-text").text("Bad Request");
            },
            404: () => { // Reservation not found
                $('.error-text').text("Reservation does not exist");
            },
            500: () => { // Internal Server Error
                $('.error-text').text("Internal Server Error");
            }
        }
    });
});
