---
title: "Update the statuses of ads"
doc_id: 1739953422970882
path: "API Reference / Ads / Update the statuses of ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the statuses of ads

Use this endpoint to enable, disable or delete an ad or ads. 

> <span style="color:DodgerBlue">**Note**</span><br>
> The operation status of a deleted ad or an ad in a deleted ad group or campaign cannot be modified. You can use [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986), [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922), or [/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770) to get the operation status of a campaign, an ad group or an ad via `operation_status`.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/ad/update/status/|/v1.3/ad/status/update/|
|Request parameter name|`opt_status`| `operation_status`|
|Request parameter data type |`advertiser_id`:number<br>`ad_ids`: number[]<br>`aco_ad_ids`: number[]|`advertiser_id`:string<br>`ad_ids`: string[]<br>`aco_ad_ids`: string[]|
|Response parameter data type| `ad_ids`: number[]<br>`aco_ad_ids`: number[]|`ad_ids`: string[]<br>`aco_ad_ids`: string[]|
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/ad/status/update/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type. Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|ad_ids {+Conditional}| string[] | A list of ad IDs. Allowed quantity: `1-20`. Either `ad_ids` or `aco_ad_ids` has to be set. |
|aco_ad_ids{+Conditional}| string[] | A list of ACO ad IDs. Only support `ENABLE` and `DISABLE` for ACO ads.  Allowed quantity: `1-20`. Either `ad_ids` or `aco_ad_ids` has to be set.  |
|advertiser_id {Required}|string| Advertiser ID |
|operation_status {Required}|string| The operation being made. <br>Enum values: `DELETE` (delete),` DISABLE` (pause), `ENABLE` (enable). <br><p><span style="color:darkred"><b>Note</b></span>: The status of deleted ads or ads in deleted campaigns or ad groups cannot be modified.|
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "operation_status": "OPT_STATUS",
    "ad_ids": [
        "AD_IDS"
    ]
}' \
https://business-api.tiktok.com/open_api/v1.3/ad/status/update/
(/code)
```

## Response

```xtable
|Field|Data Type|Description |
|---|---|---|
|code |number|Return code, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|Return data  |
#|ad_ids | string[]| List of updated ad IDs |
#|aco_ad_ids| string[] | List of updated ACO ad IDs |
#|status |string|The operation that was executed. Enum values: `DELETE`,`DISABLE`, `ENABLE`  |
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
  "message": "OK",
  "code": 0,
  "data": {
    "status": "ENABLE",
    "ad_ids": [
      11,
      22
    ]
  },
  "request_id": "2019091810032301011023224213552"
}
(/code);
```

