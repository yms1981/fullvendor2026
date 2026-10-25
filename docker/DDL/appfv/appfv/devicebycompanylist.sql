create definer = root@localhost view devicebycompanylist as
select `a`.`deviceinfo`  AS `deviceinfo`,
       `a`.`so`          AS `so`,
       `a`.`version`     AS `version`,
       `a`.`created`     AS `created`,
       `a`.`updated_at`  AS `updated_at`,
       `a`.`versionname` AS `versionname`,
       `b`.`company_id`  AS `company_id`
from (`appfv`.`deviceinfos` `a` left join `appfv`.`devicesCompanytemp` `b` on (`b`.`deviceinfo` = `a`.`deviceinfo`));

