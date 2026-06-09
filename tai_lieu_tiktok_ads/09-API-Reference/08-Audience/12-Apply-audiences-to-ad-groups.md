---
title: "Apply audiences to ad groups"
doc_id: 1758615097524225
path: "API Reference / Audience / Apply audiences to ad groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Apply audiences to ad groups

Use this endpoint to apply audience to or disconnect audience from multiple ad groups.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/apply/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type. Allowed format: `"application/json"`.|
```

**Parameters**

```xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|custom_audience_id{Required} |string|Custom audience ID. You can only pass one custom audience ID.
|adgroup_ids{Required}|string[]|A list of ad group IDs. <br><p><span style="color:darkred"><b>Note</b></span>:<br><ul><li>`adgroup_ids` and `custom_audience_id` should be under the same advertiser account. Otherwise, an error will occur.<li>Lookalike Audience cannot be used in Reach & Frequency ads. Otherwise, an error will occur. See below for more details. </li><br>1. If the `custom_audience_id` is a Lookalike Audience and the `adgroup_ids` are Reach & Frequency ad groups, an error will occur.<p>2. If the `custom_audience_id` is a Lookalike Audience with the `REACH_FREQUENCY` audience subtype, an error will occur.
|action_mode {Required}|string|Specific operation to be performed on the audience. Enum values: `Apply`, `Disconnect`.
|usage_mode  {+Conditional}|string|Whether to include this audience in or exclude it from your ad groups. Required when `action_mode` is Apply. Enum values: `Include`, `Exclude`. 
```

### Example
#### 1. Apply Audience

```xcodeblock
(code)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/apply' \
--header 'Access-Token: 123' \
--header 'Content-Type: application/json' \
--data-raw '{
    "advertiser_id":"123",
    "custom_audience_id":"123",
    "adgroup_ids":["123"],
    "action_mode":"Apply",
    "usage_mode":"Include"
}'
(/code)
```
#### 2. Disconnect audience
```xcodeblock
(code)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/apply' \
--header 'Access-Token: 123' \
--header 'Content-Type: application/json' \
--data-raw '{
    "advertiser_id":"123",
    "custom_audience_id":"123",
    "adgroup_ids":["123"],
    "action_mode":"Disconnect"
}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|Returned Data. |
|request_id |string|The log ID of a request, which uniquely identifies the request.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "123",
    "data": {}
}
(/code);
```


