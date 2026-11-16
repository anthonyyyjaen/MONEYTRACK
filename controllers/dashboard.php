<?php

require "G_Controller.php";
require "../config/database.php";

$ret = [];
$ret["status"] = null;
$ret["error"] = "";
$ret["response"] = [];

$function = $_GET['fn'] ?? '';

switch ($function) {
    case 'getCutoff':
        $cutoffs = getCutoff();
        $months = getMonths();
        $years = getYears();

        $cutoffElements = "";
        foreach ($cutoffs as $key => $cutoff) {
            $cutoffElements .= "<option value='$key'>$cutoff</option>";
        }

        $monthsElements = "";
        foreach ($months as $key => $month) {
            $selected = $key == date("m") ? "selected" : "";
            $monthsElements .= "<option value='$key' $selected>$month</option>";
        }

        $yearElements = "";
        foreach ($years as $key => $year) {
            $yearElements .= "<option value='$key'>$year</option>";
        }

        $ret["status"] = 200;
        $ret["cutoffs"] = $cutoffElements;
        $ret["months"] = $monthsElements;
        $ret["years"] = $yearElements;
        break;
    case 'getExpensesByCutoff':
        try {

            $cutoff = empty($_GET['cutoff']) ? 1 : $_GET['cutoff'];
            $month = empty($_GET['month']) ? date("m") : $_GET['month'];
            $year = empty($_GET['year']) ? date("Y") : $_GET['year'];
            $yearMonth = "$year-$month";

            $res = $conn->query("SELECT * FROM " . TABLE_EXPENSE . " WHERE STATUS = '" . EXPSTATUS_PENDING . "' AND DELDATE IS NULL");
            $tbody = "";
            $tfoot = "";

            if ($res->num_rows > 0) {
                $tbody = "";
                $tfoot = "";
                $totalAmount = 0;
                while ($detail = $res->fetch_assoc()) {
                    $dates = generateCalendarData($detail, $yearMonth, [$cutoff]);
                    $transactions = getTransactionByExpCode($conn, $detail['EXPENSECODE']);

                    foreach ($dates as $date) {
                        $totalAmount += floatval($detail['AMOUNT']);

                        $formattedAmount = numberFormat($detail['AMOUNT']);
                        $formattedDate = dateFormat($date['date']);
                        $types = explode("-", $date['type']);
                        $status = empty($transactions[$calData['date']]) ? 'unpaid' : 'paid';
                        $tbody .= "
                        <tr class='$status'>
                            <td>{$types[0]}</td>
                            <td>{$date['name']}</td>
                            <td>$formattedDate</td>
                            <td>$formattedAmount</td>
                        </tr>
                    ";
                    }
                }
                $totalAmount = numberFormat($totalAmount);
                $tfoot = "
                <tr>
                    <td colspan='3'></td>
                    <td>$totalAmount</td>
                </tr>
                ";
            }

            $ret["status"] = 200;
            $ret['tbody'] = $tbody;
            $ret['tfoot'] = $tfoot;
        } catch (Exception $e) {
            $ret['status'] = 400;
            $ret['error'] = $e->getMessage();
        }
        break;
    case 'getExpensesByMonth':
        try {

            $month = empty($_GET['month']) ? date("m") : $_GET['month'];
            $year = empty($_GET['year']) ? date("Y") : $_GET['year'];
            $yearMonth = "$year-$month";

            $res = $conn->query("SELECT * FROM " . TABLE_EXPENSE . " WHERE STATUS = '" . EXPSTATUS_PENDING . "' AND DELDATE IS NULL");
            $tbody = "";
            $tfoot = "";
            $totalAmount = 0;
            if ($res->num_rows > 0) {
                while ($detail = $res->fetch_assoc()) {
                    $dates = generateCalendarData($detail, $yearMonth, [1, 2]);
                    $transactions = getTransactionByExpCode($conn, $detail['EXPENSECODE']);

                    foreach ($dates as $date) {
                        $totalAmount += floatval($detail['AMOUNT']);

                        $formattedAmount = numberFormat($detail['AMOUNT']);
                        $formattedDate = dateFormat($date['date']);
                        $types = explode("-", $date['type']);
                        $status = empty($transactions[$calData['date']]) ? 'unpaid' : 'paid';
                        $tbody .= "
                        <tr class='$status'>
                            <td>{$types[0]}</td>
                            <td>{$date['name']}</td>
                            <td>$formattedDate</td>
                            <td>$formattedAmount</td>
                        </tr>
                    ";
                    }
                }
                $totalAmount = numberFormat($totalAmount);
                $tfoot = "
                <tr>
                    <td colspan='3'></td>
                    <td>$totalAmount</td>
                </tr>
            ";
            }

            $ret["status"] = 200;
            $ret['tbody'] = $tbody;
            $ret['tfoot'] = $tfoot;
        } catch (Exception $e) {
            $ret['status'] = 400;
            $ret['error'] = $e->getMessage();
        }
        break;

    case 'getTransactions':
        try {
            $tbody = "";
            $res = $conn->query("
                SELECT T.TRANSNO, E.EXPENSENAME, T.TERM, T.PAIDAMT, T.PAIDDATE
                FROM " . TABLE_TRANS . " T 
                INNER JOIN " . TABLE_EXPENSE . " E ON T.EXPENSECODE = E.EXPENSECODE
                WHERE T.DELDATE IS NULL
                AND E.DELDATE IS NULL
                ORDER BY T.SEQNO DESC
            ");
            if ($res->num_rows === 0) {
                $tbody .= "<tr><td colspan=5>No Result</td></tr>";
            } else {
                while ($detail = $res->fetch_assoc()) {
                    $amount = numberFormat($detail['PAIDAMT']);
                    $date = dateFormat($detail['PAIDDATE']);
                    $tbody .= "
                        <tr>
                            <td>{$detail['TRANSNO']}</td>
                            <td>{$detail['EXPENSENAME']}</td>
                            <td>{$detail['TERM']}</td>
                            <td>$amount</td>
                            <td>$date</td>
                        </tr>
                    ";
                }
            }

            $ret["status"] = 200;
            $ret['tbody'] = $tbody;
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
