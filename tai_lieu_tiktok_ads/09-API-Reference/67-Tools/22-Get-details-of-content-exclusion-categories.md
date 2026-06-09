---
title: "Get details of content exclusion categories"
doc_id: 1769738105333826
path: "API Reference / Tools / Get details of content exclusion categories"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get details of content exclusion categories

Use this endpoint to get the detailed information of [content categories](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1739381752981505#item-link-Category%20exclusion) or [vertical categories](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1739381752981505#item-link-Vertical%20sensitivity) that can be excluded from appearing next to your ads.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tool/content_exclusion/info/

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
|category_ids {Required}|string[]|IDs of the content exclusion categories or vertical sensitivity categories. You can get `category_id` from the response of [/tool/content_exclusion/get/](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1769738074686465). |
```
### Example
```xcodeblock
(code curl http)
curl --location -g 'https://business-api.tiktok.com/open_api/v1.3/tool/content_exclusion/info/?advertiser_id={{advertiser_id}}&category_ids=[{{category_id}},{{category_id}}]' \
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
#|content_exclusion_list|object[]|A list of content categories or vertical categories.|
##|category_id|string|Category ID.|
##|category_name|string| Category name.|
##|supported_regions|string[]|The country or region codes that the category supports.|
##|description|string|Category description.|
##|category_type|string|Category type. Enum values: `CATEGORY_TYPE_EXCLUSION`(content exclusion category ) and `CATEGORY_TYPE_VERTICAL`(vertical sensitivity category).|
```

### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "content_exclusion_list": [
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
            },
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
            }，
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
