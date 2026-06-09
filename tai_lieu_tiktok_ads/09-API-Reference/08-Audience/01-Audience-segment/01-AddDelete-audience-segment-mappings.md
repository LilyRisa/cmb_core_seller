---
title: "Add/Delete audience segment mappings"
doc_id: 1739940585975809
path: "API Reference / Audience / Audience segment / Add/Delete audience segment mappings"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Add/Delete audience segment mappings

Use this endpoint to add mappings to audience segments, or delete existing mappings from audience segments. The mapping can be single ID mapping (one audience ID per user or device) or multiple ID mapping (multiple audience IDs per user or device ).

Please see [Customer File API vs Streaming API - Important Notes for Streaming API](https://ads.tiktok.com/marketing_api/docs?id=1739566528222210) to learn about the notes about Multiple Advertisers Associated with a Business Account, and [Guidelines for identifier normalization](https://business-api.tiktok.com/portal/docs?id=1850030360880129) to learn about how to normalize your identifiers.

><span style="color:DodgerBlue">**Note**</span><br>To apply for access to the Streaming API (formerly known as Segment API), use [Allowlist Management](https://ads.tiktok.com/marketing_api/blog/detail/7102266224578396162) feature on the Developer Portal by selecting "Streaming API" from the drop down menu. We review applications on an ongoing basis. 

><span style="color:DodgerBlue">**Note**</span><br><ul><li>Rate limits: <ul><li>10 audience IDs in one request.</li><li>5,000 QPS per advertiser account per app.</li><li>1 billion IDs (user or device IDs) per day per advertiser account.</li><li> Streaming API specific rate limit is 10,000 QPS (Basic), 20,000 QPS (Advanced), 30,000 QPS (Premium), 50,000 QPS (Ultimate).</li><li>Payload size limit is 1,000 IDs per request.</li></ul></li><li>SLA: Upon making the API call, you'll immediately receive a success/error response. In case of success, our system will process the IDs asynchronously, for which the SLA is 48 hours.</li></ul>


## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{20%}|v1.2{40%}|v1.3{40%}|
|---|---|---|
|Endpoint path| /v1.2/segment/mapping/| /v1.3/segment/mapping/|
|Request parameter data type|`advertiser_ids`: number[]| `advertiser_ids`: string[]|
|Request parameter name and data type|`audiences`(under `batch_data`): number[]|  `audience_ids `(under `batch_data`): string[] |
|Request parameters deprecated in v1.3| / | `data` |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/segment/mapping/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|Request message type. Allowed format: `application/json`.|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_ids {Required}|string[]| A list of advertiser IDs to make segment mappings, or delete existing segment mappings. All advertiser IDs must belong to the same business account. |
| action | string | Type of action to be performed.<br><br> Enum values:<ul><li>`add`: To add UIDs to an audience or a group of audiences.</li><li>`delete`: To remove UIDs from an audience or a group of audiences.</li></ul> Default value: `add`.<br><br><p><span style="color:darkred"><b>Note</b></span>: UID refers to<ul><li>hashed Device ID ( `id_type`= `IDFA_MD5`, `AAID_MD5`, `IDFA_SHA256`, or `AAID_SHA256`).</li><li>External user ID such as hashed email or hashed phone (`id_type`=`EMAIL_SHA256`,`PHONE_SHA256`)</li></ul> |
| id_schema {Required}| string[] | ID Types to be used for populating the audiences.<br><br> Allowed values: <ul><li>`IDFA_MD5`: MD5 hash of an iOS IDFA.</li><li>`AAID_MD5`: MD5 hash of an Android AAID/GAID.</li><li>`IDFA_SHA256`: SHA256 iOS IDFA.</li><li>`AAID_SHA256`: SHA256 Android AAID/GAID.</li><li>`EMAIL_SHA256`: SHA256 hash of email ID. Refer to [Guidelines for identifier normalization](https://business-api.tiktok.com/portal/docs?id=1850030360880129) to learn about how to normalize your identifiers.</li><li>`PHONE_SHA256`: SHA256 hash of a phone number in E.164 form, e.g. +1231234567.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Duplicate ID Types are not allowed.</li><li>The ID Types can be provided in any order, and you can pass in the allowed values in lower case.</li></ul> |
| batch_data {Required} | object[][] | `batch_data` contains one or more of the following items as an array in JSON format. The field is used to add or delete multiple ID mapping (multiple audience IDs per user or device ). |
#| id | string | ID of a user or device. |
#| audience_ids | string[] | Array of `audience_id` to add or delete multiple ID mapping. Audience IDs must belong to at least one of the advertisers provided in the `advertiser_ids` field.<br><br><p><span style="color:darkred"><b>Note</b></span>: You can get `audience_id` via [/segment/audience/](https://ads.tiktok.com/marketing_api/docs?id=1739940583739393). |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/segment/mapping/' \
--header 'Access-Token: <put_your_access_token_here>' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_ids": 
        ["{{advertiser_id}}", "{{advertiser_id}}"], // advertisers must belong to the same business account
    "action":"add",
    "id_schema": [
      "IDFA_SHA256"
    ],
    "batch_data": [
        [
            {
            "id": "{{id1}}",
            "audience_ids": ["17181920"] // audiences must belong to at least one of the advertisers provided in the advertiser_ids field
            },
            {
            "id": "{{id2}}",
            "audience_ids": ["17181920"]
            }
        ],
        [
            {
            "id": "{{id3}}",
            "audience_ids": ["123456","7891011","13141516"]
            },
            {
            "id": "{{id4}}",
            "audience_ids": ["17181920","2122232425","262728293"]
            }
        ],
        [
            {}, //empty because this user does not have an IDFA 
            {
            "id": "{{id5}}",
            "audience_ids": ["17181920","2122232425","262728293"]
            }
        ]
    ]
}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message. For details, see [Appendix-Return message](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 200,
    "message": "OK",
    "request_id": "XXXXXXXXXX",
    "data": {}
}
(/code);
```
### Multiple Advertisers associated with A Business Account
- A Business Account can have multiple advertisers that are associated with it. For each business account, you only need to receive authorization token once to access data associated with all its advertisers.
- You can use the same authorization token, and make one Segment Mapping call to add the same segment to multiple audiences belonging to multiple advertisers, if these advertisers belong to the same Business Account. 


