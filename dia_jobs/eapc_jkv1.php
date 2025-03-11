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
//  2025-02-28  JMK - lot  is the Latest order Transaction gained in the sub query
//
//                    os is orders in the sub-query
//                    ots is orders_transactions in the sub query
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
            ot.date_created,
            CASE
               WHEN (ot.date_created >= DATE_SUB(CURDATE(), INTERVAL 4 YEAR) OR
                      ot.date_created >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN 1
               ELSE 0
            END AS customer_paid_up
        FROM 
            customers c
        INNER JOIN
            ( SELECT 
                 cs.customers_id, 
                 MAX(ots.date_created) as latest_ot_created_date FROM customers cs 
                 JOIN 
                    orders os ON cs.customers_id = os.customers_id
                 JOIN 
                    orders_transactions ots ON os.orders_id = ots.orders_id  
                 GROUP BY
                   cs.customers_id ) lot
             ON lot.customers_id = c.customers_id
        JOIN 
            orders o ON c.customers_id = o.customers_id
        JOIN 
            orders_transactions ot ON o.orders_id = ot.orders_id  
        WHERE 
            ot.date_created = lot.latest_ot_created_date 
            AND ot.transaction_amount IS NOT NULL AND ot.transaction_status IN ('COMPLETED') 
            AND
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

