create definer = appfv@localhost view rs_requested as
select count(`od`.`product_id`) AS `requested`,
       `od`.`product_id`        AS `product_id`,
       `p`.`name`               AS `name`,
       sum(`od`.`qty`)          AS `qty`,
       `o`.`company_id`         AS `company_id`,
       `o`.`customer_id`        AS `customer_id`
from ((`appfv`.`orders` `o` join `appfv`.`order_details` `od`
       on (`o`.`order_id` = `od`.`order_id`)) join `appfv`.`products` `p` on (`od`.`product_id` = `p`.`pro_id`))
where `o`.`order_status` = 12
group by `od`.`product_id`, `o`.`company_id`, `o`.`customer_id`;

-- comment on column rs_requested.product_id not supported: PK of products

-- comment on column rs_requested.customer_id not supported: PK of customers

