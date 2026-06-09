---
title: "(To be deprecated) Run a Legacy Smart+ Campaign report"
doc_id: 1776454619412481
path: "API Reference / (To be deprecated) Legacy Smart+ / (To be deprecated) Run a Legacy Smart+ Campaign report"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated) Run a Legacy Smart+ Campaign report

Use this endpoint to run a report on Smart+ Campaigns and Smart Performance Web Campaigns at the creative level (video, ad text, Spark Ad post, catalog video, or catalog carousel). 

> <span style="color:DarkRed">**Important**</span><br>
> - **Upgraded Smart+ API**:
>     - We are actively upgrading the Smart+ API to deliver enhanced capabilities and performance. The new Upgraded Smart+ API is already launched. Learn more about [Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1853452461203458) and [available Upgraded Smart+ API endpoints](https://business-api.tiktok.com/portal/docs?id=1843301794874370).
>     - **Get started**:
>         - **Reporting**: No additional allowlist required.
>         - **Campaign Management**: To access the upgraded Smart+ experience, contact your TikTok sales representative to have your ad account allowlisted. If your ad account is already in the allowlist, you can directly use the Upgraded Smart+ API creation and modification endpoints.  
> - **Legacy API Deprecation**: 
>     - After **March 31, 2026**, you will no longer be able to create legacy Smart+ Campaigns using the [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362) endpoint.
>     - Note that legacy Smart+ Campaigns that have not been migrated will not be transitioned to the new API automatically. Attempts to use the legacy Smart+ API endpoint afterward will result in errors, rendering new campaign creation via this endpoint unavailable.
> - **Legacy API deprecation extension request process for existing legacy Smart+ creation developers**:
>     - 1. **Submit a request**: If you require an extension, please submit an application via the [External Extension Request Form for Legacy Smart+ Creation API](https://bytedance.sg.larkoffice.com/share/base/form/shrlgsm2yMsQaSoAsMy77tcAzng).
>     - 2. **Allowlist processing**: After receiving your request, we will process an allowlist entry for the associated developer app ID. After the process, developers will be able to continue calling the `/campaign/spc/create/` endpoint until **June 30, 2026**.
>     - 3. **Extended timeline for strong business needs**: If you have a strong business case requiring an extension beyond June 30, 2026, please specify your use case in the application form, or contact your sales representative directly.

>  <span style="color:DodgerBlue">**Note**</span><br>
>   <ul><li>The data returned via this endpoint might have a latency of 30 minutes. </li><li>Smart Performance Web Campaigns will be deprecated in the next API version. To ensure a smooth API integration, we recommend that you upgrade to [Smart+ Web Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122471133186) as soon as possible. Smart+ Web Campaigns are a new AI-powered automation solution that leverages machine learning to drive business outcomes for advertisers.</li></ul>

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/campaign/spc/report/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| advertiser_id {Required}  | string  | Advertiser ID. |
| campaign_ids {Required} | string[]  |List of campaign IDs. <br> <br>Max size: 1.    |
| material_type {Required} | string   | Creative material type. <br> <br>Enum values: `SPC_VIDEO`, `SPC_AD_TEXT`, `SPC_SPARK`, `SPC_CATALOG_VIDEO`, `SPC_CATALOG_CAROUSEL`. <ul><li>If `material_type` is `SPC_VIDEO`, you can retrieve data on videos. You can specify basic data, video play, interaction, in-app event, in-app event (SKAN), page event, date, and video material details metric in the `metrics` field. </li><li>If `material_type` is `SPC_AD_TEXT`, you can retrieve data on ad texts. You can only specify basic data, interaction, in-app event, in-app event (SKAN), page event, and date metrics in the `metrics` field. </li><li>If `material_type` is `SPC_SPARK`, you can retrieve data on Spark Ad posts. You can only specify basic data, interaction, in-app event, in-app event (SKAN), and page event metrics in the `metrics` field.</li><li>If `material_type` is `SPC_CATALOG_VIDEO`, you can retrieve data on catalog videos. You can only specify basic data, interaction, in-app event, in-app event (SKAN), page event and date metrics in the `metrics` field.</li><li>If `material_type` is `SPC_CATALOG_CAROUSEL`, you can retrieve data on catalog carousel. You can only specify basic data, interaction, in-app event, in-app event (SKAN), page event and date metrics in the `metrics` field. </li></ul><br>  To learn about the specific metrics within each metric type, see [Metrics in Smart+ Campaign reports](https://business-api.tiktok.com/portal/docs?id=1776461365424129).<br><br><p><span style="color:darkred"><b>Note</b></span>:  <ul><li>When`material_type` is set to `SPC_SPARK`, ensure the `campaign_ids` field only includes a campaign that contains Spark Ads exclusively. If any campaign specified within `campaign_ids` contains both Spark Ads and non-Spark Ads, an empty list will be returned.</li><li>Smart+ Campaign reports of the `SPC_CATALOG_VIDEO` or `SPC_CATALOG_CAROUSEL` type are only supported for [Smart+ Catalog Ads](https://business-api.tiktok.com/portal/docs?id=1814122471133186#item-link-Create%20Smart+%20Catalog%20Ads).</li></ul>|
| is_include_removed_material   | boolean |  Valid only when `material_type` is `SPC_VIDEO`, `SPC_AD_TEXT`, or `SPC_SPARK`.<br> <br>Whether to query data of deleted videos, texts, or posts.<br> <br>Supported values:<ul><li>`true`: Include data of deleted videos, texts, or posts in the response. </li><li>`false` : Not include data of deleted videos, texts, or posts in the response.</li></ul>Default value: `true`. |
| metrics | string[] |Metrics to query.  <br> <br>Default value: `&#91;"spend", "impressions"&#93;` . <br> <br>Refer to [Metrics in Smart+ Campaign reports](https://business-api.tiktok.com/portal/docs?id=1776461365424129) for all supported metrics for a report.  |
| query_lifetime | boolean | Whether to request the lifetime metrics.<br> <br> Default value: `false`. <br> <br>If `query_lifetime` = `true`, the `start_date` and `end_date` parameters will be ignored.  |
| start_date  {+Conditional} | string   | Required when `query_lifetime` is `false` or not specified.<br> <br> Query start date (closed interval) in the format of `YYYY-MM-DD`.<br> <br> The date is based on the ad account time zone.   |
| end_date  {+Conditional} | string   | Required when `query_lifetime` is `false` or not specified.<br> <br>Query end date (closed interval) in the format of `YYYY-MM-DD`. <br> <br>The date is based on the ad account time zone. <br> <br><p><span style="color:darkred"><b>Note</b></span>: The time difference between `start_date` and `end_date` must be less than 365 days.  |
| page     | number  | Current page number.  <br> <br>Default value: 1.   |
| page_size    | number  | Page size.  <br> <br>Value range: 1-100.   <br>Default value: 10.   |
```
### Example
#### Run a report on videos
```xcodeblock
(code curl http)
curl --location -request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/report/get/?advertiser_id={{advertiser_id}}&material_type=SPC_VIDEO&campaign_ids=[["{{campaign_id}}"]]&query_lifetime=true&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
#### Run a report on ad texts
```xcodeblock
(code curl http)
curl --location -request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/report/get/?advertiser_id={{advertiser_id}}&material_type=SPC_AD_TEXT&campaign_ids=[["{{campaign_id}}"]]&query_lifetime=true&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
#### Run a report on Spark Ad posts
```xcodeblock
(code curl http)
curl --location -request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/report/get/?advertiser_id={{advertiser_id}}&material_type=SPC_SPARK&campaign_ids=["{{campaign_id}}"]&query_lifetime=true&is_include_removed_material=true&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
#### Run a report on catalog videos
```xcodeblock
(code curl http)
curl --location -request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/report/get/?advertiser_id={{advertiser_id}}&material_type=SPC_CATALOG_VIDEO&campaign_ids=["{{campaign_id}}"]&query_lifetime=true&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
#### Run a report on catalog carousel
```xcodeblock
(code curl http)
curl --location -request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/report/get/?advertiser_id={{advertiser_id}}&material_type=SPC_CATALOG_CAROUSEL&campaign_ids=["{{campaign_id}}"]&query_lifetime=true&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

```xtable
| Field  | Data Type | Description  |
|-----|-------|-----|
| code | number | Response code. <br>For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).  |
| message | string | Response message. <br>For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).  |
| request_id  | string  | The unique ID of the request.  |
|data | object | Returned data.  |
#| list | object[]  | Data list.  |
##| dimensions| object  | Dimension data.  |
###| material_id| string | Material ID.  |
###| campaign_id | string   | Returned only when `material_type` is `SPC_VIDEO`, `SPC_AD_TEXT`, or `SPC_SPARK`. <br><br>Campaign ID. |
###| catalog_id | string | Returned only when `material_type` is `SPC_CATALOG_VIDEO` or `SPC_CATALOG_CAROUSEL`.<br><br> Catalog ID. |
##|metrics | object | Metric data.  <br><br> The following metrics will be returned alongside the metrics you specify via `metrics` in the request based on `material_type`:<ul><li>When `material_type` is `SPC_VIDEO`: `is_removed_from_spc`, `video_id` and `file_name`. </li><li>When `material_type` is `SPC_AD_TEXT`: `is_removed_from_spc`, and `ad_text`.</li><li>When `material_type` is `SPC_SPARK`:`is_removed_from_spc`, `tiktok_item_id`, `identity_id` and `tiktok_name`.</li><li>When `material_type` is `SPC_CATALOG_VIDEO` or `SPC_CATALOG_CAROUSEL`: `catalog_name`, `catalog_create_time`, and `catalog_modify_time`.</li></ul> |
###|&#123;metric_name&#125; | string | Returned metrics, which are determined by the `metrics` field specified in your request. <br><br><span style="color:darkred"><b>Note</b></span>: If your metrics include `video_resolution`, its data type is object. |
###| is_removed_from_spc  | string | Returned only when `material_type` is `SPC_VIDEO`, `SPC_AD_TEXT`, or `SPC_SPARK`. <br><br>Whether the material is removed from the Smart+ ads. <br><br>Enum values: <br><ul><li>`"1"`: true.</li><li>`"0"`: false.</li></ul>  |
###|video_id | string |Returned only when `material_type` is `SPC_VIDEO`.<br><br>  Video ID.|
###|file_name | string |Returned only when `material_type` is`SPC_VIDEO`.<br><br> Video name.  |
###|ad_text | string | Returned only when `material_type` is `SPC_AD_TEXT`.<br><br>Ad text.   |
###| identity_id | string | Returned only when `material_type` is `SPC_SPARK`. <br><br>Identity ID.  |
###| tiktok_item_id | string | Returned only when `material_type` is `SPC_SPARK`.<br><br>The ID of the TikTok post to be used as an ad (Spark Ad).   |
###| tiktok_name | string | Returned only when `material_type` is `SPC_SPARK`. <br><br>The username of the TikTok account. <br><br><p><span style="color:darkred"><b>Note</b></span>: Currently,  Smart+ Campaigns support TikTok posts with `identity_type` as `AUTH_CODE` or `TT_USER`. For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097). |
###| catalog_name| string | Returned only when `material_type` is `SPC_CATALOG_VIDEO` or `SPC_CATALOG_CAROUSEL`. <br><br>The name of the catalog. |
###| catalog_create_time | string | Returned only when `material_type` is `SPC_CATALOG_VIDEO` or `SPC_CATALOG_CAROUSEL`. <br><br> The time when the catalog was created, in the format of `YYYY-MM-DD HH:MM:SS`(UTC+0). <br><br>Example: `2024-00-01 00:00:00`. |
###| catalog_modify_time | string | Returned only when `material_type` is `SPC_CATALOG_VIDEO` or `SPC_CATALOG_CAROUSEL`. <br><br> The time when the catalog was last modified, in the format of `YYYY-MM-DD HH:MM:SS`(UTC+0). <br><br>Example: `2024-00-01 00:00:00`. |
#| page_info | object  | Pagination information. |
##| page  | number    | Current page number.  |
##| page_size | number | Page size.  |
##| total_number  | number| Total number of results.  |
##| total_page | number  | Total pages of results. |
```

### Example
#### Run a report on videos
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "metrics": {
                    "video_id": "{{video_id}}",
                    "is_removed_from_spc": "0",
                    "file_name": "{{file_name}}",
                    "impressions": "0",
                    "spend": "0.0"
                },
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "material_id": "{{material_id}}"
                }
            }
        ],
        "page_info": {
            "page_size": 100,
            "total_page": 1,
            "page": 1,
            "total_number": 1
        }
    }
}
(/code)
```

#### Run a report on ad texts
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "metrics": {
                    "ad_text": "{{ad_text}}",
                    "is_removed_from_spc": "0",
                    "spend": "0.0",
                    "impressions": "0"
                },
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "material_id": "{{material_id}}"
                }
            },
            {
                "metrics": {
                    "ad_text": "{{ad_text}}",
                    "is_removed_from_spc": "0",
                    "spend": "0.0",
                    "impressions": "0"
                },
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "material_id": "{{material_id}}"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "total_page": 1,
            "page_size": 100,
            "total_number": 2
        }
    }
}
(/code)
```
#### Run a report on Spark Ad posts
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "page": 1,
            "total_page": 1,
            "total_number": 4,
            "page_size": 100
        },
        "list": [
            {
                "metrics": {
                    "spend": "0.0",
                    "is_removed_from_spc": "0",
                    "identity_id": "{{identity_id}}",
                    "tiktok_name": "{{tiktok_name}}",
                    "tiktok_item_id": "{{tiktok_item_id}}",
                    "impressions": "0"
                },
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "material_id": "{{material_id}}"
                }
            },
            ...,
            {
                "metrics": {
                    "spend": "0.0",
                    "is_removed_from_spc": "0",
                    "identity_id": "{{identity_id}}",
                    "tiktok_name": "{{tiktok_name}}",
                    "tiktok_item_id": "{{tiktok_item_id}}",
                    "impressions": "0"
                },
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "material_id": "{{material_id}}"
                }
            }
        ]
    }
}
(/code)
```
#### Run a report on catalog videos
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "page_size": 100,
            "total_number": 1,
            "page": 1,
            "total_page": 1
        },
        "list": [
            {
                "dimensions": {
                    "catalog_id": "{{catalog_id}}",
                    "campaign_id": "{{campaign_id}}"
                },
                "metrics": {
                    "catalog_name": "{{catalog_name}}",
                    "catalog_create_time": "{{catalog_create_time}}",
                    "impressions": "0",
                    "spend": "0.000",
                    "catalog_modify_time": "{{catalog_modify_time}}"
                }
            }
        ]
    }
}
(/code)
```
#### Run a report on catalog carousel
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "catalog_id": "{{catalog_id}}",
                    "campaign_id": "{{campaign_id}}"
                },
                "metrics": {
                    "spend": "0.000",
                    "impressions": "0",
                    "catalog_modify_time": "{{catalog_modify_time}}",
                    "catalog_create_time": "{{catalog_create_time}}",
                    "catalog_name": "{{catalog_name}}"
                }
            }
        ],
        "page_info": {
            "total_number": 1,
            "total_page": 1,
            "page_size": 100,
            "page": 1
        }
    }
}
(/code)
```


