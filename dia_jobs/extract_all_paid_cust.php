<?php
$servername = "localhost";
$username = "imumcrco";
$password = "Defense02Time42!!!";
$dbname = "imumcrco_osc4wuw";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT 
            c.customers_id,
            o.customers_company,
            o.customers_name,
            c.customers_lastname,
            c.customers_firstname,
            c.customers_email_address,
            o.orders_id,
            ot.transaction_amount,
            ot.transaction_currency,
            ot.transaction_status,
            ot.date_created
        FROM 
            customers c
        JOIN 
            orders o ON c.customers_id = o.customers_id
        JOIN 
            orders_transactions ot ON o.orders_id = ot.orders_id  
        WHERE 
            ot.transaction_amount IS NOT NULL AND
            ot.transaction_status IN ('COMPLETED') AND
            (ot.date_created >= DATE_SUB(CURDATE(), INTERVAL 4 YEAR) OR
            ot.date_created >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $fp = fopen('paid_customers.csv', 'w');
    fputcsv($fp, array('Customers ID', 'Customers Company', 'Customers Full Name', 'Customers Last Name','Customer First Name', 'Customer Email', 'Orders Id', 'Transaction Amount', 'Currency', 'Transaction Status', 'Payment Date'));

    while($row = $result->fetch_assoc()) {
        fputcsv($fp, $row);
    }

    fclose($fp);
    echo "CSV file created successfully.";
} else {
    echo "No records found.";
}

$conn->close();
?>

