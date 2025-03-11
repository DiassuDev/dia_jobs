<?php
//  2025-03-02 - JMK - First creation of extract all paid cameras data feed.  
//                     Assumptions:
//                     1) Using Agile techniques to minimize coding needed and to revenue.
//                     2) Will not release AI packages renewals charges till 2025-11-01.
//                     3) At that point we will need to include queries to decern between (maybe) 
//                        purchases of cameras and purchase of AI package licenses
//                     4) Unless we can put all the AI on the cameras using Jetson hardware instead of GCP AI
//                     5) The GCP might cost more per month than to port to Jetson.
//                     6) Mentality is to keep GCP costs to minimum for just the front end hosting.
//                     7) As of this date, the processing is on the GCP cloud and included in fee for camera for first year.
//                     8) Counts below are just for Cameras as nothing else should be purchased until 2025-11-01.
//                     9) If can move off of GCP by 2025-11-01 then don't need to modify this any more or charge a fee
//                     10) After initial purchase, if we stay on GCP cloud after 2025-11-01 then we need to add another
//                     11) Job  or add logic to this that will help to renew the AI Packages with a yearly and 6 months cost while still main
// 2025-03-09 - JMK -  Update the column headings to refect the columns in the cameras table in the app.
//                     Extract all data needed for cameras db and for the Ansible scripts to provision the cameras.
//
//                     NOTES About the output:
//IMPORTANT: NB: 
//
// customer_paid_up_count is the count of the number of orders that were paid up during the period as of today that are paid up orders.  qty is the number of cameras to light up when customer_paid_up_count is non-zero.  It is the number of orders that must be straightened out by customer service (maybe sent through again or use another card)  for the zero customer_paid_up_counts.

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
$sql = "
SELECT  /* This is the original query to find all paid not paid orders 
              now as a sub-query */
            c.customers_id,
            p.platform_name,  /* New */
            o.customers_company, 
            o.customers_name,
            c.customers_lastname,
            c.customers_firstname,
            c.customers_email_address,
            /* o.orders_id, */
            ot.transaction_amount,
            ot.transaction_currency,
            oss.qty as qty,           /* New */
            oss.value_inc_tax,        /* New */
            oss.orders_id,            /* New */
            DATE(oss.date_added) as date_purchased,   /* New */
            ot.transaction_status,
            ot.date_created,
            CASE
               WHEN ot.transaction_status = 'COMPLETED' THEN
                  CASE
                     WHEN (ot.date_created >= DATE_SUB(CURDATE(), INTERVAL 4 YEAR) OR
                           ot.date_created >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN 1
                     ELSE 0
                  END
               WHEN ot.transaction_status = 'PENDING' THEN 0
               ELSE 0
            END AS customer_paid_up,
            o.orders_status
        FROM
            customers c
        JOIN
            platforms p ON p.platform_id = c.platform_id
        LEFT JOIN
            orders o ON o.customers_id = c.customers_id
                        
        INNER JOIN orders_transactions ot on ot.orders_id =  o.orders_id  
                   AND ot.orders_id = o.orders_id
        INNER JOIN orders_splinters oss ON o.orders_id = oss.orders_id
        WHERE
            p.platform_name = 'SafeSchool'
            AND
            oss.splinters_type = 5
            AND
            (ot.date_created >= DATE_SUB(CURDATE(), INTERVAL 4 YEAR) OR
            ot.date_created >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
";

// AND ot.transaction_amount IS NOT NULL AND ot.transaction_status IN ('COMPLETED') 
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $fp = fopen('all_customers_payment_status.csv', 'w');
    fputcsv($fp, array('customers_id', 'platform_name', 'customers_company', 'customers_name', 'customers_last_name','customers_first_name', 'customers_email_address', 'transaction_amount', 'transaction_currency', 'cam_quantity','value_inc_tax','orders_id', 'date_cam_purchased', 'transaction_status','date_created','orders_paid_up','order_status'));

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

