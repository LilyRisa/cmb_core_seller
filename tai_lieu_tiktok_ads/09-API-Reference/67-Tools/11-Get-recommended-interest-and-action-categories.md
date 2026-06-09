---
title: "Get recommended interest and action categories"
doc_id: 1736275204260866
path: "API Reference / Tools / Get recommended interest and action categories"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get recommended interest and action categories

Use this endpoint to get recommended interest and action categories based on historical performance data in the same industries.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{22%}|v1.2{40%}|v1.3{38%}|
|---|---|---|
|Endpoint path|/v1.2/tools/target_recommend_tags/|/v1.3/tool/targeting_category/recommend/|
|Request parameter name|`region_code`|`region_codes`|
|Request parameter data type |`app_id`: number|`app_id`: string|
|Response parameter name| <ul><li>`name` (in the `interest_categories` object)</li><li>`name`(in the `action_categories` object ) </li><li>`placement` </li><li>`industry_types`   |<ul><li>` interest_category_name`</li><li>`action_category_name`</li><li>`placements`</li><li>`special_industries`  |
|Response parameter name and data type|<ul><li>`id` (in the `interest_categories` object): number</li><li>`id`(in the `action_categories` object): number |<ul><li> `interest_category_id`: string </li><li>`action_category_id`: string |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/targeting_category/recommend/

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {required}|string|The content type of the request. Allowed value: `"application/json"`|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser ID. |
|region_codes {required}|string[]|List of country or region codes. For example: `["US", "JP", "KR"]`|
|app_id|string|ID of the mobile app that you want to promote. This field in optional and only valid if you want to promote a mobile app.|
```


### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "region_code": [
        "REGION_CODE"
    ],
    "app_id": "APP_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/tool/targeting_category/recommend/
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
#|interest_categories|object[]|Recommended interest categories.|
##|interest_category_id|string|Interest category ID. It can used as one of the targeting settings when creating an ad group.|
##|interest_category_name|string| Interest category name.|
##|placements|string[]|List of placements that the interest categories can be used. If blank is returned, it means the interest category can be used in all placements.|
##|special_industries|string[]|Ad categories. Enum values: <ul><li>`HOUSING`: Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.</li><li>`EMPLOYMENT`: Ads for job offers, internship, professional certification programs or other related opportunities.</li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities. </li></ul><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If the returned value is empty(`[]`), it means that the interest categories do not support any industry types.</li><li>This field is only supported for advertisers who are registered in America or Canada.</li></ul> |
#|action_categories|object[]|Recommended action categories.|
##|action_category_id|string|Action category ID. It can be used as one of the targeting settings when creating an ad group.|
##|action_category_name|string| Action category name.|
##|description|string|Action category description.|
##|scene|string|Action category scene. Enum values: `CREATOR_RELATED`, `VIDEO_RELATED`.|
##|special_industries|string[]|Ad categories. Enum values:<ul><li>`HOUSING`: Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.</li><li>`EMPLOYMENT`: Ads for job offers, internship, professional certification programs or other related opportunities.</li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities.</li></ul><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If the returned value is empty(`[]`), it means that the interest categories do not support any industry types.</li><li>This field is only supported for advertisers who are registered in America or Canada.</li></ul>|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "20220614231448010245246039155606FD",
    "data": {
        "interest_categories": [
            {
                "interest_category_id": "11",
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "placements": [],
                "interest_category_name": "Vehicles & Transportation"
            },
            {
                "interest_category_id": "20",
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "placements": [],
                "interest_category_name": "Apps"
            },
            {
                "interest_category_id": "23117104",
                "special_industries": [],
                "placement": [
                    "PLACEMENT_TIKTOK",
                    "PLACEMENT_PANGLE"
                ],
                "interest_category_name": "Talk Shows"
            },
            {
                "interest_category_id": "20105",
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "placement": [],
                "interest_category_name": "Online Shopping"
            },
            {
                "interest_category_id": "10",
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "placement": [],
                "interest_category_name": "Education"
            },
            {
                "interest_category_id": "26105",
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "placement": [],
                "interest_category_name": "Housekeeping"
            }
        ],
        "action_categories": [
            {
                "action_category_name": "Fitness & Health",
                "action_category_id": "21",
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "description": "TikTok users who have followed healthy lifestyle or fitness creators.",
                "scene": "CREATOR_RELATED"
            },
            {
                "action_category_name": "Other Transportation and Vehicle",
                "action_category_id": "22002",
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "description": "TikTok users who have followed creators that post content about other types of transportation vehicles.",
                "scene": "CREATOR_RELATED"
            },
            {
                "action_category_name": "Selfie",
                "action_category_id": "27001",
                "special_industries": [
                    "HOUSING",
                    "EMPLOYMENT",
                    "CREDIT"
                ],
                "description": "TikTok users who have followed creators that post selfies.",
                "scene": "CREATOR_RELATED"
            }
        ]
    }
}
(/code);
```

