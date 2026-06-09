---
title: "Get the advertiser list of a Billing Group"
doc_id: 1738458108705794
path: "API Reference / BC Billing Groups / Get the advertiser list of a Billing Group"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the advertiser list of a Billing Group

Use this endpoint to get the list of advertisers that are bound to a Billing Group. 

Both Agency and Direct Business Centers are able to perform billing group related operations.  

> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission are authorized to get the Billing Group advertiser list. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/bc/billing_group/advertiser/list/|/v1.3/bc/billing_group/advertiser/list/|
|Request parameter data type |`bc_id`: number <br> `billing_group_id`: number|`bc_id`: string <br> `billing_group_id`: string|
|Response parameter data type|`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/billing_group/advertiser/list/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string| Business Center ID |
|billing_group_id {Required}|string| Billing Group ID |
|page|number| Page number. Default value: 1|
|page_size|number|Page size, in the range of 1-50. The default value is 10.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET https://business-api.tiktok.com/open_api/v1.3/bc/billing_group/advertiser/list/?bc_id={{bc_id}}&billing_group_id={{billing_group_id}}
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|list|object[]|List of advertisers|
##|advertiser_id|string|Ad Account ID|
#|page_info|object| Pagination information|
##|page|number| Current page number|
##|page_size| number | Page size|
##|total_number| number | Total number of results |
##|total_page| number | Total pages of results|
|request_id |string|The log id of the request, which uniquely identifies a request  |
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 40104,
    "message": "The access_token is empty.",
    "request_id": "{{request_id}}"
}
(/code)
```


