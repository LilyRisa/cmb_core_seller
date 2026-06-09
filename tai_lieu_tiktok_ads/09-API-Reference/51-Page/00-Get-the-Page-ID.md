---
title: "Get the Page ID"
doc_id: 1820826387779586
path: "API Reference / Page / Get the Page ID"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the Page ID

Use this endpoint to get the Page ID. After a page is created, you can get the page ID and then use the page ID in your ads.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/pages/get/|/v1.3/page/get/|
|Request parameter data type|`advertiser_id`: number<br>`library_id`: number<br>`start`: number<br>`end`: number|`advertiser_id`: string<br>`library_id`: string<br>`start`: string<br>`end`: string|
|Response parameter data type|`duplicate_id`: number<br>`page_id`: number<br>`template_id`: number<br>`create_time`: number<br>`update_time`: number<br>`publish_time`: number<br>`user_id`: number|`duplicate_id`: string<br>`page_id`: string<br>`template_id`: string<br>`create_time`: string<br>`update_time`: string<br>`publish_time`: string<br>`user_id`: string|
|New response parameter|/|`destination_urls`<br> `app_id`<br>`has_cpp` <br>`messaging_app_type`<br> `messaging_app_account_id`<br> `template_name`<br>`supported_objectives`|
```

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/page/get/

**Method** GET

**Header**
```xtable
|Field{30%}|Data Type{10%}|Description{60%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**
```xtable
|Field{30%}|Data Type{10%}|Description{60%}|
|---|---|---|
|advertiser_id {+Conditional}|string|Either `advertiser_id` or `library_id` has to be set. <br><br> To get pages under an ad account, you must specify the advertiser ID.|
|library_id {+Conditional}|string|Either `advertiser_id` or `library_id` has to be set. <br><br>To get pages under a Business Center, you must specify the ID of the form library that contains the page and leads. You can migrate pages and leads from an ad account to a Business Center.|
|page |number|Current number of pages.<br><br> Default value: 1. Value range: ≥ 1.|
|page_size |number|Page size.<br><br> Default value: 10. Value range: 1-100.|
|status |string| Page status.<br><br>Enum values: `EDITED` (draft), `PUBLISHED` (ready).  |
|title |string|The page name for filtering results.<br><br>This filter supports exact matches.|
|update_time_range|object| Filter for pages updated in the specified time range. |
#|start {+Conditional}|string| Required when `update_time_range` is passed.<br><br> Start time in the format of `YYYY-MM-DD HH:MM:SS` (UTC time). <br><br>Example: `2024-01-01 00:00:00`. |
#|end {+Conditional}|string| Required when `update_time_range` is passed.<br><br>End time in the format of `YYYY-MM-DD HH:MM:SS` (UTC time). <br><br>Example: `2024-01-01 00:00:00`. |
|business_type |string| Page type. <br><br>Enum values: `LEAD_GEN`(Instant Form), `STORE_FRONT`(Storefront Page), `APP_PROFILE_PAGE` (App Profile Page),`TIKTOK_INSTANT_PAGE` (Custom Page or Custom TikTok Instant Page), `SHOP_ADS_PLP` (Shop Ads Product List Page), `SHOP_ADS_PDP` (Shop Ads Product Detail Page), `POP_UP_FORM`(Direct Message Page). <br>Default value: `LEAD_GEN`.|
|business_types |string[]| Page types. <br><br>Enum values: `LEAD_GEN`(Instant Form), `STORE_FRONT`(Storefront Page), `APP_PROFILE_PAGE` (App Profile Page),`TIKTOK_INSTANT_PAGE` (Custom Page or Custom TikTok Instant Page), `SHOP_ADS_PLP` (Shop Ads Product List Page), `SHOP_ADS_PDP` (Shop Ads Product Detail Page), `POP_UP_FORM`(Direct Message Page). <br><p><span style="color:darkred"><b>Note</b></span>: If it is passed concurrently with `business_type`, the value of `business_type` will be ignored.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/page/get/?advertiser_id={{advertiser_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return Message, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Return data.|
#|list |object[]| Page list. |
##|page_id|string| Page ID. |
##|status|string| Page status.<br><br>Enum values: `EDITED` (draft), `PUBLISHED` (ready).  |
##|title|string| Title. |
##|preview_url|string| Preview link of the page. The link is generated after the page is ready, and will be empty when the page is only a draft. |
##|template_id|string| The ID for the page's template. It indicates the specific template type associated with this page.|
##| template_name|string|The name of the template type that the page uses.|
##| supported_objectives|string[]|Returned only when the following conditions are both met:<ul><li>The page is associated with a `template_id`.</li><li>The corresponding template is compatible with certain objectives.</li></ul>A list of campaign objectives compatible with this page template.<br><br>Enum values: `APP_PROMOTION`, `WEB_CONVERSIONS`, `LEAD_GENERATION`, `TRAFFIC`, `REACH`, `VIDEO_VIEWS`, `ENGAGEMENT`, `PRODUCT_SALES`, `RF_REACH`.<br><br>For a detailed explanation of enum values, see [Enumeration-Advertising Objective](https://business-api.tiktok.com/portal/docs/enumerations-appendix/v1.3#item-link-Advertising%20Objective).<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Even with a supported objective, you can only use the page if it is compatible with all other campaign settings.</li></ul>|
##|thumbnail|string| Page preview thumbnail image. |
##|create_time|string| The creation time of the page in the format of `YYYY-MM-DD HH:MM:SS` (UTC time). <br><br>Example: `2024-01-01 00:00:00`. |
##|update_time|string| The time the page was last updated in the format of `YYYY-MM-DD HH:MM:SS` (UTC time). <br><br>Example: `2024-01-01 00:00:00`. |
##|publish_time|string| The publishing time of the page in the format of `YYYY-MM-DD HH:MM:SS` (UTC time). <br><br>Example: `2024-01-01 00:00:00`. |
##| app_id | string | Returned for all App Profile Pages and TikTok Instant Pages where an App is specified. <br><br>The ID of the App that is specified in the page.<br><br>To filter App Profile Pages, set `business_type` to `APP_PROFILE_PAGE` or set `business_types` to `["APP_PROFILE_PAGE"]` in the request. |
##| has_cpp | boolean | Returned only for TikTok Instant Pages.<br><br>Whether the TikTok Instant Page contains [Custom Product Page](https://business-api.tiktok.com/portal/docs?id=1798658439859202).<br><br>Enum values: `true`, `false`. <br><br> To filter TikTok Instant Pages, set `business_type` to `TIKTOK_INSTANT_PAGE` or set `business_types` to `["TIKTOK_INSTANT_PAGE"]` in the request. |
##|destination_urls|string[]| Returned when your `business_type` is`LEAD_GEN`(Instant Form), `APP_PROFILE_PAGE` (App Profile Page), or `TIKTOK_INSTANT_PAGE` (Custom Page or Custom TikTok Instant Page).<br><br>Destination URLs. |
##| messaging_app_type | string | The type of instant messaging app associated with the TikTok Instant Page. <br><br>Enum values: <ul><li>`MESSENGER`: Messenger. </li><li>`WHATSAPP`: WhatsApp. </li><li>`IM_URL`: No instant messaging app is associated with the TikTok Instant Page.</li></ul> |
##| messaging_app_account_id | string | The ID of the instant messaging app account associated with the TikTok Instant Page. <br><br><ul><li>When `messaging_app_type` is `MESSENGER`, this field represents the Facebook Page ID.</li><li>When `messaging_app_type` is `WHATSAPP`, this field represents the WhatsApp phone number.</li><li>When `messaging_app_type` is `IM_URL`, this field will be an empty string (`""`).</li></ul>|
##|transfer_status|string| Whether the page has been transferred to Business Center. <br><br>Enum values: `UNSET`, `TRANSFERRED` |
##|user_id|string| ID of the user who created the page. |
##|is_associated|boolean| Whether Storefront Page has been associated with some ad (Storefront Page cannot be reused, and each page can only be associated with one ad).|
##|duplicate_id|string| Duplicate source page ID. |
#|page_info |object | Pagination information.|
##|page |number | Current page. |
##|page_size |number | Page size. |
##|total_number |number | Total number of results.|
##|total_page |number | Total number of pages. |
|request_id |string| Log id of the request, uniquely identifying a request. |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "create_time": "{{create_time}}",
                "destination_urls": [],
                "duplicate_id": "{{duplicate_id}}",
                "page_id": "{{page_id}}",
                "preview_url": "{{preview_url}}",
                "publish_time": "{{publish_time}}",
                "status": "PUBLISHED",
                "supported_objectives": [
                    "LEAD_GENERATION"
                ],
                "template_id": "{{template_id}}",
                "template_name": "{{template_name}}",
                "thumbnail": "{{thumbnail}}",
                "title": "{{title}}",
                "transfer_status": "UNSET",
                "update_time": "{{update_time}}",
                "user_id": "{{user_id}}"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_number": 1,
            "total_page": 1
        }
    }
}
(/code);
```

