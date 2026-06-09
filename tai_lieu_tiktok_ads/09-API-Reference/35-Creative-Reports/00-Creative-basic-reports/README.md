---
title: "Creative basic reports"
doc_id: 1740662135093314
path: "API Reference / Creative Reports / Creative basic reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Creative basic reports

# Run a creative basic report
Use this endpoint to run a basic report on creative assets.

> <span style="color:DodgerBlue">**Note**</span><br>
> The creative basic reports include data on both undeleted and deleted creative assets.

## Before you start
Create ads or campaigns using [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354), [/ad/aco/create/](https://business-api.tiktok.com/portal/docs?id=1739473063234626), or [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362) to ensure cost data is generated at the creative level. If you haven't created any ads or campaigns, or if your ads or campaigns haven't generated any cost, you might receive empty results.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/creative/reports/get/|/v1.3/creative/report/get/|
|Request parameter name |`order_field` <br> `order_type`|`sort_field` <br> `sort_type`|
|Request parameter data type |`advertiser_id`: number <br> `ad_id`: number[] <br> `adgroup_id`: number[] <br> `campaign_id`: number[]<br> `country_code`: list|`advertiser_id`: string <br> `ad_id`: string[] <br> `adgroup_id`: string[] <br> `campaign_id`: string[]<br> `country_code`: string[]|
|Request parameter deprecated in v1.3|/|`image_mode`|
|Response parameter name|`ad_number`|`num_ads`|
|Response parameter data type | `country_code`: list[string]<br>`placement`: list[string]|`country_code`: string[]<br>`placement`: string[]|
|Response parameter data type and name|`related_ads`: number[] <br> `related_adgroups`: number[]|`related_ad_ids`: string[] <br> `related_adgroup_ids`: string[]|
|Reponse parameter deprecated in v1.3|/|`ad_cost_top5`<br>`ad_cost_top5_total`<br>`adgroup_cost_top5`<br>`adgroup_cost_top5_total`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/report/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
| Access-Token {Required}| string| Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type <br>Accepted Value: `"application/json"`.  |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID |
| material_type{Required} | string | Material type. Enum values: `VIDEO`, `IMAGE`, `INSTANT_PAGE`. |
| lifetime | boolean | Whether to query all data. <br><br>If this field is set to `true`, you do not need to specify `start_date` and `end_date`. <br><br>Supported values: `true`, `false`.<br><br>Default value: `false`.   |
| start_date{+Conditional} | string | Required when `lifetime` is set to `false`. <br><br>Start time, closed interval. Format such as: 2020-01-01 (advertiser time zone).  |
| end_date{+Conditional} | string | Required when `lifetime` is set to `false`. <br><br>End time, closed interval. Format such as: 2020-01-01 (advertiser time zone).|
| info_fields |string[] | Information you want to get about creatives. The default value is [`material_id`, `video_id`, `image_id`, `page_id`]. Allowed values: All data fields that are shown under `info` in the response. |
| metrics_fields | string[] | The metrics or dimension data that you need. The default value is: [`impressions` , `spend`]. Value range: all fields under `metrics` in the response. |
| filtering | object | Filtering criteria |
#| material_id | string[] | Material ID. You can specify at most 10 material IDs to filter the results for.  |
#| material_name | string | Material name. Fuzzy search is supported.|
#|ad_name| string| The ad name. Fuzzy search is supported.|
#|ad_id| string[] | A list of ad IDs you want to filter by. Bulk query is supported.|
#|adgroup_name| string | The ad group name that you want to filter by. Fuzzy search is supported. |
#|adgroup_id| string[] | The list of ad groups that you want to filter by. Bulk query is supported|
#|campaign_name| string | The name of the campaign that you want to filter by. Fuzzy search is supported|
#|campaign_id|string[]| The list of campaigns that you want to filter by. Bulk query is supported.|
#| country_code | string[] | List of location codes that you want to filter by. For enum values, see [Appendix - Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010) |
#| placement | list | List of ad placements that you want to filter by. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) |
#|page_id|string[]|Instant page IDs|
#|page_status|string[]|Instant page status. Enum values: `DRAFT`, `READY`|
#|page_biz_type|string[]|Instant page business type. Enum values: `CUSTOM`, `APP_PROFILE`, `INSTANT_FORM`|
#|page_template_type|string[]|Instant page template type. When `page_biz_type` includes `CUSTOM`, the allowed values are <ul><li>`CUSTOMIZED`</li><li>`INTRODUCTION`</li><li>`BRAND_SAFETY`</li><li>`SALES_PRODUCT`</li><li>`MOVIE_TRAILER`</li></ul><p>When `page_biz_type` includes `INSTANT_FORM`, the enum values are `NORMAL_FORM` and `ADVANCED_FORM`.</p>|
#|app_profile_country|string[]|Codes of the country or location that the instant page is deployed to. Valid when `instant_page_biz_type` includes `APP_PROFILE`.|
#|app_profile_external_app_id|string[]|ID of the app that the instant page directs to. Valid when `instant_page_biz_type` includes `APP_PROFILE`|
#|customized_page_external_action|string[]|Conversion events that are specific to tiktok instant pages. The enumeration values are found in: [Appendix - Conversion events](https://ads.tiktok.com/marketing_api/docs?id=1739361474981889).|
#| spend | object | Total Cost Range |
##| min | float | Minimum value, open interval. Not filling means there is no minimum. |
##| max | float | Maximum value, open interval. "No" means no limit to the maximum value. |
#|conversion | object | The conversion value|
##| min | float | The minimum value. open interval. If not specified, there is no limit on the minimum value. |
##| max |float | The maximum value. Open interval. If not specified, there is no limit on the maximum value. |
| sort_field | string | Field to sort by. Support sorting according to the creation time of the material and all the index data. Not sorted by default. |
| sort_type | string | Sorting order. Enum values: `ASC`, `DESC`. Default value: `DESC`. |
| page | number | Current number of pages. Default value: 1, range: ≥ 1 |
| page_size | number | Page size. Default value: 10, range: 1-1000 |
```

### Example

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/creative/report/get/?advertiser_id={{advertiser_id}}&material_type=VIDEO&lifetime=false&start_date=2023-01-01&end_date=2023-08-01&info_fields=["material_id", "video_id", "image_id","page_id"]&metrics_fields=["spend","impressions","clicks"]&page=1&page_size=10' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data|object|Returned data.|
#|list |object[]| Report information |
##| info | object | Material information |
###| material_id | string | Material ID |
###| video_id | string | Video ID |
###| image_id  | string | Image ID |
###| source | string | Source of material. For enum values, see [Enumeration - Material Source](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
###| country_code | string[] | Country or location code. For enum values, see [Appendix - Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010) |
###| placement | string[] | Placement. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) |
###| num_ads | number | The number of ads. |
###| total_active_days  | number | The total active days. An active day is a day that has spending (`spend` > 0). This field only supports `VIDEO` type of material. <br><br><p><span style="color:darkred"><b>Note</b></span>: </p><ul><li>This metric is supported only when `lifetime` is set to `false` in the request.</li><li>When `lifetime` is set to `true` in the request, the value of this field will be 0.</li></ul>|
###| related_ad_ids| string[]| The IDs of the related ads. This field only supports `VIDEO` type of material.|
###| adgroup_number | number | The number of related ad groups. This field only supports `VIDEO` type of material.|
###| related_adgroup_ids | string[] | The IDs of the related ad groups. This field only supports `VIDEO` type of material.|
###|page_id|string|Instant page ID|
###|page_status|string|Instant page status. Enum values: `DRAFT`, `READY`|
###|page_biz_type|string[]|Instant page business type. Enum values: `CUSTOM`, `APP_PROFILE`, `INSTANT_FORM`|
###|page_template_type|string[]|Instant page template type. When `page_biz_type` includes `CUSTOM`, the allowed values are <ul><li>`CUSTOMIZED`</li><li>`INTRODUCTION`</li><li>`BRAND_SAFETY`</li><li>`SALES_PRODUCT`</li><li>`MOVIE_TRAILER`</li></ul>When `page_biz_type` includes `INSTANT_FORM`, the allowed values are `NORMAL_FORM` and `ADVANCED_FORM`.|
###|page_thumbnail|string|Link to the page thumbnail|
###|app_profile_country|string[]|Codes of the countries or locations that the instant page is deployed to. Valid when `instant_page_biz_type` includes `APP_PROFILE`.|
###|app_profile_external_app_id|string[]|ID of the app that the instant page directs to. Valid when `instant_page_biz_type` includes `APP_PROFILE`|
###|app_profile_icon|string|App icon|
###|customized_page_external_action|string[]|Conversion events that are specific to customized instant pages|
##| metrics | object | Metrics data. Please see the [Metrics table](https://ads.tiktok.com/marketing_api/docs?id=1739067551410178) for details. |
#|page_info |object|Paging information|
##|page |number|current page number|
##|page_size |number|Paging Size|
##|total_number |number|Total|
##|total_page |number|Total number|
|request_id |string|The log id of a request, which uniquely identifies the request. |
```

### Example

``` xcodeblock
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
                    "clicks": "0",
                    "impressions": "0",
                    "spend": "0"
                },
                "info": {
                    "page_id": null,
                    "image_id": null,
                    "video_id": "{{video_id}}",
                    "material_id": "{{material_id}}"
                }
            },
            {
                "metrics": {
                    "clicks": "17",
                    "impressions": "30",
                    "spend": "0"
                },
                "info": {
                    "page_id": null,
                    "image_id": null,
                    "video_id": "{{video_id}}",
                    "material_id": "{{material_id}}"
                }
            },
            {
                "metrics": {
                    "clicks": "0",
                    "impressions": "0",
                    "spend": "0"
                },
                "info": {
                    "page_id": null,
                    "image_id": null,
                    "video_id": "{{video_id}}",
                    "material_id": "{{material_id}}"
                }
            },
            {
                "metrics": {
                    "spend": "0",
                    "clicks": "0",
                    "impressions": "0"
                },
                "info": {
                    "page_id": null,
                    "image_id": null,
                    "video_id": "{{video_id}}",
                    "material_id": "{{material_id}}"
                }
            },
            {
                "metrics": {
                    "spend": "0",
                    "clicks": "3",
                    "impressions": "3"
                },
                "info": {
                    "page_id": null,
                    "image_id": null,
                    "video_id": "{{video_id}}",
                    "material_id": "{{material_id}}"
                }
            },
            {
                "metrics": {
                    "clicks": "0",
                    "impressions": "0",
                    "spend": "0"
                },
                "info": {
                    "page_id": null,
                    "image_id": null,
                    "video_id": "{{video_id}}",
                    "material_id": "{{material_id}}"
                }
            },
            {
                "metrics": {
                    "clicks": "0",
                    "impressions": "0",
                    "spend": "0"
                },
                "info": {
                    "page_id": null,
                    "image_id": null,
                    "video_id": "{{video_id}}",
                    "material_id": "{{material_id}}"
                }
            },
            {
                "metrics": {
                    "clicks": "0",
                    "impressions": "0",
                    "spend": "0"
                },
                "info": {
                    "page_id": null,
                    "image_id": null,
                    "video_id": "{{video_id}}",
                    "material_id": "{{material_id}}"
                }
            },
            {
                "metrics": {
                    "impressions": "11",
                    "spend": "0",
                    "clicks": "1"
                },
                "info": {
                    "page_id": null,
                    "image_id": null,
                    "video_id": "{{video_id}}",
                    "material_id": "{{material_id}}"
                }
            },
            {
                "metrics": {
                    "spend": "0",
                    "clicks": "0",
                    "impressions": "0"
                },
                "info": {
                    "page_id": null,
                    "image_id": null,
                    "video_id": "{{video_id}}",
                    "material_id": "{{material_id}}"
                }
            }
        ],
        "page_info": {
            "total_number": 560,
            "total_page": 56,
            "page_size": 10,
            "page": 1
        }
    }
}
(/code);
```
