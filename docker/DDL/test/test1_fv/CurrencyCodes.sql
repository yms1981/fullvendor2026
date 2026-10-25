create definer = `test1-fv`@localhost view CurrencyCodes as
select `test1_fv`.`currency_formats`.`CurrencyCode` AS `CurrencyCode`
from `test1_fv`.`currency_formats`
group by `test1_fv`.`currency_formats`.`CurrencyCode`
order by `test1_fv`.`currency_formats`.`CurrencyCode`;

