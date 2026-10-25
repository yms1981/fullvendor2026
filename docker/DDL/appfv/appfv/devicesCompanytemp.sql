create definer = root@localhost view devicesCompanytemp as
select `appfv`.`gpstracker`.`company_id` AS `company_id`, `appfv`.`gpstracker`.`deviceinfo` AS `deviceinfo`
from `appfv`.`gpstracker`
group by `appfv`.`gpstracker`.`company_id`, `appfv`.`gpstracker`.`deviceinfo`;

