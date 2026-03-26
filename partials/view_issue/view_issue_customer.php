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
        <h1 class="single-issue__heading">Issue</h1>
        <span class="single-issue__date">Raised on <?php echo $issue->getCreationDate()->format("d/m/Y"); ?></span>

        <table class="single-issue__info">
            <tr>
                <td>Bundle</td>
                <td><?php echo $bundle->getTitle(); ?></td>
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

            <tr>
                <td>Issue Status</td>
                <td><?php echo $issue->getStatus() == IssueStatus::Ongoing ? 'Ongoing' : 'Resolved'; ?></td>
            </tr>

            <?php if ($issue->getStatus() == IssueStatus::Resolved) : ?>
                <tr>
                    <td>Seller Response</td>
                    <td><?php echo $issue->getSellerResponse(); ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="single-issue__reservation-actions">
            <a href="/view_reservation.php?id=<?php echo $issue->getReservationID(); ?>" class="button button--rounded">View Reservation</a>
            <a href="/view_bundle.php?id=<?php echo $issue->getBundleID(); ?>" class="button button--rounded">View Bundle</a>
        </div>

    </div>
</div>