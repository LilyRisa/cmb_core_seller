---
title: "Budget verification ratio and value range for each currency"
doc_id: 1737585839634433
path: "Marketing API / Campaign Management / Guides / Campaign / Budget / Budget verification ratio and value range for each currency"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Budget verification ratio and value range for each currency

This article lists the budget verification ratio and precision of each currency, and provides two tables where you directly see the daily budget value range for each currency at the campaign and ad group levels. To learn more about budgets and how to set a budget, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114).
 
## Budget verification ratio and precision of each currency
See the table below to learn about the allowed precision and budget verification ratio for each currency. Budget verification ratio is used to determine the budget value range at the ad group and campaign levels. See [Budget-Budget setting guidelines](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114) to learn about the budget verification logic.


```xtable
| Currency {25%} | Code {25%}| Budget verification ratio{25%} | Precision{25%} |
|---|---|---|
| US Dollar | USD | 1 | 0.01 |
| Euro | EUR | 1 | 0.01 |
| Japanese Yen | JPY | 100 | 1 |
| Hong Kong Dollar | HKD | 10 | 0.01 |
| British Pound | GBP | 1 | 0.01 |
| Malaysian Ringgit | MYR | 1 | 0.01 |
| Russian Ruble | RUB | 100 | 0.01 |
| South African Rand | ZAR | 10 | 0.01 |
| South Korean Won | KRW | 1,000 | 1 |
| UAE Dirham | AED | 1 | 0.01 |
| Saudi Riyal | SAR | 1 | 0.01 |
| Hungarian Forint | HUF | 1 | 1 |
| Polish Zloty | PLN | 4 | 0.01 |
| Danish Krone | DKK | 10 | 0.01 |
| Swedish Krona | SEK | 10 | 0.01 |
| Norwegian Krone | NOK | 10 | 0.01 |
| Turkish Lira | TRY | 10 | 0.01 |
| Mexican Peso | MXN | 10 | 0.01 |
| Thai Baht | THB | 10 | 0.01 |
| Australian Dollar | AUD | 1 | 0.01 |
| Canadian Dollar | CAD | 1 | 0.01 |
| New Zealand Dollar | NZD | 1 | 0.01 |
| Singapore Dollar | SGD | 1 | 0.01 |
| Swiss Franc | CHF | 1 | 0.01 |
| Chinese Yuan | CNY | 10 | 0.01 |
| Algerian Dinar | DZD | 100 | 0.01 |
| Argentine Peso | ARS | 1 | 0.01 |
| Bangladeshi Taka | BDT | 100 | 0.01 |
| Bolivian Boliviano | BOB | 10 | 0.01 |
| Brazilian Real | BRL | 1 | 0.01 |
| Chilean Peso | CLP | 1,000 | 1 |
| Colombian Peso | COP | 1,000 | 0.01 |
| Costa Rican Colon | CRC | 1,000 | 0.01 |
| Czech Koruna | CZK | 10 | 0.01 |
| Egyptian Pound | EGP | 10 | 0.01 |
| Guatemalan Quetzal | GTQ | 10 | 0.01 |
| Honduran Lempira | HNL | 10 | 0.01 |
| Icelandic Krona | ISK | 100 | 1 |
| Indian Rupee | INR | 100 | 0.01 |
| Indonesian Rupiah | IDR | 10,000 | 1 |
| Israeli New Shekel | ILS | 1 | 0.01 |
| Kenyan Shilling | KES | 100 | 0.01 |
| Macanese Pataca | MOP | 10 | 0.01 |
| New Taiwan Dollar | TWD | 10 | 1 |
| Nicaraguan Cordoba | NIO | 10 | 0.01 |
| Nigerian Naira | NGN | 100 | 0.01 |
| Pakistani Rupee | PKR | 100 | 0.01 |
| Paraguayan Guarani | PYG | 10,000 | 1 |
| Peruvian Nuevo Sol | PEN | 1 | 0.01 |
| Philippine Peso | PHP | 50 | 0.01 |
| Qatari Riyal | QAR | 1 | 0.01 |
| Romanian Leu | RON | 1 | 0.01 |
| Ukrainian Hryvnia | UAH| 10 | 0.01 |
| Uruguayan Peso | UYU | 10 | 0.01 |
| Venezuelan Bolivar | VEF | 100,000 | 0.01 |
| Vietnamese Dong | VND | 10,000 | 1 |
```
## Daily budget value range
The value range of the daily advertising budget for each currency is calculated as <strong>base daily budget value range</strong> * <strong>Budget Verification Ratio for the currency</strong>. 

- For campaigns, with the exception of those with `objective_type` as `PRODUCT_SALES` and `campaign_product_source` as `STORE` , the base daily budget value range is \[50, 10 000 000). For campaigns with `objective_type` as `PRODUCT_SALES` and `campaign_product_source` as `STORE`, the base daily budget value range is \[10, 10 000 000).     
- For ad groups, with the exception of those with `product_source` as `STORE` or `SHOWCASE`, the base daily budget value range is \[20,10 000 000). For ad groups with `product_source` as `STORE` or `SHOWCASE`, the base daily budget value range is \[10, 10 000 000). 

The Budget Verification Ratio for each currency is listed in the table above and you can see the minimum and maximum daily budget for each currency in the tables below.

### At the campaign level

```xtable
| Currency{16%} | Code{10%} | Minimum daily budget (inclusive)<br>(for <code>PRODUCT_SALES</code> campaigns with <code>campaign_product_source</code> as <code>STORE</code>) {26%} | Minimum daily budget (inclusive)<br>(for other campaigns){24%} | Maximum daily budget (exclusive)<br> (for all camapigns){24%} |
|---|---|---|---|---|
| US Dollar | USD | 10 | 50 | 10,000,000 |
| British Pound | GBP | 10 | 50 | 10,000,000 |
| Chinese Yuan | CNY | 100 | 500 | 100,000,000 |
| Euro | EUR | 10 | 50 | 10,000,000 |
| Hong Kong Dollar | HKD | 100 | 500 | 100,000,000 |
| Japanese Yen | JPY | 1,000 | 5,000 | 1,000,000,000 |
| Algerian Dinar | DZD | 1,000 | 5,000 | 1,000,000,000 |
| Argentine Peso | ARS | 10 | 50 | 10,000,000 |
| Australian Dollar | AUD | 10 | 50 | 10,000,000 |
| Bangladeshi Taka | BDT | 1,000 | 5,000 | 1,000,000,000 |
| Bolivian Boliviano | BOB | 100 | 500 | 100,000,000 |
| Brazilian Real | BRL | 10 | 50 | 10,000,000 |
| Canadian Dollar | CAD | 10 | 50 | 10,000,000 |
| Chilean Peso | CLP | 10,000 | 50,000 | 10,000,000,000 |
| Colombian Peso | COP | 10,000 | 50,000 | 10,000,000,000 |
| Costa Rican Colon | CRC | 10,000 | 50,000 | 10,000,000,000 |
| Czech Koruna | CZK | 100 | 500 | 100,000,000 |
| Danish Krone | DKK | 100 | 500 | 100,000,000 |
| Egyptian Pound | EGP | 100 | 500 | 100,000,000 |
| Guatemalan Quetzal | GTQ | 100 | 500 | 100,000,000 |
| Honduran Lempira | HNL | 100 | 500 | 100,000,000 |
| Hungarian Forint | HUF | 10 | 50 | 10,000,000 |
| Icelandic Krona | ISK | 1,000 | 5,000 | 1,000,000,000 |
| Indian Rupee | INR | 1,000 | 5,000 | 1,000,000,000 |
| Indonesian Rupiah | IDR | 100,000 | 500,000 | 100,000,000,000 |
| Israeli New Shekel | ILS | 10 | 50 | 10,000,000 |
| Kenyan Shilling | KES | 1,000 | 5,000 | 1,000,000,000 |
| Macanese Pataca | MOP | 100 | 500 | 100,000,000 |
| Malaysian Ringgit | MYR | 10 | 50 | 10,000,000 |
| Mexican Peso | MXN | 100 | 500 | 100,000,000 |
| New Taiwan Dollar | TWD | 100 | 500 | 100,000,000 |
| New Zealand Dollar | NZD | 10 | 50 | 10,000,000 |
| Nicaraguan Cordoba | NIO | 100 | 500 | 100,000,000 |
| Nigerian Naira | NGN | 1,000 | 5,000 | 1,000,000,000 |
| Norwegian Krone | NOK | 100 | 500 | 100,000,000 |
| Pakistani Rupee | PKR | 1,000 | 5,000 | 1,000,000,000 |
| Paraguayan Guarani | PYG | 100,000 | 500,000 | 100,000,000,000 |
| Peruvian Nuevo Sol | PEN | 10 | 50 | 10,000,000 |
| Philippine Peso | PHP | 500 | 2,500 | 500,000,000 |
| Polish Zloty | PLN | 40 | 200 | 40,000,000 |
| Qatari Riyal | QAR | 10 | 50 | 10,000,000 |
| Romanian Leu | RON | 10 | 50 | 10,000,000 |
| Russian Ruble | RUB | 1,000 | 5,000 | 1,000,000,000 |
| Saudi Riyal | SAR | 10 | 50 | 10,000,000 |
| Singapore Dollar | SGD | 10 | 50 | 10,000,000 |
| South African Rand | ZAR | 100 | 500 | 100,000,000 |
| South Korean Won | KRW | 10,000 | 50,000 | 10,000,000,000 |
| Swedish Krona | SEK | 100 | 500 | 100,000,000 |
| Swiss Franc | CHF | 10 | 50 | 10,000,000 |
| Thai Baht | THB | 100 | 500 | 100,000,000 |
| Turkish Lira | TRY | 100 | 500 | 100,000,000 |
| UAE Dirham | AED | 10 | 50 | 10,000,000 |
| Ukrainian Hryvnia | UAH | 100 | 500 | 100,000,000 |
| Uruguayan Peso | UYU | 100 | 500 | 100,000,000 |
| Venezuelan Bolivar | VEF | 1,000,000 | 5,000,000 | 1,000,000,000,000 |
| Vietnamese Dong | VND | 100,000 | 500,000 | 100,000,000,000 |
```

### At the ad group level 
 
><span style="color:DodgerBlue">**Note**</span><br>
> If the ad group is created from the TikTok Ads Manager in <strong>Simplified Mode</strong>, the base minimum daily budget value at the ad group level may not be 20: <ul><li> For advertising goal Traffic, the base minimum daily budget is 5.</li><li> For advertising goal Community interaction, Lead generation, or Conversions, the base minimum daily budget is 10.</li><li> For other advertising goals, the base minimum daily budget is 20.</li></ul>

```xtable
| Currency{16%} | Code{10%} | Minimum daily budget (inclusive)<br>(for <code>PRODUCT_SALES</code> ad groups with <code>product_source</code> as <code>STORE</code> or <code>SHOWCASE</code>) {26%} | Minimum daily budget (inclusive)<br>(for other ad groups){24%} | Maximum daily budget (exclusive)<br> (for all ad groups){24%} |
|---|---|---|---|---|
| US Dollar | USD | 10 | 20 | 10,000,000 |
| British Pound | GBP | 10 | 20 | 10,000,000 |
| Chinese Yuan | CNY | 100 | 200 | 100,000,000 |
| Euro | EUR | 10 | 20 | 10,000,000 |
| Hong Kong Dollar | HKD | 100 | 200 | 100,000,000 |
| Japanese Yen | JPY | 1,000 | 2,000 | 1,000,000,000 |
| Algerian Dinar | DZD | 1,000 | 2,000 | 1,000,000,000 |
| Argentine Peso | ARS | 10 | 20 | 10,000,000 |
| Australian Dollar | AUD | 10 | 20 | 10,000,000 |
| Bangladeshi Taka | BDT | 1,000 | 2,000 | 1,000,000,000 |
| Bolivian Boliviano | BOB | 100 | 200 | 100,000,000 |
| Brazilian Real | BRL | 10 | 20 | 10,000,000 |
| Canadian Dollar | CAD | 10 | 20 | 10,000,000 |
| Chilean Peso | CLP | 10,000 | 20,000 | 10,000,000,000 |
| Colombian Peso | COP | 10,000 | 20,000 | 10,000,000,000 |
| Costa Rican Colon | CRC | 10,000 | 20,000 | 10,000,000,000 |
| Czech Koruna | CZK | 100 | 200 | 100,000,000 |
| Danish Krone | DKK | 100 | 200 | 100,000,000 |
| Egyptian Pound | EGP | 100 | 200 | 100,000,000 |
| Guatemalan Quetzal | GTQ | 100 | 200 | 100,000,000 |
| Honduran Lempira | HNL | 100 | 200 | 100,000,000 |
| Hungarian Forint | HUF | 10 | 20 | 10,000,000 |
| Icelandic Krona | ISK | 1,000 | 2,000 | 1,000,000,000 |
| Indian Rupee | INR | 1,000 | 2,000 | 1,000,000,000 |
| Indonesian Rupiah | IDR | 100,000 | 200,000 | 100,000,000,000 |
| Israeli New Shekel | ILS | 10 | 20 | 10,000,000 |
| Kenyan Shilling | KES | 1,000 | 2,000 | 1,000,000,000 |
| Macanese Pataca | MOP | 100 | 200 | 100,000,000 |
| Malaysian Ringgit | MYR | 10 | 20 | 10,000,000 |
| Mexican Peso | MXN | 100 | 200 | 100,000,000 |
| New Taiwan Dollar | TWD | 100 | 200 | 100,000,000 |
| New Zealand Dollar | NZD | 10 | 20 | 10,000,000 |
| Nicaraguan Cordoba | NIO | 100 | 200 | 100,000,000 |
| Nigerian Naira | NGN | 1,000 | 2,000 | 1,000,000,000 |
| Norwegian Krone | NOK | 100 | 200 | 100,000,000 |
| Pakistani Rupee | PKR | 1,000 | 2,000 | 1,000,000,000 |
| Paraguayan Guarani | PYG | 100,000 | 200,000 | 100,000,000,000 |
| Peruvian Nuevo Sol | PEN | 10 | 20 | 10,000,000 |
| Philippine Peso | PHP | 500 | 1,000 | 500,000,000 |
| Polish Zloty | PLN | 40 | 80 | 40,000,000 |
| Qatari Riyal | QAR | 10 | 20 | 10,000,000 |
| Romanian Leu | RON | 10 | 20 | 10,000,000 |
| Russian Ruble | RUB | 1,000 | 2,000 | 1,000,000,000 |
| Saudi Riyal | SAR | 10 | 20 | 10,000,000 |
| Singapore Dollar | SGD | 10 | 20 | 10,000,000 |
| South African Rand | ZAR | 100 | 200 | 100,000,000 |
| South Korean Won | KRW | 10,000 | 20,000 | 10,000,000,000 |
| Swedish Krona | SEK | 100 | 200 | 100,000,000 |
| Swiss Franc | CHF | 10 | 20 | 10,000,000 |
| Thai Baht | THB | 100 | 200 | 100,000,000 |
| Turkish Lira | TRY | 100 | 200 | 100,000,000 |
| UAE Dirham | AED | 10 | 20 | 10,000,000 |
| Ukrainian Hryvnia | UAH | 100 | 200 | 100,000,000 |
| Uruguayan Peso | UYU | 100 | 200 | 100,000,000 |
| Venezuelan Bolivar | VEF | 1,000,000 | 2,000,000 | 1,000,000,000,000 |
| Vietnamese Dong | VND | 100,000 | 200,000 | 100,000,000,000 |
```

