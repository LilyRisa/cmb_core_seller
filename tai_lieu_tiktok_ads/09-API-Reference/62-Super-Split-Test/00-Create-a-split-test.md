---
title: "Create a split test"
doc_id: 1742666471475201
path: "API Reference / Super Split Test / Create a split test"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a split test

Use this endpoint to create a split test group.  

Before you start, see [here](https://ads.tiktok.com/marketing_api/docs?id=1742668394531842&rid=hsya9xzyz74) to find out the prerequisites of creating a split test and [here](https://ads.tiktok.com/marketing_api/docs?id=1742668948278274&rid=hsya9xzyz74) to find out how to create a split test. 

Currently, we support split test at ad group level and campaign level. For ad group level split test, the total budget will be evenly distributed to each group. For campaign level split test, the budget will follow the existing budget amount set for each campaign.

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/split_test/create/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| split_test_level| string| Split test level. <br><br>Enum values: <ul><li>`ADGROUP`(Ad group level split test.)</li><li>`CAMPAIGN`(Campaign level split test.)</li></ul>Default value: `ADGROUP`.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Campaign-level split tests are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Comparing two ad groups from different campaigns in a split test is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. The campaigns can have different advertising objectives.</li></ul>|
| object_ids | string[] | `adgroup_ids` bound to this split-test. <br><ul><li>For <b>ad group level</b> split test, input either this field or `cells`.</li><li>For <b>campaign level</b> split test, input `cells` instead of this field.</li></ul>|
|cells {+Conditional}| object[]| Required when `split_test_level` is set to `CAMPAIGN`.<br><br>List of cells in split test. The length must be 2.|
#| object_ids | string[] | If you select `ADGROUP` in `split_test_level`, pass `adgroup_id` to this field. The length of each cell must be 1.<br>If you select `CAMPAIGN` in `split_test_level`, pass `campaign_id` to this field. Length range: [1,10].|
| test_variable | string | Split test variable. It will NOT affect your test results. <br><br>Enum values: <ul><li>`BIDDING_OPTIMIZATION`: Set different optimization values for bidding & optimization fields to compare how optimization methods impact your ad performance. </li><li>`TARGTING`: Set different targeting values at the ad group level for comparing how effective your ad groups are at reaching different audiences or demographics. </li><li>`CREATIVE`: Set different creatives to compare how different creatives impact your ad performance. </li><li>`CUSTOM`: Set multiple variables to compare how these variables impact your ad performance. This enum value is only available for <b>campaign level</b> split test.</li></ul>Default value: <ul><li>For <b>ad group level</b> split test, the default value is `BIDDING_OPTIMIZATION`. </li><li>For <b>campaign level</b> split test, the default value is `CUSTOM`.</li></ul><br><p><span style="color:darkred"><b>Note:</b></span> The `test_variable` cannot be updated once specified. |
| key_metric | string | The key metric you want to monitor. <br><br>Enum values: <ul><li>`UNSET`: Unset.</li><li>`CONVERT_RATE`: CVR (Conversion rate).</li><li> `CLICK_THROUGH_RATE`: Click through rate (CTR).</li><li>`COST_PER_SHOW`: Cost per mille (CPM). </li><li> `COST_PER_CONVERT`: Cost per conversion. </li><li> `COST_PER_REACH`: Cost per reach. </li><li> `COST_PER_CLICK`: Cost per click. </li><li> `COST_PER_FOCUSED_VIEW`: Cost per focused view. </li><li>`FOCUSED_VIEW_RATE`: Focused view rate. </li><li>`COMPLETE_PAYMENT_ROAS`: Complete Payment ROAS.</li><li>`COST_PER_LANDING_PAGE_VIEW`: Cost per landing page view.</li><li>`LANDING_PAGE_VIEW_RATE`: Landing page view rate.</li><li>`CLICK`(<span style="color:red">deprecated</span>): Click.</li><li>`REACH`(<span style="color:red">deprecated</span>): Reach.</li><li> `SHOW`(<span style="color:red">deprecated</span>): Impression. </li><li> `CONVERT`(<span style="color:red">deprecated</span>): Conversion. </li><li> `COST`(<span style="color:red">deprecated</span>): Cost.</li></ul> <br>See [Super Split Test - Supported optimization goals](https://ads.tiktok.com/marketing_api/docs?id=1742668394531842&rid=2xb5zgugmdy) to find out the key metrics  supported under different optimization goals. <br>If no value is specified for this field, the following default values will be used: <ul><li>For <b>ad group level</b> split test, if both ad groups use `REACH` optimization goal, the default key metric will be `COST_PER_REACH`. Otherwise, the default key metric will be `COST_PER_SHOW`. </li><li>For <b>campaign level</b> split test, the default key metric is `UNSET`.</li></ul>|
| budget {+Conditional} | float | <ul><li>Required when `split_test_level` is set to `ADGROUP`. </li><li>Do not specify this field when `split_test_level` is set to `CAMPAIGN`. Otherwise, an error will occur.</li></ul><br>The total split test budget. <br><br><ul><li>For <b>ad group level</b> split test, the budget will be distributed to each group evenly. This budget specified here will overwrite the existing budgets in the two ad groups. If you want to change the total budget after the test has started, we recommend you update the budgets in the two ad groups to the same value. </li><li>For <b>campaign level</b> split test, the budget will follow the existing budget amount set for each campaign.</li></ul>|
| start_time {Required} | string | The start time of this split test, in the format of YYYY-MM-DD HH:MM:SS (UTC+0). Example: 2022-01-01 00:00:00. <br><br><p><span style="color:darkred"><b>Note:</b></span><ul><li>The time of campaign level split test can only be set to hour level. Thus, any MM and SS input will default to 00. </li><li>The start time set for campaign level split test will <b>not</b> overwrite the start time of campaigns and ad groups in this test.</li></ul>|
| end_time {Required} | string | The end time of this split test, in the format of YYYY-MM-DD HH:MM:SS (UTC+0). Example: 2022-01-01 00:00:00. <br><br><p><span style="color:darkred"><b>Note:</b></span><ul><li>The time of campaign level split test can only be set to hour level. Thus, any MM and SS input will default to 00. </li><li>The end time set for campaign level split test will <b>not</b> overwrite the end time of campaigns and ad groups in this test.</li></ul>|
```

### Example

```xcodeblock
(code curl http)
curl --location 'https://business-api.tiktok.com/open_api/v1.3/split_test/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "start_time": "2024-03-09 02:42:13",
    "end_time": "2024-03-10 02:42:13",
    "object_ids": [
        "{{object_ids}}",
        "{{object_ids}}"
    ],
    "key_metric": "FOCUSED_VIEW_RATE",
    "test_variable": "BIDDING_OPTIMIZATION",
    "budget": 100
}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#|split_test_group_id|string|Split test group ID.|
|request_id|string|The log id of a request, which uniquely identifies the request. |
```


### Example

```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "split_test_group_id": "{{split_test_group_id}}"
    }
}
(/code)
```


