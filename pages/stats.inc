<?php
$stats = $mgr-> getStats();
?>
<div class="middle left" ng-controller="StatisticsController">
    <h1>Faucet Statistics</h1>

    <h2>General</h2>
    <ul>
        <li>Total Users: <b><?= $stats["user_count"] ?> users</b></li>
        <li>Total Amount Dispensed: <b><?= $stats["total_dispensed"]|0 ?> satoshi</b></li>
        <li>Total Amount Withdrawn: <b><?= $stats["total_withdrawn"] ?> satoshi</b></li>
        <li>Total Amount Spent: <b><?= $stats["total_spent"] ?> satoshi</b></li>
        <li>Total Referred Users: <b><?= $stats["total_referred_users"] ?> users</b></li>
        <li>Top Paid Address: <b><?= $stats["top_paid_address"] ?></b></li>
        <li>Total Top Paid Amount: <b><?= $stats["top_paid_amount"] ?> satoshi</b></li>
    </ul>

    <h2>Curve Faucet</h2>
    <ul>
        <li>Total Times Rolled: <b><?= $stats["spinner"]["fractal_count"] + $stats["spinner"]["fractal_count"] ?> times</b></li>
        <li>Lowest Number Rolled: <b>00<?= $stats["spinner"]["lowest_number"]|0 ?> ({{getSatoshiValueByCurve(<?= $stats["spinner"]["lowest_number"] ?>, '<?= $stats["spinner"]["lowest_number_curve"] ?>')}} satoshi)</b></li>
        <li>Average Number Rolled: <b><?= $stats["spinner"]["avg_number"]|0 ?></b></li>
        <li>Average Fractal Dispense: <b>{{getSatoshiValueByCurve(<?= $stats["spinner"]["avg_fractal_number"]|0 ?>,'fractal')}} satoshi</b></li>
        <li>Average Radical Dispense: <b>{{getSatoshiValueByCurve(<?= $stats["spinner"]["avg_radical_number"]|0 ?>,'radical')}} satoshi</b></li>
        <li>Latest Dispense: <b><?= date('M j g:i A', $stats["spinner"]["latest_dispense_time"]) ?></b></li>
    </ul>

    <h2>Payouts</h2>
    <ul>
        <li>Average Payout Amount: <b><?= $stats["avg_payout_amount"]|0 ?> satoshi</b></li>
        <li>Total Payouts Made: <b><?= ($stats["faucetbox_payouts"] + $stats["paytoshi_payouts"]) ?> times</b></li>
        <li>Total Referral Payouts: <b><?= $stats["referral_payouts"] ?></b></li>
    </ul>

    <h2>Referrals</h2>
    <ul>
        <li>Top Referrer: <b><?= $stats["top_referrer"] ?></b></li>
        <li>Top Referrer Referrals: <b><?= $stats["top_referred"] ?> users</b></li>
        <li>Total Referred Users: <b><?= $stats["total_referred_users"] ?> users</b></li>
        <li>Total Referral Earnings: <b><?= $stats["total_referral_reward"]|0 ?> satoshi</b></li>
    </ul>
</div>