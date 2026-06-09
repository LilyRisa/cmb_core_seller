---
title: "Get action categories"
doc_id: 1737166752522241
path: "API Reference / Tools / Get action categories"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get action categories

Use this endpoint to get action category enumeration values. Action categories are also referred to as behavioral categories in our documentation.

Action targeting provides advertisers with a way to serve relevant ads to users based on their recent in-app behaviors. In-app behaviors can be categorized into two types: video related actions (views, likes, comments or share), and creator related actions (following or viewed homepage). Accordingly, the `action_scene` where a developer can use action categories when calling [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) is `CREATOR_RELATED`, or `VIDEO_RELATED`.

> <span style="color:DodgerBlue">**Note**</span><br>
> We recommend that you use the Consolidated Interest and Behavior API ([/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1796638342793218)) to search for or list targeting categories and hashtags for interests and behaviors. This API provides a consolidated and efficient approach to access the necessary targeting information.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{22%}|v1.2{40%}|v1.3{38%}|
|---|---|---|
|Endpoint path|/v1.2/tools/action_category/|/v1.3/tool/action_category/|
|Request parameter name|`industry_types`|`special_industries`|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter name|`industry_types`|`special_industries`|
|Response parameter name and data type|<ul><li>`id`(in the `action_categories` object): number</li><li>`children`(in the  `action_categories` object): number[]|<ul><li> `action_category_id`: string</li><li>`sub_category_ids`: string[]|
|New request parameter|/|`language`|
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/tool/action_category/

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
|advertiser_id {Required}|string| Advertiser ID. |
|special_industries|string[]| Special ad categories.<br><br> Enum values:<ul><li>`HOUSING`: Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.</li><li>`EMPLOYMENT`: Ads for job offers, internship, professional certification programs or other related opportunities.</li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: This field is generally available to advertisers registered in America or Canada. Advertisers who are not registered in America or Canada but want to target these countries with special ad categories need to apply for an additional allowlist.|
|language|string| Category name language in repsonse. Default value: `en`. <br> Supported languages: `en`, ` zh`, ` ja`, ` de`, ` es`, ` fr`, ` id`, ` it`, ` ko`, ` ru`, ` th`, ` tr`, ` vi`, ` ar`, ` pt`, ` ms`.<br>For details, see [Enumeration - Language Code](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
https://business-api.tiktok.com/open_api/v1.3/tool/action_category/
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data. |
#|action_categories |object[]| Action Categories list. |
##|description |string| Action description. |
##|action_category_id |string| Action category ID. |
##|level |number| Level. |
##|sub_category_ids |string[]| Subcategory ID list. |
##|name |string| Action category name. |
##|special_industries|string[]|Special ad categories.<br><br> Enum values: <ul><li>`HOUSING`(Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.)</li><li>`EMPLOYMENT`(Ads for job offers, internship, professional certification programs or other related opportunities.)</li><li>`CREDIT`(Ads for credit card offers, auto loans, long-term financing or other related opportunities.) </li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li> If the returned value is empty(`[]`), it means that the action categories do not support any special ad categories.</li></ul>|
##|action_scene |string| Action scene. Enum values: `CREATOR_RELATED`, `VIDEO_RELATED`. |
|request_id |string|The log ID of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "20220630215533010245242106175C5C29",
    "data": {
        "action_categories": [
            {
                "description": "TikTok users who have followed comedy creators.",
                "sub_category_ids": [
                    "1001"
                ],
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "action_scene": "CREATOR_RELATED",
                "level": 1,
                "name": "Comedy",
                "action_category_id": "1"
            },
            {
                "description": "TikTok users who have followed creators that post original performance or original narration content.",
                "sub_category_ids": [
                    "2001"
                ],
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "action_scene": "CREATOR_RELATED",
                "level": 1,
                "name": "Drama",
                "action_category_id": "2"
            },
            ...
      ]
  }
}
(/code);
```
