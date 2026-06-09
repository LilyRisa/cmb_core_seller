---
title: "Set or update the Brand Safety Hub settings of an ad account"
doc_id: 1830112569774274
path: "API Reference / Brand Safety / Set or update the Brand Safety Hub settings of an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Set or update the Brand Safety Hub settings of an ad account

Use this endpoint to set or update the Brand Safety Hub settings of an ad account.

Brand Safety Hub allows you to define the default ad account-level first-party brand safety settings, including TikTok Inventory Filter and Suitability Controls such as Category Exclusion and Vertical Sensitivity.

> <span style="color:DodgerBlue">**Note**</span><br>
>Currently, the settings configured through this endpoint will automatically apply to your future [Smart+ Campaigns](https://business-api.tiktok.com/portal/docs?id=1767959274087425) but will not apply to future regular campaigns created using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602).


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tiktok_inventory_filters/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| cover_all_ad_objectives {Required} | boolean | Whether to apply the brand safety settings (`brand_safety_type`, `category_exclusion_ids`, and `vertical_sensitivity_id`) to all advertising objectives.<br><br>Supported values: <ul><li>`true`: To apply the brand safety settings to all objectives, including Reach, Video views, Community Interaction, Traffic, App Promotion, Lead Generation, Website Conversions, and Product Sales.</li><li>`false`: To apply the brand safety settings only to Reach, Video Views, and Community Interaction objectives.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Once set to `true`, this setting cannot be updated back to `false`. |
| brand_safety_type | string | Inventory filter tier.<br>The tier you select will apply to placements in the For You, Following, and Profile Page feeds, as well as Search feed, where ads appear next to search results being viewed.<br><br>Enum values: <ul><li>`EXPANDED_INVENTORY`: Expanded inventory. Your ads will not appear next to explicitly inappropriate content, but they may appear next to content that features mature themes.</li><li>`STANDARD_INVENTORY`: Standard inventory. Your ads will appear next to content that is appropriate for most brands.</li><li>`LIMITED_INVENTORY`: Limited inventory. Your ads will not appear next to content that contains mature themes.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you want to update the brand safety settings, you need to specify `brand_safety_type`, `category_exclusion_ids`, and `vertical_sensitivity_id` simultaneously. |
| category_exclusion_ids | string[] | Valid only when `brand_safety_type` is `STANDARD_INVENTORY` or `LIMITED_INVENTORY`.<br><br>The ID list of content categories that you want to avoid showing your ads next to.<br><br>To obtain a list of content exclusion category IDs, use [/tool/content_exclusion/get/](https://business-api.tiktok.com/portal/docs?id=1769738074686465) and check the returned `excluded_category_list`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Currently, category exclusion is not available for the Product Sales objective.|
| vertical_sensitivity_id | string | Valid only when `brand_safety_type` is `STANDARD_INVENTORY` or `LIMITED_INVENTORY`.<br><br>The ID of the vertical category to exclude sensitive vertical-related content that you want to avoid showing your ads next to.<br><br>To obtain a list of vertical sensitivity categories, use [/tool/content_exclusion/get/](https://business-api.tiktok.com/portal/docs?id=1769738074686465) and check the returned `vertical_sensitivity_list`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Currently, vertical sensitivity controls are not available for the following objectives: Traffic, App Promotion, Lead Generation, Website Conversions, and Product Sales. |
```

### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/tiktok_inventory_filters/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "cover_all_ad_objectives": true,
    "brand_safety_type": "STANDARD_INVENTORY",
    "category_exclusion_ids": ["201"],
    "vertical_sensitivity_id": "1001"
}'
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
#| cover_all_ad_objectives | boolean | Whether to apply the brand safety settings (`brand_safety_type`, `category_exclusion_ids`, and `vertical_sensitivity_id`) to all advertising objectives.<br><br>Supported values: <ul><li>`true`: To apply the brand safety settings to all objectives, including Reach, Video views, Community Interaction, Traffic, App Promotion, Lead Generation, Website Conversions, and Product Sales.</li><li>`false`: To apply the brand safety settings only to Reach, Video Views, and Community Interaction objectives.</li></ul> |
#| brand_safety_type | string | Inventory filter tier.<br>The tier you select will apply to placements in the For You, Following, and Profile Page feeds, as well as Search feed, where ads appear next to search results being viewed.<br><br>Enum values: <ul><li>`EXPANDED_INVENTORY`: Expanded inventory. Your ads will not appear next to explicitly inappropriate content, but they may appear next to content that features mature themes.</li><li>`STANDARD_INVENTORY`: Standard inventory. Your ads will appear next to content that is appropriate for most brands.</li><li>`LIMITED_INVENTORY`: Limited inventory. Your ads will not appear next to content that contains mature themes.</li></ul> |
#| category_exclusion_ids | string[] | The ID list of content categories that you want to avoid showing your ads next to.<br><br>To obtain the details of the content categories based on category IDs, use [/tool/content_exclusion/info/](https://business-api.tiktok.com/portal/docs?id=1769738105333826).<br><br><p><span style="color:darkred"><b>Note</b></span>: Currently, category exclusion is not available for the Product Sales objective. |
#| vertical_sensitivity_id | string | The ID of the vertical category to exclude sensitive vertical-related content that you want to avoid showing your ads next to.<br><br>To obtain the details of the vertical categories based on category IDs, use [/tool/content_exclusion/info/](https://business-api.tiktok.com/portal/docs?id=1769738105333826).<br><br><p><span style="color:darkred"><b>Note</b></span>: Currently, vertical sensitivity controls are not available for the following objectives: Traffic, App Promotion, Lead Generation, Website Conversions, and Product Sales. |
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
        "brand_safety_type": "STANDARD_INVENTORY",
        "category_exclusion_ids": [
            "201"
        ],
        "cover_all_ad_objectives": true,
        "vertical_sensitivity_id": "1001"
    }
}
(/code)
```
