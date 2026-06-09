---
title: "Update a TTO Creator Marketplace campaign"
doc_id: 1830338948169794
path: "API Reference / TikTok One / Update a TTO Creator Marketplace campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a TTO Creator Marketplace campaign

Use this endpoint to update a TikTok One (TTO) Creator Marketplace campaign.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Type{10%}|Description{60%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TTO Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Type{10%}|Description{60%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
| campaign_id{Required} | string | The ID of an existing TTO Creator Marketplace campaign.<br><br>To retrieve existing campaigns with your TTO Creator Marketplace account, use [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258). |
| campaign_type{Required}  | string | The type of the TTO Creator Marketplace campaign.<br><br>Enum value:<ul><li>`CAMPAIGN`: A campaign with a [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link).</li><li>`BRAND_LINK`: A campaign with a [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link).</li></ul>|
| handle_names  | string[] | <ul><li>Valid only when a campaign with a campaign-level invite link is specified through `campaign_id`.</li><li>Not supported when a campaign with a brand-level invite link is specified through `campaign_id`.</li></ul><br>The handle name list of additional TikTok creators that you want to invite to the campaign with a campaign-level invite link.<br>The new creators will be added to the existing list.<br><br> Max size: 100.<br><br>For example, if you created a campaign with `handle_names` as `&#91;"&#123;&#123;handle_name_1&#125;&#125;","&#123;&#123;handle_name_2&#125;&#125;"&#93;` and want to invite two more creators, you can pass `campaign_id` and set `handle_names` to `&#91;"&#123;&#123;handle_name_3&#125;&#125;","&#123;&#123;handle_name_4&#125;&#125;"&#93;`. The `handle_names` value of the updated campaign will be `&#91;"&#123;&#123;handle_name_1&#125;&#125;","&#123;&#123;handle_name_2&#125;&#125;","&#123;&#123;handle_name_3&#125;&#125;","&#123;&#123;handle_name_4&#125;&#125;"&#93;`. </li></ul> |
|send_notification|boolean|Valid only for a campaign with a campaign-level invite link. <ul><li>To retrieve such a campaign, use [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258) and select one campaign with `campaign_type` as `CAMPAIGN`.</li></ul><br> Whether to send a notification regarding the TTO Creator Marketplace campaign invitation to the inboxes of invited TikTok creators (`handle_names`).<br> The creators can open the notification to join the campaign.<br><br> Supported values: `true`,`false`.<br>Default value: `false`. |
| advertiser_ids | string[] | A list of additional ad accounts to associate the campaign with.<br>When a creator's video is linked to the campaign, these ad accounts will automatically be granted Spark Ads authorization for the video.<br>You can specify ad accounts that share the same business center, legal entity, or logged in email.<br><br>Max size: 50.<br><br>For example, if you created a campaign with `advertiser_ids` as `["&#123;&#123;advertiser_id_1&#125;&#125;","&#123;&#123;advertiser_id_2&#125;&#125;"]` and want to invite two more creators, you can set `advertiser_ids` to `["&#123;&#123;advertiser_id_3&#125;&#125;","&#123;&#123;advertiser_id_4&#125;&#125;"]`. The `advertiser_ids` value of the updated campaign will be `["&#123;&#123;advertiser_id_1&#125;&#125;","&#123;&#123;advertiser_id_2&#125;&#125;","&#123;&#123;advertiser_id_3&#125;&#125;","&#123;&#123;advertiser_id_4&#125;&#125;"]`. |
```
### Example
#### Update a campaign with a campaign-level invite link

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "tto_tcm_account_id": "{{tto_tcm_account_id}}",
    "campaign_id":"{{campaign_id}}",
    "campaign_type":"CAMPAIGN",
    "handle_names": ["{{handle_name}}"]
}'
(/code)
```

#### Update a campaign with a brand-level invite link
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "tto_tcm_account_id": "{{tto_tcm_account_id}}",
    "campaign_id":"{{campaign_id}}",
    "campaign_type":"CAMPAIGN",
    "advertiser_ids": [
        "{{advertiser_id}}"
    ]
}'
(/code)
```

## Response

``` xtable
|Field{35%}|Type{10%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| campaign_id | string | The ID of the TTO Creator Marketplace campaign. |
#| brand_name | string | Brand name. |
#| campaign_name | string | Campaign name. |
#| handle_names | string[] | The handle name list of TikTok creators that the campaign is for. |
#| campaign_description | string | Campaign description. |
#|anchor_id|string|The ID of the anchor that is associated with the campaign.|
#| advertiser_ids | string[] | A list of ad accounts that the campaign is associated with. <br> When a creator's video is linked to the campaign, these ad accounts will automatically be granted Spark Ads authorization for the video. |
#| spark_ads_requested_authorization_days | integer | The default Spark Ads authorization days for videos uploaded to the campaign. <br><br> Enum values: 0, 7, 30, 60, 180, 365. |
#| invite_link | string | The invite link that is associated with the TTO Creator Marketplace campaign. <br><br> The invite link will not expire. |
#| create_time| string | The time when the campaign was created, in the format of `YYYY-MM-DD HH:MM:SS`(UTC+0 time).<br><br> Example: `2024-01-01 00:00:01`. |
#| campaign_type | string | The type of the TTO Creator Marketplace campaign.<br><br>Enum values:<ul><li>`CAMPAIGN`: A campaign with a [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link).</li><li>`BRAND_LINK`: A campaign with a [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link).</li></ul> |
#| brand_profile_id | string | The ID of the Brand Profile associated with the campaign. |
#|country_codes|string[]|Returned when at least one creator specified in the request parameter `handle_names` has been successfully invited to the campaign.<br><br>The list of countries or region codes associated with the campaign.<br>Example: `["US"]`.|
#|failed_handle_names|string[]|Returned when at least one creator specified in the request parameter `handle_names` failed to be invited to the campaign.<br><br>The list of handles that failed to be added to the campaign.<br><br>For example, if you provided `["handle_name_1","handle_name_2"]` in the request parameter `handle_names` and `handle_name_1` was successfully added while `handle_name_2` failed, the response will show `handle_names` as `["handle_name_1"]` and `failed_handle_names` as `["handle_name_2"]`.|
```

### Example
#### Update a campaign with a campaign-level invite link
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "brand_name": "{{brand_name}}",
        "campaign_description": "{{campaign_description}}",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "campaign_type": "CAMPAIGN",
        "create_time": "{{create_time}}",
        "handle_names": [
            "{{handle_names}}",
            "{{handle_names}}"
        ],
        "invite_link": "{{invite_link}}",
        "spark_ads_requested_authorization_days": 0,
        "update_time": "{{update_time}}"
    }
}
(/code)
```
#### Update a campaign with a brand-level invite link
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "advertiser_ids": [
            "{{advertiser_ids}}",
            "{{advertiser_ids}}"
        ],
        "brand_name": "{{brand_name}}",
        "brand_profile_id": "{{brand_profile_id}}",
        "campaign_id": "{{campaign_id}}",
        "campaign_type": "BRAND_LINK",
        "create_time": "{{create_time}}",
        "invite_link": "{{invite_link}}",
        "spark_ads_requested_authorization_days": 7,
        "update_time": "{{update_time}}"
    }
}
(/code)
```

