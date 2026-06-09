---
title: "Run a synchronous report"
doc_id: 1740302848100353
path: "API Reference / Reporting / Run a synchronous report"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Run a synchronous report

Use this endpoint to create a synchronous report task. 

This endpoint can currently return the reporting data of up to 20,000 advertisements. If your number of advertisements exceeds 20,000, please use `campaign_ids` / `adgroup_ids` / `ad_ids` as a filter to obtain the reporting data of all advertisements in batches. If you use `campaign_ids` / `adgroup_ids` / `ad_ids` as a filter, you can pass in up to 100 IDs at a time.

- To learn about how to run a synchronous report, see [Run a synchronous report](https://ads.tiktok.com/marketing_api/docs?id=1738864778664961). 
- To find out the supported dimensions, metrics, and use cases for different report types, see [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186). 
- To learn about the relationship between advertising service type, report type and report data level, see [Overview-Data level](https://ads.tiktok.com/marketing_api/docs?id=1751087777884161&rid=h0qg6e2ivzd).
- To find out answers to common questions regarding reporting, see [FAQ](https://ads.tiktok.com/marketing_api/docs?id=1738864850353154).

><span style="color:DodgerBlue">**Note**</span><br>
>If you compare the synchronous report with a report downloaded from TTAM (TikTok Ads Manager）, you will discover data inconsistency because the downloaded report should be compared with its counterpart, an asynchronous report, rather than a synchronous report. Both the TTAM downloaded report and the API asynchronous report do not implement the 20,000-truncation for ads.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 
```xtable
|Changes {30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Endpoint path | /v1.2/reports/integrated/get/| /v1.3/report/integrated/get/|
| Request parameter name | `filters` | `filtering` |
| Request parameter data type | `advertiser_id`: number | `advertiser_id`: string |
| Request parameter deprecated in v1.3| / | `lifetime`(replaced by the new request parameter `query_lifetime`) |
| New request parameter| /| `query_lifetime`<br>`bc_id`<br>`enable_total_metrics`<br>`advertiser_ids`|
| Response parameter data type | `advertiser_id`: number<br> `campaign_id`: number <br>`adgroup_id`: number<br> `ad_id`: number | `advertiser_id`: string<br> `campaign_id`: string <br>`adgroup_id`: string<br> `ad_id`: string |
| New response parameter | / | `X-Tt-Ads-Throttle`: string<br> `contextual_tag`: string |
| Dimensions  | / | See the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) to find out the dimension changes in each report type.   |
|Metrics  | / | See the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) to find out the metric changes in each report type.   |
```


## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/>

**Method** GET

**Header**

```xtable
| Field {25%} | Data Type{15%} | Description {60%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
| Field {25%} | Data Type{15%} | Description {60%}|
|---|---|---|
| advertiser_id {+Conditional} | string | You need to pass in either `advertiser_id`/`advertiser_ids` or `bc_id`.<ul><li>When `report_type` is set to `BASIC` or `AUDIENCE`, pass in `advertiser_id` or `advertiser_ids`. If you pass in both `advertiser_id` and `advertiser_ids`, `advertiser_id` will be ignored.</li><li>When `report_type` is set to `PLAYABLE_MATERIAL`, `CATALOG`, or `TT_SHOP`, pass in `advertiser_id`.</li><li>When `report_type` is set to `BC`, pass in `bc_id`.</li></ul><br>Advertiser ID. |
|advertiser_ids {+conditional}| string[] |When `report_type` is set to `BASIC` or `AUDIENCE`, you need to pass in either `advertiser_id` or `advertiser_ids`. If you pass in both `advertiser_id` and `advertiser_ids`, `advertiser_id` will be ignored. <br>Valid when `report_type` is `BASIC` or `AUDIENCE`.<br> <br> A list of advertiser IDs. <br> Max size: 5.  <br><br>Please be aware of the following information when working with this field: <ul><li>When `advertiser_ids` is used, we will return up to 20,000 IDs in response. The cutoff logic for IDs is based on the creation time of campaigns, ad groups, or ads in descending order, but we cannot guarantee that each advertiser will have an evenly distributed number of IDs.</li><li>When `advertiser_ids` is used, the returned metrics data in the response will include `currency`, `timezone`, and `advertiser_id`. </li><ul><li>Different advertisers might have different time zones. You can use `multi_adv_report_in_utc_time` to determine whether the data returned should be set to the UTC+0 timezone for all advertisers or set to the local timezone for each respective advertiser. </li><li>Different advertisers might have different currencies, and we will return the corresponding currency for each advertiser in the returned `metrics`. These metric data will be presented without a decimal point. For instance, a spend of "100 USD" will be displayed as 100 without any decimal indicator such as ".000".</li></ul><li>If you want to view the total added-up metrics for all advertiser IDs, enable `enable_total_metrics` in your request. The returned `total_metrics` in the response will include `currency`, `timezone`, and `advertiser_id`, but all with a value of `-`.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>If you use `advertiser_ids` but do not include any ID dimensions in the dimensions field, we will return `0` as the value for `currency`, `timezone`, and `advertiser_id` and `-` as the value of <b>cost-related</b> metrics, such as `spend`, in the `metrics` object of the response.</li><li>The advertisers specified in `advertiser_ids` should all belong to the same TikTok for Business user. Otherwise, an error will occur. Use [/oauth2/advertiser/get/](https://business-api.tiktok.com/portal/docs?id=1738455508553729) to retrieve a list of advertiser IDs that belong to the same TikTok for Business user.|
| bc_id{+Conditional} | string | You need to pass in either `advertiser_id`/`advertiser_ids` or `bc_id`. <ul><li>When `report_type` is set to `BASIC` or `AUDIENCE`,, pass in `advertiser_id`or `advertiser_ids`.</li><li>When `report_type` is set to `PLAYABLE_MATERIAL`, `CATALOG`, or `TT_SHOP`, pass in `advertiser_id`.</li><li>When `report_type` is set to `BC`, pass in `bc_id`.</li></ul><br>ID of a Business Center that you have access to.<br><br> To get a list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| service_type | string | Not supported when `report_type` is `BC`. <br><br> Ad service type. <br><br>Enum values:<ul><li>`AUCTION`: auction ads, or both auction ads and reservation ads.</li><li>`RESERVATION` (<span style="color:Red">deprecated</span>) : reservation ads. </li></ul> <br>Default value when `report_type` is not set to `BC`: `AUCTION`. <br><br><p><span style="color:darkred"><b>Note</b></span>: To retrieve the reporting data of TopView ads, use the [basic reports](https://business-api.tiktok.com/portal/docs?id=1738864915188737) or [audience reports](https://business-api.tiktok.com/portal/docs?id=1738864928947201) and specify the value `RESERVATION_TOP_VIEW` for the `buying_type` [filter](https://business-api.tiktok.com/portal/docs?id=1751443975608321).|
| report_type {Required} | string | Report type. <br><br>Enum values: <ul><li>`BASIC`: basic report.</li><li>`AUDIENCE`: audience report.</li><li>`PLAYABLE_MATERIAL`: playable ads report.</li><li>`CATALOG`: DSA report.  </li><li>`BC`: Business Center report.</li></li><li>`TT_SHOP`: GMV max ads report.</ul><br><ul> <li>When `service_type` is `AUCTION`, `BASIC`, `AUDIENCE`, `PLAYABLE_MATERIAL`, `CATALOG`, and `TT_SHOP` reports are all supported.</li><li>When `report_type` is `BC`, you don't need to pass `service_type`.</li></ul>|
| data_level {+Conditional}| string | Not supported when `report_type` is `BC`. <br> Required when `report_type` is `BASIC`,`AUDIENCE` or `CATALOG`. <br><br>The data level that you'd like to query in reports.<br><br> Enum values:<ul><li>`AUCTION_AD`: auction ads or both auction ads and reservation ads, ad level.</li><li>`AUCTION_ADGROUP`: auction ads or both auction ads and reservation ads, ad group level. </li><li>`AUCTION_CAMPAIGN`: auction ads or both auction ads and reservation ads, campaign level.</li><li>`AUCTION_ADVERTISER`: auction ads or both auction ads and reservation ads, advertiser level. </li><li>`RESERVATION_AD`(<span style="color:Red">deprecated</span>): reservation ads, ad level. </li><li>`RESERVATION_ADGROUP`(<span style="color:Red">deprecated</span>): reservation ads, ad group level. </li><li>`RESERVATION_CAMPAIGN`(<span style="color:Red">deprecated</span>): reservation ads, campaign level. </li><li>`RESERVATION_ADVERTISER`(<span style="color:Red">deprecated</span>): reservation ads, advertiser level. </li></ul>  <br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>If you specify this field as `AUCTION_ADVERTISER`, data of all Auction Ads and Reservation Ads under the advertiser account will be returned. </li><li>When `report_type` is `BASIC`, `AUDIENCE` or `CATALOG`:<ul><li>If you set this field to `AUCTION_CAMPAIGN`, the data for all ads under undeleted auction campaigns will be returned, because by default the `campaign_status` filter is applied and defaults to `STATUS_NOT_DELETE`.</li><li>If you set this field to `AUCTION_ADGROUP`, the data for all ads under undeleted auction ad groups will be returned, because by default the `adgroup_status` filter is applied and defaults to `STATUS_NOT_DELETE`.</li><li>If you set this field to `AUCTION_AD`, only the data for undeleted ads under all auction campaigns will be returned, because by default the `ad_status` filter is applied and defaults to `STATUS_NOT_DELETE`.</li><li>If you want to get the data for all ads under all auction campaigns, set the `ad_status` filter to `STATUS_ALL` (`filtering=[&#123;"field_name":"ad_status","filter_type":"IN","filter_value":"[\"STATUS_ALL\"]"&#125;]`).</li></ul></li><li> When `report_type` is `TT_SHOP`, you can only set this field to `AUCTION_ADVERTISER` or `AUCTION_CAMPAIGN`.</li></ul>|
| dimensions {Required} | string[] | Grouping conditions. For example: `&#91;"campaign_id", "stat_time_day"&#93;` indicates that both `campaign_id` and `stat_time_day` (days) are grouped.<br><br> Different report types support different dimensions. <ul><li>Supported dimensions in [basic reports](https://business-api.tiktok.com/portal/docs?id=1751443956638721)</li><li>Supported dimensions in [audience reports](https://business-api.tiktok.com/portal/docs?id=1751454103714818)</li><li>Supported dimensions in [playable ad reports](https://business-api.tiktok.com/portal/docs?id=1751617879104514)</li><li>Supported dimensions in [DSA reports](https://business-api.tiktok.com/portal/docs?id=1751617879104514)</li><li> Supported dimensions in [Business Center reports](https://business-api.tiktok.com/portal/docs?id=1775747465089026)</li><li>Supported dimensions in [GMV max ads reports](https://business-api.tiktok.com/portal/docs?id=1803073629472770)</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If `report_type` is not set to `BC`: <ul><li>When `dimensions` contains `stat_time_day`, the query time range cannot exceed 30 days.</li><li>When `dimensions` contains `stat_time_hour`, the query time range cannot exceed 1 day. </li></ul></li><li>If your query `dimensions` contains more than xx_id and there is no ads reporting within the query time range, an empty list will be returned.</li></ul> |
| metrics | string[] | Metrics to query. <br><br>Different report types support different metrics.  For supported metrics for each report type, see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186). <br><br><ul><li>When `report_type` is set to `BASIC`, `AUDIENCE`, `PLAYABLE_MATERIAL`, or `CATALOG`, the default value is `["spend", "impressions"]`. </li><li>When `report_type` is set to `BC`, the default value is all the metrics supported by the dimension grouping specified through `dimensions`. </li><li>When `report_type` is set to `TT_SHOP`, the default value is `["spend", "billed_cost"]`.</li></ul><br>Note that the amount of currency metrics are based on the currency set by the advertiser's account.  |
| enable_total_metrics | boolean | Whether to enable the total added-up data for your requested metrics. <br>When `enable_total_metrics` is enabled, we will provide the aggregate data for all pages as you query different pages. Under this condition, you only need to specify this field when requesting data for the first page.<br>Due to the 20,000-truncation limitation, when `enable_total_metrics` is enabled, we will provide the total added-up data for up to 20,000 IDs. <br> <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>It only supports auction basic reports and GMV max ads reports.</li><li>It is only valid when one ID dimension and(or) `stat_time_day` is specified in the `dimensions` field. Examples: `["campaign_id"]`, `["campaign_id", "stat_time_day"]`. </li><li>It can not be used in the sandbox environment. |
| start_date {+Conditional} | string | This field is required when `query_lifetime` is `false` or not specified. <br> <br>Query start date (closed interval) in the format of `YYYY-MM-DD`. The date is based on the ad account time zone. <br> <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When `report_type` is not set to `BC` and the value of `dimensions` does not include `stat_time_day`, the maximum time range you can specify through `start_date` and `end_date` is 365 days.</li><li>When `report_type` is not set to `BC` and the value of `dimensions` includes `stat_time_day`, the maximum time range you can specify through `start_date` and `end_date` is 30 days.</li><li>When `report_type` is set to `BC`, the maximum time range you can specify through `start_date` and `end_date` is 365 days. </li></ul>|
| end_date {+Conditional} | string |This field is required when `query_lifetime` is `false` or not specified. <br> <br> Query end date (closed interval) in the format of `YYYY-MM-DD`. The date is based on the ad account time zone. <br> <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When `report_type` is not set to `BC` and the value of `dimensions` does not include `stat_time_day`, the maximum time range you can specify through `start_date` and `end_date` is 365 days.</li><li>When `report_type` is not set to `BC` and the value of `dimensions` includes `stat_time_day`, the maximum time range you can specify through `start_date` and `end_date` is 30 days.</li><li>When `report_type` is set to `BC`, the maximum time range you can specify through `start_date` and `end_date` is 365 days. </li></ul>|
|query_lifetime | boolean  |Supported when `report_type` is `BASIC` or `PLAYABLE_MATERIAL`.<br><br>Whether to request the lifetime metrics.  Default value: `False`. If `query_lifetime` = `True`, the `start_date` and `end_date` parameters will be ignored. The lifetime metric name is the same as the normal one. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Breakdown by time or audience is not supported when querying for lifetime metrics.</li><li>Breakdown by location code (`country_code`) is not supported when querying for lifetime metrics.</li><li>Audience reports, DSA reports, Business Center reports, and GMV max ads reports do not support lifetime metrics.</li></ul>|
| multi_adv_report_in_utc_time | boolean | Supported when you have used `advertiser_ids` in your request.<br><br>Whether to set the returned metrics in the local timezone of each respective advertiser. <br><br>Default value: `false`. If it is set to `true`, the returned metrics will be set to the UTC+0 timezone for all advertisers. |
| order_field | string | Sorting field. <br><br> All supported metrics (excluding attribute metrics) support sorting. Not sorting by default.|
| order_type | string | Sorting order. <br><br> Enum values: `ASC`, `DESC`. <br><br> Default value: `DESC`. |
| filtering | object[] |<ul><li>Supported when `report_type` is `BASIC`, `AUDIENCE`, `PLAYABLE_MATERIAL`, `CATALOG`, or `BC`.</li><li>Not supported when `report_type` is `TT_SHOP`. </li></ul><br> Filtering conditions. <br><br> Supported filtering conditions vary based on `service_type` and `data_level`. For filters supported in different report types, refer to the articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186). <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When you pass in `filtering`, make sure you specify the fields `field_name`, `filter_type`, and `filter_value` at the same time as an object in a list. </li><li>If you want to use `country_code` as your grouping filter, then please specify `country_code` in the `dimensions` field of the request. Note that `country_code` works with basic report (sync and async), audience report (sync and async), playable ad report (sync only), and Business Center report (sync only).</li></ul> |
#| field_name {+Conditional} | string | Filter field name.|
#| filter_type {+Conditional}| string | Filter type.  <br><br>Enum values: <ul><li>`IN`: Contain any of (exact matching). When you use this filter type, the filtered value must be a valid JSON array character string. </li><li>`CONTAIN_ANY_OF`: Contain any of (fuzzy matching). When you use this filter type, the filtered value must be a valid JSON array character string.</li><li>`MATCH`: Match. This filter type is equivalent to `like` operations in MySQL and supports fuzzy matching.</li><li>`NOT_IN`: Does not contain any of (exact matching). When you use this filter type, the filtered value must be a valid JSON array character string.</li><li>`GREATER_EQUAL`: Greater than or equal to.</li><li>`GREATER_THAN`: Greater than.</li><li>`LOWER_EQUAL`: Less than or equal to.</li><li>`LOWER_THAN`: Less than.</li><li>`BETWEEN`: Between. When you use this filter type, the filtered value must be a valid JSON array character string containing exactly two string values.</li></ul>  |
#| filter_value{+Conditional} | string | The value to filter. <br><br>When `filter_type` is `IN` ,`CONTAIN_ANY_OF`, or `NOT_IN`, `filter_value` must be a valid JSON array character string. |
| query_mode {-to be deprecated} | string |Not supported when `report_type` is `BC`. <br><br>The way data is queried. <br><br>Enum values: `REGULAR`, `CHUNK`. <br><br>Default value: `REGULAR`.  <br><br>With `CHUNK` mode on, data can be returned much faster in a more stable way. Meanwhile, pagination will not be working with `CHUNK`.<br><br><p><span style="color:darkred"><b>Note</b></span>:  <ul><li>`CHUNK` mode will be deprecated soon. To ensure a smooth integration, we recommend that you do not use this mode.</li><li>`CHUNK` mode is only supported in synchronous basic reports.</li></ul> |
| page | number | Current page number. <br><br> Default value: `1`. |
| page_size | number | Pagination size. <br><br> Value range: 1-1,000. <br><br> Default value: `10`.|
```


### Example

#### Retrieving the ad (asset group) level data of an Upgraded Smart+ Ad Group through a basic report
```xcodeblock
(code python)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/?advertiser_id={{advertiser_id}}&report_type=BASIC&dimensions=["ad_id_v2","stat_time_day"]&metrics=["campaign_automation_type","ad_name","ad_id_v2","spend","impressions"]&filtering=[{"field_name":"campaign_automation_type","filter_type":"IN","filter_value":"[\"UPGRADED_SMART_PLUS\"]"},{"field_name":"adgroup_ids","filter_type":"IN","filter_value":"[\"{{adgroup_id}}\"]"}]&data_level=AUCTION_AD&start_date=2025-12-03&end_date=2025-12-03&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
#### Retrieving the creative level data of an Upgraded Smart+ Ad Group through a basic report 
```xcodeblock
(code python)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/?advertiser_id={{advertiser_id}}&report_type=BASIC&dimensions=["ad_id","stat_time_day"]&metrics=["campaign_automation_type","ad_name","ad_id","spend","impressions"]&filtering=[{"field_name":"campaign_automation_type","filter_type":"IN","filter_value":"[\"UPGRADED_SMART_PLUS\"]"},{"field_name":"adgroup_ids","filter_type":"IN","filter_value":"[\"{{adgroup_id}}\"]"}]&data_level=AUCTION_AD&start_date=2025-12-03&end_date=2025-12-03&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```
#### Filtering by Upgraded Smart+ Ad (Asset Group) IDs through `ad_id_v2`
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/' \
--header 'Access-Token: "{{Access-Token}}"' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "service_type": "AUCTION",
    "report_type": "BASIC",
    "data_level": "AUCTION_AD",
    "dimensions": [
        "ad_id_v2"
    ],
    "metrics": [
        "spend",
        "ad_id_v2"
    ],
    "filtering": [
        {
            "field_name": "ad_ids_v2",
            "filter_type": "IN",
            "filter_value": "[\"{{ad_id_v2}}\",\"{{ad_id_v2}}\"]"
        }
    ],
    "start_date": "2025-07-01",
    "end_date": "2025-07-30",
    "page": "1",
    "page_size": "1000"
}'
(/code)
```


## Response

**Response Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|X-Tt-Ads-Throttle|string|A warning message to indicate the request's over limit issue.<br>E. g. When the number of ad ids of queried advertiser account is more than 20,000: `X-Tt-Ads-Throttle:"Number of ad ids is equal or more than 20k, only return the top 20k id's results, sorting by create time desc order."`|
```

**Response Parameters**

```xtable
|Field{30%}|Data Type{10%}|Description{40%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id |string|The log ID of the request, which uniquely identifies a request.  |
|data |object|Return data.|
#|total_metrics|object| The total added-up data for your requested metrics. Returned when you enable `enable_total_metrics` in the request.  |
##| campaign_name | string | Auction Ads Campaign Name / Reservation Ads Group Name. Metrics that cannot be aggregated, such as `campaign_name`,  will be returned as `-`. |
##| spend | string | The total added-up data for spend.  |
##| impressions | string | The total added-up data for impressions.|
##| reach | string | The total added-up data for reach.  |
##|{Other metrics} | string | The total added-up data for other metrics.|
#|list|object[]| Data list. |
##| dimensions | object | All requested dimension data. |
###| advertiser_id | string |Returned when `dimensions` contains `advertiser_id`.    <br><br>Advertiser ID. |
###| campaign_id | string  | Returned when `dimensions` contains `campaign_id`. <br><br>Campaign ID.  |
###| adgroup_id  | string | Returned when `dimensions` contains `adgroup_id`.  <br><br>Ad Group ID. |
###| ad_id | string  | Returned when `dimensions` contains `ad_id`. <br><br>Ad ID. <br><br>The types of data you can get from this metric include:<ul><li>the ad level data for Manual Ads.</li><li>the ad level data for Smart+ Campaigns.</li><li>the <b>creative </b>level data for Upgraded Smart+ Ads.</li></ul>|
###| ad_id_v2 | string | Returned when `dimensions` contains the dimension `ad_id_v2`.<br><br>Ad ID v2. <br><br>The types of data you can get from this metric include:<ul><li>the ad level data for Manual Ads.</li><li>the ad level data for Smart+ Campaigns.</li><li>the<b> ad</b> level data for Upgraded Smart+ Ads.</li></ul> |
###| stat_time_day| string | Returned when `dimensions` contains `stat_time_day`. <br><br>The time (days) when the reporting occurred. <br><br>Format: `2020-01-01 00:00:00`. |
###| stat_time_hour | string |Returned when `dimensions` contains `stat_time_hour`. <br><br> The time (hours) when the reporting occurred. <br><br>Format: `2020-01-01 10:00:00`. |
###| ac | string | Returned when `dimensions` contains `ac`. <br><br>Audience internet connection types. See [Enumeration - Connection Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details. |
###| age | string |Returned when `dimensions` contains `age`. <br><br>Audience age group. See [Enumeration - Age Group](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details. |
###| country_code | string | Returned when `dimensions` contains `country_code`.<br><br>Audience country or region code. For enum values, see [Appendix - Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010).  |
###| interest_category | string | Returned when `dimensions` contains `interest_category`.  <br> <br>Old first-level interest category. <br> <br><p><span style="color:DarkRed"><b>Note</b></span>: Old first-level interest categories will be deprecated in the next API version.|
###| interest_category_v2| string |Returned when `dimensions` contains `interest_category`.  <br> <br>New first-level interest category.  <br> <br>Use [/tool/interest_category/](https://ads.tiktok.com/marketing_api/docs?id=1737174348712961) endpoint to get the complete list of interest categories.  |
###| gender | string |Returned when `dimensions` contains `gender`. <br><br> Targeting gender. Enum values: `FEMALE`, `MALE`, `NONE`. |
###| language | string | Returned when `dimensions` contains `language`.<br><br>Targeting language. See [Enumeration - Languages](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details.  |
###| placement | string | Returned when `dimensions` contains `placement`.<br><br>Placement. See [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details.  |
###| platform | string |Returned when `dimensions` contains `platform`.  <br><br>Targeting operating system. See [Enumeration - Operating System](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for details. |
###| contextual_tag  | string |<Returned when `dimensions` contains `contextual_tag`. <br><br>Contextual tag. |
##| metrics | object | All requested metric data. |
###| campaign_name | string | Auction Ads Campaign Name / Reservation Ads Group Name. |
###| spend | string | Total spend. |
###| impressions | string | Number of impression.|
###| reach | string | Number of reach. |
###|{Other metrics} | string | For metrics supported in different report types, refer to the articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186).|
###| advertiser_id | string |Returned when you have specified `advertiser_ids` in your request.<br><br>  Advertiser ID.  |
###| timezone | string | Returned when you have specified `advertiser_ids` in your request.<br><br> Your advertiser's timezone.   |
###| currency | string | Returned when you have specified `advertiser_ids` in your request.  <br><br>Your advertiser's currency.   |
###| campaign_automation_type | string | Returned when `metrics` contains the `campaign_automation_type` metric in the request.<br><br>Campaign automation type.<br><br>Enum values:<ul><li>`MANUAL`: Manual Campaigns.</li><li>`SMART_PLUS`: Smart+ Campaigns.</li><li>`UPGRADED_SMART_PLUS`: Upgraded Smart+ Campaigns. To learn more about Upgraded Smart+ Campaigns, see [Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1853452461203458).<ul><li>When `campaign_automation_type` is `UPGRADED_SMART_PLUS`, the returned `ad_id_v2` is the ID of the Upgraded Smart+ Ad.</li></ul></li><li>`UPGRADED_SMART_PLUS_CREATIVE`: Upgraded Smart+ Campaigns. To learn more about Upgraded Smart+ Campaigns, see [Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1853452461203458).<ul><li>When `campaign_automation_type` is `UPGRADED_SMART_PLUS_CREATIVE`, the returned `ad_id` is the ID of a creative in the Upgraded Smart+ Ad.</li></ul></li></ul> |
###| ad_id_v2 | string | Returned when `metrics` contains the `ad_id_v2` metric in the request.<br><br>Ad ID v2. <br><br><ul><li>The types of data you can get from this metric include:<ul><li>The ad IDs of Manual Ads</li><li>The ad IDs of  Smart+ Campaigns</li><li>The ad IDs of Upgraded Smart+ Ads</li></ul></li></ul> |
###| ad_text_list | string[] | Returned when the following conditions are met:<ul><li>`dimensions` contains the dimension `ad_id_v2` and `ad_id_v2` is an Upgraded Smart+ Ad.</li><li>`metrics` contains `ad_text`.</li><li>`report_type` is `BASIC`, `AUDIENCE`, or `CATALOG`.</li></ul><br>A list of ad texts. |
###| call_to_action_list | string[] | Returned when the following conditions are met:<ul><li>`dimensions` contains the dimension `ad_id_v2` and `ad_id_v2` is an Upgraded Smart+ Ad.</li><li>`metrics` contains `call_to_action`.</li><li>`report_type` is `BASIC` or `AUDIENCE`.</li></ul><br>A list of call-to-actions. |
###| ad_profile_image_list | string[] | Returned when the following conditions are met:<ul><li>`dimensions` contains the dimension `ad_id_v2`.</li><li>`metrics` contains `ad_profile_image`.</li><li>`report_type` is `BASIC` or `AUDIENCE`.</li></ul><br>A list of ad level profile images. |
###| ad_url_list | string[] | Returned when the following conditions are met:<ul><li>`dimensions` contains the dimension `ad_id_v2` and `ad_id_v2` is an Upgraded Smart+ Ad.</li><li>`metrics` contains `ad_url`.</li><li>`report_type` is `BASIC` or `AUDIENCE`.</li></ul><br>A list of ad level URLs. |
###| image_mode_list | string[] | Returned when the following conditions are met:<ul><li>`dimensions` contains the dimension `ad_id_v2` and `ad_id_v2` is an Upgraded Smart+ Ad.</li><li>`metrics` contains `image_mode`.</li><li>`report_type` is `BASIC` or `AUDIENCE`.</li></ul><br>A list of formats. |
###| image_info_list | string[] | Returned when the following conditions are met:<ul><li>`dimensions` contains the dimension `ad_id_v2` and `ad_id_v2` is an Upgraded Smart+ Ad.</li><li>`metrics` contains `image_info`.</li><li>`report_type` is `BASIC` or `AUDIENCE`.</li></ul><br>A list of images. |
###| ad_display_name_list | string[] | Returned when the following conditions are met:<ul><li>`dimensions` contains the dimension `ad_id_v2` and `ad_id_v2` is an Upgraded Smart+ Ad.</li><li>`metrics` contains `ad_display_name`.</li><li>`report_type` is `BASIC` or `AUDIENCE`.</li></ul><br>A list of ad display names. |
###| identity_type_list | string[] | Returned when the following conditions are met:<ul><li>`dimensions` contains the dimension `ad_id_v2` and `ad_id_v2` is an Upgraded Smart+ Ad.</li><li>`metrics` contains `identity_type`.</li><li>`report_type` is `BASIC` or `AUDIENCE`.</li></ul><br>A list of identity types. |
###| profile_image_list | string[] | Returned when the following conditions are met:<ul><li>`dimensions` contains the dimension `ad_id_v2` and `ad_id_v2` is an Upgraded Smart+ Ad.</li><li>`metrics` contains `profile_image`.</li><li>`report_type` is `BASIC` or `AUDIENCE`.</li></ul><br>A list of profile images. |
#| page_info |object | Pagination information.|
##| page |number | Current page number. |
##| page_size |number | Page size. |
##| total_number |number | Total number of results.|
##| total_page |number | Total pages of results. |
```

### Example
#### Retrieving the ad (asset group) level data of Upgraded Smart+ Ads in a basic report
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_page": 1,
            "total_number": 2
        },
        "list": [
            {
                "dimensions": {
                    // "ad_id_v2" returns ad level data
                    "ad_id_v2": "{{ad_id_v2}}",
                    "stat_time_day": "2025-12-03 00:00:00"
                },
               
                "metrics": {
                    // ad ID
                    "ad_id_v2": "{{ad_id_v2}}",
                    // ad name
                    "ad_name": "{{ad_name}}",
                    "impressions": "1079",
                    "spend": "14.68",
                    "campaign_automation_type": "UPGRADED_SMART_PLUS"
                }
            },
            {
                "dimensions": {
                    "ad_id_v2": "{{ad_id_v2}}",
                    "stat_time_day": "2025-12-03 00:00:00"
                },
                "metrics": {
                    "ad_id_v2": "{{ad_id_v2}}",
                    "ad_name": "{{ad_name}}",
                    "impressions": "52",
                    "spend": "0.70",
                    "campaign_automation_type": "UPGRADED_SMART_PLUS"
                }
            }
        ]
    }
}
(/code)
```
#### Retrieving the creative level data of Upgraded Smart+ Ads in a basic report
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_page": 1,
            "total_number": 3
        },
        "list": [
            {
                "dimensions": {
                   // "ad_id" returns creative level data
                    "ad_id": "{{ad_id}}",
                    "stat_time_day": "2025-12-03 00:00:00"
                },
                "metrics": {
                    // creative ID
                    "ad_id": "{{ad_id}}",
                    // creative name
                    "ad_name": "{{ad_name}}",
                    "impressions": "513",
                    "spend": "7.97",
                    "campaign_automation_type": "UPGRADED_SMART_PLUS_CREATIVE"
                }
            },
            {
                "dimensions": {
                    "ad_id": "{{ad_id}}",
                    "stat_time_day": "2025-12-03 00:00:00"
                },
                "metrics": {
                    "ad_id": "{{ad_id}}",
                    "ad_name": "{{ad_name}}",
                    "impressions": "37",
                    "spend": "0.58",
                    "campaign_automation_type": "UPGRADED_SMART_PLUS_CREATIVE"
                }
            },
            {
                "dimensions": {
                    "ad_id": "{{ad_id}}",
                    "stat_time_day": "2025-12-03 00:00:00"
                },
                "metrics": {
                    "ad_id": "{{ad_id}}",
                    "ad_name": "{{ad_name}}",
                    "impressions": "10",
                    "spend": "0.08",
                    "campaign_automation_type": "UPGRADED_SMART_PLUS_CREATIVE"
                }
            }
        ]
    }
}
(/code)
```
#### Filtering by Upgraded Smart+ Ad (Asset Group) IDs through `ad_id_v2`
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "page": 1,
            "page_size": 2,
            "total_number": 2,
            "total_page": 1
        },
        "list": [
            {
                "dimensions": {
                    "ad_id_v2": "{{ad_id_v2}}"
                },
                "metrics": {
                    "spend": "0.00",
                    "ad_id_v2": "{{ad_id_v2}}"
                }
            },
            {
                "dimensions": {
                    "ad_id_v2": "{{ad_id_v2}}"
                },
                "metrics": {
                    "spend": "186.78",
                    "ad_id_v2": "{{ad_id_v2}}"
                }
            }
        ]
    }
}
(/code)
```


