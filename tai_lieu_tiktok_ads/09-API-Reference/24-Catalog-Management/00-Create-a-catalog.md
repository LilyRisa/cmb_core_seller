---
title: "Create a catalog"
doc_id: 1740306481704961
path: "API Reference / Catalog Management / Create a catalog"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a catalog

Use this endpoint to create and set up your catalog using information such as name, targeted locations, and currency.  

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/create/| /v1.3/catalog/create/|
|Request parameter name |`country`|`region_code`|
|Request parameter data type |`bc_id`: number|`bc_id`: string|
|New request parameters |/|`additional_config_list`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/create/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. <br>Accepted Value: `"application/json"`.  |
```

**Parameters**

``` xtable
| Field | Type | Description |
|--- |--- |--- |
| bc_id {Required} | string | Business Center ID. |
| name {Required} | string | Catalog name. <br><br>Length limit: 128 characters.|
| catalog_type {Required} | string | Catalog type. <br>Enum values: <ul><li>`ECOM`: E-commerce catalog. </li><li> `HOTEL`: hotel catalog.</li><li>`FLIGHT`: flight catalog.</li><li> `DESTINATION`: destination catalog.</li><li> `ENTERTAINMENT`: entertainment catalog.</li><li>`AUTO_VEHICLE`: Auto-Inventory catalog.</li><li>`AUTO_MODEL`: Auto-Model catalog.</li><li>`MINI_SERIES`: mini series catalog.</li></ul><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The entertainment catalog is currently an allowlist-only feature and is invitation-only because this catalog type is under Alpha Testing. If you would like to access it, please contact your TikTok representative. However, acceptance into the Alpha Test is not guaranteed.</li><li>The mini series catalog is currently an allowlist-only feature and is invitation-only because the catalog type is under testing. If you would like to access it, please contact your TikTok representative. However, acceptance into the test is not guaranteed. </li></ul> |
| catalog_conf {Required} | object | Catalog configuration information. |
#|region_code {Required} | string | The region code of the primary targeting region.<br><br> To get a list of available countries and regions, use [/catalog/available_country/get/](https://ads.tiktok.com/marketing_api/docs?id=1740491257516034). Select a region code from the region codes returned in `region_codes`.<br><br>When `catalog_type` is `AUTO_VEHICLE` or `AUTO_MODEL`, see <a href="#item-link-List of region codes and currencies for Auto-Inventory and Auto-Model catalogs">List of region codes and currencies for Auto-Inventory and Auto-Model catalogs</a> to find out the supported `region_code` values.<br><br>To find out the corresponding region for a region code, refer to [Appendix - Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010).<br><br>Example: `US`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Once set, this field cannot be updated.</p>|
#|currency {Required} | string | Currency for the primary targeting region. <br><br>For supported currencies, seethe <b>Code</b> column in [Budget verification ratio and precision of each currency](https://business-api.tiktok.com/portal/docs?id=1737585839634433#item-link-Budget%20verification%20ratio%20and%20precision%20of%20each%20currency).<br><br>When `catalog_type` is `AUTO_VEHICLE` or `AUTO_MODEL`, see <a href="#item-link-List of region codes and currencies for Auto-Inventory and Auto-Model catalogs">List of region codes and currencies for Auto-Inventory and Auto-Model catalogs</a> to find out the corresponding `currency` value for the specified `region_code` value.<br><br>Example: `USD`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Once set, this field cannot be updated.</p>|
#|channel |string| Channel for creating the catalog.<br><br> Enum values: <ul><li>`PARTNER`: Partners.</li><li>`CLIENT`: Direct advertisers.</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: Once set, this field cannot be updated.</p>|
#|additional_config_list|object[]|Valid only when `catalog_type` is `MINI_SERIES`.<br><br>Details of additional targeting regions.<br><br>No max size.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Once set, this field cannot be updated.</li><li>If you want to target multiple regions through mini series catalogs, specify the additional targeting regions through `additional_config_list` during catalog creation. Then upload short drama series that target multiple regions.</li></ul></p>|
##|region_code {+Conditional}|string|Required when `additional_config_list` is specified.<br><br>The region code of the additional targeting region.<br><br>To get a list of available countries and regions, use [/catalog/available_country/get/](https://business-api.tiktok.com/portal/docs?id=1740491257516034). Select a region code from the region codes returned in `region_codes`.<br><br>To find out the corresponding region for a region code, refer to [Appendix - Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010).<br><br>Example: `GB`.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Once set, this field cannot be updated.</li><li>Duplicate region codes are not supported.</li></ul></p>|
##|currency {+Conditional}|string|Required when `additional_config_list` is specified.<br><br>Currency for the additional targeting region.<br><br>For supported currencies, see the <b>Code</b> column in [Budget verification ratio and precision of each currency](https://business-api.tiktok.com/portal/docs?id=1737585839634433#item-link-Budget%20verification%20ratio%20and%20precision%20of%20each%20currency).<br><br>Example: `GBP`.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Once set, this field cannot be updated.</li><li>You can only specify up to one currency for each additional targeting region.</li></ul></p>|
```
### List of region codes and currencies for Auto-Inventory and Auto-Model catalogs
The following table lists the supported `region_code` values and corresponding `currency` values for Auto-Inventory and Auto-Model catalogs.
``` xtable
| Region {20%}| <code>region_code</code> value{20%} | <code>currency</code> value {60%}|
|--- |--- |--- |
| Argentina | `AR` | `ARS` |
| Australia | `AU` | `AUD` |
| Austria | `AT` | `EUR` |
| Azerbaijan | `AZ` | `AZN` |
| Bahrain | `BH` | `BHD` |
| Bangladesh | `BD` | `BDT` |
| Belarus | `BY` | `BYN` |
| Belgium | `BE` | `EUR` |
| Brazil | `BR` | `BRL` |
| Cambodia | `KH` | `KHR` |
| Canada | `CA` | `CAD` |
| Chile | `CL` | `CLP` |
| Colombia | `CO` | `COP` |
| Czechia | `CZ` | `CZK` |
| Denmark | `DK` | `DKK` |
| Ecuador | `EC` | `USD` |
| Egypt | `EG` | `EGP` |
| Finland | `FI` | `EUR` |
| France | `FR` | `EUR` |
| Germany | `DE` | `EUR` |
| Greece | `GR` | `EUR` |
| Hungary | `HU` | `HUF` |
| Indonesia | `ID` | `IDR` |
| Iraq | `IQ` | `IQD` |
| Ireland | `IE` | `EUR` |
| Israel | `IL` | `ILS` |
| Italy | `IT` | `EUR` |
| Japan | `JP` | `JPY` |
| Kazakhstan | `KZ` | `KZT` |
| Korea | `KR` | `KRW` |
| Kuwait | `KW` | `KWD` |
| Malaysia | `MY` | `MYR` |
| Mexico | `MX` | `MXN` |
| Morocco | `MA` | `MAD` |
| Netherlands | `NL` | `EUR` |
| New Zealand | `NZ` | `NZD` |
| Norway | `NO` | `NOK` |
| Oman | `OM` | `OMR` |
| Pakistan | `PK` | `PKR` |
| Peru | `PE` | `PEN` |
| Philippines | `PH` | `PHP` |
| Poland | `PL` | `PLN` |
| Portugal | `PT` | `EUR` |
| Qatar | `QA` | `QAR` |
| Romania | `RO` | `RON` |
| Russia | `RU` | `RUB` |
| Saudi Arabia | `SA` | `SAR` |
| Serbia | `RS` | `RSD` |
| Singapore | `SG` | `SGD` |
| South Africa | `ZA` | `ZAR` |
| Spain | `ES` | `EUR` |
| Sweden | `SE` | `SEK` |
| Switzerland | `CH` | `CHF` |
| Taiwan, China | `TW` | `TWD` |
| Thailand | `TH` | `THB` |
| Turkey | `TR` | `TRY` |
| Ukraine | `UA` | `UAH` |
| United Arab Emirates | `AE` | `AED` |
| United Kingdom | `GB` | `GBP` |
| United States | `US` | `USD` |
| Uruguay | `UY` | `UYU` |
| Vietnam | `VN` | `VND` |
``` 


### Example

```xcodeblock
(code curl http)

    curl -X POST -H "Access-Token:YOUR_ACCESS_TOKEN" \
    -H "Content-Type:application/json" \
    -d '{\"bc_id\": BC_ID,\"name\": \"NAME\",\"catalog_type\": \"CATALOG_TYPE\",\"catalog_conf\": {\"region_code\": \"COUNTRY\",\"currency\": \"CURRENCY\"}}' \
    https://business-api.tiktok.com/open_api/v1.3/catalog/create/
(/code)
```

## Response

``` xtable
| Field | Type | Description |
|--- |--- |--- |
| code | number  |Return code. For the complete list of response codes and descriptions, see [Appendix - Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Return message. For details, see [Appendix-Return message](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
| data | object | Returned data. |
# | catalog_id | string | Catalog ID. |
| request_id | string | The log id of a request, which uniquely identifies the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
"message": "OK",
"code": 0,
"data": {
    "catalog_id": "6869649744440854277"
},
"request_id": "2020090707561801023125321410627"
}
(/code);
```
