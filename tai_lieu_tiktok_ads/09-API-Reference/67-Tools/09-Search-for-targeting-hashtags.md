---
title: "Search for targeting hashtags"
doc_id: 1736271339521025
path: "API Reference / Tools / Search for targeting hashtags"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Search for targeting hashtags

Use this endpoint to search for targeting hashtags based on seed keywords. 

The returned hashtag (`keyword_id`) can be passed on to the `action_category_ids` field in [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) to target users who watched TikTok videos with the hashtag. You need to set `action_scene` to `HASHTAG_RELATED` simultaneously.

> <span style="color:DodgerBlue">**Note**</span><br>
> We recommend that you use the Consolidated Interest and Behavior API ([/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1796638342793218)) to search for or list targeting categories and hashtags for interests and behaviors. This API provides a consolidated and efficient approach to access the necessary targeting information.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tools/hashtag/recommend/|/v1.3/tool/hashtag/recommend/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|New request parameters| / | `operator` |
|Response parameter data type|`keyword_id`: number|`keyword_id`: string|
|New response parameters| / | `input_keyword` |
```


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/hashtag/recommend/

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
|advertiser_id {Required}|string| Advertiser ID. |
|keywords{Required}|string[]|Keywords that you want to get recommended hashtags for. <br>If you specify multiple unrelated keywords and set `operator` to `AND`, it is possible that no recommended hashtags are returned.<br><br>Max size: 10.|
| operator | string | The operator to be used between the keywords. <br><br> Enum values: <ul><li>`AND`: Recommended hashtags will be generated based on all the keywords specified in `keywords`. </li><li>`OR`: Recommended hashtags will be generated separately for each individual keyword in `keywords`.</li></ul><br>Default value: `AND`. |
```


### Example

```xcodeblock
(code curl http)
curl --location -g --request GET 'https://business-api.tiktok.com/open_api/v1.3/tool/hashtag/recommend/?keywords=[%22game%22]&advertiser_id=1234' \
--header 'Access-Token: xxxx' \
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
#|recommend_keywords|object[]|Recommend hashtags.|
##| input_keyword | string | Returned only when `operator` is set to `OR` and more than one keyword is specified in `keywords` in the request. <br><br>The specific keyword for which the recommended hashtag is generated. <br><br> For instance, if the hashtag "#gametime" is recommended for the keyword "game", the returned `input_keyword` will be `"game"`, and `keyword` will be `"#gametime"`. |
##|keyword|string|Recommended hashtag.|
##|keyword_id|string| Hashtag keyword ID. <br><br>It can be passed to the `action_category_ids` field when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). You need to set `action_scene` to `HASHTAG_RELATED` simultaneously to target users who watched TikTok videos with the hashtag.|
##|keyword_status|string|Keyword status.<br><br> Enum values: `ONLINE` (available for use), `OFFLINE` (not available for use).|
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
        "recommend_keywords": [
              {
                "keyword": "#gameroom",
                "keyword_id": "3568527772311",
                "keyword_status": "ONLINE"
            },
            {
                "keyword": "#gametime",
                "keyword_id": "3391154394694",
                "keyword_status": "ONLINE"
            }
        ]
    }
}
(/code);
```


