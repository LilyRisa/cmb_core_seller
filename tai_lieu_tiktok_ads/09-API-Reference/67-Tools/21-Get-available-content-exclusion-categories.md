---
title: "Get available content exclusion categories"
doc_id: 1769738074686465
path: "API Reference / Tools / Get available content exclusion categories"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get available content exclusion categories

Use this endpoint to get [content categories](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1739381752981505#item-link-Category%20exclusion) or [vertical sensitivity categories](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1739381752981505#item-link-Vertical%20sensitivity) that can be excluded from appearing next to your ads.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/content_exclusion/get/

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
|objective_type {Required}|string|Advertising objective. Only supports `REACH`, `VIDEO_VIEWS` and `ENGAGEMENT`.|
|brand_safety_type|string|Brand safety type. Default value: `NO_BRAND_SAFETY`. Enum values:<ul><li>`NO_BRAND_SAFETY`: To not use any brand safety solution. Full inventory, which means your ads may appear next to some content featuring mature themes.</li><li>`EXPANDED_INVENTORY`: Use TikTok's brand safety solution. Expanded inventory means that your ads will appear next to content where most inappropriate content has been removed, and that does not contain mature themes. In the next API version, `EXPANDED_INVENTORY` will replace `NO_BRAND_SAFETY` and will be the default brand safety option.<br><p><span style="color:darkred"><b>Note</b></span>:`EXPANDED_INVENTORY` is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>`STANDARD_INVENTORY`: Use TikTok's brand safety solution. Standard inventory means that ads will appear next to content that is appropriate for most brands.</li><li>`LIMITED_INVENTORY`: Use TikTok's brand safety solution. Limited inventory means that your ads will not appear next to content that contains mature themes.<br><p><span style="color:darkred"><b>Note</b></span>: <li>If you want to retrieve vertical sensitivity category IDs(`vertical_sensitivity_list`), you can only use `STANDARD_INVENTORY` or `LIMITED_INVENTORY`.</li><li>If you are not on the allowlist for vertical sensitivity, the `vertical_sensitivity_list` will be null in the response. </li>|
```

### Example

``` xcodeblock
(code curl http)
curl --location 'https://business-api.tiktok.com/open_api/v1.3/tool/content_exclusion/get/?advertiser_id={{advertiser_id}}&objective_type=VIDEO_VIEWS' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
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
#|excluded_category_list|object[]|A list of content exclusion categories.|
##|category_id|string|Category ID.|
##|category_name|string| Category name.|
##|supported_regions|string[]|The country or region codes that the category supports.|
##|description|string|Category description.|
##|category_type|string|Category type.  For `excluded_category_list`,`CATEGORY_TYPE_EXCLUSION`(content exclusion category ) will be returned .  |
#|vertical_sensitivity_list|object[]|A list of vertical categories containing sensitive content. |
##|category_id|string|Category ID.|
##|category_name|string|Category name.|
##|supported_regions|string[]|The country or region codes that the category supports.|
##|description|string|Category description.|
##|category_type|string|Category type. For `vertical_sensitivity_list`,`CATEGORY_TYPE_VERTICAL`(vertical sensitivity category) will be returned.  |
```

### Example

``` xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "excluded_category_list": [
            {
                "description": "YouthContentDescriptionKey",
                "category_type": "CATEGORY_TYPE_EXCLUSION",
                "supported_regions": [
                    "US",
                    "GB",
                    "AU",
                    "CA"
                ],
                "category_id": "200",
                "category_name": "Youth content"
            },
            {
                "description": "CombatSportsDescriptionKey",
                "category_type": "CATEGORY_TYPE_EXCLUSION",
                "supported_regions": [
                    "US",
                    "GB",
                    "AU",
                    "CA"
                ],
                "category_id": "201",
                "category_name": "Combat sports"
            },
            {
                "description": "ViolentVideoGamesDescriptionKey",
                "category_type": "CATEGORY_TYPE_EXCLUSION",
                "supported_regions": [
                    "US",
                    "GB",
                    "AU",
                    "CA"
                ],
                "category_id": "202",
                "category_name": "Violent video games"
            },
            {
                "description": "GamblingDescriptionKey",
                "category_type": "CATEGORY_TYPE_EXCLUSION",
                "supported_regions": [
                    "US",
                    "GB",
                    "AU",
                    "CA"
                ],
                "category_id": "203",
                "category_name": "Gambling and lotteries"
            }
        ],
        "vertical_sensitivity_list": []
    }
}


{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "excluded_category_list": [
            {
                "category_type": "CATEGORY_TYPE_EXCLUSION",
                "category_id": "200",
                "category_name": "Youth content",
                "description": "YouthContentDescriptionKey",
                "supported_regions": [
                    "US",
                    "GB",
                    "AU",
                    "CA"
                ]
            },
            {
                "category_type": "CATEGORY_TYPE_EXCLUSION",
                "category_id": "201",
                "category_name": "Combat sports",
                "description": "CombatSportsDescriptionKey",
                "supported_regions": [
                    "US",
                    "GB",
                    "AU",
                    "CA"
                ]
            },
            {
                "category_type": "CATEGORY_TYPE_EXCLUSION",
                "category_id": "202",
                "category_name": "Violent video games",
                "description": "ViolentVideoGamesDescriptionKey",
                "supported_regions": [
                    "US",
                    "GB",
                    "AU",
                    "CA"
                ]
            },
            {
                "category_type": "CATEGORY_TYPE_EXCLUSION",
                "category_id": "203",
                "category_name": "Gambling and lotteries",
                "description": "GamblingDescriptionKey",
                "supported_regions": [
                    "US",
                    "GB",
                    "AU",
                    "CA"
                ]
            }
        ],
        "vertical_sensitivity_list": [
            {
                "category_type": "CATEGORY_TYPE_VERTICAL",
                "category_id": "1000",
                "category_name": "Beauty",
                "description": "This will exclude any content that TikTok deems as controversial or negative beauty-related patterns which may impact your campaign performance.",
                "supported_regions": [
                    "US",
                    "GB",
                    "AU",
                    "CA"
                ]
            },
            {
                "category_type": "CATEGORY_TYPE_VERTICAL",
                "category_id": "1001",
                "category_name": "Food",
                "description": "This will exclude any content that TikTok deems as unhealthy food behaviors, body shaming, or graphic food content which may impact your campaign performance.",
                "supported_regions": [
                    "US",
                    "GB",
                    "AU",
                    "CA"
                ]
            },
            {
                "category_type": "CATEGORY_TYPE_VERTICAL",
                "category_id": "1002",
                "category_name": "Pets",
                "description": "This will exclude any content that TikTok deems as unfair treatment to animals or pets, such as sensitive issues like animal testing, animal products used in fashion or pet illness, or demise-related content which may impact your campaign performance.",
                "supported_regions": [
                    "US",
                    "GB",
                    "AU",
                    "CA"
                ]
            }
        ]
    }
}
(/code)
```
