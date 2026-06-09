---
title: "Get webpage anchors"
doc_id: 1826564965840066
path: "API Reference / TikTok One / Get webpage anchors"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get webpage anchors

Use this endpoint to retrieve existing webpage anchors within your TikTok One (TTO) Creator Marketplace account.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/anchor/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok One Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
|anchor_ids|string[]|A list of anchor IDs.<br><br>Max size: 100.<br><br>If `anchor_ids` is not passed, all anchors within the TTO Creator Marketplace account will be returned.|
|page|integer|Current page number.<br><br>Value range: ≥ 1.<br>Default value: 1.|
|page_size|integer|Page size.<br><br>Value range: 1-50.<br>Default value: 10.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/anchor/get/?tto_tcm_account_id={{tto_tcm_account_id}}&page=1&page_size=50' \
--header 'Access-Token: {{access_token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|anchors|object[]|A list of anchors.|
##|anchor_id|string|The ID of the anchor.|
##|status|string|The status of the anchor.<br><br>Enum value: <ul><li>`DRAFT`: The anchor is in draft mode.</li><li>`CREATED`: The anchor has been created but has not been under review.</li><li>`IN_REVIEW`: The anchor is under review.</li><li>`APPROVED`: The anchor has passed review.</li><li>`REJECTED_BY_AUDIT`: The anchor was rejected during the review.</li></ul>|
##|anchor_type|string|The type of anchor.<br><br>Enum value: <ul><li>`WEB_ANCHOR`: Webpage anchor. A type of anchor through which you can display your website link above the creator's video description.</li></ul>|
##|anchor_sub_type|string|Returned when `anchor_type` is `WEB_ANCHOR`. The specific type of webpage anchor.<br><br>Enum value: <ul><li>`DESTINATION`: Web destination link. This type directs viewers to a web destination link that can be opened in TikTok. The anchor shows up both in the creator's video and the comment section.</li><li>`ECOMMERCE`: E-commerce template. This type directs viewers to an internal landing page where brands can add a coupon link. The anchor shows up only in the creator's video comment section.</li><li>`ENTERTAINMENT`: Entertainment template. This type directs viewers to an internal landing page where brands can add a video clip. The anchor shows up only in the creator's video comment section.</li></ul>|
##|country_code|string|The code of the country or region. The app information from this country or region's app store will be used. This anchor won’t be available in other countries or regions.<br><br>To learn about the country or region that the code represents, see [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010).|
##|call_to_action|object|Anchor link information.|
###|landing_page_url|string|The URL of the page that the anchor will redirect viewers to. This page shows more information about your product or service.|
###|ios_download_link|string|The iOS download link where people can download your app. <br><br>When `anchor_type` is `WEB_ANCHOR`, this field will be null.|
###|ios_deep_link|string|The iOS download deeplink where people can download your app. <br><br>When `anchor_type` is `WEB_ANCHOR`, this field will be null.|
###|android_download_link|string|The Android download link where people can download your app.<br><br> When `anchor_type` is `WEB_ANCHOR`, this field will be null.|
###|android_deep_link|string|The Android download deeplink where people can download your app. <br><br>When `anchor_type` is `WEB_ANCHOR`, this field will be null.|
##|anchor_title|string|The title of the anchor.|
##|anchor_name|string|The name of the anchor.|
##|anchor_sub_title|string|The title of the anchor, which is used to tell TikTok users what you're promoting about your product or service.<br><br> When `anchor_sub_type` is `DESTINATION`, this field will be null.|
##|thumbnail_url|string|The thumbnail URL for the anchor.|
##|coupon_link|object|This field returns a non-null value only when `anchor_sub_type` is `ECOMMERCE`. <br><br>Information about the coupon link that redirects users to a page with more information about your campaign discount.|
###|discount|number|Discount.<br><br>For instance, a `discount` of `5` means 5% off.|
###|coupon_url|string|Coupon URL.|
###|coupon_code|string|Coupon code.|
##|pixel_id|string|The ID of the pixel that measures and analyzes campaign performance.|
##|creator_preview_url|string|The preview URL of the anchor and landing page that you can use for mobile phone preview purposes.<br><br> The URL won't expire.|
##|advertiser_preview_url|string|The mobile preview URL of the anchor and landing page that you can use for desktop preview purposes. <br><br>The URL won't expire. <br><br>When `anchor_sub_type` is `DESTINATION`, this field will be null.|
#|page_info|object|Pagination Information.|
##|page|number|Current page number.|
##|page_size|number|Current page size.|
##|total_number|number|Total number of results.|
##|total_page|number|Total number of pages.|
```

### Example
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "anchors": [
            {
                "advertiser_preview_url": "{{advertiser_preview_url}}",
                "anchor_id": "{{anchor_id}}",
                "anchor_name": "{{anchor_name}}",
                "anchor_sub_title": "{{anchor_sub_title}}",
                "anchor_sub_type": "ENTERTAINMENT",
                "anchor_title": "{{anchor_title}}",
                "anchor_type": "WEB_ANCHOR",
                "call_to_action": {
                    "android_deep_link": null,
                    "android_download_link": null,
                    "ios_deep_link": null,
                    "ios_download_link": null,
                    "landing_page_url": "{{landing_page_url}}"
                },
                "country_code": "TW",
                "coupon_link": null,
                "creator_preview_url": "{{creator_preview_url}}",
                "pixel_id": null,
                "status": "IN_REVIEW",
                "thumbnail_url": "{{thumbnail_url}}"
            },
            {
                "advertiser_preview_url": "{{advertiser_preview_url}}",
                "anchor_id": "{{anchor_id}}",
                "anchor_name": "{{anchor_name}}",
                "anchor_sub_title": "{{anchor_sub_title}}",
                "anchor_sub_type": "ECOMMERCE",
                "anchor_title": "{{anchor_title}}",
                "anchor_type": "WEB_ANCHOR",
                "call_to_action": {
                    "android_deep_link": null,
                    "android_download_link": null,
                    "ios_deep_link": null,
                    "ios_download_link": null,
                    "landing_page_url": "{{landing_page_url}}"
                },
                "country_code": "US",
                "coupon_link": {
                    "coupon_code": "",
                    "coupon_url": "",
                    "discount": 5
                },
                "creator_preview_url": "{{creator_preview_url}}",
                "status": "IN_REVIEW",
                "thumbnail_url": "{{thumbnail_url}}"
            },
            {
                "advertiser_preview_url": null,
                "anchor_id": "{{anchor_id}}",
                "anchor_name": "{{anchor_name}}",
                "anchor_sub_title": null,
                "anchor_sub_type": "DESTINATION",
                "anchor_title": "Watch Now",
                "anchor_type": "WEB_ANCHOR",
                "call_to_action": {
                    "android_deep_link": null,
                    "android_download_link": null,
                    "ios_deep_link": null,
                    "ios_download_link": null,
                    "landing_page_url": "{{landing_page_url}}"
                },
                "country_code": "CA",
                "coupon_link": null,
                "creator_preview_url": "{{creator_preview_url}}",
                "pixel_id": "7258197703320944642",
                "status": "IN_REVIEW",
                "thumbnail_url": "{{thumbnail_url}}"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 50,
            "total_number": 3,
            "total_page": 1
        }
    }
}
(/code)
```
