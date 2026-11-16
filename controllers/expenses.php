<?php

require "G_Controller.php";
require "../config/database.php";

$ret = [];
$ret["status"] = null;
$ret["error"] = "";
$ret["response"] = [];

$function = $_GET['fn'] ?? '';

switch ($function) {
    case 'getCategory':
        $res = $conn->query("SHOW COLUMNS FROM " . TABLE_EXPENSE . " LIKE 'CATEGORY'");
        $type = $res->fetch_assoc()['Type'];

        $ret["status"] = 200;
        $ret["response"] = buildEnumElments("Category", $type);
        break;

    case 'getInterval':
        $res = $conn->query("SHOW COLUMNS FROM " . TABLE_EXPENSE . " LIKE 'PAYINTERVAL'");
        $type = $res->fetch_assoc()['Type'];

        $ret["status"] = 200;
        $ret["response"] = buildEnumElments("Pay Interval", $type);
        break;

    case 'getExpenses':
        try {
            $res = $conn->query("SELECT * FROM " . TABLE_EXPENSE . " WHERE STATUS = '" . EXPSTATUS_PENDING . "' AND DELDATE IS NULL");
            if ($res->num_rows === 0) throw new Exception("Empty");

            $caledarDetails = [];

            $colorIndex = 0;
            while ($row = $res->fetch_assoc()) {
                $transactions = getTransactionByExpCode($conn, $row['EXPENSECODE']);
                $caledarDetails = array_merge(
                    $caledarDetails,
                    array_values(generateCalendarData($row, "", [], calendarColor($colorIndex), $transactions))
                );
                $colorIndex++;
            }

            $ret["status"] = 200;
            $ret['response'] = $caledarDetails;
        } catch (Exception $e) {
            $ret['status'] = 400;
            $ret['error'] = $e->getMessage();
        }
        break;

    case 'getExpense':
        try {
            $expensecode = $_GET['expensecode'];

            $res = $conn->query("SELECT * FROM " . TABLE_EXPENSE . " WHERE EXPENSECODE = '$expensecode' AND STATUS = '" . EXPSTATUS_PENDING . "' AND DELDATE IS NULL LIMIT 1");
            if ($res->num_rows === 0) throw new Exception("Expense not found");

            $status = "<span class='status-badge pending'> PENDING </span>";

            $detail = $res->fetch_assoc();
            $calDetail = generateCalendarData($detail);
            $transactions = getTransactionByExpCode($conn, $detail['EXPENSECODE']);

            $totalpaid = 0;
            $totalamount = 0;
            $timeline = "";
            foreach ($calDetail as $key => $calData) {
                $totalamount += floatval($detail['AMOUNT']);

                $term = $key + 1;
                $isPaid = empty($transactions[$calData['date']]) ? false : true;
                $totalpaid += empty($transactions[$calData['date']]) ? 0 : floatval($transactions[$calData['date']]['PAIDAMT']);

                $timeline .= "
                    <div class='timeline-item'>
                        <div class='marker'></div>
                        <div class='content'>
                            <h3>{$calData['name']}</h3>
                            <time datetime='{$calData['date']}'>" . dateFormat($calData['date']) . "</time>
                            <p>" . numberFormat($detail['AMOUNT']) . "</p>
                        </div>
                        <button
                            type='button'
                            class='paid-button btn " . ($isPaid ? 'btn-paid' : 'btn-mark-paid') . "'
                            " . ($isPaid ? 'disabled' : '') . "
                            data-id='{$detail['EXPENSECODE']}|$term|{$calData['date']}'>

                            <i class='bi " . ($isPaid ? 'bi-check-circle-fill' : 'bi-credit-card') . "'></i>
                            " . ($isPaid ? 'Paid' : 'Mark as Paid') . "
                        </button>
                    </div>
                ";
            }
            $detail['TOTALPAID'] = numberFormat($totalpaid);
            $detail['TOTALAMOUNT'] = numberFormat($totalamount);
            $detail['BALANCE'] = numberFormat($totalamount - $totalpaid);

            $ret["status"] = 200;
            $ret['response']['detail'] = $detail;
            $ret['response']['status'] = $status;
            $ret['response']['timeline'] = $timeline;
        } catch (Exception $e) {
            $ret['status'] = 400;
            $ret['error'] = $e->getMessage();
        }
        break;

    case 'saveExpenses':
        try {
            $formData = json_decode(file_get_contents("php://input"), true);
            $validatedData = buildExpense($conn, $formData);
            insertData($conn, TABLE_EXPENSE, $validatedData);

            $ret["status"] = 200;
        } catch (Exception $e) {
            $ret['status'] = 400;
            $ret['error'] = $e->getMessage();
        }
        break;

    case 'paidExpense':
        try {
            $expensecode = $_POST['expensecode'];
            $term = $_POST['term'];
            $paiddate = $_POST['paiddate'];

            $res = $conn->query("SELECT * FROM " . TABLE_EXPENSE . " WHERE EXPENSECODE = '$expensecode' AND STATUS = '" . EXPSTATUS_PENDING . "' AND DELDATE IS NULL LIMIT 1");
            if ($res->num_rows === 0) throw new Exception("Expense not found.");
            $expense = $res->fetch_assoc();

            $res = $conn->query("SELECT * FROM " . TABLE_TRANS . " WHERE EXPENSECODE = '$expensecode' AND TERM = $term AND DELDATE IS NULL LIMIT 1");
            if ($res->num_rows > 0) throw new Exception("This term is already paid.");

            $formattedTransactionData = buildPaidExpense($expense, $paiddate, $term);
            insertData($conn, TABLE_TRANS, $formattedTransactionData);
            $ret["status"] = 200;
        } catch (Exception $e) {
            $ret['status'] = 400;
            $ret['error'] = $e->getMessage();
        }
        break;

    default:
        $ret['status'] = 404;
        $ret['error'] = 'Undefined Function.';
        break;
}

echo json_encode($ret);

function buildExpense($conn, $formData)
{
    $code = "";

    while ($code == "") {
        $radcode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 10));
        $res = $conn->query("SELECT * FROM " . TABLE_EXPENSE . " WHERE EXPENSECODE = '$radcode' AND DELDATE IS NULL LIMIT 1");
        if ($res->num_rows === 0) $code = $radcode;
    }

    return [
        'CATEGORY' => $formData['selcategory'],
        'EXPENSECODE' => $code,
        'EXPENSENAME' => $formData['txtexpensename'],
        'PAYINTERVAL' => $formData['selpayinterval'],
        'EXPENSESTART' => empty($formData['txtstartdate']) ? null : $formData['txtstartdate'],
        'EXPENSEEND' => empty($formData['txtenddate']) ? null : $formData['txtenddate'],
        'CUSTOMDAY' => empty($formData['txtcustomday']) ? null : $formData['txtcustomday'],
        'AMOUNT' => round($formData['txtamount'], 2),
        'STATUS' => 'Pending'
    ];
}

function buildPaidExpense(
    array $expense,
    string $paiddate,
    string $term,
) {

    $transno = "T" . date("YmdHis") . str_pad($term, 3, '0', STR_PAD_LEFT) . $expense['EXPENSECODE'];
    return [
        'TRANSNO' => $transno,
        'EXPENSECODE' => $expense['EXPENSECODE'],
        'EXPENSEDATE' => $paiddate,
        'TERM' => $term,
        'PAIDAMT' => $expense['AMOUNT'],
        'PAIDDATE' => date("Y-m-d H:i:s"),
    ];
}
