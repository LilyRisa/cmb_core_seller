---
title: "Report on TTO Creator Marketplace videos"
doc_id: 1815693573340162
path: "API Reference / TikTok One / Report on TTO Creator Marketplace videos"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Report on TTO Creator Marketplace videos

Use this endpoint to obtain the reporting data on videos within a TikTok One (TTO) Creator Marketplace campaign. 

><span style="color:DodgerBlue">**Note**</span><br> 
>  <ul><li>Reporting data is available from the moment the video is uploaded to TikTok. If a Creator retroactively uses an invite link to tag a video, the reporting data will be backfilled.</li><li>For campaigns that are created via API, reporting data will not be accessible through [the TTO Creator Marketplace platform](https://ads.tiktok.com/creative/creatormarketplace).</li></ul>

## Comparing TCM and TTO Creator Marketplace endpoints
The following table outlines the differences between TCM and TTO Creator Marketplace endpoints.
```xtable
| Changes {19%}| TCM Endpoint{44%} | TTO Creator Marketplace Endpoint{37%} |
|---|---|---|
| Endpoint path | /v1.3/tcm/report/get/v2/|/v1.3/tto/tcm/report/ |
| Same request parameters | `start_date`<br>`end_date`<br>`page`<br>`page_size` | `start_date`<br>`end_date`<br>`page`<br>`page_size` |
| Deprecated request parameters   | N/A |`tcm_account_id`<br>`order_ids` <br>`need_daily_stats` <br> `metrics` |
| New request parameters   | N/A |`tto_tcm_account_id`<br>`campaign_id` |
| Response parameters of the same name <br><br>Note: These parameters might be nested within different objects or object arrays.| `insights.metrics.video_id`<br>`insights.metrics.video_views`<br>`insights.metrics.video_views_organic` <br>`insights.metrics.video_views_paid`<br>`insights.metrics.reach` <br>`insights.metrics.engagement_count`<br>`insights.metrics.engagement_rate`<br>`insights.metrics.video_completion_rate`<br>`insights.metrics.likes`<br>`insights.metrics.comments`<br>`insights.metrics.shares` <br>`insights.metrics.total_play_time`<br>`insights.metrics.average_view_time`<br> `insights.metrics.two_seconds_views` <br> `insights.metrics.six_seconds_views`<br> `insights.metrics.video_view_retention`<br>`insights.metrics.video_views_by_source`<br>`insights.metrics.video_views_by_source.for_you` <br>`insights.metrics.video_views_by_source.hashtag`<br>`insights.metrics.video_views_by_source.sound`<br>`insights.metrics.video_views_by_source.personal_profile` <br>`insights.metrics.video_views_by_source.search`<br> `insights.metrics.video_views_by_source.following` <br>`insights.metrics.audience_interest_distribution`<br>`insights.metrics.audience_interest_distribution.<br>interest_tag`<br>`insights.metrics.audience_interest_distribution.<br>percentage`<br>`insights.metrics.daily_stats` <br>`insights.metrics.daily_stats.date`<br> `insights.metrics.daily_stats.views`<br>`insights.metrics.daily_stats.video_views_organic`<br>`insights.metrics.daily_stats.video_views_paid` <br>`insights.metrics.daily_stats.engagement_rate`<br>`insights.metrics.daily_stats.video_completion_rate`<br>`insights.metrics.daily_stats.likes` <br> `insights.metrics.daily_stats.comments` <br> `insights.metrics.daily_stats.shares`<br>`page_info`<br> `page_info.page` <br> `page_info.page_size`<br> `page_info.total_page`<br> `page_info.total_number`| `videos.video_info.video_id` <br>`videos.video_views`<br>`videos.video_views_organic`<br>`videos.video_views_paid`<br> `videos.reach`<br>`videos.engagement_count` <br> `videos.engagement_rate`<br> `videos.video_completion_rate`<br> `videos.likes`<br> `videos.comments`<br>`videos.shares`<br> `videos.total_play_time`<br>`videos.average_view_time` <br> `videos.two_seconds_views`<br> `videos.six_seconds_views` <br>`videos.video_view_retention`<br>`videos.video_views_by_source` <br>`videos.video_views_by_source.for_you` <br>`videos.video_views_by_source.hashtag`<br>`videos.video_views_by_source.sound` <br> `videos.video_views_by_source.personal_profile`<br>`videos.video_views_by_source.search` <br>`videos.video_views_by_source.following` <br>`videos.audience_interest_distribution`<br> `videos.audience_interest_distribution.<br>interest_tag` <br>`videos.audience_interest_distribution.<br>percentage`<br> `videos.daily_stats` <br>`videos.daily_stats.date`<br>`videos.daily_stats.views`<br> `videos.daily_stats.video_views_organic` <br>`videos.daily_stats.video_views_paid`<br>`videos.daily_stats.engagement_rate`<br> `videos.daily_stats.video_completion_rate`<br>`videos.daily_stats.likes`<br>`videos.daily_stats.comments`<br> `videos.daily_stats.shares` <br> `page_info` <br> `page_info.page` <br> `page_info.page_size`<br> `page_info.total_page`<br> `page_info.total_number`|
| Deprecated response parameters   | N/A |`insights`<br>`insights.dimensions`<br> `insights.dimensions.tcm_account_id`<br>`insights.dimensions.order_id` <br>`insights.dimensions.order_type`<br>`insights.dimensions.video_id`<br>`insights.dimensions.handle_name`<br>`insights.dimensions.video_first_release_time`<br>`insights.metrics`<br>`insights.metrics.video_boosted_date`<br>`insights.metrics.engagement_rate_benchmark_p25`<br>`insights.metrics.engagement_rate_benchmark_p50`<br>`insights.metrics.<br>most_popular_country_state_distribution`<br>`insights.metrics.most_popular_country_state_distribution.country`<br>`insights.metrics.most_popular_country_state_distribution.locale_distribution` <br>`insights.metrics.most_popular_country_state_distribution.locale_distribution.locale`<br>`insights.metrics.most_popular_country_state_distribution.locale_distribution.percentage`<br>`insights.metrics.<br>video_complete_rate_benchmark_p25`<br>`insights.metrics.<br>video_complete_rate_benchmark_p50`<br>`insights.metrics.<br>lifetime_top_country_distribution`<br>`insights.metrics.<br>lifetime_top_country_distribution.country`<br>`insights.metrics.<br>lifetime_top_country_distribution.percentage`<br>`insights.metrics.lifetime_device_distribution`<br>`insights.metrics.<br>lifetime_device_distribution.device`<br>`insights.metrics.<br>lifetime_device_distribution.percentage`<br>`insights.metrics.lifetime_gender_distribution`<br>`insights.metrics.lifetime_gender_distribution.<br>gender`<br>`insights.metrics.lifetime_gender_distribution.<br>percentage`<br>`insights.metrics.lifetime_top_age_distribution`<br>`insights.metrics.lifetime_top_age_distribution.<br>age`<br>`insights.metrics.lifetime_top_age_distribution.<br>percentage`<br>`insights.metrics.daily_stats.reach`|
| New response parameters | N/A   |`tto_tcm_account_id`<br> `campaign_id`<br>`country_code`<br>`videos`<br>`videos.video_info` <br>`videos.video_info.create_time`<br> `videos.video_info.embed_url`<br>`videos.video_info.thumbnail_url`<br>`videos.video_info.authorization_code`<br>`videos.creator_info`<br>`videos.creator_info.display_name`<br>`videos.creator_info.handle_name`<br>`videos.audience_genders_distribution`<br>`videos.audience_genders_distribution.gender`<br>`videos.audience_genders_distribution.percentage`<br>`videos.audience_countries_distribution`<br>`videos.audience_countries_distribution.country`<br>`videos.audience_countries_distribution.<br>percentage`<br>`videos.audience_age_distribution`<br>`videos.audience_age_distribution.age`<br>`videos.audience_age_distribution.percentage`<br>`videos.audience_device_distribution`<br>`videos.audience_device_distribution.device`<br>`videos.audience_device_distribution.percentage`<br>`videos.audience_locale_distribution`<br>`videos.audience_locale_distribution.locale`<br>`videos.audience_locale_distribution.percentage`<br>`videos.likes_paid`<br>`videos.likes_organic`<br>`videos.comments_paid`<br>`videos.comments_organic`<br>`videos.shares_paid`<br>`videos.shares_organic`<br>`videos.engagement_rate_paid`<br>`videos.engagement_rate_organic`<br>`videos.anchor_metrics`<br>`videos.anchor_metrics.anchor_views`<br>`videos.anchor_metrics.anchor_clicks`<br>`videos.anchor_metrics.anchor_unique_views`<br>`videos.anchor_metrics.anchor_unique_clicks`<br>`videos.anchor_id`<br>`campaign_type`<br>`campaign_channel_type`<br>`ad_impressions`<br>`ad_cost`<br>`ad_cpa`<br>`ad_ctr`<br>`ad_cvr`<br>`reach_organic`<br>`reach_paid`<br>`video_completion_rate_organic`<br>`video_completion_rate_paid`<br>`average_view_time_organic`<br>`average_view_time_paid`<br>`organic_two_seconds_views`<br>`paid_two_seconds_views`<br>`organic_six_seconds_views`<br>`paid_six_seconds_views`<br>`audience_language_distribution`<br>`language`<br>`percentage`<br>`videos.daily_stats.engagement_rate_organic`<br>`videos.daily_stats.engagement_rate_paid`<br>`videos.daily_stats.likes_organic`<br>`videos.daily_stats.likes_paid`<br>`videos.daily_stats.comments_organic`<br>`videos.daily_stats.comments_paid`<br>`videos.daily_stats.shares_organic`<br>`videos.daily_stats.shares_paid`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/report/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok One Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
|Country-Code {Required}|string|A country or region code that is associated with the TikTok One Creator Marketplace campaign (`campaign_id`). <br> <br>To obtain a valid country or region code associated with the TikTok One Creator Marketplace campaign, use [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258) and select one code from the returned `country_codes`. |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TikTok One Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
|campaign_id {Required} | string | The ID of a TikTok One Creator Marketplace campaign with a [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link) or [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link). <br><br>To create a campaign and obtain the campaign ID, use [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458). |
| start_date {+Conditional} | string | Required when `end_date` is passed. <br><br>  <ul><li>If you specify both `start_date` and `end_date`, the response will include the `daily_stats` parameter that shows the daily metrics for the specified time range.</li><li>If you don't specify both `start_date` and `end_date`, the response will not include the daily metrics (`daily_stats`).</li></ul> <br> Report start date, in the format of `YYYY-MM-DD` (UTC+0 time).  <br> <br>The `start_date` should not be earlier than the campaign creation time. To obtain the campaign creation time, check the `create_time` returned from [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458) or [/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258).<br> <br> Example: `2024-01-01`. |
| end_date {+Conditional} | string | Required when `start_date` is passed.<br><br>  <ul><li>If you specify both `start_date` and `end_date`, the response will include the `daily_stats` parameter that shows the daily metrics for the specified time range.</li><li>If you don't specify both `start_date` and `end_date`, the response will not include the daily metrics (`daily_stats`).</li></ul> <br>Report end date, in the format of `YYYY-MM-DD` (UTC+0 time).<br> <br> Example: `2024-02-01`. |
| page | integer | Current page number. <br>This field specifies which page of results to retrieve. The number of pages is based on the total number of records and the `page_size`. <br><br>Default value: 1. <br>Value range: ≥ 1. |
|page_size|integer|Page size.<br>This specifies the number of records (the performance metrics for videos linked to the specified campaign ID) that you try to retrieve for each request.<br><br>Default value: 25.<br>Value range: 1-100.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/report/?tto_tcm_account_id={{tto_tcm_account_id}}&campaign_id={{campaign_id}}&start_date=2024-09-25&end_date=2024-09-27' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Country-Code: {{Country-Code}}' \
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
#| tto_tcm_account_id | string | The ID of the TikTok One Creator Marketplace account. |
#| campaign_id | string | The ID of the TikTok One Creator Marketplace campaign. |
#| country_code | string | A country or region code that is associated with the TikTok One Creator Marketplace campaign. |
#| campaign_type | string | The type of the TTO Creator Marketplace campaign.<br><br>Enum values:<ul><li>`CAMPAIGN`: A campaign with a [campaign-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Campaign-level%20invite%20link).</li><li>`BRAND_LINK`: A campaign with a [brand-level invite link](https://business-api.tiktok.com/portal/docs?id=1830337139853313#item-link-Brand-level%20invite%20link).</li></ul> |
#|campaign_channel_type|string|The channel type where the campaign was created.<br><br>Enum values: <ul><li>`STANDARD_CAMPAIGN`: A Standard Campaign created on the TikTok One platform.</li><li>`LITE_CAMPAIGN`: A Lite Campaign created on the TikTok One platform.</li><li>`API_LITE_CAMPAIGN`: A Lite Campaign created via the API.</li></ul>|
#| page_info | object | Pagination information. |
##| page | integer | Current page number. |
##| page_size | integer | Current page size. |
##| total_page | integer | Total number of pages. |
##| total_number | integer | Total number of videos.<br><br><p><span style="color:darkred"><b>Note</b></span>: This number is country-specific and depends on the header parameter `Country-Code`. For example, if a campaign includes two videos in the US and one video in Canada, `total_number` will be 2 when `Country-Code` is set to `US`, and 1 when set to `CA`. |
#| videos | object[] | The list of videos that have been linked to the TikTok One Creator Marketplace campaign. |
##| video_info | object | Information about the video. |
###| create_time | string | The time when the video was published, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0 time). <br><br>Example: `2024-01-01 00:00:01`. |
###| embed_url | string | An embeddable link to the TikTok video. This URL can be used to embed the TikTok video in external websites or applications.<br><br> Example: `https://v77.tiktokcdn.com/&#123;&#123;embed_url&#125;&#125;`. |
###| thumbnail_url | string | Temporary URL for thumbnail of the video. Expiration date-time is included in the `x-expires` query param as Epoch/Unix time in seconds. <br><br>Example: `https://p16-sign-va.tiktokcdn.com/obj/tos-maliva-p-0068/12345678a12e3456a02017dcf822217e_1725607566?lk3s=c6b69ba0&nonce=51584&refresh_token=73f581ee21c7de32951926a0104f51a2&x-expires=1729173600&x-signature=vIBf8%2Bmp4%2FxXQpKrca1B5%2B%2FfpTU%3D&shp=c6b69ba0&shcp=-`. |
###| video_id | string | The unique identifier for the video. |
###| authorization_code | string | The Spark Ads authorization code for the video. |
##| reach_organic | integer | Organic reach (unique viewers).<br>The estimated number of unique viewers who viewed the video at least once from organic traffic. |
##| reach_paid | integer | Paid reach (unique viewers).<br>The estimated number of unique viewers who viewed the video at least once from paid traffic. |
##| video_completion_rate_organic | double | Organic video completion rate.<br>The percentage of viewers who watched a video in its entirety. It’s calculated by dividing the number of complete video views by total number of video views. |
##| video_completion_rate_paid | double | Paid video completion rate.<br>The percentage of viewers who watched a video in its entirety. It’s calculated by dividing the number of complete video views by total number of video views. |
##| average_view_time_organic | double | Organic average view time, in seconds.<br>The average amount of time viewers spend watching a video. It’s calculated by dividing the total organic play time of the video by the total number of video views. |
##| average_view_time_paid | double | Paid average view time, in seconds.<br>The average amount of time viewers spend watching a video. It’s calculated by dividing the total paid play times of the video by the total number of video views. |
##| organic_two_seconds_views | double | Organic 2-Second video views.<br>Percentage of views that lasted at least 2 seconds long. It's calculated as the number of organic views that lasted more than 2 seconds divided by views. |
##| paid_two_seconds_views | double | Paid 2-Second video views.<br>Percentage of views that lasted at least 2 seconds long. It's calculated as the number of paid views that lasted more than 2 seconds divided by views. |
##| organic_six_seconds_views | double | Organic 6-Second video views.<br>Percentage of views that lasted at least 6 seconds long. It's calculated as the number of organic views that lasted more than 6 seconds divided by views. |
##| paid_six_seconds_views | double | Paid 6-Second video views.<br>Percentage of views that lasted at least 6 seconds. It's calculated as the number of paid views that lasted more than 6 seconds divided by views. |
##| audience_language_distribution | object[] | Distribution of video views by audience countries.<br>Updated daily. |
###| language | string | The language code representing the user’s configured browsing language. You can use this value to map language codes to languages by using [/tool/language/](https://business-api.tiktok.com/portal/docs?id=1737188554152962).<br>Example: `en`. |
###| percentage | double | The percentage of the audience using a language.<br>Example: `0.10526315789473684`. <br>A value of 0.10526315789473684 indicates a percentage of approx. 10.52%. |
##| creator_info | object | Information about the TikTok Creator. |
###| display_name | string | The display name of the Creator. |
###| handle_name | string | The handle name of the Creator. |
##| ad_clicks | float | The number of clicks on your ads. |
##| ad_conversions | float | The number of times your ad achieved an outcome based on the objective and settings you selected.<br><br><p><span style="color:darkred"><b>Note</b></span>: This field is based on the selected campaign objective and settings. |
##|ad_cost|double|Ads cost.<br>The estimated total amount of money you've spent on your campaign, ad group, or ad during its schedule.<br><br>Updated in real time.|
##|ad_cpa|double|Cost per conversion.<br>The average amount of money you've spent on a paid conversion.<br><br>Updated in real time.|
##| ad_cpc | float | The average amount of money you've spent on a paid click. |
##| ad_cpm | float | The average amount of money you've spent per 1,000 paid impressions. |
##|ad_ctr|double|CTR (destination).<br>The percentage of times people saw your ad and performed a click.<br><br>Updated in real time.|
##|ad_cvr|double|Conversion rate (CVR).<br>The percentage of conversions from all impressions on your ads.<br><br>Updated in real time.|
##|ad_impressions|integer|Impressions.<br>The number of times your ads were on screen.<br><br>Updated in real time.|
##| video_views | integer | Total views.<br>Total number of times the video has been watched. <br><br> Updated in real time |
##| video_views_organic | integer | Organic video views.<br> Total number of views acquired through organic delivery, and not through [Spark Ads](https://business-api.tiktok.com/portal/docs?id=1739470744631298). <br><br> Updated in real time |
##| video_views_paid | integer | Paid views.<br> Total number of views acquired through [Spark Ads](https://business-api.tiktok.com/portal/docs?id=1739470744631298).<br><br> Updated in real time |
##| reach | integer | Reach (Unique viewers). <br> Estimated number of unique users who viewed the video at least once.<br><br>Updated daily |
##| engagement_count | integer | Engagement count. <br> Total number of likes (`likes`), shares (`shares`), and comments (`comments`). <br><br> Updated in real time |
##| engagement_rate | double | Engagement rate, calculated as the engagement count (`engagement_count`) divided by the total number of views (`video_views`). <br><br> Updated in real time |
##| engagement_rate_organic | double  | Organic engagement rate. <br>The percentage of the audience who interacted with the campaign or video through organic delivery. It is calculated by dividing the number of organic interactions (likes, comments, and shares from non-paid views) by the total number of organic video views.<br><br> Updated in real time |
##| engagement_rate_paid | double  | Paid engagement rate.<br>The percentage of the audience who interacted with the campaign or video through Spark Ads. It is calculated by dividing the number of paid interactions (likes, comments, and shares from paid views) by the total number of paid video views.<br><br>Updated in real time |
##| video_completion_rate | double | Video completion rate, calculated as total completed video views divided by the total number of views (`video_views`). <br><br> Updated in real time for the first seven days after the post was created and then updated daily afterwards |
##| likes | integer | Likes. <br>Total number of likes associated with the video. <br><br>Updated in real time |
##| likes_organic |integer | Organic likes. <br>Total number of likes generated through non-paid (organic) delivery. <br><br>Updated in real time |
##| likes_paid |integer | Paid likes. <br>Total number of likes generated through Spark Ads. <br><br>Updated in real time |
##| comments | integer | Comments.<br> Total number of comments associated with the video. <br><br>Updated in real time |
##| comments_organic | integer | Organic comments.<br>Total number of comments generated through non-paid (organic) delivery. <br><br>Updated in real time |
##| comments_paid | integer| Paid comments.<br>Total number of comments generated through Spark Ads. <br><br>Updated in real time |
##| shares | integer | Shares. <br> Total number of shares associated with the video.<br><br> Updated in real time |
##| shares_organic | integer | Organic shares. <br>Total number of shares generated through non-paid (organic) delivery.<br><br> Updated in real time |
##| shares_paid | integer | Paid shares. <br> Total number of shares generated through Spark Ads. <br><br>Updated in real time |
##| total_play_time | integer | Total play time, in seconds.<br> Total amount of time users spend watching the video. <br><br> Updated in real time for the first seven days after the post was created and then updated daily afterwards |
##| average_view_time | double | Average view time, in seconds.<br>Average amount of time users spend watching the video, calculated as the total play time (`total_play_time`) divided by views (`video_views`). <br><br>Updated in real time for the first seven days after the post was created and then updated daily afterwards |
##| two_seconds_views | double | 2-Second video views. <br> Percentage of views that lasted more than two seconds, calculated as the number of views that lasted more than 2 seconds divided by views (`video_views`).<br><br>Updated daily |
##| six_seconds_views | double | 6-Second video views. <br>Percentage of views that lasted more than six seconds, calculated as the number of views that lasted more than six seconds divided by views (`video_views`).<br><br> Updated daily |
##| video_view_retention | double[] | A list of numbers indicating the video retention rate at each second. <br><br>For instance, the value `&#91;1, 0.61, 0.27, 0.2, 0.16, 0.14, 0.11, 0.09, 0.08, 0.07, 0.07, 0.06, 0.05, 0.05, 0.05, 0.04, 0.04&#93;` indicates that during the first second, the video retention rate decreases from 100% to 61%.  <br><br>Updated in real time for the first seven days after the post was created and then updated daily afterwards |
##| video_views_by_source | object | View sources. <br> Breakdown of how viewers were exposed to the video. <br><br>Updated in real time for the first seven days after the post was created and then updated daily afterwards |
###| for_you | integer | Video views from For You page. |
###| hashtag | integer| Video views from hashtag. |
###| sound | integer| Video views from sound. |
###| personal_profile | integer | Video views from personal profile. |
###| search | integer | Video views from search. |
###| following | integer | Video views from following. |
###| other | integer | Video views from other. |
##| audience_genders_distribution | object[] | Breakdown of male and female users among viewers. <br><br> Updated daily|
###| gender | string | Gender.<br><br> Enum values:<ul><li> `FEMALE`: female. </li><li> `MALE`: male. </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: This field might return `null` if the system cannot determine the gender.  |
###| percentage | double | Percentage. |
##| audience_countries_distribution | object[] | Distribution of video views by audience countries. <br><br> Updated daily |
###| country | string | Country or region code.<br><br> Example: `US`. |
###| percentage | double | Percentage. |
##| audience_age_distribution | object[] | Breakdown of viewers by age group.<br><br>Updated daily |
###| age | string | Age group. <br><br>Enum values:<ul><li>`1`: 13-17. </li><li>`2`: 18-24.</li><li>`3`: 25-34.</li><li> `4`: 35-44 </li><li>`5`: 45-54.</li><li>`6`: 55+. </li></ul>|
###| percentage | double | Percentage. |
##| audience_device_distribution | object[] | Breakdown of viewers by operating system. <br><br>  Updated daily |
###| device | string | Device operating system.<br><br> Enum values:<ul><li>`android`: Android.</li><li>`ios`: iOS.</li></ul>|
###| percentage | double | Percentage. |
##| audience_locale_distribution | object[] | Breakdown of viewers by state-level locales in the country or region. <br><br> Only available for the US. <br><br>  Updated daily |
###| locale | string | State-level locale. <br><br> For enum values, see [Location IDs](https://business-api.tiktok.com/portal/docs?id=1739311040498689). |
###| percentage | double | Percentage. <br>Total number of viewers in the state (`locale`) divided by the total number of viewers in the country or region. |
##| audience_interest_distribution | object[] | Breakdown of viewers by interest categories.<br><br>Updated daily |
###| interest_tag | string | The ID of the interest category.<br><br> To learn about the corresponding interest category name for each interest category ID, see <a href="#item-link-Mapping between interest category ID and name">Mapping between interest category ID and name</a>. |
###| percentage | double | Percentage. |
##|anchor_id|string|The ID of the anchor that is associated with the campaign.|
##|anchor_metrics|object|Anchor metrics.|
###|anchor_views|number|Anchor views.<br>Total number of times the anchor has been viewed.|
###|anchor_clicks|number|Anchor clicks.<br>Total number of clicks on the anchor.|
###|anchor_unique_views|number|Unique anchor views.<br>The number of unique viewers who saw the anchor.|
###|anchor_unique_clicks|number|Unique anchor clicks.<br>The number of times the anchor was clicked by distinct users.|
##| daily_stats | object[] | Daily statistics breakdown.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>`daily_stats` is only available for the most recent 90 days. If the specified `start_date` and `end_date` fall outside this 90-day window, `daily_stats` will return null.</li><li>We won't fill in the missing metrics with any default value (such as 0), so if one metric is missing from one day, that means the data is not available.</li></ul> |
###| date | string | Date, in the format of `YYYY-MM-DD` (UTC+0 time). |
###| views | integer | Total views. <br>Total number of times the video has been watched on the date. |
###| video_views_organic | integer | Organic video views.<br>Total number of views acquired through organic delivery, and not through [Spark Ads](https://business-api.tiktok.com/portal/docs/create-spark-ads/v1.3) on the date. |
###| video_views_paid | integer | Paid views. <br>Total number of views acquired through [Spark Ads](https://business-api.tiktok.com/portal/docs/create-spark-ads/v1.3) on the date. |
###| engagement_rate | double | Engagement rate, calculated as the total number of likes (`likes`), shares (`shares`), and comments (`comments`) divided by the total number of views (`views`) on the date. |
###|engagement_rate_organic|double|Organic engagement rate.<br>Engagement rate acquired through organic delivery, and not through [Spark Ads](https://business-api.tiktok.com/portal/docs/create-spark-ads/v1.3) on the date.|
###|engagement_rate_paid|double|Paid engagement rate.<br>Engagement rate acquired through [Spark Ads](https://business-api.tiktok.com/portal/docs/create-spark-ads/v1.3) on the date.|
###| video_completion_rate | double | Video completion rate, calculated as total completed video views divided by the total number of views (`views`) on the date. |
###| likes | integer | Likes.<br> Total number of likes associated with the video on the date. |
###|likes_organic|integer|Organic likes.<br>Total number of likes acquired through organic delivery, and not through [Spark Ads](https://business-api.tiktok.com/portal/docs/create-spark-ads/v1.3) on the date.|
###|likes_paid|integer|Paid likes.<br>Total number of likes acquired through [Spark Ads](https://business-api.tiktok.com/portal/docs/create-spark-ads/v1.3) on the date.|
###| comments | integer | Comments.<br> Total number of comments associated with the video on the date. |
###|comments_organic|integer|Organic comments.<br>Total number of comments acquired through organic delivery, and not through [Spark Ads](https://business-api.tiktok.com/portal/docs/create-spark-ads/v1.3) on the date.|
###|comments_paid|integer|Paid comments.<br>Total number of comments acquired through [Spark Ads](https://business-api.tiktok.com/portal/docs/create-spark-ads/v1.3) on the date.|
###| shares | integer | Shares. <br>Total number of shares associated with the video on the date. |
###|shares_organic|integer|Organic shares.<br>Total number of shares acquired through organic delivery, and not through [Spark Ads](https://business-api.tiktok.com/portal/docs/create-spark-ads/v1.3) on the date.|
###|shares_paid|integer|Paid shares.<br>Total number of shares acquired through [Spark Ads](https://business-api.tiktok.com/portal/docs/create-spark-ads/v1.3) on the date.|
```
### Mapping between interest category ID and name

```xtable
| Interest category ID <br>（<code>interest_id</code>）{30%} | Interest category name{70%}  |
|---|---|
| 10 | Education |
| 11 | Vehicles & Transportation |
| 12 | Baby, Kids & Maternity |
| 13 | Financial Services |
| 14 | Beauty & Personal Care |
| 15 | Tech & Electronics |
| 16 | Appliances |
| 17 | Travel |
| 18 | Household Products |
| 19 | Pets |
| 20 | Apps |
| 21 | Home Improvement |
| 22 | Apparel & Accessories |
| 23 | News & Entertainment |
| 24 | Business Services |
| 25 | Games |
| 26 | Life Services |
| 27 | Food & Beverage |
| 28 | Sports & Outdoors |
| 29 | Health |
| 30 | E-Commerce (Non-app) |
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
        "campaign_channel_type": "API_LITE_CAMPAIGN",
        "campaign_id": "{{campaign_id}}",
        "campaign_type": "CAMPAIGN",
        "page_info": {
            "page": 1,
            "page_size": 25,
            "total_number": 1,
            "total_page": 1
        },
        "tto_tcm_account_id": "{{tto_tcm_account_id}}",
        "videos": [
            {
                "ad_cost": 0,
                "ad_cpa": 0,
                "ad_ctr": 0,
                "ad_cvr": 0,
                "ad_impressions": 0,
                "ad_roas": 0,
                "anchor_id": null,
                "anchor_metrics": null,
                "audience_age_distribution": [
                    {
                        "age": "4",
                        "percentage": 0.625
                    },
                    {
                        "age": "3",
                        "percentage": 0.375
                    }
                ],
                "audience_countries_distribution": [
                    {
                        "country": "US",
                        "percentage": 0.7142857142857143
                    },
                    {
                        "country": "GB",
                        "percentage": 0.14285714285714285
                    },
                    {
                        "country": "SA",
                        "percentage": 0.14285714285714285
                    }
                ],
                "audience_device_distribution": [
                    {
                        "device": "ios",
                        "percentage": 1
                    },
                    {
                        "device": "android",
                        "percentage": 0
                    }
                ],
                "audience_genders_distribution": [
                    {
                        "gender": "MALE",
                        "percentage": 0.875
                    },
                    {
                        "gender": "FEMALE",
                        "percentage": 0.125
                    }
                ],
                "audience_interest_distribution": [
                    {
                        "interest_tag": "14",
                        "percentage": 0.047619047619047616
                    },
                    {
                        "interest_tag": "18",
                        "percentage": 0.047619047619047616
                    },
                    {
                        "interest_tag": "11",
                        "percentage": 0.047619047619047616
                    },
                    {
                        "interest_tag": "10",
                        "percentage": 0.047619047619047616
                    },
                    {
                        "interest_tag": "17",
                        "percentage": 0.047619047619047616
                    },
                    {
                        "interest_tag": "20",
                        "percentage": 0.047619047619047616
                    },
                    {
                        "interest_tag": "21",
                        "percentage": 0.047619047619047616
                    },
                    {
                        "interest_tag": "22",
                        "percentage": 0.047619047619047616
                    },
                    {
                        "interest_tag": "26",
                        "percentage": 0.047619047619047616
                    },
                    {
                        "interest_tag": "30",
                        "percentage": 0.047619047619047616
                    }
                ],
                "audience_locale_distribution": null,
                "average_view_time": 3.63,
                "comments": 0,
                "comments_organic": null,
                "comments_paid": null,
                "creator_info": {
                    "display_name": "{{display_name}}",
                    "handle_name": "{{handle_name}}"
                },
                "daily_stats": null,
                "engagement_count": 0,
                "engagement_rate": 0,
                "engagement_rate_organic": null,
                "engagement_rate_paid": null,
                "likes": 0,
                "likes_organic": null,
                "likes_paid": null,
                "reach": 6,
                "shares": 0,
                "shares_organic": null,
                "shares_paid": null,
                "six_seconds_views": 0.2222222222222222,
                "total_play_time": 29,
                "two_seconds_views": 0.6666666666666666,
                "video_completion_rate": 0.5,
                "video_info": {
                    "create_time": "{{create_time}}",
                    "embed_url": "{{embed_url}}",
                    "thumbnail_url": "{{thumbnail_url}}",
                    "video_id": "{{video_id}}"
                },
                "video_view_retention": [
                    1,
                    1,
                    0.83,
                    0.67,
                    0.5
                ],
                "video_views": 6,
                "video_views_by_source": [
                    {
                        "source": "sound",
                        "video_views": 0
                    },
                    {
                        "source": "hashtag",
                        "video_views": 0
                    },
                    {
                        "source": "following",
                        "video_views": 0
                    },
                    {
                        "source": "for_you",
                        "video_views": 0
                    },
                    {
                        "source": "search",
                        "video_views": 3
                    },
                    {
                        "source": "personal_profile",
                        "video_views": 3
                    }
                ],
                "video_views_organic": 6,
                "video_views_paid": null
            }
        ]
    }
}
(/code)
```

