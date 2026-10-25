create definer = appfv@localhost view versiones as
select `a`.`id` AS `id`, `a`.`company_id` AS `company_id`, `b`.`fecha` AS `fecha`, `b`.`updatenumber` AS `version`
from (`appfv`.`version` `a` join `appfv`.`app_updates` `b` on (`b`.`ID` = `a`.`id`))
order by `a`.`company_id`;

