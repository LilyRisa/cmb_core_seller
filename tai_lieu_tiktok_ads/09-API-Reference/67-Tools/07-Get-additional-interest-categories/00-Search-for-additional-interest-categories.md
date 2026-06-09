---
title: "Search for additional interest categories"
doc_id: 1763590884474882
path: "API Reference / Tools / Get additional interest categories / Search for additional interest categories"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Search for additional interest categories

Use this endpoint to search for additional interest categories. You need to specify one or more seed keywords and the seed keyword or keywords will be used to search for related additional interest categories. 

The returned additional interest categories (`keyword_id`) with `status` as `EFFECTIVE` can be passed to the `interest_keyword_ids` field when calling [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). Then the additional interest categories will be used in the ad group to target audience.

> <span style="color:DodgerBlue">**Note**</span><br>
> We recommend that you use the Consolidated Interest and Behavior API ([/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1796638342793218)) to search for or list targeting categories and hashtags for interests and behaviors. This API provides a consolidated and efficient approach to access the necessary targeting information.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{22%}|v1.2{40%}|v1.3{38%}|
|---|---|---|
|Endpoint path|/v1.2/tools/interest_keyword/recommend/|/v1.3/tool/interest_keyword/recommend/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Request parameter name and data type |`keywords`: string[]|`keyword`: string|
|New request parameters |/ |`audience_type`<br>`keywords`|
|Response parameter name and data type |`id`: number|  `keyword_id`: string |
|New response parameters |/ |`input_keyword`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/interest_keyword/recommend/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser ID. |
| keywords {+Conditional} | string[] | <ul><li> Either `keywords` or `keyword` has to be set. </li><li> If `keywords` and `keyword` are passed simultaneously, `keyword` will be ignored.</li></ul> <br> A list of seed keywords that you provide to search for additional interest categories. <br>Each seed keyword will be used separately to generate corresponding additional interest categories. <br><br> Max size: 10. |
| keyword {+Conditional} | string | <ul><li> Either `keywords` or `keyword` has to be set. </li><li> If `keywords` and `keyword` are passed simultaneously, `keyword` will be ignored.</li></ul> <br>One seed keyword that you provide to search for additional interest categories.  |
|mode|string|Search mode.<br><br> Enum values: `FUZZ_MATCH`, `SEMANTIC_RECOMMEND`. <br>Default value: `FUZZ_MATCH`.|
|language|string| Keyword language. <br><br>  Enum values: `fr`, `id`, `it`, `ja`, `ms`, `ar`, `vi`, `en`, `ru`, `es`, `th`, `tr`, `hi`, `zh`, `de`, `ko`.  <br>Default value: `en`. <br><br>Note that target audience languages are not limited by keyword languages. You can use English categories to target audience that do not speak English.|
|limit|number| Number of additional interest categories you want to get.  <br> <br>Default value: 50. <br> Value range: 1-50.|
|audience_type|string| Audience type.  <br> <br>Enum values: <ul><li> `GENERAL_INTEREST`:  General interest. The audiences have a general, long-term interest in a video category.</li><li> `PURCHASE_INTENTION` (deprecated): Purchase intent. The audiences' recent behavior suggests they may buy something related to a content category. </li></ul><br>Default value: `GENERAL_INTEREST`.|
```


### Example
```xcodeblock
(code cURL HTTP)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/interest_keyword/recommend/' \
--header 'Access-Token: xxxx' \
--header 'Content-Type: application/json' \
--data-raw '{
    "advertiser_id": "1234567886354716677",
    "keyword": "test",
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data |object|Returned data.|
#|recommended_keywords|object[]|A list of additional interest categories.|
##| input_keyword | string | Returned only when `keywords` is included in the request and contains more than one keyword. <br><br> The specific seed keyword for which the additional interest category is generated. <br><br>For instance, if the additional interest category "Tester" is generated for the seed keyword "test", the returned `input_keyword` will be `"test"`, and `keyword` will be `"Tester"`. |
##|keyword|string|Additional interest category.|
##|keyword_id|string| ID of the additional interest category.|
##|language|string|Language of the additional interest category.<br><br>Enum values: `fr`, `id`, `it`, `ja`, `ms`, `ar`, `vi`, `en`, `ru`, `es`, `th`, `tr`, `hi`, `zh`, `de`, `ko`. |
##|status|string|Whether the additional interest category will be effective. <br><br> Enum values:<ul><li> `EFFECTIVE`: effective. An additional interest category is effective if using it can reach a reasonable number of people. </li><li>`INEFFECTIVE`: ineffective. An additional interest category is ineffective if the estimated audience size is too small.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Only effective additional interest categories can be used in ad groups to target audiences.|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "202207011538040102452421060C18B592",
    "data": {
        "recommended_keywords": [
            {
                "status": "EFFECTIVE",
                "language": "1",
                "keyword": "Testament",
                "keyword_id": "1915651459"
            },
            {
                "status": "EFFECTIVE",
                "language": "1",
                "keyword": "Tester",
                "keyword_id": "1916254642"
            },
            {
                "status": "EFFECTIVE",
                "language": "1",
                "keyword": "Brain test",
                "keyword_id": "2027741302"
            },
            {
                "status": "EFFECTIVE",
                "language": "1",
                "keyword": "Test",
                "keyword_id": "2027749623"
            },
            {
                "status": "EFFECTIVE",
                "language": "1",
                "keyword": "Test drive",
                "keyword_id": "2027768725"
            },
            ...
        ]
    }
}
(/code);
```
