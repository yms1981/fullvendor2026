create definer = root@localhost view pro_repetidos as
select `appfv`.`products`.`company_id`    AS `company_id`,
       `appfv`.`products`.`sku`           AS `sku`,
       count(`appfv`.`products`.`pro_id`) AS `cuenta`
from `appfv`.`products`
group by `appfv`.`products`.`sku`, `appfv`.`products`.`company_id`;

