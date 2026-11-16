<?php
require "../config/database.php";

function calendarColor($index)
{
    $colors = [
        '#E53935',
        '#D81B60',
        '#8E24AA',
        '#5E35B1',
        '#3949AB',
        '#1E88E5',
        '#039BE5',
        '#00ACC1',
        '#00897B',
        '#43A047',
        '#7CB342',
        '#C0CA33',
        '#FDD835',
        '#FFB300',
        '#FB8C00',
        '#F4511E',
        '#6D4C41',
        '#757575',
        '#546E7A',
        '#EC407A',
        '#AB47BC',
        '#7E57C2',
        '#5C6BC0',
        '#42A5F5',
        '#29B6F6',
        '#26C6DA',
        '#26A69A',
        '#66BB6A',
        '#9CCC65',
        '#D4E157',
        '#FFEE58',
        '#FFCA28',
        '#FFA726',
        '#FF7043',
        '#8D6E63',
        '#BDBDBD',
        '#78909C',
        '#AD1457',
        '#6A1B9A',
        '#4527A0',
        '#283593',
        '#1565C0',
        '#0277BD',
        '#00838F',
        '#00695C',
        '#2E7D32',
        '#558B2F',
        '#9E9D24',
        '#F9A825',
        '#EF6C00'
    ];

    return $colors[$index];
}

function numberFormat($value)
{
    return MONEYSIGN . " " . number_format($value, 2);
}

function dateFormat($date)
{
    return date('F j, Y', strtotime($date));
}

function getCutoff()
{
    return  [
        1 => "First Cutoff (" . FIRSTCUTOFF_START . ")",
        2 => "Second Cutoff (" . SECONDCUTOFF_START . ")"
    ];
}

function getMonths()
{
    $months = [];
    for ($i = 1; $i <= 12; $i++) {
        $key = str_pad($i, 2, '0', STR_PAD_LEFT);
        $months[$key] = date('F', mktime(0, 0, 0, $i, 1));
    }
    return $months;
}

function getYears($interval = 5)
{
    $years = [];
    $currentYear = (int) date('Y');

    for ($i = 0; $i < $interval; $i++) {
        $year = $currentYear - $i;
        $years[$year] = $year;
    }

    return $years;
}

function generateCalendarData(
    array $detail,
    string $yearMonth = "",
    array $cutoff = [],
    string $color = "",
    array $transactions = []
) {
    $range = [];

    if ($detail['CUSTOMDAY']) {
        $range = getDateRange(
            $detail['EXPENSESTART'],
            $detail['EXPENSEEND'],
            explode(",", $detail['CUSTOMDAY'])
        );
    } else {
        $range[] = $detail['EXPENSESTART'];
    }

    $details = [];

    // Determine cutoff date boundaries
    if (!empty($yearMonth)) {
        $cutoff1Start = strtotime($yearMonth . '-' . FIRSTCUTOFF_START);
        $cutoff1End = strtotime($yearMonth . '-' . FIRSTCUTOFF_END);

        $cutoff2Start = strtotime($yearMonth . '-' . SECONDCUTOFF_START);
        $cutoff2End = strtotime(
            date('Y-m-' . SECONDCUTOFF_END, strtotime($yearMonth . '-01 +1 month'))
        );
    }

    foreach ($range as $key => $date) {
        if(isset($transactions[$date])) continue;

        $dateTimestamp = strtotime($date);

        $cutoffPeriod = 0;

        if (!empty($yearMonth)) {

            // Cutoff 1:
            if (
                $dateTimestamp >= $cutoff1Start &&
                $dateTimestamp <= $cutoff1End
            ) {
                $cutoffPeriod = 1;
            }

            // Cutoff 2:
            elseif (
                $dateTimestamp >= $cutoff2Start &&
                $dateTimestamp <= $cutoff2End
            ) {
                $cutoffPeriod = 2;
            } else {
                continue;
            }

            // Filter selected cutoff
            if (!empty($cutoff) && !in_array($cutoffPeriod, $cutoff)) {
                continue;
            }
        }

        $seq = $key + 1;

        $name = $detail['EXPENSENAME'];
        $name .= count($range) == 1 ? "" : " [$seq]";

        $details[] = [
            "id" => $detail['EXPENSECODE'],
            "name" => $name,
            "date" => $date,
            "type" => $detail['CATEGORY'] . "-" . $detail['EXPENSECODE'],
            "cutoff" => $cutoffPeriod,
            "color" => $color
        ];
    }

    return $details;
}

function getDateRange($startDate, $endDate, $daysOfMonth)
{
    $startDate = new DateTime($startDate);
    $endDate = new DateTime($endDate);

    $dates = [];
    $currentMonth = (clone $startDate)->modify('first day of this month');
    $lastMonth = (clone $endDate)->modify('first day of this month');

    // Sort days in ascending order and remove duplicates
    $daysOfMonth = array_unique($daysOfMonth);
    sort($daysOfMonth);

    while ($currentMonth <= $lastMonth) {
        $year = (int) $currentMonth->format('Y');
        $month = (int) $currentMonth->format('m');
        $daysInMonth = (int) $currentMonth->format('t');

        foreach ($daysOfMonth as $day) {
            if ($day < 1 || $day > $daysInMonth) {
                continue;
            }

            $date = new DateTime();
            $date->setDate($year, $month, $day);
            $date->setTime(0, 0, 0);

            if ($date >= $startDate && $date <= $endDate) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        $currentMonth->modify('first day of next month');
    }

    return $dates;
}


function buildEnumElments($fieldname, $type)
{
    $elements = "";
    preg_match("/^enum\((.*)\)$/", $type, $matches);
    $enums = str_getcsv($matches[1], ',', "'");

    $elements .= "<option value='' selected disabled>Select $fieldname</option>";
    foreach ($enums as $enum) {
        $elements .= "<option value='$enum'>$enum</option>";
    }
    return $elements;
}

function getTransactionByExpCode(
    mysqli $conn,
    string $expensecode
) {
    $transRes = $conn->query("SELECT * FROM " . TABLE_TRANS . " WHERE EXPENSECODE = '$expensecode' AND DELDATE IS NULL ORDER BY SEQNO");
    $transactions = [];

    while ($row = $transRes->fetch_assoc()) {
        $transactions[$row['EXPENSEDATE']] = [
            'EXPENSEDATE' => $row['EXPENSEDATE'],
            'PAIDAMT' => $row['PAIDAMT'],
            'PAIDDATE' => $row['PAIDDATE'],
        ];
    }
    return $transactions;
}

function insertData(
    mysqli $conn,
    string $table,
    array $data,
) {
    $data['ADDBY'] = "BY";
    $data['ADDDATE'] = "NOW()";

    $columns = implode(', ', array_keys($data));

    $values = [];
    foreach ($data as $column => $value) {
        if ($value == "NOW()")
            $values[] = "NOW()";
        else if ($value == null)
            $values[] = "NULL";
        else
            $values[] = "'" . $conn->real_escape_string($value) . "'";
    }
    $values = implode(', ', $values);

    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";

    if (!$conn->query($sql))
        throw new Exception("Insert failed: " . $conn->error);
}


function updateData(
    mysqli $conn,
    string $table,
    array $data,
    array $where,

) {
    $set = [];

    $data['EDITBY'] = "BY";
    $data['EDITDATE'] = "NOW()";


    foreach ($data as $column => $value) {
        if ($value == "NOW()")
            $set[] = "{$column} = NOW()";
        else
            $set[] = "{$column} = '" . $conn->real_escape_string($value) . "'";
    }

    $conditions = [];

    foreach ($where as $column => $value) {
        if ($value == null)
            $conditions[] = "{$column} IS NULL";
        else
            $conditions[] = "{$column} = '" . $conn->real_escape_string($value) . "'";
    }

    $sql = "
        UPDATE {$table}
        SET " . implode(', ', $set) . "
        WHERE " . implode(' AND ', $conditions);

    if (!$conn->query($sql))
        throw new Exception("Update failed: " . $conn->error);
}
