---
title: "Video Insights reports"
doc_id: 1789700050715650
path: "API Reference / Creative Reports / Video Insights reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Video Insights reports

# Run a Video Insights report

Use this endpoint to run a Video Insights report on videos across one or more ad accounts, encompassing both Spark Ads and non-Spark Ads.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/report/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| report_type {Required} | string | Report type. <br><br>Enum values: <ul><li>`VIDEO_INSIGHT`: a Video Insights report for one or more advertiser accounts.</li></ul> |
| advertiser_id {+Conditional} | string | Either `advertiser_id` or `advertiser_ids` has to be set. <br><br>The advertiser ID that you want to run reports for. |
| advertiser_ids {+Conditional} | string[] | Either `advertiser_id` or `advertiser_ids` has to be set. <br><br> A list of advertiser IDs that you want to run reports for. <br><br>Max size: 50. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> You need to have access to all the corresponding ad accounts. To retrieve the ad accounts in a Business Center that you have access to, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401).</li><li> If the currencies of the ad accounts vary, the currency of the metrics will default to USD. </li></ul> |
| start_date {Required} | string | Query start date, closed interval, in the format of `YYYY-MM-DD` (UTC+0 time). <br><br>Example: `2024-01-01`. <br><br>The maximum query time range defined by `start_date` and `end_date` is 365 days. |
| end_date{Required} | string | Query end date closed interval, in the format of `YYYY-MM-DD` (UTC+0 time). <br><br>Example: `2024-01-01`. <br><br>The maximum query time range defined by `start_date` and `end_date` is 365 days. |
| metrics_fields | string[] | The metrics that you want to retrieve data for. <br><br>  <ul><li> If this field is not passed, all the metrics supported in Video Insights reports will be returned by default.</li><li> To find the supported metrics, see [Supported metrics in Video Insights reports](https://business-api.tiktok.com/portal/docs?id=1789703614085122).</li></ul> |
| filtering | object | Filtering conditions. |
#| material_id| string[] | A list of video material IDs. <br><br>Max size: 20. <br><br>To obtain the material ID of a video, use [/file/video/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740050472224769). |
#| material_name | string | The video material name. <br><br>Fuzzy search is supported. |
#| tiktok_item_ids | string[] | An ID list of TikTok Spark Ads posts that the video or videos were used to create. <br><br>Max size: 20. <br><br>To obtain TikTok post IDs, use [/tt_video/info/](https://ads.tiktok.com/marketing_api/docs?id=1738376324021250) or [/identity/video/get/](https://ads.tiktok.com/marketing_api/docs?id=1740218475032577). |
#| country_code | string[] | A code list of locations where the videos were delivered. <br><br>For enum values, see [Appendix - Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010). |
#| creative_formats | string[] | List of creative formats. <br><br>Enum values: <ul><li>`VIDEO_16_9`: Video (16:9).</li><li> `VIDEO_1_1`: Video (1:1).</li><li>`VIDEO_9_16`: Video (9:16). </li><li>`OTHER`: Other. </li></ul>|
#| video_material_sources | string[] | List of video material sources. <br><br>Enum values: <ul><li>`UPLOADED_TO_TIKTOK_ADS_MANAGER`: Uploaded to TikTok Ads Manager. </li><li> `UPLOADED_TO_CATALOG`: Uploaded to Catalog. </li><li> `CREATIVE_TOOL_SMART_VIDEO`: Creative Tool - Smart Video. </li><li> `CREATIVE_TOOL_QUICK_OPTIMIZATION`: Creative Tool - Quick Optimization. </li><li> `CREATIVE_TOOL_VIDEO_TEMPLATE`: Creative Tool - Video Template.</li><li>`CREATIVE_TOOL_SMART_VIDEO_SOUNDTRACK`: Creative Tool - Smart Video Soundtrack. </li><li>`CREATIVE_TOOL_TIKTOK_VIDEO_EDITOR`: Creative Tool - TikTok Video Editor. </li><li>`TIKTOK_CREATIVE_EXCHANGE`: TikTok Creative Exchange.</li><li>`CATALOG_VIDEO_TEMPLATE`: Catalog Video Template. </li><li>`DYNAMIC_VIDEO_EDITOR`: Dynamic Video Editor. </li><li>`CREATIVE_CHALLENGE`: Creative challenge. </li><li>`AUTOMATED_CREATIVE_OPTIMIZATION`: Automated Creative Optimization. </li><li>`OTHER`: Other. </li><li> `QUICK_GENERATION`: Quick Generation.</li><li>`CREATIVE_CENTER_VIDEO_UPLOAD`: Creative Center - Video upload.</li><li> `CREATIVE_CENTER_TIKTOK_VIDEO_EDITOR`：Creative Center - TikTok video editor.</li><li> `CREATIVE_CENTER_VIDEO_TEMPLATE`：Creative Center - Video template.</li><li>`DYNAMIC_SCENE`：Dynamic Scene.</li><li>`SMART_OPTIMIZATION_TOOL`: Smart Optimization Tool.</li></ul>|
#| placement | string[] | List of ad placements for the videos. <br><br>Enum values: <ul><li>`PLACEMENT_TIKTOK`: TikTok.</li><li> `PLACEMENT_PANGLE`: Pangle.</li><li>`PLACEMENT_GLOBAL_APP_BUNDLE`: Global App Bundle.</li></ul> |
#| identities | string[] | Identity types that were used for the delivery of the videos. <br><br>Enum values: <ul><li>`SPARK_ADS`: Spark Ads identities, including Authorized User, TikTok User, and TikTok Account User in Business Center identities.</li><li>`NON_SPARK_ADS`: Non-Spark Ads identities, that is, Custom User identities.</li></ul> <br>To learn more about identity types, see [Identities](https://business-api.tiktok.com/portal/docs?id=1738958351620097). |
#| ad_name | string | The ad name. <br><br>Fuzzy search is supported. |
#| ad_id | string[] | A list of ad IDs. <br><br>Bulk query is supported. <br><br>Max size: 20. |
#| adgroup_name | string | The ad group name. <br><br>Fuzzy search is supported. |
#| adgroup_id | string[] | A list of ad group IDs. <br><br>Max size: 20. |
#| campaign_name | string | The campaign name. <br><br>Fuzzy search is supported. |
#| campaign_id | string[] | A list of campaign IDs. <br><br>Max size: 20. |
| sort_field | string | Field to sort by. <br><br>The supported fields are listed in [Supported metrics in Video Insights reports](https://business-api.tiktok.com/portal/docs?id=1789703614085122). |
| sort_type | string | Sorting order. <br><br> Enum values: `ASC`, `DESC`. <br> Default value: `DESC`.|
| page | number | Current number of pages. <br><br> Default value: 1. <br> Value range: ≥ 1. |
| page_size| number | Page size. <br><br> Default value: 10. <br>Value range: 1-1,000. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/creative/report/get/?report_type=VIDEO_INSIGHT&advertiser_id={{advertiser_id}}&start_date=2023-12-24&end_date=2024-01-23' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| list | object[] | Video insights data. <br><br><p><span style="color:darkred"><b>Note</b></span>: By default, only videos with a total cost (`spend`) greater than 0 will be included. Videos with no cost will be filtered out. |
##| metrics | object | Metric data for the video. <br><br> <ul><li> If `metrics_fields` is not passed in the request, all the metrics supported in Video Insights reports will be returned by default. To find the supported metrics, see [Supported metrics in Video Insights reports](https://business-api.tiktok.com/portal/docs?id=1789703614085122).</li><li> If `metrics_fields` is passed in the request, only the specified metrics supported will be returned.</li></ul>|
##| info | object | Material information for the video. |
###| material_id | string | Video material ID. |
###| material_name | string | Video material name. |
###| create_time | string | The time at which the video was created, in [ISO-8601](https://en.wikipedia.org/wiki/ISO_8601) format. <br><br> Example: "2024-01-01T00:00:01Z". |
###| tiktok_item_ids | string[] | Returned only when the video (`material_id`) has been used to create Spark Ads. <br><br>The ID list of the TikTok Spark Ads posts that are created using the video. |
###| country_code| string[] | The code list of locations that the video (`material_id`) was delivered in. <br><br>For enum values, see [Appendix - Location code]( https://business-api.tiktok.com/portal/docs?id=1737585867307010). |
###| video_material_source | string | Video material source. <br><br>Enum values: <ul><li>`UPLOADED_TO_TIKTOK_ADS_MANAGER`: Uploaded to TikTok Ads Manager. </li><li> `UPLOADED_TO_CATALOG`: Uploaded to Catalog. </li><li> `CREATIVE_TOOL_SMART_VIDEO`: Creative Tool - Smart Video. </li><li> `CREATIVE_TOOL_QUICK_OPTIMIZATION`: Creative Tool - Quick Optimization. </li><li> `CREATIVE_TOOL_VIDEO_TEMPLATE`: Creative Tool - Video Template.</li><li>`CREATIVE_TOOL_SMART_VIDEO_SOUNDTRACK`: Creative Tool - Smart Video Soundtrack. </li><li>`CREATIVE_TOOL_TIKTOK_VIDEO_EDITOR`: Creative Tool - TikTok Video Editor. </li><li>`TIKTOK_CREATIVE_EXCHANGE`: TikTok Creative Exchange.</li><li>`CATALOG_VIDEO_TEMPLATE`: Catalog Video Template. </li><li>`DYNAMIC_VIDEO_EDITOR`: Dynamic Video Editor. </li><li>`CREATIVE_CHALLENGE`: Creative challenge. </li><li>`AUTOMATED_CREATIVE_OPTIMIZATION`: Automated Creative Optimization. </li><li>`OTHER`: Other. </li><li> `QUICK_GENERATION`: Quick Generation.</li><li>`CREATIVE_CENTER_VIDEO_UPLOAD`: Creative Center - Video upload.</li><li> `CREATIVE_CENTER_TIKTOK_VIDEO_EDITOR`：Creative Center - TikTok video editor.</li><li> `CREATIVE_CENTER_VIDEO_TEMPLATE`：Creative Center - Video template.</li><li>`DYNAMIC_SCENE`：Dynamic Scene.</li><li>`SMART_OPTIMIZATION_TOOL`: Smart Optimization Tool.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If the material source of the video cannot be identified, the value of this field might be null. |
###| placement | string[] | The ad placements of the video. <br><br>Enum values: <ul><li>`PLACEMENT_TIKTOK`: TikTok.</li><li> `PLACEMENT_PANGLE`: Pangle.</li><li>`PLACEMENT_GLOBAL_APP_BUNDLE`: Global App Bundle.</li></ul>|
###| identity | string | Identity type that was used for the delivery of the video. <br><br>Enum values: <ul><li>`SPARK_ADS`: Spark Ads identities, including Authorized User, TikTok User, and TikTok Account User in Business Center identities.</li><li>`NON_SPARK_ADS`: Non-Spark Ads identities, that is, Custom User identities.</li></ul> <br>To learn more about identity types, see [Identities](https://business-api.tiktok.com/portal/docs?id=1738958351620097). |
###| currency | string | The code of the currency in which the metric data in `metrics` is calculated. <br><br> Example: USD. <br><br>To learn about the currency that a currency code corresponds to, see [Budget verification ratio and precision of each currency](https://business-api.tiktok.com/portal/docs?id=1737585839634433). |
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
                "metrics": {
                    "impressions": "63727",
                    "cpm": "10.27",
                    "ctr": "0.38",
                    "organic_video_views": null,
                    "clicks": "243",
                    "cpc": "2.69",
                    "spend": "654.39",
                    "conversion_rate_v2": "0.01",
                    "conversion": "6",
                    "cost_per_conversion": "109.07"
                },
                "info": {
                    "material_name": "{{material_name}}",
                    "identity": "NON_SPARK_ADS",
                    "placement": [
                        "PLACEMENT_TIKTOK"
                    ],
                    "video_material_source": "UPLOADED_TO_TIKTOK_ADS_MANAGER",
                    "currency": "USD",
                    "country_code": [
                        "CA",
                        "US"
                    ],
                    "create_time": "2024-01-11T07:42:46Z",
                    "material_id": "{{material_id}}"
                }
            },
            {
                "metrics": {
                    "impressions": "200626",
                    "cpm": "4.62",
                    "ctr": "1.36",
                    "organic_video_views": "58242.0",
                    "clicks": "2732",
                    "cpc": "0.34",
                    "spend": "927.25",
                    "conversion_rate_v2": "0.01",
                    "conversion": "17",
                    "cost_per_conversion": "54.54"
                },
                "info": {
                    "material_name": "{{material_name}}",
                    "tiktok_item_ids": [
                        "{{tiktok_item_id}}"
                    ],
                    "identity": "SPARK_ADS",
                    "placement": [
                        "PLACEMENT_TIKTOK"
                    ],
                    "video_material_source": null,
                    "currency": "USD",
                    "country_code": [
                        "CA",
                        "US"
                    ],
                    "create_time": "2023-09-25T08:47:43Z",
                    "material_id": "{{material_id}}"
                }
            }
        ],
        "page_info": {
            "total_number": 2,
            "total_page": 1,
            "page_size": 10,
            "page": 1
        }
    }
}
(/code)
```


