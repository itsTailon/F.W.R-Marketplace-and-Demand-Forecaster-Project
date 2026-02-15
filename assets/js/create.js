
$('#submit-btn').click(() => {
    // Get values in input fields/textboxes
    var bundleName = $("#name").val();
    var bundleDesc = $("#description").val();
    var bundleRRP = $("#rrp").val();
    var bundleDiscountPrice = $("#discount-price").val();
    var sellerID = $("#sellerID").val();

    $('.error-text').text(""); // Reset red error text

    // Perform validation checks

    // Bundle name must be 3+ characters
    if (bundleName.length < 3) {
        $('.error-text').text("Bundle name must be 3 characters or more.") // Display error in red text
        return false; // Halt further execution
    }

    // Bundle name cannot exceed 100 characters
    if (bundleName.length > 100) {
        $('.error-text').text("Bundle name too long! Must be 100 characters or less.");
        return false;
    }

    // Bundle needs a description
    if (bundleDesc.length == 0) {
        $('.error-text').text("Bundle description may not be empty.");
        return false;
    }

    // Bundle RRP must be a number
    if (isNaN(parseFloat(bundleRRP))) {
        $('.error-text').text("Recommended retail price must be a number.");
        return false;
    }

    // Bundle discount price must be a number
    if (isNaN(parseFloat(bundleDiscountPrice))) {
        $('.error-text').text("Discounted price must be a number.");
        return false;
    }


    // Send PUT request to bundle API
    $.ajax({
        url: "/backend/API/Model/bundle.php",
        type: 'POST',
        data: {
            title: bundleName,
            details: bundleDesc,
            rrp: bundleRRP,
            discountedPrice: bundleDiscountPrice,
        },
        statusCode: {
            200: () => { // Edit successful
                $('#bundle-name').text(bundleName);
            },
            400: () => {
                $('.error-text').text("Bad Request");
            },
            403: () => {
                $('.error-text').text("Permission denied");
            },
            404: () => {
                $('.error-text').text("Bundle not found");
            },
            500: () => {
                $('.error-text').text("Internal Server Error");
            }
        }
    });
});

// If clear button is clicked
$("#clear-btn").click(() => {
    // Empty all the input boxes
    $("#name").val('');
    $("#description").val('');
    $("#rrp").val('');
    $("#discount-price").val('');
});
