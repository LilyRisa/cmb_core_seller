---
title: "Create or update a TTO Creator Marketplace campaign"
doc_id: 1815693549459458
path: "API Reference / TikTok One / Create or update a TTO Creator Marketplace campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create or update a TTO Creator Marketplace campaign

Use this endpoint to create or update a TikTok One (TTO) Creator Marketplace campaign.  

You can create a campaign along with any of the following invite link types:
- A campaign-level invite link to associate videos with a specific campaign.
- A brand-level invite link to associate videos with your brand account.

To learn more about the invite link types, see [TTO Creator Marketplace Content Linking](https://business-api.tiktok.com/portal/docs?id=1830337139853313).

> <span style="color:DodgerBlue">**Note**</span><br>
> - Orders are no longer used. Campaign reporting provides performance data for individual videos instead of orders.
> - Campaign codes are no longer used. Creators should now use invite links to tag videos. 
> - The campaigns that are created via this endpoint will be displayed on [the TTO Creator Marketplace platform](https://ads.tiktok.com/creative/creatormarketplace).
> - After you create a TTO Creator Marketplace campaign, if a creator wants to publish a video on your platform, you can ask the creator to directly link a video to the campaign during publication by specifying `tto_invite_link` in [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234).

## Comparing TCM and TTO Creator Marketplace endpoints
The following table outlines the differences between TCM and TTO Creator Marketplace endpoints.
```xtable
| Changes {24%}| TCM Endpoint{36%} | TTO Creator Marketplace Endpoint{40%} |
|---|---|---|
| Endpoint path | /v1.3/tcm/order/create/v2/ | /v1.3/tto/tcm/campaign/create/ |
|Same request parameters | `brand_name`<br>`handle_names`<br> `default_spark_ads_requested_authorization_days`| `brand_name`<br>`handle_names`<br> `default_spark_ads_requested_authorization_days` |
|Deprecated request parameters | N/A| `tcm_account_id`  <br>`description` <br> `campaign_code` <br>`order_source`  <br> `deliverables`  <br>`deliverables.deliverable_name` <br> `deliverables.spark_ads_requested_authorization_days` <br>`video_number`  |
|New request parameters   |  N/A|`tto_tcm_account_id`<br> `campaign_name`<br> `campaign_description` <br>`campaign_id`<br>`advertiser_ids`<br>`anchor_id`<br>`campaign_type`<br>`brand_profile_id`<br>`send_notification`<br>`business_account_handle`|
|Same response parameters | `invite_link` | `invite_link`|
|Deprecated response parameters |  N/A| `campaign_code` <br> `remaining_orders`<br> `orders`  <br> `orders.order_id`  <br>`orders.handle_name` |
|New response parameters   | N/A|`campaign_id`<br> `brand_name`<br>`campaign_name` <br>`handle_names`<br>`campaign_description` <br>`spark_ads_requested_authorization_days` <br>`create_time` <br>`advertiser_ids`<br>`anchor_id`<br>`campaign_type`<br>`brand_profile_id`<br> `dm_info`<br>`business_account_handle`<br>`dm_allowed`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/create/

**Method** POST

**Header**

```xtable
|Field{40%}|Type{10%}|Description{50%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TTO Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

### For campaigns with a campaign-level invite link
``` xtable
|Field{40%}|Type{10%}|Description{50%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
| campaign_type | string | The type of the TTO Creator Marketplace campaign.<br><br>Enum value:<ul><li>`CAMPAIGN`: A campaign with a [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link).</li></ul>Default value: `CAMPAIGN`. |
| brand_profile_id{+Conditional} | string | Either `brand_profile_id` or `brand_name` is required.<br><br>The ID of a Brand Profile within the TTO Creator Marketplace account to associate with the campaign.<ul><li>To create a Brand Profile and obtain the Brand Profile ID, use [/tto/tcm/brand/profile/create/](https://business-api.tiktok.com/portal/docs?id=1830335387914241).</li><li>To retrieve existing Brand Profile IDs, use [/tto/tcm/brand/profile/get/](https://business-api.tiktok.com/portal/docs?id=1830335437705217). </li></ul>|
| brand_name {+Conditional} | string | Either `brand_profile_id` or `brand_name` is required.<br><br>Brand name. <br><br>Length limit: 60 characters.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you are updating an existing campaign (with `campaign_id` specified in the request), the current campaign `brand_name` value will be used regardless of the value you pass in. |
| campaign_name {Required} | string | Campaign name. <br> Your campaign name should be informative and stand out to creators. You can follow the format &#91;product name - product description&#93; to tell creators about the basics of your brand or product.<br><br>Length limit: 120 characters. <br><br> Example: `CapCut - video editor`<br><br><p><span style="color:darkred"><b>Note</b></span>: If you are updating an existing campaign (with `campaign_id` specified in the request), the current campaign `campaign_name` value will be used regardless of the value you pass in. |
| campaign_description| string | Campaign description.<br>Provide relevant information about your campaign, such as your product's positioning, target audiences, and core features. <br><br> Length limit: 1,000 characters. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you are updating an existing campaign (with `campaign_id` specified in the request), the current campaign `campaign_description` value will be used regardless of the value you pass in. |
|anchor_id|string|ID of the anchor to associate with the campaign.<br>Anchors are links you add to your post that help viewers learn more about your product or service.<br>The anchor is only visible to audiences in the anchor’s target country.<br><br>Performance data of the anchor can only be measured when the creator's country matches the anchor’s target country. The video is still visible to the creator's audience, but the anchor attached is only visible to audiences in the anchor's country.<br><br>To obtain the anchors within your TTO Creator Marketplace account, use [/tto/tcm/anchor/get/](https://business-api.tiktok.com/portal/docs?id=1826564965840066).|
| advertiser_ids | string[] | A list of ad accounts to associate the campaign with.<br>When a creator's video is linked to the campaign, these ad accounts will automatically be granted Spark Ads authorization for the video and the video will be automatically synced to the creative library of these ad accounts.<br><br> When you specify `advertiser_ids`, `default_spark_ads_requested_authorization_days` is required. <br><br> Max size: 50. <br><br> To obtain the IDs of the ad accounts that you can access via `Access-Token`, use [/oauth2/advertiser/get/](https://business-api.tiktok.com/portal/docs?id=1738455508553729). |
| default_spark_ads_requested_authorization_days {+Conditional}| integer |<ul><li> Valid when you are creating a new campaign.<ul><li>Required when `advertiser_ids` is specified.</li><li>Optional when `advertiser_ids` is not specified.</li></ul></li><li>Ignored when you are updating an existing campaign.</li></ul><br>The default number of authorization days you want to request from the creator to boost all of the videos uploaded for the campaign as Spark Ads. <br><br>Value range: 0-365. <br>Default value: 0. <br><br>Once this field is set to a value other than the value 0, when a creator accepts the invitation and uploads videos to the campaign, the creator automatically grants you Spark Ads authorization for the videos for the specified number of days. |
| handle_names {Required} | string[] | The handle name list of TikTok creators that you want to invite to the campaign. <br><br> Max size: 100.<br><br><ul><li>For new campaigns, specify the handle names of TikTok Creators to invite. </li><li>For existing campaigns, specify additional handle names to invite. The new Creators will be added to the existing list. For example, if you created a campaign with `handle_names` as `&#91;"&#123;&#123;handle_name_1&#125;&#125;","&#123;&#123;handle_name_2&#125;&#125;"&#93;` and want to invite two more creators, you can pass `campaign_id` and set `handle_names` to `&#91;"&#123;&#123;handle_name_3&#125;&#125;","&#123;&#123;handle_name_4&#125;&#125;"&#93;`. The `handle_names` value of the updated campaign will be `&#91;"&#123;&#123;handle_name_1&#125;&#125;","&#123;&#123;handle_name_2&#125;&#125;","&#123;&#123;handle_name_3&#125;&#125;","&#123;&#123;handle_name_4&#125;&#125;"&#93;`. </li></ul> |
|send_notification|boolean|Whether to send a notification regarding the TTO Creator Marketplace campaign invitation to the inboxes of invited TikTok creators (`handle_names`).<br> The creators can open the notification to join the campaign.<br><br> Supported values: `true`,`false`.<br>Default value: `false`. |
| campaign_id | string | Valid when you are updating an existing campaign. <br> If this field is passed, all the other fields except `tto_tcm_account_id` and `handle_names` will be ignored. <br><br> The ID of an existing TTO Creator Marketplace campaign. <br><br>When you pass this field, you need to pass `handle_names` simultaneously to add new Creators to an existing campaign. |
| business_account_handle | string |The handle (username) of the Business Account. <br><br>When you include this parameter during campaign creation, the creator will have the option to message the brand directly when they open the generated invite link (invite_link).<br><br><ul><li>Only applicable when the handle is a non-EU Business Account.</ul><p><span style="color:darkred"><b>Note</b></span>: Associating a Business Account with a TTO Creator Marketplace Campaign during campaign creation is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|

```
#### Example
##### Create a new campaign with a campaign-level invite link
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "tto_tcm_account_id": "{{tto_tcm_account_id}}",
    "brand_name": "{{brand_name}}",
    "campaign_name": "{{campaign_name}}",
    "campaign_description": "{{campaign_description}}",
    "default_spark_ads_requested_authorization_days":60,
    "handle_names": ["{{handle_name}}","{{handle_name}}"]
}'
(/code)
```

##### Update an existing campaign with a campaign-level invite link
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "tto_tcm_account_id": "{{tto_tcm_account_id}}",
    "campaign_id": "{{campaign_id}}"
    "handle_names": ["{{handle_name}}","{{handle_name}}"],
    "brand_name": "", 
    "campaign_name": "",
    "campaign_description": ""
}'
(/code)
```
### For campaigns with a brand-level invite link
``` xtable
|Field{40%}|Type{10%}|Description{50%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
| campaign_type  {Required}| string | The type of the TTO Creator Marketplace campaign.<br><br>Enum value:<ul><li>`BRAND_LINK`: A campaign with a [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link).</li></ul> |
| brand_profile_id {Required} | string | The ID of a Brand Profile within the TTO Creator Marketplace account to associate with the campaign.<ul><li>To create a Brand Profile and obtain the Brand Profile ID, use [/tto/tcm/brand/profile/create/](https://business-api.tiktok.com/portal/docs?id=1830335387914241).</li><li>To retrieve existing Brand Profile IDs, use [/tto/tcm/brand/profile/get/](https://business-api.tiktok.com/portal/docs?id=1830335437705217). </li></ul>|
| advertiser_ids | string[] | A list of ad accounts to associate the campaign with.<br>When a creator's video is linked to the campaign, these ad accounts will automatically be granted Spark Ads authorization for the video. <br>You can specify ad accounts that share the same business center, legal entity, or logged in email. <br><br> When you specify `advertiser_ids`, `default_spark_ads_requested_authorization_days` is required. <br><br> Max size: 50. <br><br> To obtain the IDs of the ad accounts that you can access via `Access-Token`, use [/oauth2/advertiser/get/](https://business-api.tiktok.com/portal/docs?id=1738455508553729). |
| default_spark_ads_requested_authorization_days {+Conditional}| integer |<ul><li> Valid when you are creating a new campaign.<ul><li>Required when `advertiser_ids` is specified.</li><li>Optional when `advertiser_ids` is not specified.</li></ul></li><li>Ignored when you are updating an existing campaign.</li></ul><br>The default number of authorization days you want to request from the creator to boost all of the videos uploaded for the campaign as Spark Ads. <br><br>Value range: 0-365. <br>Default value: 0. <br><br>Once this field is set to a value other than the value 0, when a creator accepts the invitation and uploads videos to the campaign, the creator automatically grants you Spark Ads authorization for the videos for the specified number of days. |
| business_account_handle | string |The handle (username) of the Business Account. <br><br>When you include this parameter during campaign creation, the creator will have the option to message the brand directly when they open the generated invite link (invite_link).<br><br><ul><li>Only applicable when the handle is a non-EU Business Account.</ul><p><span style="color:darkred"><b>Note</b></span>: Associating a Business Account with a TTO Creator Marketplace Campaign during campaign creation is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
```
#### Example
##### Create a new campaign with a brand-level invite link
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "tto_tcm_account_id": "{{tto_tcm_account_id}}",
    "campaign_type": "BRAND_LINK",
    "brand_profile_id": "{{brand_profile_id}}",
    "advertiser_ids": ["{{advertiser_id}}"],
    "default_spark_ads_requested_authorization_days": 7
}'
(/code)
```

## Response

``` xtable
|Field{32%}|Type{15%}|Description{53%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| campaign_id | string | The ID of the TTO Creator Marketplace campaign. |
#| brand_name | string | Brand name. |
#| campaign_name | string | Campaign name. |
#| handle_names | string[] | The handle name list of TikTok creators that the campaign is for. <br><br><p><span style="color:darkred"><b>Note</b></span>: Due to the latency in data processing, `handle_names` may be an empty list (`&#91;&#93;`) when creators have been invited to a campaign. If you encounter an empty list, you can wait for some time and use the [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258) endpoint to confirm the handle name list of creators invited to the campaign.|
#| campaign_description | string | Campaign description. |
#|anchor_id|string|The ID of the anchor that is associated with the campaign.|
#| advertiser_ids | string[] | A list of ad accounts that the campaign is associated with. <br> When a creator's video is linked to the campaign, these ad accounts will automatically be granted Spark Ads authorization for the video. |
#| spark_ads_requested_authorization_days | integer | The default Spark Ads authorization days for videos uploaded to the campaign. <br><br> Enum values: 0, 7, 30, 60, 180, 365. |
#| invite_link | string | The invite link that is associated with the TikTok One Creator Marketplace campaign. <br><br> The invite link will not expire. |
#| create_time| string | The time when the campaign was created, in the format of `YYYY-MM-DD HH:MM:SS`(UTC+0 time).<br><br> Example: `2024-01-01 00:00:01`. |
#| campaign_type | string | The type of the TTO Creator Marketplace campaign.<br><br>Enum values:<ul><li>`CAMPAIGN`: A campaign with a [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link).</li><li>`BRAND_LINK`: A campaign with a [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link).</li></ul> |
#| brand_profile_id | string | The ID of the Brand Profile associated with the campaign. |
#|country_codes|string[]|Returned when at least one creator specified in the request parameter `handle_names` has been successfully invited to the campaign.<br><br>The list of countries or region codes associated with the campaign.<br>Example: `["US"]`.|
#|failed_handle_names|string[]|Returned when at least one creator specified in the request parameter `handle_names` failed to be invited to the campaign.<br><br>The list of handles that failed to be added to the campaign.<br><br>For example, if you provided `["handle_name_1","handle_name_2"]` in the request parameter `handle_names` and `handle_name_1` was successfully added while `handle_name_2` failed, the response will show `handle_names` as `["handle_name_1"]` and `failed_handle_names` as `["handle_name_2"]`.|
#|dm_info|string|Direct messaging link information.|
##|business_account_handle|string|The handle (username) of the Business Account.|
##|dm_allowed|boolean|Whether direct messaging is allowed.<br>Supported values: `true`(allowed), `false`(not allowed).|
```

### Example
#### Create a new campaign with a campaign-level invite link
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "campaign_description": "{{campaign_description}}",
        "campaign_id": "{{campaign_id}}",
		"brand_name": "{{brand_name}}",
        "campaign_name": "{{campaign_name}}",
        "create_time": "2024-10-03 19:32:04",
        "handle_names": [
            "{{handle_name}}",
            "{{handle_name}}"
        ],
        "invite_link": "https://www.tiktok.com/t/111111/",
        "spark_ads_requested_authorization_days": 60,
        "campaign_type": "CAMPAIGN"
    }
}
(/code)
```
#### Update an existing campaign with a campaign-level invite link
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "campaign_description": "{{campaign_description}}",
        "campaign_id": "{{campaign_id}}",
        "brand_name": "{{brand_name}}",
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "handle_names": [
            "{{handle_name}}",
            "{{handle_name}}"
        ],
        "invite_link": "{{invite_link}}",
        "spark_ads_requested_authorization_days": 60,
        "campaign_type": "CAMPAIGN"
    }
}

(/code)
```

#### Create a new campaign with a brand-level invite link
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "advertiser_ids": ["{{advertiser_id}}"],
        "brand_name": "{{brand_name}}",
        "brand_profile_id": "{{brand_profile_id}}",
        "campaign_id": "{{campaign_id}}",
        "campaign_type": "BRAND_LINK",
        "create_time": "{{create_time}}",
        "invite_link": "{{invite_link}}",
        "spark_ads_requested_authorization_days": 7
    }
}
(/code)
```
