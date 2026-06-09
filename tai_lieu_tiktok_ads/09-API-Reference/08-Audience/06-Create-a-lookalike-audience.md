---
title: "Create a lookalike audience"
doc_id: 1739940511844353
path: "API Reference / Audience / Create a lookalike audience"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a lookalike audience

Use this endpoint to create a lookalike audience. You need to provide an existing audience under your advertiser account.

><span style="color:DodgerBlue">**Note**</span><br><ul><li>Newly created audiences require at most 48 hours to be analyzed and processed before they become active. </li><li>Use [/dmp/custom_audience/get/](https://ads.tiktok.com/marketing_api/docs?id=1739940507792385) to check the current status of an audience. Only available (`is_valid`) audiences can be operated on. </li></ul>


 
 ## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dmp/custom_audience/lookalike/create/|/v1.3/dmp/custom_audience/lookalike/create/|
|Request parameter name|`system`|`mobile_os`|
|Request parameter data type |`advertiser_id`: number<br>`source_audience_id`: number<br>` placements`: string|`advertiser_id`: string<br>`source_audience_id`: string<br>` placements`: string[]|
|Request parameter name and data type|`is_contain_source`: number|`include_source`: boolean|
|Response parameter data type |`custom_audience_id`: number|`custom_audience_id`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/lookalike/create/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type. <br>Allowed format: `"application/json"`.|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|custom_audience_name {Required}|string|Audience name. Maximum of 128 characters.|
|audience_sub_type{-Deprecated}|string| Audience sub type, indicating the type of ads that the audience can be used. Enum values: `NORMAL` Normal audience. It can be used in non-R&F ads. `REACH_FREQUENCY`: Audience for Reach & Frequency. It can only be used in R&F ads. Default: `NORMAL`.|
|lookalike_spec {Required}|object||
#|source_audience_id{Required}|string|Source (seed) audience ID. Source audience cannot be an existing lookalike audience.<br><p><span style="color:darkred"><b>Note</b></span>: The size of source audience should be no less than 100. |
#|include_source{Required}|boolean|Whether to include the source audience in the new lookalike audience.|
#|mobile_os{Required}|string|Device operating systems. Enum values support `ALL` for both `ANDROID` and `IOS`. See [Enumeration-Audience Management-Lookalike System](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details.|
#|placements{Required}|string[]|The apps where you want to deliver your ads. Enum values: `TikTok`, `TopBuzz & BuzzVideo`, `Pangle`. See [Enumeration - Audience Management-Lookalike Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details.|
#|location_ids{Required}|string[]|IDs of the locations that you want to target. For enum values, see [Enumeration-Lookalike Locations](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
#|audience_size{Required}|string|Size of lookalike audience. Enum values: `NARROW`, `BALANCED` and `BROAD`.|
```

### Example

``` xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/lookalike/create/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: token' \
--data-raw '{
    "advertiser_id": "ADVERTISER_ID", 
    "calculate_type": "CALCULATE_TYPE", 
    "custom_audience_name": "CUSTOM_AUDIENCE_NAME", 
    "lookalike_spec": {
        "source_audience_id": SOURCE_AUDIENCE_ID,
        "is_contain_source": 1,
        "system": "ALL",
        "placements": ["TikTok"],
        "location_ids": ["JP", "US"],
        "audience_size": "NARROW"
    }
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
#|custom_audience_id |string|Custom audience ID.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "custom_audience_id": "123"
    }
}
(/code);
```
