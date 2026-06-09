---
title: "Cancel the R&F ad order"
doc_id: 1740489099367425
path: "API Reference / Reach & Frequency / Cancel the R&F ad order"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Cancel the R&F ad order

Use this endpoint to withdraw an R&F ad order to suspend its delivery. Once an order has been withdrawn, the status of the ad group will be changed to "**Closed**". Ads whose status is "**Deliverying**" cannot be withdrawn.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/adgroup/rf/order/cancel/|/v1.3/rf/order/cancel/|
|Request parameter data type |`advertiser_id`: number<br>`adgroup_ids`: number[]|`advertiser_id`: string<br>`adgroup_ids`: string[]|
|Response parameter data type|`fail_adgroup_ids`: number[]|`fail_adgroup_ids`: string[]|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/rf/order/cancel/

**Method** POST

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
| adgroup_ids {Required} | string[] | ID of the ad group to be withdrawn (ID must correspond to R&F ad group). |
```

### Example

``` xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "adgroup_ids": [
        "ADGROUP_IDS"
    ]
}' \
https://business-api.tiktok.com/open_api/v1.3/rf/order/cancel/
(/code)
```

## Response

``` xtable
| Field | Data Type | Description |
| --- | --- | --- |
| code | number | Return code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| message | string | Return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
| request_id | string | The log id of a request, which uniquely identifies the request. |
| data | object | Returned data.  |
#| fail_adgroup_ids | string[] | ID of the ad group whose withdrawal request has failed. |
```
### Example
``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
    	"fail_adgroup_ids": [222]
    }
    "request_id":"12345678"
}
(/code);
```
