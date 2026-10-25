create definer = appfv@localhost view version as
select max(`appfv`.`app_updates`.`ID`) AS `id`, `appfv`.`app_updates`.`company_id` AS `company_id`
from `appfv`.`app_updates`
group by `appfv`.`app_updates`.`company_id`
order by `appfv`.`app_updates`.`company_id`;

