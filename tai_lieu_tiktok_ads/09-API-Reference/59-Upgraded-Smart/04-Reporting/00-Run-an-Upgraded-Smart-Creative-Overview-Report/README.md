---
title: "Run an Upgraded Smart+ Creative Overview Report"
doc_id: 1843317489576961
path: "API Reference / Upgraded Smart+ / Reporting / Run an Upgraded Smart+ Creative Overview Report"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Run an Upgraded Smart+ Creative Overview Report

Use this endpoint to retrieve an Upgraded Smart+ Creative Overview Report, an overview of the reporting data on creatives within Upgraded Smart+ Ads.

Upgraded Smart+ Creative Overview Reports doesn't support breakdown by time range (hour, day, week, or month).

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/smart_plus/material_report/overview/

**Method** GET

**Header**
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| dimensions {Required} | string[] | Dimension grouping conditions.<br><br>For example, `&#91;"campaign_id", "main_material_id"&#93;` indicates that both `campaign_id` and `main_material_id` are grouped.<br><br>To learn about available dimensions and supported dimension groupings, see [Supported dimensions](https://business-api.tiktok.com/portal/docs?id=1843337892165889). |
| metrics | string[] | Metrics to query.<br><br>Default value: `&#91;"spend", "impressions"&#93;`.<br><br>Note that the amount of currency metrics is based on the currency of the ad account (`advertiser_id`).<br><br>To learn about the supported metrics at the ad level, see [Supported ad-level metrics for Upgraded Smart+ Creative Reports](https://business-api.tiktok.com/portal/docs?id=1843337909199938). |
| filtering | object | Filtering conditions. |
#| campaign_ids | string[] | A list of campaign IDs.<br><br>Max size: 200.<br><br>To obtain campaign IDs, use [/smart_plus/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1843312818332930).<br><br><ul><li>Filter combination rule: You cannot combine the filter `campaign_ids` with the filter `adgroup_ids` or `smart_plus_ad_ids`.</li><li>Filter and dimension combination rule: You can combine the filter with any of the following dimensions:<ul><li>`advertiser_id`</li><li>`campaign_id`</li><li>`adgroup_id`</li><li>`smart_plus_ad_id`</li></ul></li></ul> |
#| adgroup_ids | string[] | A list of ad group IDs.<br><br>Max size: 200.<br><br>To obtain ad group IDs, use [/smart_plus/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1843314879617026).<br><br><ul><li> Filter combination rule: You cannot combine the filter `adgroup_ids` with the filter `campaign_ids` or `smart_plus_ad_ids`.</li><li>Filter and dimension combination limitation: You can combine the filter with any of the following dimensions:<ul><li>`advertiser_id`</li><li>`adgroup_id`</li><li>`smart_plus_ad_id`</li></ul></li></ul>  |
#| smart_plus_ad_ids | string[] | A list of ad IDs.<br><br>Max size: 200.<br><br>To obtain ad IDs, use [/smart_plus/ad/get/](https://business-api.tiktok.com/portal/docs?id=1843317378982914).<br><br><ul><li>Filter combination rule: You cannot combine the filter `smart_plus_ad_ids` with the filter `campaign_ids` or `adgroup_ids`.</li><li>Filter and dimension combination rule: You can combine the filter with any of the following dimensions:<ul><li>`advertiser_id`</li><li>`smart_plus_ad_id`</li></ul></li></ul>   |
#| main_material_types | string[] | A list of main creative types.<br><br>Enum values:<ul><li>`VIDEO_NON_SPARK_ADS`: Video (non-Spark Ads).</li><li>`VIDEO_SPARK_ADS`: Video (Spark Ads).</li><li>`CAROUSEL_NON_SPARK_ADS`: Carousel (non-Spark Ads).</li><li>`CAROUSEL_SPARK_ADS`: Carousel (Spark Ads).</li><li>`SINGLE_IMAGE_NON_SPARK_ADS`: Single image (non-Spark Ads).</li><li>`CATALOG_VIDEO`: Catalog video.</li><li>`CATALOG_VIDEO_TEMPLATE`: Catalog video template.</li><li>`CATALOG_CAROUSEL`: Catalog carousel.</li><li>`CATALOG_MULTI_SHOW`: Catalog Multi-Show. Multi-Show Experience is an auto-play video carousel experience designed to drive user exploration and engagement across a breadth of personally-relevant title offerings within your content library.</li></ul><br><ul><li>Filter combination rule: The filter `main_material_types` can be used alone.</li><li>Filter and dimension combination rule: You can combine the filter with any of the following dimensions:<ul><li>`advertiser_id`</li><li>`campaign_id`</li><li>`adgroup_id`</li><li>`smart_plus_ad_id`</li></ul> </li></ul>|
#| main_material_ids | string[] | A list of of main creative IDs.<br><br><ul><li>Filter combination rule: When you use this filter, you can specify up to 400 IDs in total for the filters `main_material_ids`, `ad_text_entity_ids`, `call_to_action_entity_ids`, and `interactive_add_on_entity_ids`.</li><li>Filter and dimension combination rule: You can combine the filter with any of the following dimensions:<ul><li>`advertiser_id`</li><li>`campaign_id`</li><li>`adgroup_id`</li><li>`smart_plus_ad_id`</li></ul></li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: When you use the filter `main_material_id`, you need to specify the filter `main_material_types` simultaneously and filter only one type.</p> |
#| ad_text_entity_ids | string[] | A list of ad text entity IDs.<br><br>The ID is a system-generated unique identifier for an ad text.<br><br><ul><li>Filter combination rule: When you use this filter, you can specify up to 400 IDs in total for the filters `main_material_ids`, `ad_text_entity_ids`, `call_to_action_entity_ids`, and `interactive_add_on_entity_ids`.</li><li>Filter and dimension combination rule: You can combine the filter with any of the following dimensions:<ul><li>`advertiser_id`</li><li>`campaign_id`</li><li>`adgroup_id`</li><li>`smart_plus_ad_id`</li></ul></li></ul> |
#| call_to_action_entity_ids | string[] | A list of call-to-action entity IDs.<br><br>The ID is a system-generated unique identifier for a call-to-action.<br><br><ul><li>Filter combination rule: When you use this filter, you can specify up to 400 IDs in total for the filters `main_material_ids`, `ad_text_entity_ids`, `call_to_action_entity_ids`, and `interactive_add_on_entity_ids`.</li><li>Filter and dimension combination rule: You can combine the filter with any of the following dimensions:<ul><li>`advertiser_id`</li><li>`campaign_id`</li><li>`adgroup_id`</li><li>`smart_plus_ad_id`</li></ul> </li></ul> |
#| interactive_add_on_entity_ids | string[] | A list of interactive add-on entity IDs.<br><br>The ID is a system-generated unique identifier for an interactive add-on.<br><br><ul><li>Filter combination rule: When you use this filter, you can specify up to 400 IDs in total for the filters `main_material_ids`, `ad_text_entity_ids`, `call_to_action_entity_ids`, and `interactive_add_on_entity_ids`.</li><li>Filter and dimension combination rule: You can combine the filter with any of the following dimensions:<ul><li>`advertiser_id`</li><li>`campaign_id`</li><li>`adgroup_id`</li><li>`smart_plus_ad_id`</li></ul></li></ul>|
| query_lifetime | boolean | Whether to request lifetime metrics.<br><br>Supported values: `true`, `false`.<br>Default value: `false`.<br><br>If you set `query_lifetime` to `true`, the parameters `start_date` and `end_date` will be ignored. |
| start_date {+Conditional} | string | Required when `query_lifetime` is `false` or not specified.<br><br>Query start date (closed interval) in the format of `YYYY-MM-DD`.<br><br>The date is based on the ad account time zone. |
| end_date {+Conditional} | string | Required when `query_lifetime` is `false` or not specified.<br><br>Query end date (closed interval) in the format of `YYYY-MM-DD`.<br><br>The date is based on the ad account time zone.<br><br><p><span style="color:darkred"><b>Note</b></span>: The time difference between `start_date` and `end_date` must be equal to or less than 365 days.</p> |
| sort_field | string | Sorting field.<br><br>Default sorting field: `spend`.<br><br>All supported metrics (excluding attribute metrics) support sorting. |
| sort_type | string | Sorting order.<br><br>Enum values: `ASC` (ascending), `DESC`(descending).<br>Default value: `DESC`. |
| page | number | Current page number.<br><br>Value range: ≥1.<br>Default value: 1. |
| page_size | number | Page size.<br><br>Value range: 1-100.<br>Default value: 10. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/material_report/overview/?advertiser_id={{advertiser_id}}&dimensions=["smart_plus_ad_id","main_material_id"]&metrics=["spend"]&filtering={"smart_plus_ad_ids": ["{{smart_plus_ad_id}}"]}&start_date={{start_date}}&end_date={{end_date}}&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
#| list|object[]|Data list.|
##| dimensions|object|Dimension data.|
###| advertiser_id|string|Returned when the parameter `dimensions` contains `advertiser_id` in the request.<br><br>Advertiser ID.|
###| campaign_id|string|Returned when the parameter `dimensions` contains `campaign_id` in the request.<br><br>Campaign ID.|
###| adgroup_id|string|Returned when the parameter `dimensions` contains `adgroup_id` in the request.<br><br>Ad Group ID.|
###| smart_plus_ad_id|string|Returned when the parameter `dimensions` contains `smart_plus_ad_id`in the request.<br><br>Ad ID.|
###| main_material_id|string|Returned when the parameter `dimensions` contains `main_material_id` in the request.<br><br>Material ID.<br><br><ul><li>When `main_material_type` is `VIDEO_NON_SPARK_ADS`, `CATALOG_VIDEO`, or `SINGLE_IMAGE_NON_SPARK_ADS`, this field represents the ID of a main creative.</li><li>When `main_material_type` is `VIDEO_SPARK_ADS` or `CAROUSEL_SPARK_ADS`, this field represents the ID of TikTok post.</li><li>When `main_material_type` is `CATALOG_VIDEO_TEMPLATE`, `CATALOG_CAROUSEL`, or `CATALOG_MULTI_SHOW`, this field represents the ID of a catalog.</li><li>When `main_material_type` is `CAROUSEL_NON_SPARK_ADS`, this field represents the ID of a carousel.</li></ul>|
###| main_material_type|string|Returned when the parameter `dimensions` contains `main_material_id` in the request.<br><br>Main creative type.<br><br>Enum values:<ul><li>`VIDEO_NON_SPARK_ADS`: Video (non-Spark Ads).</li><li>`VIDEO_SPARK_ADS`: Video (Spark Ads).</li><li>`CAROUSEL_NON_SPARK_ADS`: Carousel (non-Spark Ads).</li><li>`CAROUSEL_SPARK_ADS`: Carousel (Spark Ads).</li><li>`SINGLE_IMAGE_NON_SPARK_ADS`: Single image (non-Spark Ads).</li><li>`CATALOG_VIDEO`: Catalog video.</li><li>`CATALOG_VIDEO_TEMPLATE`: Catalog video template.</li><li>`CATALOG_CAROUSEL`: Catalog carousel.</li><li>`CATALOG_MULTI_SHOW`: Catalog Multi-Show. Multi-Show Experience is an auto-play video carousel experience designed to drive user exploration and engagement across a breadth of personally-relevant title offerings within your content library.</li></ul>|
###| ad_text_entity_id|string|Ad text entity ID.<br><br>The ID is a system-generated unique identifier for an ad text.|
###| ad_text_entity_type|string|Ad text entity type.<br><br>Enum value:<ul><li>`text`: ad text.</li></ul>|
###| call_to_action_entity_id|string|Call-to-action entity ID.<br><br>The ID is a system-generated unique identifier for a call-to-action.|
###| call_to_action_entity_type|string|Call-to-action entity type.<br><br>Enum values:<ul><li>`manual_call_to_action`: standard call to action.</li><li>`dynamic_call_to_action`: dynamic call to action.</li></ul>|
###| interactive_add_on_entity_id|string|Interactive add-on entity ID.<br><br>The ID is a system-generated unique identifier for an interactive add-on.|
###| interactive_add_on_entity_type|string|Interactive add-on entity type.<br><br>Enum value:<ul><li>`add_on`: interactive add-on.</li></ul>|
##| metrics|object|Metric data.<br><br>If the parameter `dimensions` does not contain `smart_plus_ad_id` in the request, the following metrics will return an empty string (`""`) :<ul><li>`ad_material_id`</li><li>`material_primary_status`</li><li>`material_second_status`</li><li>`operation_status`</li></ul>|
###| &#123;metric_name&#125;|string|Returned metrics, which are determined by the `metrics` field specified in the request.|
###| ad_material_id|string|An ad-specific material ID generated when a particular creative is used in an ad.<br><br>This ID differs from the creative ID you receive when uploading the creative to your ad account’s Creative Library.|
###| main_material_name|string|Main creative name.|
###| ad_text|string|Ad text.|
###| call_to_action|string|Call-to-action.|
###| interactive_add_on_name|string|Interactive add-on name.|
###| interactive_add_on_id|string|Interactive add-on ID.<br><br>This ID is generated when you create the interactive add-on in the Creative Library and is different from `interactive_add_on_entity_id`.|
###| material_primary_status|string|Returned when the parameter `dimensions` contains `smart_plus_ad_id` in the request.<br><br>The primary status of the creative.<br><br>For enum values, see [List of values for `material_primary_status`](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Material%20primary%20status).|
###| material_second_status_list|string[]|The list of secondary statuses of the creative.<br><br>For enum values, see [List of values for `material_second_status_list`](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Material%20secondary%20status).<br><br><p><span style="color:darkred"><b>Note</b></span>: When the parameter `dimensions` doesn't contain `smart_plus_ad_id` in the request, this field will not be returned.</p>|
###| operation_status|string|Operation status.<br><br>Enum values:<ul><li>`ENABLE` : enabled (in 'ON' status).</li><li>`DISABLE`: disabled (in 'OFF' status).</li><li>`FROZEN`: terminated and cannot be enabled.</li></ul>|
#| page_info|object|Pagination information.|
##| page|number|Current page number.|
##| page_size|number|Page size.|
##| total_number|number|Total number of results.|
##| total_page|number|Total pages of results.|
```

### Example
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "main_material_id": "{{main_material_id}}",
                    "main_material_type": "VIDEO_SPARK_ADS",
                    "smart_plus_ad_id": "{{smart_plus_ad_id}}"
                },
                "metrics": {
                    "main_material_name": "{{main_material_name}}",
                    "spend": "0.00"
                }
            },
            {
                "dimensions": {
                    "main_material_id": "{{main_material_id}}",
                    "main_material_type": "CAROUSEL_SPARK_ADS",
                    "smart_plus_ad_id": "{{smart_plus_ad_id}}"
                },
                "metrics": {
                    "main_material_name": "{{main_material_name}}",
                    "spend": "0.00"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 100,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```

