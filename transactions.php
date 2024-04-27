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
include './APIs/get_transaction_info.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions</title>
</head>
<body>
        <?php include './Misc/nav.html'; ?>
    <h1>Transaction Records for <?php echo $fullName; ?></h1>
    
    <table style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr>
                <th style="border: 1px solid black;">Transaction Number</th>
                <th style="border: 1px solid black;">Date</th>
                <th style="border: 1px solid black;">Services</th>
                <th style="border: 1px solid black;">Payment Status</th>
                <th style="border: 1px solid black;">Amount</th>
                <th style="border: 1px solid black;">Discount</th>
                <th style="border: 1px solid black;">VAT</th>
                <th style="border: 1px solid black;">Total Amount</th>
                <th style="border: 1px solid black;">Branch Name</th>
                <th style="border: 1px solid black;">Treatment ID</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($resultTransactions->num_rows > 0) {
                while ($rowTransaction = $resultTransactions->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td style='border: 1px solid black;'>" . $rowTransaction['TransactionNumber'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowTransaction['Date'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowTransaction['Services'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowTransaction['PaymentStatus'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowTransaction['Amount'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowTransaction['Discount'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowTransaction['VAT'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowTransaction['TotalAmount'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowTransaction['BranchName'] . "</td>";
                    echo "<td style='border: 1px solid black;'>" . $rowTransaction['TreatmentID'] . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='10' style='border: 1px solid black;'>No transaction records found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
