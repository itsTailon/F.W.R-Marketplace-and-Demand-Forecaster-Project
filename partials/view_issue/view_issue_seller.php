<?php

use TTE\App\Helpers\CurrencyTools;
use TTE\App\Model\Bundle;
use TTE\App\Model\IssueStatus;

// Load Bundle object
$bundle = $issue->getBundle();
?>

<div class="single-issue-container">
    <div class="single-issue-dashboard-buttons">
        <a href="issues.php" class="button button--rounded">Issues</a>
        <a href="dashboard.php" class="button button--rounded">Home</a>
    </div>

    <div class="single-issue">
        <h1 class="single-issue__heading">Customer Issue</h1>
        <span class="single-issue__date">Raised on <?php echo $issue->getCreationDate()->format("d/m/Y"); ?></span>

        <table class="single-issue__info">
            <tr>
                <td>Bundle</td>
                <td><?php echo $bundle->getTitle(); ?></td>
            </tr>

            <tr>
                <td>Customer</td>
                <td><?php echo $issue->getCustomer()->getUsername(); ?></td>
            </tr>

            <tr>
                <td>Price</td>
                <td>£<?php echo CurrencyTools::gbxToDecimalString($bundle->getDiscountedPriceGBX()); ?> (RRP £<?php echo CurrencyTools::gbxToDecimalString($bundle->getRrpGBX()); ?>)</td>
            </tr>

            <tr>
                <td>Reservation Status</td>
                <td><?php echo ucfirst($issue->getReservation()->getStatus()->value); ?></td>
            </tr>

            <tr>
                <td>Issue Title</td>
                <td><?php echo $issue->getTitle(); ?></td>
            </tr>

            <tr>
                <td>Issue Text</td>
                <td><?php echo $issue->getDescription(); ?></td>
            </tr>
        </table>

        <div class="single-issue__reservation-actions">
            <a href="/view_reservation.php?id=<?php echo $issue->getReservationID(); ?>" class="button button--rounded">View Reservation</a>
            <a href="/view_bundle.php?id=<?php echo $issue->getBundleID(); ?>" class="button button--rounded">View Bundle</a>
        </div>

        <?php if ($issue->getStatus() == IssueStatus::Ongoing): ?>
            <div class="single-issue__respond">
                <div>
                    <label for="issue-response" class="single-issue__respond__label">Choose a response to close this issue.</label>
                    <select name="" class="category-selector" id="issue-response">
                        <option value="Refund will be issued in store.">Refund will be issued in store.</option>
                        <option value="Please contact the store directly to resolve this.">Please contact the store directly to resolve this.</option>
                        <option value="Issue closed as resolved.">Issue closed as resolved.</option>
                        <option value="Issue closed as spam.">Issue closed as spam.</option>
                    </select>
                </div>

                <button id="submit" class="single-issue__respond__submit-btn button button--rounded">Close Issue</button>
            </div>

            <script>
                $("#submit").click(function () {
                    // Send API request
                    $.ajax({
                        url: "/backend/API/Model/issue.php",
                        type: "PUT", // PUT to indicate issue update
                        dataType: "text",
                        data: { // Attach data
                            sellerResponse: $("#issue-response").val(),
                            issueID: <?php echo $issue->getID(); ?>,
                        },

                        statusCode: {
                            200: function () {
                                alert("Issue successfully closed.");
                                location.reload();
                            },

                            400: function () {
                                alert("Error. Please try again later.");
                                location.reload();
                            },

                            403: function () {
                                alert("Error. Please try again later.");
                                location.reload();
                            },

                            410: function () {
                                alert("Error. Please try again later.");
                                location.reload();
                            },

                            409: function () {
                                alert("Error. Please try again later.");
                                location.reload();
                            },

                            500: function () {
                                alert("Error. Please try again later.");
                                location.reload();
                            },
                        },

                    });
                });


            </script>
        <?php else: ?>

        <?php endif; ?>
    </div>
</div>