---
title: "Get a suggested bid"
doc_id: 1737107845597186
path: "API Reference / Tools / Get a suggested bid"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get a suggested bid

Use this endpoint to get a suggested bid value for your ad group based on basic campaign and ad group settings like objectives, conversion events, and locations. Note that the suggested bid can only be used with Cost Cap bidding strategy (`bid_type = BID_TYPE_CUSTOM`).

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tools/suggest_bid/|/v1.3/tool/bid/recommend/|
|Request parameter data type |`advertiser_id`: number<br>`campaign_id`: number|`advertiser_id`: string<br>`campaign_id`: string|
|Request parameter name and data type|`locations`: number[]|`location_ids`: string[]|
```



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/bid/recommend/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```
**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser ID. |
|campaign_id |string| Campaign ID.|
|objective_type {required}|string| Campaign objective. The current API endpoint only supports Traffic and Website conversions objectives. For enum values and descriptions, see [Objectives](https://ads.tiktok.com/marketing_api/docs?id=1737585562434561). <br><p><span style="color:darkred"><b>Note</b></span>: This API is being revamped to support larger function scope and more accurate result. Please stay tuned for updates.|
|location_ids {required}|string[]| IDs of the locations that you want to target.|
|external_action|string|Conversion event for the ad group.|
```


### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "campaign_id": "CAMPAIGN_ID",
    "objective_type": "OBJECTIVE_TYPE",
    "locations": [
        "LOCATIONS"
    ],
    "external_action": "EXTERNAL_ACTION"
}' \
https://business-api.tiktok.com/open_api/v1.3/tool/bid/recommend/
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| The log ID of a request, which uniquely identifies the request.|
|data |object|Returned data.|
#|bid|number|Recommended bid value.|

```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "1234",
    "data": {
       "bid": 1.0
	   }
}
(/code);
```
