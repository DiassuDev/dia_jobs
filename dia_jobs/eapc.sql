SELECT pnp.customers_id,
       pnp.platform_name,
       pnp.customers_company,
       pnp.customers_name,
       pnp.customers_lastname,
       pnp.customers_firstname,
       pnp.transaction_status as camera_payment_status,
       SUM(pnp.qty) as cameras_purchased,
       pnp.date_purchased as date_cam_purchased,
       SUM(pnp.customer_paid_up) as customer_paid_up_cam_orders
FROM 
( SELECT  /* This is the original query to find all paid not paid orders 
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
            END AS customer_paid_up
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
            ot.date_created >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) ) pnp
GROUP BY 
   pnp.customers_id, pnp.customer_paid_up
ORDER BY pnp.customers_id ASC
