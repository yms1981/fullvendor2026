create definer = appfv@`%` view CurrencyCodes as
select `appfv`.`currency_formats`.`CurrencyCode` AS `CurrencyCode`
from `appfv`.`currency_formats`
group by `appfv`.`currency_formats`.`CurrencyCode`
order by `appfv`.`currency_formats`.`CurrencyCode`;

