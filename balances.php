<?php
session_start();
include './APIs/config.php';

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.html");
    exit();
}

// Include a separate file for retrieving patient information
include './APIs/get_balance_info.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Balance Records</title>
</head>
<body>
     <?php include './Misc/nav.html'; ?>
    <h1>Balance Records for <?php echo $fullName; ?></h1>
    
    <table style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr>
                <th style="border: 1px solid black;">Balance ID</th>
                <th style="border: 1px solid black;">Transaction ID</th>
                <th style="border: 1px solid black;">Remaining Balance Amount</th>
                <th style="border: 1px solid black;">Payment Status</th>
                <th style="border: 1px solid black;">Payment Due Date</th>
                <th style="border: 1px solid black;">Branch Name</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($resultBalances->num_rows > 0) {
                while ($rowBalance = $resultBalances->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td style='border: 1px solid black;'>" . $rowBalance['BalanceID'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowBalance['TransactionNumber'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowBalance['RemainingBalanceAmount'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowBalance['PaymentStatus'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowBalance['PaymentDueDate'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowBalance['BranchName'] . "</td>";;
                }
            } else {
                echo "<tr><td colspan='7' style='border: 1px solid black;'>No balance records found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
