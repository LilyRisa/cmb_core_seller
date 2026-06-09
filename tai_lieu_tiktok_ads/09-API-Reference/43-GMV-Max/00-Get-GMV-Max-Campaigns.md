---
title: "Get GMV Max Campaigns"
doc_id: 1826463372290177
path: "API Reference / GMV Max / Get GMV Max Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get GMV Max Campaigns

Use this endpoint to retrieve the GMV Max Campaigns within an ad account.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/gmv_max/campaign/get/

**Method** GET

**Header**

```xtable
|Field{35%}|Type{15%}|Description{50%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{35%}|Type{15%}|Description{50%}|
|--- |--- |--- |
|advertiser_id {Required}|string|Advertiser ID.|
|fields|string[]|Fields that you want to get.<br><br>If this field is not specified, all fields are returned by default.<br><br>For allowed fields, see the fields under `list` in the response.|
|filtering {Required}|object|Filters on the data.<br><br>Example: `filtering=&#123;"gmv_max_promotion_types":["PRODUCT_GMV_MAX"]&#125;`.|
#|gmv_max_promotion_types {Required}|string[]|GMV Max Campaign type.<br><br>Enum values:<ul><li>`PRODUCT_GMV_MAX`: [Product GMV Max Campaign](https://business-api.tiktok.com/portal/docs?id=1822009220448257).</li><li>`LIVE_GMV_MAX`: [Live GMV Max Campaign](https://business-api.tiktok.com/portal/docs?id=1822009242546258).</li></ul>|
#|store_ids|string[]|A list of TikTok Shop IDs.<br><br>Max size: 10.<br><br>To obtain a TikTok Shop that is available for GMV Max Campaigns, use [/gmv_max/store/list/](https://business-api.tiktok.com/portal/docs?id=1822001044479041) and confirm that the returned `is_gmv_max_available` is `true`.|
#|campaign_ids|string[]|A list of campaign IDs.<br><br>Max size: 100.|
#|campaign_name|string|Campaign name.|
#|primary_status|string|Primary status.<br><br>Enum values:<ul><li>`STATUS_DELIVERY_OK`: Active.</li><li>`STATUS_DISABLE`: Inactive.</li><li>`STATUS_DELETE`: Deleted.</li></ul>|
#|creation_filter_start_time|string|The earliest campaign creation time, in the format of `YYYY-MM-DD HH:MM:SS` (UTC time). This filter will retrieve campaigns created after this time.<br><br>Example: `2025-01-01 00:00:01`.<br><br>If `creation_filter_start_time` and `creation_filter_end_time` are not specified, the results will include GMV Max Campaigns created at any time.<br><br><p><span style="color:darkred"><b>Note</b></span>: To ensure task efficiency and speed, consider setting a time range within 6 months for the creation time filters.|
#|creation_filter_end_time|string|The latest campaign creation time, in the format of `YYYY-MM-DD HH:MM:SS` (UTC time). This filter will retrieve campaigns created before this time.<br><br>Example: `2025-02-01 00:00:01`.<br><br>If `creation_filter_start_time` and `creation_filter_end_time` are not specified, the results will include GMV Max Campaigns created at any time.<br><br><p><span style="color:darkred"><b>Note</b></span>: To ensure task efficiency and speed, consider setting a time range within 6 months for the creation time filters.|
|page|integer|Current page number.<br><br>Value range: ≥ 1.<br>Default value: 1.|
|page_size|integer|Page size.<br><br>Value range: 1-100.<br>Default value: 10.|
```

### Example
#### Retrieve Product GMV Max Campaigns
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/campaign/get/?advertiser_id={{advertiser_id}}&filtering={"gmv_max_promotion_types": ["PRODUCT_GMV_MAX"]}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

#### Retrieve LIVE GMV Max Campaigns
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/campaign/get/?advertiser_id={{advertiser_id}}&filtering={"gmv_max_promotion_types": ["LIVE_GMV_MAX"]}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{35%}|Type{15%}|Description{50%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|list|object[]|A list of campaigns.<br><br>The returned fields are generated based on the `fields` specified in the request. All fields are returned by default.|
##|advertiser_id|string|Advertiser ID.|
##|campaign_id|string|Campaign ID.|
##|campaign_name|string|Campaign name.|
##|operation_status|string|The ON/OFF status of campaign.<br><br>Enum values:<ul><li>`ENABLE`: in the ON status.</li><li>`DISABLE`: in the OFF status.</li></ul>|
##|create_time|string|The time when the campaign was created, in the format of `YYYY-MM-DD HH:MM:SS` (UTC time).<br><br>Example: `2025-01-01 00:00:01`.|
##|modify_time|string|The time when the campaign was last modified, in the format of `YYYY-MM-DD HH:MM:SS` (UTC time).<br><br>Example: `2025-01-01 00:00:01`.|
##|objective_type|string|Advertising objective.<br><br>Enum value: `PRODUCT_SALES` (Product Sales).|
##|secondary_status|string|Secondary status.<br><br>For enum values, see [Supported campaign secondary statuses for GMV Max Campaigns](https://business-api.tiktok.com/portal/docs?id=1757239620352002#item-link-For%20GMV%20Max%20Campaigns).|
##|roi_protection_compensation_status|string|The ROI protection compensation status of the campaign.<br>ROI protection is a feature that automatically issues ad credits when the return on investment (ROI) for your campaign falls below a certain threshold despite following GMV Max guidelines and best practices. To learn about the eligibility criteria for ROI protection and scenarios where the campaign is ineligible for ROI protection, see [About ROI protection for GMV Max campaigns](https://ads.tiktok.com/help/article/about-roi-protection-for-gmv-max-campaigns).<br><br>Enum values: <ul><li>`IN_EFFECT`: ROI protection eligible. This campaign is eligible for an ad credit if your ads drive more than 20 conversions in 24 hours, but your ROI result is less than 90% of your target. To maintain campaign eligibility for ROI protection, don't edit your ROI, pause ad delivery, or enable max delivery. Campaign eligibility resets every 24 hours.</li><li>`NOT_ELIGIBLE`: ROI protection ineligible. This campaign isn't eligible for an ad credit if the target ROI isn't met because its ROI has been edited, the campaign has been paused, max delivery is enabled, or there are issues with your shop, ad accounts, livestreams, or products. Campaign eligibility resets every 24 hours.</li></ul>|
#|page_info|object|Pagination information.|
##|page|integer|Current page number.|
##|page_size|integer|Paging size.|
##|total_number|integer|Total number of results.|
##|total_page|integer|Total pages of results.|
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
                "campaign_name": "{{campaign_name}}",
                "advertiser_id": "{{advertiser_id}}",
                "objective_type": "PRODUCT_SALES",
                "modify_time": "{{modify_time}}",
                "secondary_status": "CAMPAIGN_STATUS_DISABLE",
                "campaign_id": "{{campaign_id}}",
                "create_time": "{{create_time}}",
                "operation_status": "DISABLE"
            },
            {
                "campaign_name": "{{campaign_name}}",
                "advertiser_id": "{{advertiser_id}}",
                "objective_type": "PRODUCT_SALES",
                "modify_time": "{{modify_time}}",
                "secondary_status": "CAMPAIGN_STATUS_DISABLE",
                "campaign_id": "{{campaign_id}}",
                "create_time": "{{create_time}}",
                "operation_status": "DISABLE"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_page": 1,
            "total_number": 2
        }
    }
}
(/code)
```
