---
title: "Get TTO Creator Marketplace campaigns"
doc_id: 1815693562720258
path: "API Reference / TikTok One / Get TTO Creator Marketplace campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get TTO Creator Marketplace campaigns

Use this endpoint to retrieve the information of TikTok One (TTO) Creator Marketplace campaigns. 

## Comparing TCM and TTO Creator Marketplace endpoints
The following table outlines the differences between TCM and TTO Creator Marketplace endpoints.
```xtable
| Changes {24%}| TCM Endpoint{32%} | TTO Creator Marketplace Endpoint{44%} |
|---|---|---|
| Endpoint path | /v1.3/tcm/order/get/v2/| /v1.3/tto/tcm/campaign/ |
| Same request parameters| `page`<br>`page_size`| `page`<br>`page_size` |
| Deprecated request parameters | N/A |`tcm_account_id`<br>`query_type`<br>`order_ids` <br>`campaign_code`<br>`invite_link`<br>`fields`|
| New request parameters  |N/A | `tto_tcm_account_id`<br>`campaign_ids`<br>`campaign_type` |
|Same response parameters| `orders.video_ids`<br>`page_info`<br>`page_info.page`<br>`page_info.page_size`<br>`page_info.total_number`<br>`page_info.total_page` | `campaigns.video_ids`<br>`page_info`<br>`page_info.page`<br>`page_info.page_size`<br>`page_info.total_number`<br>`page_info.total_page` |
|Deprecated response parameters  | N/A |`orders`<br>`orders.order_id` <br>`orders.handle_name`<br>  `orders.campaign_code` <br>`orders.invite_link`<br> `orders.quota`<br>`orders.quota.current_video_count`<br>`orders.quota.per_creator_video_limit`<br>`orders.quota.total_video_limit` |
|New response parameters   | N/A |`campaigns`<br>`campaigns.campaign_id` <br>`campaigns.brand_name`<br>`campaigns.campaign_name` <br>`campaigns.handle_names`<br> `campaigns.campaign_description` <br>`campaigns.spark_ads_requested_authorization_days`<br> `campaigns.invite_link`<br>`campaigns.create_time`<br>`campaigns.country_codes`<br>`campaigns.advertiser_ids`<br>`campaigns.anchor_id`<br>`campaign_type`<br>`brand_profile_id`<br> `dm_info`<br>`business_account_handle`<br>`dm_allowed`|
```


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/

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
| tto_tcm_account_id {Required} | string | The ID of the TikTok One Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
| campaign_ids  | string[] | The ID list of TikTok One Creator Marketplace campaigns. <br><br> Max size: 5. <br><br>To create a campaign and obtain the campaign ID, use [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458). |
| campaign_type | string | The type of the TTO Creator Marketplace campaign.<br><br>Enum value:<ul><li>`CAMPAIGN`: A campaign with a [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link).</li><li>`BRAND_LINK`: A campaign with a [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link).</li></ul>Default value: `CAMPAIGN`. |
| page | integer | Current page number.<br><br>Value range: ≥ 1.<br><br>Default value: 1. |
| page_size | integer | Page size.<br><br>Value range: 1-5. <br><br>Default value: 5.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/?tto_tcm_account_id={{tto_tcm_account_id}}&campaign_ids=["{{campaign_id}}","{{campaign_id}}"]' \
--header 'Access-Token: {{Access-Token}}' \
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
#| campaigns | object[] | The list of TTO Creator Marketplace campaigns. |
##| campaign_id | string | The ID of the TTO Creator Marketplace campaign. |
##| campaign_type | string | The type of the TTO Creator Marketplace campaign.<br><br>Enum values:<ul><li>`CAMPAIGN`: A campaign with a [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link).</li><li>`BRAND_LINK`: A campaign with a [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link).</li></ul> |
##| brand_profile_id | string | The ID of the Brand Profile associated with the campaign. |
##| brand_name | string | Brand name. |
##| campaign_name | string | Campaign name. |
##| handle_names | string[] | The handle name list of TikTok creators that the campaign is for. |
##| campaign_description | string | Campaign description. |
##|anchor_id|string|The ID of the anchor that is associated with the campaign.|
##| video_ids | string[] | List of video IDs linked to the campaign. |
##| advertiser_ids | string[] | A list of ad accounts that the campaign is associated with. <br> When a creator's video is linked to the campaign, these ad accounts will automatically be granted Spark Ads authorization for the video. |
##| spark_ads_requested_authorization_days | integer | The default Spark Ads authorization days for videos uploaded to the campaign. <br><br>  Enum values: 0, 7, 30, 60, 180, 365. |
##| invite_link | string | The invite link that is associated with the TTO Creator Marketplace campaign. |
##| create_time | string | The time when the campaign was created, in the format of `YYYY-MM-DD HH:MM:SS`(UTC+0 time).<br><br> Example: `2024-01-01 00:00:01`. |
##| country_codes | string[] | The list of country or region codes associated with the TTO Creator Marketplace campaign.<br><br>This field represents the regions where the creators invited to the campaign are located. To learn about the values that might be returned for this field, see [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010).<br><br>You can pass the field value to the Header parameter `Country-Code` in [/tto/tcm/report/](https://business-api.tiktok.com/portal/docs?id=1815693573340162) to obtain video-level reports for a specific region for a TikTok One Creator Marketplace campaign.<br><br>Example: `CA`. |
#| page_info | object | Page information. |
##| page | number | Current page number. |
##| page_size| number | Page size. |
##| total_number | number | Total number of results. |
##| total_page | number | Total number of pages. |
#|dm_info|string|Direct messaging link information.|
##|business_account_handle|string|The handle (username) of the Business Account.|
##|dm_allowed|boolean|Whether direct messaging is allowed.<br>Supported values: `true`(allowed), `false`(not allowed).|
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
        "campaigns": [
            {
                "campaign_type": "CAMPAIGN",
				"brand_name": "{{brand_name}}",
                "campaign_description": "{{campaign_description}}",
				"anchor_id": null,
                "campaign_id": "{{campaign_id}}",
                "campaign_name": "{{campaign_name}}",
                "country_codes": [
                    "US"
                ],
                "create_time": "2024-10-03 19:32:04",
                "handle_names": [
                    "{{handle_name}}"
                ],
                "invite_link": "https://www.tiktok.com/t/1111/",
                "spark_ads_requested_authorization_days": 0,
                "video_ids": []
            },
            {
                "campaign_type": "CAMPAIGN",
				"brand_name": "{{brand_name}}",
                "campaign_description": "{{campaign_description}}",
                "campaign_id": "{{campaign_id}}",
                "campaign_name": "{{campaign_name}}",
                "country_codes": [
                    "US"
                ],
                "create_time": "2024-09-25 20:47:49",
                "handle_names": [
                    "{{handle_name}}"
                ],
                "invite_link": "https://www.tiktok.com/t/333333/",
                "spark_ads_requested_authorization_days": 0,
				"advertiser_ids":[],
                "video_ids": [
                    "{{video_id}}"
                ]
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 5,
            "total_number": 2,
            "total_page": 1
		}
    }
}
(/code)
```
