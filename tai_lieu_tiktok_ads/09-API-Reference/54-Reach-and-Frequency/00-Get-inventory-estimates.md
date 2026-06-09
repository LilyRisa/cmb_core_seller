---
title: "Get inventory estimates"
doc_id: 1738235487941633
path: "API Reference / Reach & Frequency / Get inventory estimates"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get inventory estimates

Use this endpoint to get inventory estimates for Reach & Frequency ads.  

See [About Reach & Frequency - Inventory estimate](https://ads.tiktok.com/marketing_api/docs?id=1744571201117186) for more details about inventory estimate and the mapping between the prediction results returned via this endpoint and the results on TikTok Ads Manager.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/adgroup/rf/inventory/estimate/|/v1.3/rf/inventory/estimate/|
|Request parameter name|`age`<br>`operation_system`  <br>`connection_type`  <br>`device_price` <br>`brand_safety`|`age_groups`<br>`operating_systems`<br>`network_types`  <br>`device_price_ranges`  <br>`brand_safety_type`  |
|Request parameter data type|`advertiser_id`: number  |`advertiser_id`: string|
|Request parameter name and data type|`audience`: number[]<br>`excluded_audience`: number[] <br>`location`: number[]<br>`device_models`: number[] <br>`carriers_v2`: number[]  <br>`interest_category_v2`: number[]  |`audience_ids`: string[]<br>`excluded_audience_ids`: string[]<br>`location_ids`: string[]<br>`device_model_ids`: string[]<br>`carrier_ids`: string[]<br>`interest_category_ids`: string[]|
|Request parameters deprecated in v1.3| /|`reach_percentage`|
|New request parameters|/| `rf_purchased_type`: string<br> `budget`: float<br> `purchased_impression`: number<br> `purchased_reach`: number<br>`rf_campaign_type`: string<br>`contextual_tag_ids`: string[]|
|Response parameters deprecated in v1.3|/|`estimate_key`<br>`max_reach` |
|New response parameters| / |`max_reach` |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/rf/inventory/estimate/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

``` xtable
| Field{35%} | Data Type{12%} | Description{53%} |
| --- | --- | --- |
| advertiser_id {Required} | string | Advertiser ID |
| audience_info  {Required}| object | Audience targeting rules |
#|audience_ids| string[]|IDs of the audiences that you want to target. For an audience to be targeted or excluded in R&F ads, you must set `audience_sub_type` to `REACH_FREQUENCY` when creating or updating the audience. |
#|excluded_audience_ids|string[]|IDs of the audiences that you want to exclude. For an audience to be targeted or excluded in R&F ads, you must set `audience_sub_type` to `REACH_FREQUENCY` when creating or updating the audience.|
#|age_groups|string[]|Target age range. If your targeting locations include Israel or Brazil, `AGE_13_17` cannot be used. For enum values, see [Enumeration - Audience Age Range](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
#| gender | string | Target gender.  For enum values, see [Enumeration - Audience Gender](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
#| languages | string[] | Target language. For enum values, see [Enumeration - Audience Language](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
#| location_ids {Required} | string[] | IDs of the targeted locations.  targeting countries and/or regions. The location can be at the country or region level, the Province, DMA (Designated Market Area) or Metropolitan level, or the city level. <br>Location targeting supports a single country / region only.  If multiple location IDs are specified, they must from the same country / region. <br>You can find the supported locations from [Reach & Frequency](https://ads.tiktok.com/marketing_api/docs?id=1738235311704065). For the list of location IDs, see [Location IDs](https://ads.tiktok.com/marketing_api/docs?id=1739311040498689).|
#|operating_systems | string[] | Operating system. For enum values, see [Enumeration - Audience Operation System](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) |
#|network_types |string[]|Network type. Default value: `unlimited`. For enum values, see [Enumeration - Audience Network Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
#|device_model_ids| string[]| IDs of the device models that you want to target. Currently only brand-level model IDs are supported. When this field is specified, `device_price_ranges` must not be specified. For enum values of device model IDs on different levels, please see [Device Models](https://ads.tiktok.com/marketing_api/docs?id=1737172880570369). <br><p><span style="color:darkred"><b>Note</b></span>: Only active devices (`is_active` = `True` in the response of [/tool/device_model/](https://ads.tiktok.com/marketing_api/docs?id=1737172880570369)) can be used to create ads.|
#|device_price_ranges |number[]|Targeting device price range. 10000 means 1000+. The numbers must be in multiples of 50. <br><b><span style="color: darkred">Important</span></b>: The upper limit you set will be added by 50 and the resulting new number will be used as the actual upper limit for device targeting. The actual upper limit is shown in the ad group settings in TikTok Ads Manager. If you set and get the price range of [0, 250], it actually means [0, 300].|
#|carrier_ids |string[]| Carrier IDs. Use [/tool/carrier/](https://ads.tiktok.com/marketing_api/docs?id=1737168013095938) endpoint to get the enum values for this field. |
#|interest_category_ids |string[]|Interest classification. If the interest is specified, users that do not meet interest target will be excluded during delivery. Do not specify if you wish to target everyone. Use [/tool/target_recommend_tags/](https://ads.tiktok.com/marketing_api/docs?id=1736275204260866) to get a list of recommended interest categories based on your targeting regions and your industries. Use [/tool/interest_category/](https://ads.tiktok.com/marketing_api/docs?id=1737174348712961) endpoint to get the complete list of interest categories.|
#|brand_safety_type|string|Brand safety type for Reach and Frequency ads. Default value: `NO_BRAND_SAFETY`. Enum values: <ul><li>`NO_BRAND_SAFETY`:  To not use any brand safety solution.  Full inventory, which means your ads may appear next to some content featuring mature themes. </li><li>`EXPANDED_INVENTORY`: Use TikTok's brand safety solution. Expanded inventory means that your ads will appear next to content where most inappropriate content has been removed, and that does not contain mature themes. In the next API version, `EXPANDED_INVENTORY` will replace `NO_BRAND_SAFETY` and will be the default brand safety option.</li><li>`STANDARD_INVENTORY`: Use TikTok's brand safety solution. Standard inventory means that ads will appear next to content that is appropriate for most brands. </li><li>`LIMITED_INVENTORY`: Use TikTok's brand safety solution. Limited inventory means that your ads will not appear next to content that contains mature themes.</li><li>`THIRD_PARTY`: Use a third-party brand safety solution.</li></ul> To get the countries and regions that your ads can be deployed to based on your brand safety settings, use the [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) endpoint. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Pre-bid third-party brand safety solutions are currently allowlist-only features. If you would like to access them, please contact your TikTok representative. </li></ul>|
#|contextual_tag_ids |string[]|Contextual tag IDs. You can use [/tool/contextual_tag/get/](https://ads.tiktok.com/marketing_api/docs?id=1747747118654465) to get available contextual tags. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> This is an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li>Only supports `RF_REACH` as objectives (`objective_type`) at the campaign level. </li><li>Not supported when `brand_safety_type` is set to `THIRD_PARTY`.</li><li>Not supported when `feed_type` is set to `TOP_FEED`.</li><li>If you set `rf_campaign_type` to `PULSE` in [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602), then you must enter contextual tags of `PREMIUM` type in this field. In this case, the CPM (`cpm`) in your ad group is fixed. </li></ul>|
|schedule_start_time {Required} | string | Start time (UTC+0): format "YYYY-MM-DD HH:MM:SS". The HH:MM:SS value must be equal to 00:00:00 for the target region. For example, 2020-10-24 05:00:00 is equal to 2020-10-24 00:00:00 in the UTC-5 time zone. <br><p><span style="color:darkred"><b>Note</b></span>: If the targeting locations (`location_ids`) you specify observe Daylight Saving Time, ignore Daylight Saving Time and pass in Standard Time. <br>For example, if you want to start the schedule for the Netherlands at `2022-04-02 00:00:00` local time  (Daylight Saving Time has started, UTC+2 timezone), you need to regard the time zone of the Netherlands as Standard Time Zone (UTC+1) and specify this field as `2022-04-02 23:00:00`. |
| schedule_end_time {Required} | string | End time (UTC+0): format "YYYY-MM-DD HH:MM:SS". The HH:MM:SS value must be equal to 23:59:59 for the target region. For example, 2020-11-11 04:59:59 is equal to 2020-11-10 23:59:59 in the UTC-5 time zone. The time interval between `schedule_end_time` and `schedule_start_time` must not exceed 30 days. <br><p><span style="color:darkred"><b>Note</b></span>: If the targeting locations (`location_ids`) you specify observe Daylight Saving Time, ignore Daylight Saving Time and pass in Standard Time. <br>For example, if you want to end the schedule for the Netherlands at `2022-11-02 23:59:59`  local time (Daylight Saving Time has started, UTC+2 timezone) , you need to regard the time zone of the Netherlands as Standard Time Zone (UTC+1) and specify this field as `2022-11-02 22:59:59`.|
| frequency {Required} | number | Frequency. This parameter, together with the `frequency_schedule` parameter, determines the upper limit for how frequently an ad is shown to users (only for R&F ads).<br> For example, if `frequency` = 2 and `frequency_schedule` = 3, it means the ad will be displayed at most twice every 3 days.<br>The following conditions should be met:<ul><li>1 ≤ `frequency` ≤ `frequency_schedule`</li><li>1 ≤ `frequency_schedule` ≤ min(`schedule_end_time` - `schedule_start_time`, 30). </li><li>The frequency cap determined by `frequency` and `frequency_schedule` does not exceed four times a day.</li></ul>  |
| frequency_schedule {Required} | number | Frequency cycle (in days). <br>This parameter, together with the `frequency` parameter, determines the upper limit for how frequently an ad is displayed to users (only for R&F ads). Please see the description for the `frequency` field. |
|objective_type {Required} | string | The advertising objective. Enum values: `RF_REACH`.|
|cpv_video_duration | string | The target video view duration. <br> Enum values: `SIX_SECONDS`, `TWO_SECONDS`.|
|feed_type |string| Feed type option. Enum values: `STANDARD_FEED`, `TOP_FEED`. `STANDARD_FEED` refers to large inventory with normal CPM price while `TOP_FEED`refers to limited inventory with higher CPM price. |
| rf_purchased_type | string | Buying type. Allowed values: <ul><li>`FIXED_SHOW`: Purchase to achieve a fixed amount of impressions.</li><li>`FIXED_REACH`: Purchase to reach a fixed number of users.</li><li>`FIXED_BUDGET`: Purchase with a fixed budget.</li></ul> |
| budget {+Conditional}| float | Budget. Required when `rf_purchased_type` = `FIXED_BUDGET`. Based on the budget amount that you specify, we will return the estimated values for other fields.  |
| purchased_impression {+Conditional}| number | Impressions to be purchased, <b>in the unit of 1,000</b>. Required when  `rf_purchased_type` = `FIXED_SHOW`. Based on the impression amount that you specify, we will return the estimated values for other fields.  |
| purchased_reach {+Conditional} | number | Number of users reached to be purchased, <b>in the unit of 1,000</b>. Required when  `rf_purchased_type` = `FIXED_REACH`.  Based on the reach amount that you specify, we will return the estimated values for other fields.  |
|rf_campaign_type |string|When `objective_type` is specified as `RF_REACH`, use this field to set the campaign as a TikTok Pulse campaign, then you can get available premium contextual tags. <br><br>Enum values: `STANDARD` (Reach & Frequency campaign), `PULSE` (TikTok Pulse campaign). <br><br>If you set this field as `PULSE`, only one location ID (`location_id`) will be returned.<br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Do not pass in this field when `objective_type` is not specified as `RF_REACH`.</li><li>You need to pass the `location_id` value returned in this endpoint to `location_ids` when you use [/adgroup/rf/create/](https://ads.tiktok.com/marketing_api/docs?id=1738235338194945) to create a TikTok Pulse ad group.</li></ul>|
```

### Example

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/rf/inventory/estimate/?advertiser_id={{advertiser_id}}&objective_type=RF_REACH&audience_info={%22location_ids%22%3A[%22{{location_id}}%22]}&schedule_start_time={{schedule_start_time}}&schedule_end_time={{schedule_end_time}}&frequency={{frequency}}&frequency_schedule={{frequency_schedule}}&rf_purchased_type=FIXED_BUDGET&budget={{budget}}' \
--header 'Access-Token: {{Access-Token}}'
}'
(/code)
```

## Response

``` xtable
| Field {40%}| Data Type{15%} | Description{45%}|
| --- | --- | --- |
| code | number | Return code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| message | string | Return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
| request_id | string | The log id of a request, which uniquely identifies the request. |
| data| object | Returned data.  |
#| default_result | object | Estimated inventory result. <br> If you have specified a fixed value for `purchased_impression`, `budget`or `purchased_reach`  in request, depending on the fixed value you have specified, we will return the corresponding predicted values in the `default_result` object in the response. <br>For example, if you set `rf_purchased_type` to `FIXED_SHOW` and specify a fixed value in `purchased_impression`, then based on the impressions you set, we will calculate and return the estimated values for `budget`, `cpm`, `reach` and `average_frequency` in `default_result`. |
##| base_info | object | Basic information. |
###| budget | float | Total budget. |
###| cpm | float | CPM (Cost per mille). <br> Formula: `cpm` = `budget` / `impression`. |
###| impression | number | Total impressions, <b>in the unit of 1,000</b>.<br>  For example, the value of 110 for this field represents 110,000 impressions.|
###| reach | number | Number of users reached, <b>in the unit of 1,000</b>. <br> For example, the value of 110 for this field represents 110,000 users reached.|
###| max_reach| number| Number of total bookable reach based on the specified feed type, targeting settings and campaign duration, <b>in the unit of 1,000</b>.<br><br>For example, the value of 200 in this field represents an available inventory of 200,000 users.|
###| average_frequency | float | Formula: impressions per person (`average_frequency`) = total impressions  (`impression`)/ numbers of users reached  (`reach`). |
##| daily_cost | object | Daily spend. |
###| cost | float | Spend for the day. |
###| date | string | Date.<br>Format: `"YYYY-MM-DD"`.<br>Example: `"2024-01-01"`. |
##| frequency_per_person | object | Distribution of impressions per person.<br><p><span style="color:darkred"><b>Note</b></span>: When the frequency cap determined by `frequency` and `frequency_schedule` in the request exceeds four times a day, the value for the `frequency_per_person` will remain unchanged. |
###| frequency | number | Impressions per person. |
###| percentage | float | The percentage of users for the `frequency`.<br>For example, if `frequency` is `1` and `percentage` is `0.42304230423042305`, then around 42.3% users will be shown the ad once.|
#| results | object[] |Estimated inventory results for different budgets. In each result, the estimated impression, CPM, reach, frequency, daily cost, and frequency per person will be provided for the budget.<br>The data structure for this object is the same as that of `default_result`. |
##| base_info | object | Basic information. |
###| budget | float | Total budget. |
###| cpm | float | CPM (Cost per mille). <br>Formula: `cpm` = `budget` / `impression`.  |
###| impression | number | Total impressions, <b>in the unit of 1,000</b>. <br>For example, the value of 110 for this field represents 110,000 impressions.|
###| reach | number | Number of users reached, <b>in the unit of 1,000</b>.<br>For example, the value of 110 for this field represents 110,000 users reached. |
###| average_frequency | float | Formula: impressions per person (`average_frequency`) = total impressions  (`impression`)/ numbers of users reached  (`reach`). |
##| daily_cost | object | Daily spend. |
###| cost | float | Spend for the day. |
###| date | string | Date. <br>Format: `"YYYY-MM-DD"`.<br>Example: `"2024-01-01"`. |
##| frequency_per_person | object[] | Distribution of impressions per person. <br><p><span style="color:darkred"><b>Note</b></span>: When the frequency cap determined by `frequency` and `frequency_schedule` in the request exceeds four times a day, the value for the `frequency_per_person` will remain unchanged.|
###| frequency | number | Impressions per person. |
###| percentage | float | The percentage of users for the frequency.<br>For example, if `frequency` is `1` and `percentage` is `0.42304230423042305`, then around 42.3% users will be shown the ad once. |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "default_result": {
            "daily_cost": [
                {
                    "date": "2020-08-07",
                    "cost": 3587.5169107559
                },
                {
                    "date": "2020-08-08",
                    "cost": 3565.1767601342
                },
                {
                    "date": "2020-08-09",
                    "cost": 3512.4961871912
                },
                {
                    "date": "2020-08-10",
                    "cost": 3537.1785786238
                }
            ],
            "frequency_per_person": [
                {
                    "percentage": 0.28,
                    "frequency": 1
                },
                {
                    "percentage": 0.72,
                    "frequency": 2
                }
            ],
            "base_info": {
                "impression": 5157,
                "average_frequency": 1.72,
                "reach": 2991,
				"max_reach": 17291,
                "cpm": 2.75,
                "budget": 14202.37
            }
        },
        "results": [
            {
                "daily_cost": [
                    {
                        "date": "2020-08-07",
                        "cost": 163.09
                    },
                    {
                        "date": "2020-08-08",
                        "cost": 163.27
                    },
                    {
                        "date": "2020-08-09",
                        "cost": 168.63
                    },
                    {
                        "date": "2020-08-10",
                        "cost": 164.8
                    }
                ],
                "frequency_per_person": [
                    {
                        "percentage": 0.2480487252,
                        "frequency": 1
                    },
                    {
                        "percentage": 0.7519512748,
                        "frequency": 2
                    }
                ],
                "base_info": {
                    "impression": 418,
                    "average_frequency": 1.75,
                    "reach": 239,
                    "cpm": 1.58,
                    "budget": 659.8
                }
           }
       ]
   }
(/code);
```


