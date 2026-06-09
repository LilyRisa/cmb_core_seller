---
title: "Get top TTO creator rankings"
doc_id: 1816378813274113
path: "API Reference / TikTok One / Get top TTO creator rankings"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get top TTO creator rankings

Use this endpoint to obtain a list of up to 100 US creators who rank highest for a ranking label on TikTok One (TTO) Creator Leaderboard. 

## Before you start
Obtain TTO creator ranking labels using [/tto/tcm/category/label/](https://business-api.tiktok.com/portal/docs?id=1816372654109698) with `label_type` set to `RANKING`.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/rank/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok One Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TikTok One Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
| ranking_type {Required} | string | The type of ranking. <br><br>Enum values: <ul><li>`BRANDED_CONTENT`: ranking by branded content.</li><li>`ORGANIC_CONTENT`: ranking by organic content. </li></ul>|
| time_period {Required} | string | The time period for the ranking.  <br><br>Enum values: <ul><li> `WEEK`: one week. </li><li>`MONTH`: one month. </li></ul>|
| time_period_lookback {Required} | string | The specific lookback period for the ranking.<br><br>Enum values: <ul><li>`ONE`: the most recent available period. </li><li>`TWO`: the second most recent period. </li><li>`THREE`: the third most recent period.</li></ul><br>For example, if the most recent available monthly period is October and you set `time_period` to `MONTH` and `time_period_lookback` to `TWO`, the system will return ranking data for September. |
| label_id {Required} | string | Creator label ID.<br><br><ul><li>When `ranking_type` is `BRANDED_CONTENT`, specify creator industry labels via this field.<ul><li>To obtain creator industry labels, use [/tto/tcm/category/label/](https://business-api.tiktok.com/portal/docs?id=1816372654109698). The creator industry labels will be available from the returned `industry_labels`. </li></ul></li><li> When `ranking_type` is `ORGANIC_CONTENT`, specify content tag labels via this field.<ul><li>To obtain content tag labels, use [/tto/tcm/category/label/](https://business-api.tiktok.com/portal/docs?id=1816372654109698). The content tag labels will be available from the returned `content_labels`. </li></ul></li></ul> |
| country_code {Required} | string | The code of the country or region that the creator ranking is for. <br><br>Currently, the only supported enum value is `US` because the creator ranking data is only available for the United States. |
| page | integer | Current page number. <br><br>Value range: ≥ 1.  Default value: 1. |
| page_size | integer | Page size. <br><br>Value range: 1-100. Default value: 20.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/rank/?tto_tcm_account_id={{tto_tcm_account_id}}&ranking_type=ORGANIC_CONTENT&time_period=WEEK&time_period_lookback=ONE&label_id=11014001&country_code=US' \
--header 'Access-Token: {{Access-Token}}'
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
#| creators | object[] | The list of up to the top 100 creators, sorted by rank.<br><br><p><span style="color:darkred"><b>Note</b></span>: The actual number of returned creators may be less than 100, depending on the available data at the time of the query. |
##| ranking_change | string | The change in rank position for the creator compared to the rank in the last time period. <br><br><ul><li>A positive `rank_change` indicates an increase in rank. For example, if the rank position shifts from 5 to 1, `rank_change` will be `+4`.</li><li>A negative `rank_change` indicates a decrease in rank. For example, if the rank position shifts from 1 to 5, `rank_change` will be `-4`.</li><li>A `rank_change` of `0` indicates no changes in rank. </li><li>A `rank_change` marked as `N/A` indicates that the creator was not present in the top ranking list in the last time period.</li></ul> |
##| profile_image | string | The temporary URL for the profile image of the creator, which needs to be re-acquired after expiration. The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. <br><br>Example: `https://p16-sign.tiktokcdn-us.com/obj/tos-useast5-avt-0123-tx/ab123456789d1234567ab54de76f0d3b?lk3s=429d27ce&nonce=42773&refresh_token=123a4ab1234567a1aa1a11a1aa11aa1a&x-expires=1732046400&x-signature=aa1Q11aa1Da1a1aVZO%1Fa1oADaL1%1D&shp=111d11ce&shcp=-`. |
##| country | string | The code of the country or region where the creator is located. <br><br>Currently, the only supported enum value is `US` because the creator ranking data is only available for the United States. |
##| handle | string | The handle name of the creator. |
##| display_name | string | The display name of the creator. |
##| industry_labels | object[] | The list of industry labels associated with the creator.<br>Creator industry labels are assigned to creators based on the commercial content they produce, including ad creatives and branded content on their TikTok accounts, and represent their commercial experience in certain industries. <br><br><p><span style="color:darkred"><b>Note</b></span>: Creators who no longer have the queried label may still be returned in the results. This is because the label was associated with them at the time the rank was generated. However, these labels can change over time, so a creator might lose a label after the rank has been generated. |
###| label_id | string | The ID of the creator industry label. |
###| label_name | string | The name of the creator industry label. |
##| content_labels | object[] | The list of content tag labels associated with the creator.<br> Content tag labels are assigned to creators based on the main focus of the creator’s TikTok account, and represent the direction of the creator’s organic content.<br><br><p><span style="color:darkred"><b>Note</b></span>: Creators who no longer have the queried label may still be returned in the results. This is because the label was associated with them at the time the rank was generated. However, these labels can change over time, so a creator might lose a label after the rank has been generated. |
###| label_name | string | The ID of the content tag label. |
###| label_id | string | The name of the content tag label. |
##| score | float | <ul><li>When `ranking_type` is `BRANDED_CONTENT`, this field represents commercial score, which is calculated based on the creator’s experience with commercial content with and the performance of their past commercial content.</li><li>When `ranking_type` is `ORGANIC_CONTENT`, this field represents follower growth score (an indicator of how quickly the creator’s follower count is growing), which is calculated based on the creator’s follower count growth and video performance within the specified time period.</li></ul> <br> Example: 81.56906666666666. |
##| number_of_followers | integer | Follower count. <br>The creator’s current follower count.<br><br>Example: 242703. |
##| branded_content_video_views | integer | Branded content video views. <br>The total views of all branded content videos, where the video’s branded content toggle was enabled, produced by the Creator within the specified time period. <br><br> Example: 528377.<br><br><p><span style="color:darkred"><b>Note</b></span>: This field returns a non-null value only when `ranking_type` is `BRANDED_CONTENT` in the request. |
##| branded_content_engagement_rate | float | Branded content engagement rate. <br>The engagement rate of branded content within the specified time period, calculated as (total number of comments, likes, and shares of branded content) / (total video views). <br><br>For example, a value of 3.02 means the branded content engagement rate is 3.02%. <br><br><p><span style="color:darkred"><b>Note</b></span>: This field returns a non-null value only when `ranking_type` is `BRANDED_CONTENT` in the request. |
##| total_video_views | integer | Total video views. <br>The total number of views from all of the creator’s videos within the specified time period.<br><br>Example: 633516. |
##| engagement_rate | float | Engagement rate. <br>The collective engagement rate for all of the creator’s videos during the specified time period. This is calculated as (total number of comments, likes and shares)/(total video views). <br><br>For example, a value of 2.97 means the branded content engagement rate is 2.97%. |
##| starting_price | integer | The creator’s starting base price.<br><br>Example: 30000. <br><br><p><span style="color:darkred"><b>Note</b></span>: If the creator’s starting base pay is not set, the value of this field will be null. |
##| starting_price_currency | string | The currency for the creator’s starting base price.<br><br>Example: `USD`. <br><br><p><span style="color:darkred"><b>Note</b></span>: If the creator’s starting base pay is not set, the value of this field will be an empty string (`""`). |
##| follower_growth | integer | The number of followers gained during the specified time period.  <br><br>Example: 180476.  <br><br><p><span style="color:darkred"><b>Note</b></span>: This field returns a non-null value only when `ranking_type` is `ORGANIC_CONTENT` in the request. |
#| last_updated_at | string | The UTC+0 time when the ranking data was last updated, in [ISO-8601](https://en.wikipedia.org/wiki/ISO_8601) format.  <br><br>Example: `2024-11-19T14:14:22Z`. |
#| page_info | object | Pagination information. |
##| page | number | Current page number. |
##| page_size | number | Page size. |
##| total_number| number | Total number of results. |
##| total_page | number | Total pages of results. |
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
        "creators": [
            {
                "branded_content_engagement_rate": null,
                "branded_content_video_views": null,
                "content_labels": [
                    {
                        "label_id": "11014001",
                        "label_name": "Automobiles"
                    }
                ],
                "country": "US",
                "display_name": "{{display_name}}",
                "engagement_rate": 1.95,
                "follower_growth": 180476,
                "handle": "{{handle}}",
                "industry_labels": [
                    {
                        "label_id": "14100000000",
                        "label_name": "Oral Care"
                    }
                ],
                "number_of_followers": 10711990,
                "profile_image": "{{profile_image}}",
                "ranking_change": "+1",
                "score": 91.476275,
                "starting_price": 30000,
                "starting_price_currency": "USD",
                "total_video_views": 329546252
            },
            ...,
            {
                "branded_content_engagement_rate": null,
                "branded_content_video_views": null,
                "content_labels": [
                    {
                        "label_id": "11014001",
                        "label_name": "Automobiles"
                    }
                ],
                "country": "US",
                "display_name": "{{display_name}}",
                "engagement_rate": 1.94,
                "follower_growth": 6083,
                "handle": "{{handle}}",
                "industry_labels": null,
                "number_of_followers": 132742,
                "profile_image": "{{profile_image}}",
                "ranking_change": "N/A",
                "score": 82.74355,
                "starting_price": null,
                "starting_price_currency": "",
                "total_video_views": 3213298
            }
        ],
        "last_updated_at": "2024-11-19T14:14:22Z",
        "page_info": {
            "page": 1,
            "page_size": 20,
            "total_number": 100,
            "total_page": 5
        }
    }
}
(/code)
```
