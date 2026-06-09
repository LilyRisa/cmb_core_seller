---
title: "Update the statuses of ad groups"
doc_id: 1739591716326402
path: "API Reference / Ad Groups / Update the statuses of ad groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the statuses of ad groups

Use this endpoint to enable, disable or delete an ad group. You can:
- Enable the ad group and deliver it to your audience with a specified delivery plan.
- Disable the ad group, suspend the delivery.
- Delete the ad group.

><span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>The ad group in a Dedicated Campaign cannot be deleted. To delete such an ad group, you have to delete the campaign.</li><li>The operation status of a deleted ad group or an ad group in a deleted campaign cannot be modified. You can use [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986) or [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922) to get the operation status of a campaign or an ad group via `operation_status`.</li></ul>


## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/adgroup/update/status/|/v1.3/adgroup/status/update/|
|Request parameter data type |`advertiser_id`: number <br> `adgroup_ids`: number[]|`advertiser_id`: string <br> `adgroup_ids`: string[]|
|Request parameter name|`opt_status`|`operation_status`|
|New request parameters| / |`allow_partial_success`|
|Response parameter data type |`adgroup_ids`: number[] |`adgroup_ids`: string[] |
|New response parameters| / |`error_list`|
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/adgroup/status/update/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br data-tomark-pass>Allowed format: `"application/json"`.|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|adgroup_ids {Required}|string[]|The IDs of the ad groups to be operated on. <br><br>Size range: 1-20.|
|operation_status {Required}|string|The operation to be performed on the ad groups.<br><br>Enum values: `DISABLE` (pause), `ENABLE` (enable), `DELETE` (delete). <br> <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The status of deleted ad groups or ad groups in deleted campaigns cannot be modified.</li><li>`ENABLE` and `DISABLE` operations are not supported for R&F ad groups.</li></ul> |
| allow_partial_success | boolean | Whether to allow partial success for the operation.<br><br>Enum values: `true`, `false`. Default value: `false`. <br><br> If this field is set to `true` and you specify an operation for multiple ad groups, the operation can succeed for only some of the specified ad groups. In this case, the ineligible ad groups will be filtered out. |
```




### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "adgroup_ids": [
        "ADGROUP_IDS"
    ],
    "operation_status": "OPT_STATUS"
}' \
https://business-api.tiktok.com/open_api/v1.3/adgroup/status/update/
(/code)
```

## Response

```xtable
|Field|Data Type|Description |
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Return data.|
#|adgroup_ids|string[]|The IDs of ad groups for which the operation was successful.|
#|status|string|The current status of the ad groups for which the operation was successful.<br><br>Enum values: `DISABLE` (paused), `ENABLE` (enabled), `DELETE` (deleted).|
#| error_list | object[] | Returned only when `allow_partial_success` is set to `true`. <br> <br>A list of errors and the corresponding failed ad groups. |
##| adgroup_id| string | The ID of the ad group for which the operation failed. |
##| error_message | string | The error message associated with the failed ad group. |
|request_id |string| The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "status": "DISABLE",
        "adgroup_ids": [
            111
        ]
    },
    "request_id": "2020031110223101018904922300361F64"
}
(/code);
```

