---
title: "Create an asynchronous report task"
doc_id: 1740302766489602
path: "API Reference / Reporting / Create an asynchronous report task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an asynchronous report task

Use this endpoint to create an asynchronous report task. 

See [Run an asynchronous report](https://ads.tiktok.com/marketing_api/docs?id=1738864800380930) to learn about how to run an asynchronous report. 

You can use different endpoints to create a standard (synchronous) report and an asynchrounous report. To find out the supported dimensions, metrics, and use cases for different report types, see [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186). Also, you need to use `POST` method for asynchronous reports and `GET` method for standard (synchronous) reports.

## Notes
See the table below to learn about the notes about rate limit, supported report types, query time range, filtering, compatibility between metrics and dimensions, CHUNK mode, pagination, validity period, data, and more.

``` xtable
| Type{30%} | Details{70%} |
|---|---|
| Rate limit | Due to computing resource limits, the rate limit for each app is 1 QPS. Please do not create a large number of asynchronous report tasks in a short period of time.<br>Furthermore, the user associated with your access token (`Access-Token`) has a separate limit of 500 asynchronous report creations per ad account every hour. |
| Supported report types | Playable ads reports are not supported in asynchronous reports. |
| Query time range | There are no limits on starting times and ending times for asynchronous reports. |
| Filtering | <ul><li>Asynchronous reports only support filtering by `campaign_ids`, `adgroup_ids`, `ad_ids` or `country_code`. You can filter the data by 20000 IDs at most. </li><li>If you want to use `country_code` as your grouping filter, then please specify `country_code` in the `dimensions` field of the request. Note that `country_code` works with basic report (sync and async), audience report (sync and async), and playable ad report (sync only). </li></ul>|
| Compatibility between metrics and dimensions | <ul><li>Querying UV metrics with audience dimensions is not supported in asynchronous reports. Please query UV metrics without audience dimensions or audience dimensions without UV metrics in asynchronous reports instead. <ul><li>Audience dimensions include `"gender"`, `"age"`, `"country_code"`, `"ac"`, `"language"`, `"platform"`, `"interest_category"`, `"placement"`, `"contextual_tag"`. </li><li>UV metrics include `"average_video_play_per_user"`, `"frequency"`, `"cost_per_1000_reached"`, `"real_time_result"`, `"real_time_result_rate"`, `"real_time_cost_per_result"`, `"result"`, `"result_rate"`, `"cost_per_result"`, `"reach"`, `"live_play_count"`. </li></ul></li><li>The dimension and metric combinations that are supported may vary between synchronous and asynchronous reports. Therefore, a valid dimension and metric combination in synchronous reports may not be supported in asynchronous reports.</li></ul>|
| CHUNK mode | CHUNK mode only supports synchronous basic reports. |
| Pagination | Paging is not supported. |
| Validity period | An asynchronous report task is valid for 30 days, after which you cannot query for its status or download its output. |
| Data | Data for asynchronous reports is refreshed once every several hours. The data for the current day may be inaccurate. |
| Known issue | Currently, asynchronous reports return the dimension `country_code` (e.g. `US`) as `country_name`(e.g. `United States`). This is a known issue and will be fixed in future release. |
```

## Comparing v1.2 and v1.3 

 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Endpoint path | /v1.2/reports/integrated/get/ (POST method) | /v1.3/report/task/create/ (POST method) |
| Request parameter name | `filters` | `filtering` |
| Request parameter data type | `advertiser_id`: number | `advertiser_id`: string |
| Request parameter deprecated in v1.3 | / | `lifetime` (replaced by the new request parameter `query_lifetime`) |
| New request parameters | / | `output_format`<br> `file_name`<br>`query_lifetime` <br>`advertiser_ids`<br>`enable_report_title_translation` |
| Dimensions  | / | See the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) to find out the dimension changes in each report type.   |
|Metrics  | / | See the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) to find out the metric changes in each report type.   |
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/report/task/create/

**Method** POST

**Header**

```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
| Field{35%} | Data Type{15%} | Description{50%} |
|---|---|---|
| advertiser_id {+Conditional} | string | Either `advertiser_id` or `advertiser_ids` has to be set. If you pass in both `advertiser_id` and `advertiser_ids`, `advertiser_id` will be ignored. <br><br>Advertiser ID.|
| advertiser_ids<br>{+Conditional} | string[] | Either `advertiser_id` or `advertiser_ids` has to be set. If you pass in both `advertiser_id` and `advertiser_ids`, `advertiser_id` will be ignored.<br><br>A list of advertiser IDs. <br>Max size: 5.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li> This field is only valid when `report_type` is `BASIC` or `AUDIENCE`.</li><li>  The advertisers specified in `advertiser_ids` should all belong to the same TikTok for Business user. Otherwise, an error will occur. Use [/oauth2/advertiser/get/](https://business-api.tiktok.com/portal/docs?id=1738455508553729) to retrieve a list of advertiser IDs that belong to the same TikTok for Business user.</li><li> If you specify `start_date` and `end_date` at the same time, the data will be pulled according to the timezone of each ad account.</li><li> When you pass multiple advertiser IDs, you can specify up to two ID dimensions (`advertiser_id` + another ID dimension) in dimensions. However, many metrics do not support the `advertiser_id` dimension. Also, it's recommended that you include `currency` in `metrics`, because the ad accounts may have different currencies.</li></ul>|
| service_type | string | Ad service type. <br><br> Enum values:<ul><li>`AUCTION`: auction ads, or both auction ads and reservation ads.</li><li>`RESERVATION` (<span style="color:Red">deprecated</span>) : reservation ads. </li></ul> Default value: `AUCTION`.<br><br><p><span style="color:darkred"><b>Note</b></span>: To retrieve the reporting data of TopView ads, use the [basic reports](https://business-api.tiktok.com/portal/docs?id=1738864915188737) or [audience reports](https://business-api.tiktok.com/portal/docs?id=1738864928947201) and specify the value `RESERVATION_TOP_VIEW` for the `buying_type` [filter](https://business-api.tiktok.com/portal/docs?id=1751443975608321). |
| report_type {Required} | string | Report type. <br><br> Enum values: `BASIC` (basic report), `AUDIENCE` (audience report), `PLAYABLE_MATERIAL` (playable ads report), `CATALOG` (DSA report).  <br><br>When `service_type` is `AUCTION`, `BASIC`, `AUDIENCE`, `PLAYABLE_MATERIAL`, and `CATALOG` reports are all supported.<br><br><p><span style="color:darkred"><b>Note</b></span>: You can retrieve the creative level data for Upgraded Smart+ Ads when `report_type` is `BASIC`, `AUDIENCE`, or `CATALOG`.</p> |
| data_level {+Conditional}| string | The data level that you'd like to query in reports. Required when `report_type` is `BASIC`,`AUDIENCE` or `CATALOG`. <br><br> Enum values:<ul><li>`AUCTION_AD`: auction ads or both auction ads and reservation ads, ad level.</li><li>`AUCTION_ADGROUP`: auction ads or both auction ads and reservation ads, ad group level. </li><li>`AUCTION_CAMPAIGN`: auction ads or both auction ads and reservation ads, campaign level.</li><li>`AUCTION_ADVERTISER`: auction ads or both auction ads and reservation ads, advertiser level. </li><li>`RESERVATION_AD`(<span style="color:Red">deprecated</span>): reservation ads, ad level. </li><li>`RESERVATION_ADGROUP`(<span style="color:Red">deprecated</span>): reservation ads, ad group level. </li><li>`RESERVATION_CAMPAIGN`(<span style="color:Red">deprecated</span>): reservation ads, campaign level. </li><li>`RESERVATION_ADVERTISER`(<span style="color:Red">deprecated</span>): reservation ads, advertiser level. </li></ul> |
| dimensions {Required} | string[] |  Grouping conditions. For example: `&#91;"campaign_id", "stat_time_day"&#93;` indicates that both `campaign_id` and `stat_time_day` (days) are grouped. <br><br>Different report types support different dimensions. <ul><li>Supported dimensions in [basic reports](https://business-api.tiktok.com/portal/docs?id=1751443956638721)</li><li>Supported dimensions in [audience reports](https://business-api.tiktok.com/portal/docs?id=1751454103714818)</li><li>Supported dimensions in [playable ad reports](https://business-api.tiktok.com/portal/docs?id=1751617879104514)</li><li>Supported dimensions in [DSA reports](https://business-api.tiktok.com/portal/docs?id=1751617879104514)</li><li> Supported dimensions in [Business Center reports](https://business-api.tiktok.com/portal/docs?id=1775747465089026)</li></ul><br><p><span style="color:darkred"><b>Note</b></span> : <ul><li>Different report types support different dimensions. For supported dimensions for each report type, see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186). </li><li>If your query `dimensions` contain more than xx_id and there is no ads reporting within the query time range, an empty list will be returned.</li><li>The dimension and metric combinations that are supported may vary between synchronous and asynchronous reports. Therefore, a valid dimension and metric combination in synchronous reports may not be supported in asynchronous reports.</li><li>The dimension `ad_id_v2` is available. Note that:<ul><li>You need to ensure the `report_type` is `BASIC`, `AUDIENCE`, or `CATALOG`.</li><li>You cannot use it together with the dimension `ad_id` or the filter `ad_ids`.</li><li>The types of data you can get include:<ul><li>the ad level data for Manual Ads.</li><li>the ad level data for Smart+ Campaigns.</li><li>the ad level data for Upgraded Smart+ Ads.</li></ul></li></ul></li></ul></ul>|
| metrics | string[] | Metrics to query. <br><br>Different report types support different metrics.  For supported metrics for each report type, see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186). <br><br>Default value: `["spend", "impressions"]`. |
| start_date {+Conditional} | string | This field is required when `query_lifetime` is `false` or not specified.<br><br>Query start date (closed interval) in the format of `YYYY-MM-DD`. The date is based on the ad account time zone. <br><br><p><span style="color:darkred"><b>Note</b></span>: Asynchronous reports have no time range limitations.|
| end_date {+Conditional} | string | This field is required when `query_lifetime` is `false` or not specified.<br><br>Query end date (closed interval) in the format of `YYYY-MM-DD`. The date is based on the ad account time zone.<br><br><p><span style="color:darkred"><b>Note</b></span>: Asynchronous reports have no time range limitations.|
|query_lifetime | boolean  |Whether to request the lifetime metrics. <br><br> Default value: `false`. <br><br>If `query_lifetime` = `true`, the `start_date` and `end_date` parameters will be ignored. The lifetime metric name is the same as the normal one. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Breakdown by time or audience is not supported when querying for lifetime metrics.</li><li>Breakdown by location code (`country_code`) is not supported when querying for lifetime metrics.</li><li>DSA reports do not support lifetime metrics. Besides, Audience reports do not support lifetime metrics at the time, and will support lifetime metrics in the first half of 2023.</li></ul>|
| order_field | string | Sorting field. <br><br>All supported metrics (excluding attribute metrics) support sorting. Not sorting by default.|
| order_type | string | Sorting order. <br><br>Enum values: `ASC`, `DESC`.<br>Default value: `DESC`.|
| enable_report_title_translation | boolean | Whether to enable the translation of the title row of the asynchronous report (including dimension names and metric names).<br>For instance, if this field is set to `false`, a report title can be `ad_id` (untranslated title) rather than `AD ID` (translated title).<br>Default value: `true`.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>This field is only valid when `report_type` is `BASIC` or `AUDIENCE`.</li><li>Starting November 15th, 2024, the translation of certain metrics in the title row of async reports will be updated to provide a better user experience. This change will not affect the metric field names themselves. To minimize potential disruptions, we strongly recommend setting `enable_report_title_translation` to `false` in your code. This will eliminate dependencies on metric translations, which are subject to periodic updates. While these translations are provided to help map metrics to their corresponding names in TikTok Ads Manager, relying on them in your code may lead to breaking changes in the future. By disabling title row  translation, you can ensure more stable and predictable behavior in your integration.</li></ul>  |
| output_format | string | Format of output. <br><br>Enum values: `CSV_STRING`, `CSV_DOWNLOAD`, `XLSX_DOWNLOAD`.<br> Default value: `CSV_STRING`.|
|file_name | string | Customized download file name, which is optional to pass when `output_format` is `CSV_DOWNLOAD` or  `XLSX_DOWNLOAD`. The maximum length is 255 characters. The file name needs to exclude the following special characters: ['/', '\t', '\b', '@', '#', '$', '%', '^', '&', '*', '(', ')', '[', ']']. |
| filtering | object[] | Filtering conditions. Supported filtering conditions vary based on `service_type` and `data_level`. For filters supported in different report types, refer to the articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186). <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When you pass in `filtering`, please make sure you specify the fields `field_name`, `filter_type`, and `filter_value` at the same time as an object in a list.</li><li>If you want to use `country_code` as your grouping filter, then please specify `country_code` in the `dimensions` field of the request. Note that `country_code` works with basic report (sync and async), audience report (sync and async), and playable ad report (sync only).</li><li>In asynchronous mode for the report types of basic reports, audience reports, and DSA reports, the only supported filter fields are `campaign_ids`, `adgroup_ids`, and `ad_ids`.</li></ul> |
# | field_name| string | Filter field name.<br><br>The filter `ad_ids_v2` is available. Note that:<ul><li>You cannot use it together with the dimension `ad_id` or the filter `ad_ids`.</li><li>The types of ID you can filter include:<ul><li>The ID of a Manual Ad.</li><li>The ID of an ad within a Smart+ Campaign.</li><li>The ID of an Upgraded Smart+ Ad.</li></ul></li><li>You need to set `data_level` to `AUCTION_AD`and include `ad_id_v2` in `dimensions`.</li><li>You can specify up to 100 values when using this filter.</li></ul>|
# | filter_type | string | Filter type. <br><br>Enum values: <ul><li>`IN`: Contains. When the filter type is this item, the filtered value needs to be a valid JSON array character string.</li><li>`MATCH`: Fuzzy matching, equivalent to `like` operations in MySQL.</li><li>`GREATER_EQUAL`: Greater than or equal to.</li><li>`GREATER_THAN`: Greater than.</li><li>`LOWER_EQUAL`: Less than or equal to.</li><li>`LOWER_THAN`: Less than.</li><li>`BETWEEN`: Between. When the filter type is this item, the filtered value needs to be a valid JSON array of 2-element string.</li></ul><br>When `field_name` is `ad_id_v2`, set `filter_type` to `IN`. |
# | filter_value | string | The value to filter. <br>  When `filter_type` is `IN`, `filter_value` needs to be a valid JSON array character string. |
```

### Example 
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/report/task/create/' \
--header 'Access-Token: {{access-token}}' \
--data-raw '{
    "advertiser_id": "{{advertiser_id}}",
    "data_level":"AUCTION_AD",
    "report_type":"BASIC",
    "dimensions": ["ad_id"],
    "metrics": ["clicks"],
    "start_date": "{{start_date}}",
    "end_date": "{{end_date}}",
    "output_format": "CSV_DOWNLOAD",
    "file_name": "{{file_name}}",
    "filtering": [
        {
        "field_name":"ad_ids",
        "filter_type":"IN",
        "filter_value": "[\"{{ad_id}}\",\"{{ad_id}}\"]"
        }
    ]
}'
(/code)
```

## Response

```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data.  |
#|task_id |string| The ID of the asynchronous report task. |
|request_id |string|The unique ID of the request.  |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "task_id": "{{task_id}}"
    },
    "request_id": "{{request_id}}"
}
(/code);
```
