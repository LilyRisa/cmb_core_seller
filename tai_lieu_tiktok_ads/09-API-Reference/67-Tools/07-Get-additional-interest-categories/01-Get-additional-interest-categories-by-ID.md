---
title: "Get additional interest categories by ID"
doc_id: 1763590894544897
path: "API Reference / Tools / Get additional interest categories / Get additional interest categories by ID"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get additional interest categories by ID

Use this endpoint to get additional interest categories by ID.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{22%}|v1.2{40%}|v1.3{38%}|
|---|---|---|
|Endpoint path|/v1.2/tools/interest_keyword/get/|/v1.3/tool/interest_keyword/get/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Request parameter name and data type|`id`: number| `keyword_id` : string|
|New request parameters|/|`filtering`<br>`audience_type`|
|Response parameter name and data type|`id`: number|  `keyword_id`: string |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/interest_keyword/get/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser ID. |
|keyword_query|object[]|Information of the additional interest category you want to get. <br>Max size = 50.|
#|keyword_id {required}|string|Additional interest category ID.|
#|language|string|Additional interest category language. Default value: `en`. <br> Supported languages: `fr`, `id`, `it`, `ja`, `ms`, `ar`, `vi`, `en`, `ru`, `es`, `th`, `tr`, `hi`, `zh`, `de`, `ko`.|
|filtering| object |Filtering conditions.|
#|audience_type| string|The audience type that you want to filter by. Enum values:<ul><li>`GENERAL_INTEREST`:  General interest. The audiences have a general, long-term interest in a video category.</li><li>`PURCHASE_INTENTION`: Purchase intent. The audiences' recent behavior suggests they may buy something related to a content category.</li></ul>Default value: `GENERAL_INTEREST`.|
```

### Example
```xcodeblock
(code http curl)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/interest_keyword/get/?advertiser_id= {{ADVERTISER_ID}}&keyword_query=[{"keyword_id": "{{keyword_id}}"}]' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data |object|Return data.|
#|keywords|object[]| List of additional interest categories.|
##|keyword|string|Additional interest category.|
##|keyword_id|string| Additional interest category ID.|
##|status|string|Whether an additional interest category will be effective. <br>Enum values: `EFFECTIVE`, `INEFFECTIVE`. <br>An additional interest category is effective if using it can reach a reasonable number of people. <br>An additional interest category is ineffective if the projected audience size is too small.<br><p><span style="color:darkred"><b>Note</b></span>: Only effective additional interest categories can be used in ad groups to target audiences.|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "2021081206012901024503714416ABA90A",
    "data": {
        "keywords": [
            {
                "keyword_id": "2027755677",
                "status": "EFFECTIVE",
                "keyword": "movie app"
            }
        ]
    }
}
(/code);
```
