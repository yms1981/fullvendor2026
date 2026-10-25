create definer = appfv@localhost view category_count as
select `prodSeparados`.`company_id`     AS `company_id`,
       count(`prodSeparados`.`pro_id`)  AS `items`,
       `prodSeparados`.`category_value` AS `category_value`
from `appfv`.`prodSeparados`
group by `prodSeparados`.`company_id`, `prodSeparados`.`category_value`;

