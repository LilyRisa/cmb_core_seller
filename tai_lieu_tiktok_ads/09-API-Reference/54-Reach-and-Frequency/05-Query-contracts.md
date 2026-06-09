---
title: "Query contracts"
doc_id: 1740489855138818
path: "API Reference / Reach & Frequency / Query contracts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Query contracts

Use this endpoint to query contracts. Since commercial contracts are time-sensitive, you will need to specify a date to check the start and end date of valid contracts that cover that date.

##  Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/rf/contract/query/|/v1.3/rf/contract/query/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|New request parameter |/|`rf_campaign_type`|
|New response parameter |/|`pulse_start_date`<br>`pulse_end_date`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/rf/contract/query/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
| Field | Data Type | Description |
| --- | --- | --- |
| advertiser_id {Required} | string | Advertiser ID |
| included_date {Required} | string | A certain date within the contract validity, or the start/end time of an ad group. Format: "2020-09-18".  |
|rf_campaign_type |string| Use this field to specify whether you want to query TikTok Pulse contract. Enum values: `STANDARD` (Reach & Frequency), `PULSE`(TikTok Pulse). <br><p><span style="color:darkred"><b>Note</b></span>: This is an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
```
### Example
``` xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/rf/contract/query/?advertiser_id={{advertiser_id}}&adgroup_ids={{adgroup_ids}}&included_date=2020-09-18' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
| Field | Data Type | Description |
| --- | --- | --- |
| code  | number | Return code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097) |
| message  | string | Return messages. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097) |
| request_id | string | The log ID of a request, which uniquely identifies the request. |
| data | object | Returned data. |
#| has_valid_contract | boolean | Whether valid contracts can be found.  Enum values: `True`, `False`. <ul><li>The correct contract start date and end date will be returned only when `has_valid_contract` = `True`, which means the specified `included_date` falls within the validity period of the Reach and Frequency contract or TikTok Pulse contract.</li><li>If `rf_campaign_type` is not set as `PULSE` in the request, `has_valid_contract` is `True`when the specified `included_date` is a date within the Reach and Frequency contract validity period ( `[start_date, end_date]`).</li><li>If `rf_campaign_type` is set as `PULSE` in the request, `has_valid_contract` is `True`when the specified `included_date` is a date within the TikTok Pulse contract validity period ( `[pulse_start_date, pulse_end_date]`).</li></ul>|
#| pulse_start_date | string | Start date of TikTok Pulse contract. Returned when `rf_campaign_type` is set to `PULSE` in your request. <br><p><span style="color:darkred"><b>Note</b></span>: When you use [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602) to create an R&F campaign and set `rf_campaign_type` to `PULSE`, the schedule delivery time of your ad group (`[schedule_start_time, schedule_end_time]`) must fall on the intersection of  `[start_date, end_date]` and `[pulse_start_date, pulse_end_date]` returned in this contract endpoint. |
#| pulse_end_date | string | End date of TikTok Pulse contract. Returned when `rf_campaign_type` is set to `PULSE` in your request. <br><p><span style="color:darkred"><b>Note</b></span>: When you use [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602) to create an R&F campaign and set `rf_campaign_type` to `PULSE`, the schedule delivery time of your ad group (`[schedule_start_time, schedule_end_time]`) must fall on the intersection of  `[start_date, end_date]` and `[pulse_start_date, pulse_end_date]` returned in this contract endpoint.  |
#| start_date | string | Start date of Reach and Frequency contract.|
#| end_date | string | End date of Reach and Frequency contract. |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{    
"message": "OK", 
"code": 0,    "data": 
	{        
		"has_valid_contract": true,        
		"start_date": "2020-10-01",       
		"end_date": "2020-11-30"   
		},   
	"request_id": "1E044AD4"}
(/code);
```

