---
title: "Get the results of a split test"
doc_id: 1742666557045761
path: "API Reference / Super Split Test / Get the results of a split test"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the results of a split test

Use this endpoint at least 24 hours after the test ends to get test results, including p-values for each metric.

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/split_test/result/get/

**Method** GET

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
|advertiser_id {Required} | string | Advertiser ID.  |
|split_test_group_id {Required} | string | Split test group ID. |
```

### Example

```xcodeblock
(code curl http)
curl --location 'https://business-api.tiktok.com/open_api/v1.3/split_test/result/get/?advertiser_id={{advertiser_id}}&split_test_group_id={{split_test_group_id}}' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```


## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#| split_test_status | string | Status of the split test. <br>Enum values: <ul><li> `UNKNOWN`: The status of the split test is unavailable. </li><li> `NOT_STARTED`: The split test has not started because the `start_time` of the split test has not arrived yet.</li><li> `IN_PROGRESS`: The split test is running.</li><li> `REPORTING_GENERATING`: The `end_time` of the split test has passed and a report is being generated. </li><li> `COMPLETED`: The split test either ends automatically, or is manually stopped via [/split_test/end/](https://ads.tiktok.com/marketing_api/docs?id=1742666522399746).</li><li>`CANCELLED`: The split test was canceled before the `start_time`. This enum value is only available for <b>campaign level</b> split test.</li></ul> |
#| split_test_level | string |Split test level. <br>Enum values: <ul><li>`ADGROUP`(Ad group level split test.)</li><li>`CAMPAIGN`(Campaign level split test.)</li></ul>|
#| object_info | object[] | Information about the two ad groups that are associated with the split test. |
##| adgroup_id | string | Ad group ID. |
##| adgroup_name | string | Ad group name. |
##| optimization_goal | string | Optimization goal of the ad group. <br>For enum values, see [Enumeration - Optimization Goal](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Optimization%20Goal). |
#| cells | object[] |Cell list of the split test.|
##| object_ids | string[] |List of `campaign_id` or `adgroup_id` in the cell that are associated with the split test.|
#| test_variable| string | Split test variable. <br>Enum values: <ul><li>`BIDDING_OPTIMIZATION`: Set different optimization values for bidding & optimization fields to compare how optimization methods impact your ad performance. </li><li>`TARGTING`: Set different targeting values at the ad group level for comparing how effective your ad groups are at reaching different audiences or demographics. </li><li>`CREATIVE`: Set different creatives to compare how different creatives impact your ad performance.</li><li>`CUSTOM`: Set multiple variables to compare how these variables impact your ad performance. This enum value is only available for <b>campaign level</b> split test.</li></ul>|
#| key_metric | string | The key metric when you created this split test. <br>If no value is specified for this field, the following default values will be used: <ul><li>For <b>ad group</b> level split test, if both ad groups use `REACH` optimization goal, the default key metric will be `COST_PER_REACH`. Otherwise, the default key metric will be `COST_PER_SHOW`. </li><li>For <b>campaign level</b> split test, the default key metric is `UNSET`.</li></ul> |
#| start_time | string | The start time of this split test, in the format of `YYYY-MM-DD HH:MM:SS` (ad account time zone).<br>Example: `2022-01-01 00:00:00`. |
#| end_time | string | The end time of this split test, in the format of `YYYY-MM-DD HH:MM:SS` (ad account time zone). <br>Example: `2022-01-01 00:00:00`. |
#| p_value_info | object[] | Test result information. |
##| metric | string | Metric name.<br><br> <ul><li>`UNSET`: Unset.</li><li>`CONVERT_RATE`: CVR (Conversion rate).</li><li> `CLICK_THROUGH_RATE`: Click through rate (CTR).</li><li>`COST_PER_SHOW`: Cost per mille (CPM). </li><li> `COST_PER_CONVERT`: Cost per conversion. </li><li> `COST_PER_REACH`: Cost per reach. </li><li> `COST_PER_CLICK`: Cost per click. </li><li> `COST_PER_FOCUSED_VIEW`: Cost per focused view. </li><li>`FOCUSED_VIEW_RATE`: Focused view rate. </li><li>`COMPLETE_PAYMENT_ROAS`: Complete Payment ROAS.</li><li>`COST_PER_LANDING_PAGE_VIEW`: Cost per landing page view.</li><li>`LANDING_PAGE_VIEW_RATE`: Landing page view rate.</li><li>`CLICK`: Click.</li><li>`REACH`: Reach.</li><li> `SHOW`: Impression. </li><li> `CONVERT`: Conversion. </li><li> `COST`: Cost.</li></ul> |
##| p_value | float | P value of each metric. There will be a winning ad group or campaign when the p value <= 0.1. |
|request_id|string|The log id of the request, which uniquely identifies a request. |
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
        "key_metric": "FOCUSED_VIEW_RATE",
        "object_info": [
            {
                "optimization_goal": "FOCUSED_VIEW_RATE",
                "adgroup_name": "{{adgroup_name}}",
                "adgroup_id": "{{adgroup_id}}"
            },
            {
                "optimization_goal": "ENGAGED_VIEW_FIFTEEN",
                "adgroup_name": "{{adgroup_name}}",
                "adgroup_id": "{{adgroup_id}}"
            }
        ],
        "start_time": "{{start_time}}",
        "test_variable": "BIDDING_OPTIMIZATION",
        "p_value_info": [],
        "end_time": "{{end_time}}",
        "split_test_status": "NOT_STARTED"
    }
}
(/code)
```
