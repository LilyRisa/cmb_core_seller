---
title: "Create a campaign, an ad group, and a Spark Ad in one step"
doc_id: 1829744071179330
path: "API Reference / Spark Ads Recommendation / Create a campaign, an ad group, and a Spark Ad in one step"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a campaign, an ad group, and a Spark Ad in one step

Use this endpoint to simultaneously set up a campaign, an ad group on the TikTok placement, and a single video Spark Ad within the ad group.

## Before you start
Review the default settings at the campaign, ad group, and ad levels in [Default settings for All-in-One Spark Ads](https://business-api.tiktok.com/portal/docs?id=1829744101665810) to understand variations based on your chosen advertising objective and optimization goal.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/spark_ad/create/

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
| campaign_name | string | Campaign name.<br><br>Length limit: 512 characters. Emojis are not supported.<br><br><p><span style="color:darkred"><b>Note</b></span>: Each English letter counts as one character while each Chinese or Japanese word counts as two. |
| objective_type | string | Advertising objective.<br><br>Enum values:<ul><li>`REACH`: Reach.</li><li>`TRAFFIC`: Traffic.</li><li>`VIDEO_VIEWS`: Video views.</li><li>`ENGAGEMENT`: Community interaction.</li></ul><br>For detailed descriptions of each objective, see [Advertising objective](https://business-api.tiktok.com/portal/docs?id=1737585562434561). |
| adgroup_name | string | Ad group name.<br><br>Length limit: 512 characters. Emojis are not supported.<br><br><p><span style="color:darkred"><b>Note</b></span>: Each English letter counts as one character while each Chinese or Japanese word counts as two. |
| saved_audience_id | string | Either `saved_audience_id` or `location_ids` is required.<br><br>Saved Audience ID.<br><br>Before using this field, call [/dmp/saved_audience/create/](https://business-api.tiktok.com/portal/docs?id=1780154541898754) to create a Saved Audience and get the Saved Audience ID in response.<br>To find out the detailed workflow and code examples, see [Create a Saved Audience](https://business-api.tiktok.com/portal/docs?id=1780156510696449). |
| location_ids | string[] |<ul><li> Either `saved_audience_id` or `location_ids` is required.</li><li>Ignored when `saved_audience_id` is specified.</li></ul><br>IDs of the locations that you want to target.<br><br>Max size: 3,000.<br><br>To get the available locations and corresponding IDs based on your placement and objective, use [/tool/targeting/search/](https://ads.tiktok.com/marketing_api/docs?id=1761236883355649) or [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713).<br>To get the list of location IDs, see [Location IDs](https://ads.tiktok.com/marketing_api/docs?id=1739311040498689). |
| gender | string | Ignored when `saved_audience_id` is specified.<br><br>Gender that you want to target.<br><br>Enum values: <ul><li>`GENDER_FEMALE`</li><li>`GENDER_MALE`</li><li>`GENDER_UNLIMITED`</li></ul> |
| age_groups | string[] | Ignored when `saved_audience_id` is specified.<br><br>Age groups you want to target.<br><br>For enum values, see [Enumeration - Age Group](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Age%20Group). |
| budget_mode | string | Ad group budget mode.<br><br>Enum values:<ul><li>`BUDGET_MODE_TOTAL`: Lifetime budget.</li><li>`BUDGET_MODE_DAY`: Daily budget.</li></ul><br>To learn about how to set budget modes, see [Budget](https://business-api.tiktok.com/portal/docs?id=1739381246298114).<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>If this field is set to `BUDGET_MODE_DAY`, then `schedule_type` can be either `SCHEDULE_START_END` or `SCHEDULE_FROM_NOW`.</li><li>If this field is set to `BUDGET_MODE_TOTAL`, then `schedule_type` must be `SCHEDULE_START_END`, which requires an end date (`schedule_end_time`).</li></ul> |
| budget | float | Ad group budget.<br><br>To learn about how to set budget modes, see [Budget](https://business-api.tiktok.com/portal/docs?id=1739381246298114). |
| schedule_type | string | Schedule type.<br><br>Enum values:<ul><li>`SCHEDULE_START_END`: To run the campaign between the scheduled start time and end time. You need to specify a start time and an end time.<ul><li>If `budget_mode` is `BUDGET_MODE_TOTAL`, this field must be set to `SCHEDULE_START_END`.</li></ul></li><li>`SCHEDULE_FROM_NOW`: To run the campaign continuously after the scheduled start time. You only need to specify a start time and the end time will be automatically set to 10 years later than the start time.</li></ul> |
| schedule_start_time | string | Schedule start time (UTC+0), in the format of `YYYY-MM-DD HH:MM:SS`.<br><br>The start time can be up to 12 hours earlier than the current time, but cannot be later than `2028-01-01 00:00:00`. |
| schedule_end_time | string | Required when `schedule_type` is `SCHEDULE_START_END`.<br><br>Schedule end time (UTC+0), in the format of `YYYY-MM-DD HH:MM:SS`.<br><br>The end time cannot be later than `2038-01-01 00:00:00`. |
| optimization_goal | string | The measurable results you'd like to drive with your ads.<br><br>The available enum values vary by `objective_type`:<ul><li>When `objective_type` is `REACH`: `REACH`.</li><li>When `objective_type` is `TRAFFIC`: `CLICK` or `TRAFFIC_LANDING_PAGE_VIEW`.</li><li>When `objective_type` is `VIDEO_VIEWS`: `ENGAGED_VIEW`.</li><li>When `objective_type` is `ENGAGEMENT`: `FOLLOWERS` or `PAGE_VISIT`.</li></ul><br>For detailed descriptions of each optimization goal, see [Enumeration - Optimization Goal](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Optimization%20Goal). |
| frequency | number | Required when `objective_type` is `REACH`.<br><br>Frequency, the maximum number of times a user can see your ad within a given period.<br>`frequency` and `frequency_schedule` define how often people see your ad.<br><br>The following conditions should be both met:<ul><li>`frequency` range: 1–1,000.</li><li>`frequency_schedule`: 1–30 (days).</li></ul>For instance, `frequency = 2` and `frequency_schedule = 3` ensure ads are shown no more than twice every three days. |
| frequency_schedule | number | Required when `objective_type` is `REACH`.<br><br>Frequency schedule, the duration (in days) over which the frequency cap is applied.<br>`frequency` and `frequency_schedule` define how often people see your ad.<br><br>The following conditions should be both met:<ul><li>`frequency` range: 1–1,000.</li><li>`frequency_schedule`: 1–30 (days).</li></ul>For instance, `frequency = 2` and `frequency_schedule = 3` ensure ads are shown no more than twice every three days. |
| bid_type | string | The bidding strategy for your ad group.<br><br>Enum values:<ul><li>`BID_TYPE_CUSTOM`: Cost Cap.<ul><li>When `optimization_goal` is `REACH`, `CLICK`, `PAGE_VISIT`, or `ENGAGED_VIEW` and `bid_type` is `BID_TYPE_CUSTOM`, you need to specify `bid_price` simultaneously.</li><li>When `optimization_goal` is `TRAFFIC_LANDING_PAGE_VIEW`, or `FOLLOWERS` and `bid_type` is `BID_TYPE_CUSTOM`, you need to specify `conversion_bid_price` simultaneously.</li></ul></li><li>`BID_TYPE_NO_BID`: Maximum Delivery.</li></ul><br>Default value: `BID_TYPE_NO_BID`. |
| bid_price | number | Required when the following conditions are both met:<ul><li>`optimization_goal` is `REACH`, `CLICK`, `PAGE_VISIT`, or `ENGAGED_VIEW`.</li><li>`bid_type` is `BID_TYPE_CUSTOM`.</li></ul><br>The average cost per result that you want to achieve. |
| conversion_bid_price | float | Required when the following conditions are both met:<ul><li>`optimization_goal` is `TRAFFIC_LANDING_PAGE_VIEW`, or `FOLLOWERS`.</li><li>`bid_type` is `BID_TYPE_CUSTOM`.</li></ul><br>The target cost per conversion for oCPM (Optimized Cost per Mille). |
| ad_name | string | Ad name.<br><br>To make the ad name automatically generated, set this field to `""` (empty string). The format of the auto-generated ad name is ad ID (`ad_id`).<br><br>Length limit: 512 characters. Emojis are not supported.<br><br><p><span style="color:darkred"><b>Note</b></span>: Each English letter counts as one character while each Chinese or Japanese word counts as two. |
| identity_type | string | Identity type.<br><br>Enum values: `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`.<br><br>For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097). |
| identity_id | string | Identity ID. |
| identity_authorized_bc_id | string | Required when `identity_type` is `BC_AUTH_TT`.<br><br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
| tiktok_item_id | string | The ID of the TikTok post to be used as a Spark Ad.<br><br>To learn more about Spark Ads, see [Create Spark Ads](https://business-api.tiktok.com/portal/docs?id=1739470744631298).<br><br>To obtain the post ID, use [/identity/video/get/](https://ads.tiktok.com/marketing_api/docs?id=1740218475032577) or [/tt_video/info/](https://ads.tiktok.com/marketing_api/docs?id=1738376324021250) and check the returned `item_id`.<br><br><p><span style="color:darkred"><b>Note</b></span>: By using Spark Ads, you confirm that you have the rights to use the music in the videos for commercial purposes. |
| call_to_action | string |<ul><li>Required when `optimization_goal` is `CLICK`, `TRAFFIC_LANDING_PAGE_VIEW`, or `PAGE_VISIT`.</li><li>Optional when `optimization_goal` is `REACH` or `ENGAGED_VIEW`.</li></ul><br>Call-to-action text.<br><br>For enum values, see [Enumeration - Call-to-Action](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action). |
| landing_page_url | string | <ul><li>Required in any of the following conditions:<ul><li>`optimization_goal` is `CLICK`, `TRAFFIC_LANDING_PAGE_VIEW`, or `PAGE_VISIT`.</li><li>`optimization_goal` is `REACH` or `ENGAGED_VIEW` and `call_to_action` is specified.</li></ul></li><li>Not supported when `optimization_goal` is `FOLLOWERS` or `PAGE_VISIT`.</li></ul><br>The landing page that users will be redirected to. |
```



### Example
#### Create Reach ads 
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/spark_ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_name": "{{campaign_name}}",
    "objective_type": "REACH",
    "adgroup_name": "{{adgroup_name}}",
    "location_ids": ["6252001"],
    "gender": "GENDER_UNLIMITED",
    "age_groups": ["AGE_18_24"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "REACH",
    "frequency": {{frequency}},
    "frequency_schedule": {{frequency_schedule}},
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "ad_name": "{{ad_name}}",
    "identity_type": "TT_USER",
    "identity_id": "{{identity_id}}",
    "tiktok_item_id": "{{tiktok_item_id}}",
    "call_to_action": "ORDER_NOW",
    "landing_page_url": "{{landing_page_url}}"
}'
(/code)
```
#### Create Traffic ads 
##### With Optimization Goal as Click
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/spark_ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_name": "{{campaign_name}}",
    "objective_type": "TRAFFIC",
    "adgroup_name": "{{adgroup_name}}",
    "location_ids": ["6252001"],
    "gender": "GENDER_UNLIMITED",
    "age_groups": ["AGE_18_24"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "ad_name": "{{ad_name}}",
    "identity_type": "TT_USER",
    "identity_id": "{{identity_id}}",
    "tiktok_item_id": "{{tiktok_item_id}}",
    "call_to_action": "ORDER_NOW",
    "landing_page_url": "{{landing_page_url}}"
}'
(/code)
```
##### With Optimization Goal as Landing Page View
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/spark_ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_name": "{{campaign_name}}",
    "objective_type": "TRAFFIC",
    "adgroup_name": "{{adgroup_name}}",
    "location_ids": ["6252001"],
    "gender": "GENDER_UNLIMITED",
    "age_groups": ["AGE_18_24"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "TRAFFIC_LANDING_PAGE_VIEW",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "ad_name": "{{ad_name}}",
    "identity_type": "TT_USER",
    "identity_id": "{{identity_id}}",
    "tiktok_item_id": "{{tiktok_item_id}}",
    "call_to_action": "ORDER_NOW",
    "landing_page_url": "{{landing_page_url}}"
}'
(/code)
```
#### Create Video Views ads 
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/spark_ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_name": "{{campaign_name}}",
    "objective_type": "VIDEO_VIEWS",
    "adgroup_name": "{{adgroup_name}}",
    "location_ids": ["6252001"],
    "gender": "GENDER_UNLIMITED",
    "age_groups": ["AGE_18_24"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "ENGAGED_VIEW",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "ad_name": "{{ad_name}}",
    "identity_type": "TT_USER",
    "identity_id": "{{identity_id}}",
    "tiktok_item_id": "{{tiktok_item_id}}",
    "call_to_action": "ORDER_NOW",
    "landing_page_url": "{{landing_page_url}}"
}'
(/code)
```
#### Create Community Interaction ads
##### With Optimization Goal as Followers
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/spark_ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_name": "{{campaign_name}}",
    "objective_type": "ENGAGEMENT",
    "adgroup_name": "{{adgroup_name}}",
    "location_ids": ["6252001"],
    "gender": "GENDER_UNLIMITED",
    "age_groups": ["AGE_18_24"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "FOLLOWERS",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "ad_name": "{{ad_name}}",
    "identity_type": "TT_USER",
    "identity_id": "{{identity_id}}",
    "tiktok_item_id": "{{tiktok_item_id}}"
}'
(/code)
```
##### With Optimization Goal as Page Visit
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/spark_ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_name": "{{campaign_name}}",
    "objective_type": "ENGAGEMENT",
    "adgroup_name": "{{adgroup_name}}",
    "location_ids": ["6252001"],
    "gender": "GENDER_UNLIMITED",
    "age_groups": ["AGE_18_24"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "PAGE_VISIT",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "ad_name": "{{ad_name}}",
    "identity_type": "TT_USER",
    "identity_id": "{{identity_id}}",
    "tiktok_item_id": "{{tiktok_item_id}}",
    "call_to_action": "CONTACT_US"
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
#| campaign_id | string | The ID of the created campaign.<br><br>To obtain the details of the campaign, use [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986). |
#| adgroup_id | string | The ID of the ad group created within the campaign.<br><br>To obtain the details of the ad group, use [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922). |
#| ad_id | string | The ID of the Spark Ad created within the ad group.<br><br>To obtain the details of the ad, use [/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770). |
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
        "ad_id": "{{ad_id}}",
        "adgroup_id": "{{adgroup_id}}",
        "campaign_id": "{{campaign_id}}"
    }
}
(/code)
```
