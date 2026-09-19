<?php
session_start();

include "admin/db.php";

/*
|--------------------------------------------------------------------------
| Khalilabad Nagar Palika - Payment Success / Order Details
|--------------------------------------------------------------------------
| This page is intentionally compatible with the payment tables used by
| the current project. It safely reads optional columns so an older
| payment table does not break the page.
|--------------------------------------------------------------------------
*/

$order_id = (int)($_GET['id'] ?? $_GET['order_id'] ?? 0);
$autoprint = isset($_GET['autoprint']) && (string)$_GET['autoprint'] === '1';

function e($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($v)
{
    return number_format((float)($v ?? 0), 2);
}

function tableExists($conn, $table)
{
    $table = $conn->real_escape_string($table);
    $q = $conn->query("SHOW TABLES LIKE '{$table}'");
    return $q && $q->num_rows > 0;
}

function columnExists($conn, $table, $column)
{
    if (!tableExists($conn, $table)) {
        return false;
    }

    $table = str_replace('`', '', $table);
    $column = $conn->real_escape_string($column);

    $q = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");

    return $q && $q->num_rows > 0;
}

function rowValue($row, $keys, $default = '')
{
    foreach ($keys as $key) {
        if (
            isset($row[$key]) &&
            $row[$key] !== '' &&
            $row[$key] !== null
        ) {
            return $row[$key];
        }
    }

    return $default;
}

function financialYear($value)
{
    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    $value = str_replace(['/', '_'], '-', $value);
    $value = preg_replace('/\s+/', '', $value);

    if (preg_match('/^(20\d{2})-(20\d{2})$/', $value, $m)) {
        return $m[1] . '-' . $m[2];
    }

    if (preg_match('/^(20\d{2})-(\d{2})$/', $value, $m)) {
        return $m[1] . '-' . (2000 + (int)$m[2]);
    }

    return $value;
}

$order = null;
$years = [];

/* =========================================================
   ORDER
   ========================================================= */

if (
    $order_id > 0 &&
    tableExists($conn, 'property_payment_orders')
) {
    $stmt = $conn->prepare("
        SELECT *
        FROM property_payment_orders
        WHERE id = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        $order =
            $stmt->get_result()->fetch_assoc();

        $stmt->close();
    }
}

/* =========================================================
   PAYMENT YEARS
   ========================================================= */

if (
    $order &&
    tableExists($conn, 'property_payment_years')
) {
    $stmt = $conn->prepare("
        SELECT *
        FROM property_payment_years
        WHERE payment_order_id = ?
        ORDER BY id ASC
    ");

    if ($stmt) {
        $stmt->bind_param(
            "i",
            $order_id
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $years[] = $row;
        }

        $stmt->close();
    }
}

/* =========================================================
   ORDER DATA
   ========================================================= */

$assessment_id = (int)rowValue(
    $order,
    ['assessment_id', 'property_id'],
    0
);

$property_no = rowValue(
    $order,
    ['property_no', 'holding_no', 'holding_number'],
    'N/A'
);

$payer_name = rowValue(
    $order,
    ['payer_name', 'owner_name', 'name'],
    'N/A'
);

$payer_mobile = rowValue(
    $order,
    ['payer_mobile', 'mobile', 'mobile_number'],
    'N/A'
);

$payment_made_at = rowValue(
    $order,
    ['payment_made_at', 'payment_at'],
    'N/A'
);

$payment_mode = rowValue(
    $order,
    ['payment_mode', 'mode_of_payment', 'mode'],
    'N/A'
);

$status = rowValue(
    $order,
    ['payment_status', 'status'],
    'pending'
);

$order_number = rowValue(
    $order,
    ['order_number', 'order_no'],
    'PAY-' . $order_id
);

$created_at = rowValue(
    $order,
    ['created_at', 'payment_date'],
    ''
);

$demand_amount = (float)rowValue(
    $order,
    ['tax_amount', 'demand_amount', 'demand_tax'],
    0
);

$form_fee = (float)rowValue(
    $order,
    ['form_fee', 'form_fees'],
    0
);

$other_amount = (float)rowValue(
    $order,
    ['other_amount', 'other_charge'],
    0
);

$boring_charge = (float)rowValue(
    $order,
    ['boring_charge', 'boring_fee'],
    0
);

$advance_received = (float)rowValue(
    $order,
    ['advance_received', 'advance_amount', 'advance_deposit'],
    0
);

$total_amount = (float)rowValue(
    $order,
    ['total_amount', 'grand_total', 'amount'],
    0
);

/*
 * If the order table does not have the individual amounts, calculate
 * demand amount from payment years.
 */
if ($demand_amount <= 0 && !empty($years)) {
    foreach ($years as $y) {
        $demand_amount += (float)rowValue(
            $y,
            ['total_tax', 'tax_amount', 'amount'],
            0
        );
    }
}

/*
 * If total_amount is missing/zero, calculate it.
 */
if ($total_amount <= 0) {
    $total_amount =
        $demand_amount +
        $form_fee +
        $other_amount +
        $boring_charge -
        $advance_received;

    if ($total_amount < 0) {
        $total_amount = 0;
    }
}

/*
 * Compatibility: older orders may store status in `status`,
 * newer orders may store it in `payment_status`. The page already
 * accepts both through rowValue().
 */
$status_lower = strtolower(trim((string)$status));

/* =========================================================
   STATUS
   ========================================================= */

$is_success =
    in_array(
        $status_lower,
        [
            'paid',
            'success',
            'successful',
            'completed',
            'complete'
        ],
        true
    );

$status_label = ucfirst($status_lower);

if ($status_label === '') {
    $status_label = 'Pending';
}

$status_class =
    $is_success
    ? 'success'
    : (
        in_array(
            $status_lower,
            ['failed', 'cancelled', 'canceled'],
            true
        )
        ? 'failed'
        : 'pending'
    );

/* =========================================================
   YEAR TOTALS
   ========================================================= */

$paid_year_count = count($years);

$display_years = [];

foreach ($years as $y) {
    $fy = financialYear(
        rowValue(
            $y,
            ['financial_year', 'year'],
            ''
        )
    );

    if ($fy !== '') {
        $display_years[] = $fy;
    }
}

$display_years =
    array_values(
        array_unique(
            $display_years
        )
    );
?>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
    :root {
        --klb-navy: #011341;
        --klb-navy-2: #071d49;
        --klb-orange: #f58220;
        --klb-orange-dark: #dc6d0b;
        --klb-green: #15803d;
        --klb-red: #b91c1c;
        --klb-bg: #f3f7fb;
        --klb-border: #dfe6ef;
        --klb-text: #243653;
        --klb-muted: #718096;
    }

    .ps-page {
        min-height: calc(100vh - 70px);
        background: linear-gradient(135deg, #f4f7fb, #edf3f8);
        padding: 30px 18px;
    }

    .ps-container {
        max-width: 1000px;
        margin: auto;
    }

    .ps-card {
        background: #fff;
        border: 1px solid var(--klb-border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 35px rgba(1, 19, 65, .08);
    }

    .ps-top {
        padding: 30px 28px 24px;
        text-align: center;
        border-bottom: 1px solid #e8edf3;
    }

    .ps-icon {
        width: 66px;
        height: 66px;
        border-radius: 50%;
        margin: 0 auto 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 29px;
    }

    .ps-icon.success {
        background: #ecfdf3;
        color: var(--klb-green);
    }

    .ps-icon.pending {
        background: #fff7ed;
        color: var(--klb-orange-dark);
    }

    .ps-icon.failed {
        background: #fff1f2;
        color: var(--klb-red);
    }

    .ps-title {
        margin: 0;
        color: var(--klb-navy);
        font-size: 25px;
        font-weight: 800;
    }

    .ps-sub {
        margin: 7px 0 0;
        color: var(--klb-muted);
        font-size: 13px;
    }

    .ps-order {
        display: inline-flex;
        margin-top: 13px;
        padding: 7px 12px;
        border-radius: 20px;
        background: #f4f7fb;
        color: var(--klb-navy);
        font-size: 12px;
        font-weight: 800;
    }

    .ps-status {
        display: inline-block;
        margin-left: 6px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
    }

    .ps-status.success {
        background: #dcfce7;
        color: #166534;
    }

    .ps-status.pending {
        background: #ffedd5;
        color: #9a3412;
    }

    .ps-status.failed {
        background: #fee2e2;
        color: #991b1b;
    }

    .ps-body {
        padding: 24px;
    }

    .ps-section {
        margin-bottom: 22px;
    }

    .ps-section-title {
        color: var(--klb-navy);
        font-size: 15px;
        font-weight: 800;
        padding-bottom: 11px;
        border-bottom: 1px solid #e8edf3;
    }

    .ps-section-title i {
        color: var(--klb-orange);
        margin-right: 7px;
    }

    .ps-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 14px;
    }

    .ps-table th,
    .ps-table td {
        border: 1px solid var(--klb-border);
        padding: 11px 13px;
        font-size: 13px;
    }

    .ps-table th {
        width: 32%;
        text-align: left;
        background: #f7f9fc;
        color: var(--klb-navy);
    }

    .ps-table td {
        color: var(--klb-text);
    }

    .ps-years {
        width: 100%;
        border-collapse: collapse;
        margin-top: 14px;
    }

    .ps-years th,
    .ps-years td {
        border: 1px solid var(--klb-border);
        padding: 11px 8px;
        text-align: center;
        font-size: 12px;
    }

    .ps-years th {
        background: var(--klb-navy);
        color: #fff;
    }

    .ps-years td {
        color: var(--klb-text);
    }

    .ps-amount {
        color: var(--klb-orange-dark);
        font-weight: 800;
    }

    .ps-breakdown {
        border: 1px solid var(--klb-border);
        border-radius: 12px;
        overflow: hidden;
        margin-top: 14px;
    }

    .ps-line {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        padding: 12px 15px;
        border-bottom: 1px solid #edf0f4;
        font-size: 13px;
    }

    .ps-line:last-child {
        border-bottom: 0;
    }

    .ps-line span {
        color: var(--klb-muted);
    }

    .ps-line strong {
        color: var(--klb-navy);
    }

    .ps-grand {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding: 17px;
        background: #fff6ed;
        border: 1px solid #ffd7b2;
        border-radius: 11px;
    }

    .ps-grand span {
        color: var(--klb-navy);
        font-weight: 800;
    }

    .ps-grand strong {
        color: var(--klb-orange-dark);
        font-size: 23px;
    }

    .ps-words {
        margin-top: 8px;
        text-align: right;
        color: var(--klb-muted);
        font-size: 12px;
        font-style: italic;
    }

    .ps-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
        padding-top: 7px;
    }

    .ps-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-width: 130px;
        padding: 11px 18px;
        border-radius: 8px;
        text-decoration: none;
        border: 0;
        cursor: pointer;
        font-weight: 800;
        font-size: 12px;
    }

    .ps-btn.primary {
        background: var(--klb-orange);
        color: #fff;
    }

    .ps-btn.primary:hover {
        background: var(--klb-orange-dark);
    }

    .ps-btn.navy {
        background: var(--klb-navy);
        color: #fff;
    }

    .ps-btn.gray {
        background: #64748b;
        color: #fff;
    }

    .ps-empty {
        padding: 20px;
        text-align: center;
        color: var(--klb-muted);
        border: 1px dashed #ccd6e3;
        border-radius: 9px;
        margin-top: 14px;
    }

    @media(max-width:650px) {
        .ps-page {
            padding: 15px 10px;
        }

        .ps-body {
            padding: 15px;
        }

        .ps-top {
            padding: 25px 15px 20px;
        }

        .ps-title {
            font-size: 20px;
        }

        .ps-table th,
        .ps-table td {
            padding: 9px;
            font-size: 12px;
        }

        .ps-table th {
            width: 40%;
        }

        .ps-years {
            min-width: 650px;
        }

        .ps-years-wrap {
            overflow-x: auto;
        }

        .ps-actions {
            flex-direction: column;
        }

        .ps-btn {
            width: 100%;
        }
    }

    @media print {
        body {
            background: #fff !important;
        }

        .ps-page {
            padding: 0;
            background: #fff;
        }

        .ps-card {
            box-shadow: none;
            border: 0;
        }

        .ps-actions {
            display: none;
        }
    }
</style>

<div class="ps-page">

    <div class="ps-container">

        <div class="ps-card">

            <?php if (!$order): ?>

                <div class="ps-top">

                    <div class="ps-icon failed">
                        <i class="fa fa-times"></i>
                    </div>

                    <h2 class="ps-title">
                        Payment Record Not Found
                    </h2>

                    <p class="ps-sub">
                        The requested payment record does not exist.
                    </p>

                </div>

                <div class="ps-body">

                    <div class="ps-actions">

                        <a
                            class="ps-btn navy"
                            href="javascript:history.back()">
                            <i class="fa fa-arrow-left"></i>
                            Go Back
                        </a>

                    </div>

                </div>

            <?php else: ?>

                <!-- HEADER -->

                <div class="ps-top">

                    <div class="ps-icon <?= e($status_class) ?>">

                        <?php if ($status_class === 'success'): ?>

                            <i class="fa fa-check"></i>

                        <?php elseif ($status_class === 'failed'): ?>

                            <i class="fa fa-times"></i>

                        <?php else: ?>

                            <i class="fa fa-clock-o"></i>

                        <?php endif; ?>

                    </div>

                    <h2 class="ps-title">

                        <?php if ($is_success): ?>
                            Payment Successful
                        <?php elseif ($status_class === 'failed'): ?>
                            Payment Failed
                        <?php else: ?>
                            Payment Request Saved
                        <?php endif; ?>

                    </h2>

                    <p class="ps-sub">
                        Property tax payment details
                        for Khalilabad Nagar Palika.
                    </p>

                    <div class="ps-order">

                        Order:
                        <?= e($order_number) ?>

                        <span class="ps-status <?= e($status_class) ?>">
                            <?= e($status_label) ?>
                        </span>

                    </div>

                </div>

                <div class="ps-body">

                    <!-- PROPERTY -->

                    <div class="ps-section">

                        <div class="ps-section-title">
                            <i class="fa fa-home"></i>
                            Property &amp; Payer Details
                        </div>

                        <table class="ps-table">

                            <tr>
                                <th>Property No</th>
                                <td>
                                    <?= e($property_no) ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Payer / Owner Name</th>
                                <td>
                                    <?= e($payer_name) ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Mobile Number</th>
                                <td>
                                    <?= e($payer_mobile) ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Payment Made At</th>
                                <td>
                                    <?= e($payment_made_at) ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Payment Mode</th>
                                <td>
                                    <?= e($payment_mode) ?>
                                </td>
                            </tr>

                            <?php if ($created_at !== ''): ?>

                                <tr>
                                    <th>Order Date</th>
                                    <td>
                                        <?= e($created_at) ?>
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </table>

                    </div>

                    <!-- YEARS -->

                    <div class="ps-section">

                        <div class="ps-section-title">
                            <i class="fa fa-calendar"></i>
                            Paid Financial Years
                        </div>

                        <?php if (!empty($years)): ?>

                            <div class="ps-years-wrap">

                                <table class="ps-years">

                                    <thead>

                                        <tr>
                                            <th>Sr. No.</th>
                                            <th>Financial Year</th>
                                            <th>Tax Amount</th>
                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php foreach (
                                            $years as $i => $year
                                        ): ?>

                                            <?php
                                            $fy = financialYear(
                                                rowValue(
                                                    $year,
                                                    ['financial_year', 'year'],
                                                    'N/A'
                                                )
                                            );

                                            $year_tax = (float)rowValue(
                                                $year,
                                                [
                                                    'total_tax',
                                                    'tax_amount',
                                                    'amount'
                                                ],
                                                0
                                            );
                                            ?>

                                            <tr>

                                                <td>
                                                    <?= $i + 1 ?>
                                                </td>

                                                <td>
                                                    <strong>
                                                        <?= e(
                                                            $fy ?: 'N/A'
                                                        ) ?>
                                                    </strong>
                                                </td>

                                                <td class="ps-amount">
                                                    ₹ <?= money($year_tax) ?>
                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php else: ?>

                            <div class="ps-empty">
                                <i class="fa fa-info-circle"></i>
                                No financial year details were stored
                                with this payment order.
                            </div>

                        <?php endif; ?>

                    </div>

                    <!-- AMOUNT BREAKDOWN -->

                    <div class="ps-section">

                        <div class="ps-section-title">
                            <i class="fa fa-calculator"></i>
                            Payment Amount Details
                        </div>

                        <div class="ps-breakdown">

                            <div class="ps-line">
                                <span>Demand / Property Tax</span>
                                <strong>
                                    ₹ <?= money($demand_amount) ?>
                                </strong>
                            </div>

                            <div class="ps-line">
                                <span>Form Fee</span>
                                <strong>
                                    ₹ <?= money($form_fee) ?>
                                </strong>
                            </div>

                            <div class="ps-line">
                                <span>Other Amount</span>
                                <strong>
                                    ₹ <?= money($other_amount) ?>
                                </strong>
                            </div>

                            <div class="ps-line">
                                <span>Boring Charge</span>
                                <strong>
                                    ₹ <?= money($boring_charge) ?>
                                </strong>
                            </div>

                            <div class="ps-line">
                                <span>Advance Received</span>
                                <strong>
                                    ₹ <?= money($advance_received) ?>
                                </strong>
                            </div>

                        </div>

                        <div class="ps-grand">

                            <span>
                                Total Payable Amount
                            </span>

                            <strong>
                                ₹ <?= money($total_amount) ?>
                            </strong>

                        </div>

                    </div>

                    <!-- ACTIONS -->

                    <div class="ps-actions">

                        <?php if ($assessment_id > 0): ?>

                            <!-- <a
                                class="ps-btn primary"
                                href="view-saf-calculations.php?id=<?= (int)$assessment_id ?>&tab=collections">
                                <i class="fa fa-list"></i>
                                View Collections
                            </a>

                            <a
                                class="ps-btn navy"
                                href="view-saf-calculations.php?id=<?= (int)$assessment_id ?>&tab=demand">
                                <i class="fa fa-file-text"></i>
                                Back to Demand
                            </a> -->

                        <?php endif; ?>

                        <button
                            type="button"
                            class="ps-btn gray"
                            onclick="window.print();">
                            <i class="fa fa-print"></i>
                            Print
                        </button>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php if ($autoprint && $order): ?>
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
<?php endif; ?>

<? php // include "include/footer.php"; 
?>