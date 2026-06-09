---
title: "Get post data of a TikTok account"
doc_id: 1762228421622786
path: "API Reference / Accounts / Insights / Get post data of a TikTok account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get post data of a TikTok account

Use this endpoint to get reach and engagement data for all the public video, photo, or text posts of a TikTok account. Video posts include organic videos displayed in the account's profile, as well as "Only show in ads" video posts (video posts used exclusively as ads that do not appear in the TikTok account's profile but are publicly accessible as ads).

The results are paginated. You can use `max_count` to set the maximum number of posts returned for each page.


> <span style="color:DodgerBlue">**Note**</span><br>
> To ensure proper use of the Accounts API, starting March 20, 2026 at 00:00 (GMT+0), developers must complete the [Accounts API Access Application Form](https://bytedance.sg.larkoffice.com/share/base/form/shrlgu4WEvtSXpEDLcCw56u4Rfc) before submitting a new developer app or requesting a scope increase that includes the "TikTok Accounts" permission scope. Please follow the instructions carefully and provide clear, accurate information to help facilitate the application review process. For more details on Accounts API use cases, please refer to the [Accounts API](https://business-api.tiktok.com/portal/docs?id=1737944384433218) documentation.

><span style="color:DodgerBlue">**Note**</span><br>
> - There is a 24-48 hour delay for some profile level metrics. To find the data latencies, you can check out the description in the Response table or see [Accounts Insights data latency- Reference table for data latency](https://ads.tiktok.com/marketing_api/docs?id=1746624508278786#item-link-Reference%20table%20for%20data%20latency).
> - Post data will stop updating 365 days after the post is published.
> - The data you can get from `/business/video/list/` depends on whether you can see the corresponding TikTok Analytics data available in the app and at [https://www.tiktok.com/analytics](https://www.tiktok.com/analytics). If the corresponding metric data are missing from TikTok Analytics, you will not be able to get the data. 
> 	- To learn about the TikTok Analytics platform (TikTok Studio or Business Analytics) that each metric corresponds to, see the field description for each metric in the response.
> 	- To view insights and analytics data, TikTok account owners need to first publish at least one video, then tap the "Turn On" button on the Analytics page of their mobile TikTok app. For more information, refer to [here](https://www.tiktok.com/feedback?id=7133058093574806018&lang=en&type=).
> - The video view data you obtain from `/business/video/list/` via the field `video_views` represents a total metric, encompassing both organic and paid activities.
> - If the data for the fields `reach`, `full_video_watched_rate`, `total_time_watched`, `average_time_watched`, `impression_sources`, and `audience_countries` are unavailable, the reason is usually that the video has not been active (viewed/liked/commented/shared) for more than 7 days. To retrieve the data for these fields, you can view/like/comment/share the inactive video and retry after 24 ~ 48h. 
> - If a post is not returned in the response, the reason is likely to be that the post has been filtered out due to violations, such as music copyright violation.

## Comparing v1.2 and v1.3
The following table outlines the differences between v1.2 and v1.3 endpoints.
```xtable
|Changes {33%}|v1.2 {33%}|v1.3 {33%}|
|---|---|---|
|Endpoint path|/v1.2/business/videos/list/|/v1.3/business/video/list/|
|Method|POST|GET|
|New request parameters|/|`ad_post_only`|
|New response parameters|/|`favorites`<br>`profile_views`<br>`website_clicks`<br>`phone_number_clicks`<br>`lead_submissions`<br>`app_download_clicks`<br>`email_clicks`<br>`address_clicks`<br>`video_view_retention`<br>`audience_genders`<br>`audience_cities`<br>`audience_types`<br>`new_followers`<br>`engagement_likes`<br>`media_type`<br>`is_ad`|
```

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/video/list/


**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162). |
```

**Parameters**

Pass params as url parameters.
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required} | string | Application specific unique identifier for the TikTok account. <br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
| fields | string[] | Requested fields. If not set, returns the <strong>default</strong> field only.<br><strong>Default value</strong>: `&#91;"item_id"&#93;`.<br>If you want to request multiple fields, you need to at least include `"item_id"` in the value for  `fields`. Otherwise, an error may occur.<br> <strong>Example</strong>: `&#91;"item_id", "create_time"&#93;`.<br><p><span style="color:darkred"><b>Note</b></span>: All video engagement metrics are aggregated over the lifetime of the video. Unlike profile-level metrics, the aggregation is not limited to a 60-day look-back period.<br> <br>To retrieve certain fields in the response, you must have specific permissions. You can check the <strong>"Required permission"</strong> column for each field in the response schema to determine the permissions needed. <br>To confirm that you have the necessary permission scope, you can check the scope returned by the [/tt_user/token_info/get/](https://ads.tiktok.com/marketing_api/docs?id=1765927978092545) endpoint. If the required permission has not been granted to you, follow the steps outlined in [Authorization](https://ads.tiktok.com/marketing_api/docs?id=1738083939371009) to enable the TikTok account user to reauthorize your app with the permission.<br> <br><strong> Supported fields</strong>: <ul><li>`item_id`: Unique identifier for the post.</li><li>`media_type`: The type of the post.</li><li>`is_ad`: Whether the post is being used in an ad.</li><li>`thumbnail_url`: Preview thumbnail URL for the post.</li><li>`share_url`: A shareable URL for the post. </li><li>`embed_url`: An embeddable URL for the post. </li><li>`caption`: The caption/description for the post. </li><li>`video_duration`: Duration of the video, in seconds. </li><li>`likes`: Likes. The total number of likes the post has received. </li><li>`comments`:  Comments. The total number of comments the post has received. </li><li>`shares`: Shares. The total number of times the post has been shared. </li><li>`favorites`: Favorites. The total number of times the post has been added to favorites.</li><li>`create_time`: Unix/epoch date-time when the post was published.</li><li>`reach`: Reach. The number of people who watched your published content at least once.</li><li>`video_views`: Video views. The number of times viewers watched your video post.<br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Counts when playback duration >0 and the playback is the first playback in an impression session. </li><li> If the user swipes away from the ad then swipes back, it would be counted as 2 impressions. Therefore, a new video view will be counted again with the new impression session. </li></ul> </li><li>`total_time_watched`: Total play time. The amount of time viewers spent watching your video post.  </li><li>`average_time_watched`: Average watch time. The average time viewers spent watching your video post. </li> <li>`full_video_watched_rate`: Watched full video rate. The percentage of viewers who finish watching your video post. </li> <li>`new_followers`: New followers. The number of viewers who started following you.  </li> <li>`profile_views`: Profile views. The total number of profile views from users who visited through your post.  </li> <li>`website_clicks`: Website clicks. The total number of clicks on your website link from users who visited your profile page through your post.</li>  <li>`phone_number_clicks`: Phone number clicks. The total number of clicks on your phone number link from users who visited your profile page through your post. </li> <li>`lead_submissions`: Lead submissions. The total number of leads (e.g. price quotes, newsletter subscriptions, etc.) collected from your consumers who visited your profile page through your post. </li>  <li>`app_download_clicks`: App download link clicks. The total number of clicks on your app download link from users who visited your profile page through your post. </li>  <li>`email_clicks`: Email clicks. The total number of times people have clicked the Email button on your profile after viewing this specific post.</li>   <li>`address_clicks`: Address clicks. The total number of times people have clicked the Address button on your profile after viewing this specific post.</li>  <li>`video_view_retention`: Audience retention. This metric indicates how many of your viewers are still watching after a certain amount of time.</li> <li>`impression_sources`: Traffic source. This metric helps you understand the breakdown of traffic sources for those who viewed your video. </li> <li>`audience_genders`: Gender. The distribution of your viewers by gender. This data is based on factors including the information viewers provide. </li>  <li>`audience_countries`: Top countries. The distribution of your viewers by the top countries or regions. This data is based on factors including the information viewers provide.  </li> <li>`audience_cities`: Top cities. The distribution of your viewers by the top cities. This data is based on factors including the information viewers provide.</li>  <li>`audience_types`: Viewer types. The breakdown of your audience into new viewers versus returning viewers, and followers versus non-followers.</li>  <li>`engagement_likes`: Engagement likes. The distribution of your viewers who liked your video at specific points in the video's timeline. </li> </ul> |
| filters | object | Filters to apply to the result set. |
#|video_ids | string[] | A list of post IDs to filter the insights for. <br> <br><strong>Example</strong>: `["6995316737801538821", "8015388216995316737"]`. |
#| ad_post_only | boolean | Valid only when `video_ids` is specified.<br> <br>Whether to filter videos (`video_ids`) to only include video posts that have been authorized to advertisers for Spark Ads creation.<br><br>Supported values: `true`, `false`. <br>Default value: `false`.|
| cursor | integer | Cursor for pagination. If the response parameter `has_more` is true, pass the returned cursor in this parameter in the subsequent request to receive the next page of results. <br> <br><strong>Default value</strong>: Current time as Epoch/Unix timestamp in milliseconds. <br><br><p><span style="color:darkred"><b>Note</b></span>: The cursor value is a UTC Unix timestamp in milliseconds. You can pass in a customized timestamp to fetch the user's videos posted before the provided timestamp. |
| max_count | integer | The maximum number of videos that will be returned for each page. <br> <br><strong>Default value</strong>: 10 <br> <br><strong>Maximum value</strong>: 20 <br><br><p><span style="color:darkred"><b>Note</b></span>: Due to our trust and safety policies, it is possible that the endpoint returns less than `max_count` number of videos even if the response parameter `has_more` is true. |
```

### Example
```xcodeblock
(code curl http)
curl --location -g --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/video/list/?business_id=ca9b8798-fbad-4e7f-99a6-17ff809a&fields=["item_id","create_time","thumbnail_url","share_url","embed_url","caption","video_views","likes","comments","shares","reach","video_duration","full_video_watched_rate","total_time_watched","average_time_watched","impression_sources","audience_countries"]' \
--header 'Access-Token: act.sdfsdf70aa4b3dfda45b17f56f9f3jIMlBaMMzJizHyB5t6yUPGLXwJUsdfsdf'
(/code)
```

## Response

**Header**

These are important headers to record for issue reports and troubleshooting. This is not an exhaustive list of response headers.
```xtable
|Field {20%}|Data Type {20%}|Description {60%}|
|---|---|---|
|Date|string|Date and time (GMT) when the response was received.<br><br>**Example**: Fri, 13 Aug 2021 08:04:42 GMT|
|X-Tt-Logid|string|Unique identifier for the API request.|
```

**Body**
``` xtable
|Field{25%}|Type{15%}|Description{40%}|Required permission{15%}|
|---|---|---|---|
| request_id | string | Unique identifier for the API request. <br><p><span style="color:darkred"><b>Note</b></span>: Please record this field for all API requests. Important for issue reports and troubleshooting. ||
| code | integer | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). ||
| message | string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). ||
| data | object | Return data ||
#| videos | object[] | The list of public posts of the TikTok account, including organic videos displayed in the account's profile, as well as "Only show in ads" video posts (video posts used exclusively as ads that do not appear in the TikTok account's profile but are publicly accessible as ads). ||
##| item_id | string | Unique identifier for the post. <br> <br><strong>Example</strong>: 6995316737801538821 <br> <br><strong>Updated in</strong>: Real-time |`video.list`|
##|media_type|string|The type of the post.<br><br>Enum values:<ul><li>`VIDEO`: a video post.</li><li>`PHOTO`: a photo or text post.</li></ul>|`video.list`|
##|is_ad | boolean | Whether the post is being used in an ad.<br>A post can be promoted via Spark Ads Push or Spark Ads Pull. Learn more about [Spark Ads](https://business-api.tiktok.com/portal/docs/create-spark-ads/v1.3).<br>Supported values: <ul><li>`true`: The post is used in an ad.</li><li>`false`: The post is not used in an ad.</li></ul>|`video.list`|
##|thumbnail_url | string | Temporary URL for thumbnail of post content. Expiration date-time included in x-expires query param as Epoch/Unix time in seconds. <br> <br><strong>Example</strong>: https://p16-sign-va.tiktokcdn.com/tos-maliva-avt-0068/75dec21d63500917fb6ec8bc59415156~c5_300x300.jpeg?x-expires=1614099600&x-signature=PmK%2BWs3LzSzRL2tYs%2FZx7EjG3Gk%3D <br> <br><strong>Updated in</strong>: Real-time  |`video.list`|
##|share_url | string | A shareable URL for this TikTok post. Note that the website behaves differently on Mobile and Desktop devices. <br> <br><strong>Example</strong>: https://www.tiktok.com/@feather_in_the_w1nd/video/6994189750940863749?utm_campaign=tt4d_open_api&utm_source=6945731504177676290 <br> <br><strong>Updated in</strong>: Real-time  |`video.list`|
##|embed_url | string | An embeddable link for this TikTok post. This URL can be used to embed the TikTok post in external websites or applications.<br> <br>If the privacy setting of the post is "Friends" or "Only you", the video will not be viewable through this link.<br> <br><strong>Example</strong>: https://www.tiktok.com/player/v1/1234567891234567891?music_info=1&description=1&autoplay=1&loop=1&utm_campaign=tt4d_open_api&utm_source=1234567891234567891 <br> <br><strong>Updated in</strong>: Real-time  |`video.list`|
##|caption | string | Post description. <br> <br><strong>Example</strong>: Nuki: me and me casa 🏠 #cat #kitten #britishshorthair <br> <br><strong>Updated in</strong>: Real-time  |`video.list`|
##|video_duration | float | Video length - in seconds, up to three decimal places. <br> <br><strong>Example</strong>: 20.001 <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`video.list`|
##|likes | integer | Likes. <br>Total number of likes on the post. <br> <br><strong>Example</strong>: 243 <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`video.list`|
##|comments | integer | Comments. <br>Total number of comments on the post. <br> <br><strong>Example</strong>: 10 <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`video.list`|
##|shares | integer |Shares.<br> Total number of times the post has been shared. <br> <br>The share count displayed in this field corresponds to the number of shares shown in the For You feed on the TikTok mobile app. It includes both external shares and internal shares sent to friends using the "Send to friends" button.<br>For example, if a post is shared externally 30 times and shared with friends within the TikTok app 20 times, the value of this field will be 50.<br> <br> <strong>Example</strong>: 10 <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`video.list`|
##| favorites | integer | Favorites.<br>The total number of times the post has been added to favorites.<br><br><strong>Data source</strong>: Business Analytics.<br> <br><b>Example</b>: 10 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time) | `video.list` |
##|create_time | string | Unix/epoch date-time when the post was posted, in UTC time-zone. <br> <br><strong>Example</strong>:1628461703 <br> <br><strong>Updated in</strong>: Real-time |`video.list`|
##|reach | integer |Reach.  <br>The number of people who watched your published content at least once. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: 136 <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`video.list`|
##|video_views | integer | Video views. <br>The number of times viewers watched your video post.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Counts when playback duration >0 and the playback is the first playback in an impression session. </li><li> If the user swipes away from the ad then swipes back, it would be counted as 2 impressions. Therefore, a new video view will be counted again with the new impression session.</li></ul> <strong>Example</strong>: 603 <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`video.list`|
##|total_time_watched | float | Total play time. <br>The amount of time viewers spent watching your video post, in seconds.<br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: 587.024 <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time) |`video.insights`|
##|average_time_watched | Float | Average watch time. <br>The average time viewers spent watching your video post, in seconds. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: 3.862 <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time) |`video.insights`|
##|full_video_watched_rate | float | Watched full video rate. <br>The percentage of viewers who finish watching your video post. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: 0.0395 <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`video.insights`|
##| new_followers | integer | New followers.<br>The number of viewers who started following you. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time) | `video.insights` |
##| profile_views | integer | Profile views. <br>The total number of profile views from users who visited through your post. <br><br><strong>Data source</strong>: Business Analytics.<br> <br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time)  <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for Business Accounts. | `video.insights` |
##| website_clicks | integer | Website clicks. <br>The total number of clicks on your website link from users who visited your profile page through your post.<br><br><strong>Data source</strong>: Business Analytics.<br> <br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time) <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration). | `video.insights` |
##| phone_number_clicks | integer | Phone number clicks. <br>The total number of clicks on your phone number link from users who visited your profile page through your post.<br><br><strong>Data source</strong>: Business Analytics.<br> <br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time) <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration).  | `video.insights` |
##| lead_submissions | integer | Lead submissions.<br>The total number of leads (e.g. price quotes, newsletter subscriptions, etc.) collected from your consumers who visited your profile page through your post.  <br><br><strong>Data source</strong>: Business Analytics.<br> <br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time) <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration). | `video.insights` |
##| app_download_clicks | integer | App download link clicks.<br>The total number of clicks on your app download link from users who visited your profile page through your post. <br><br><strong>Data source</strong>: Business Analytics.<br> <br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time)  <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration).    | `video.insights` |
##| email_clicks | integer | Email clicks. <br>The total number of times people have clicked the Email button on your profile after viewing this specific post. <br><br><strong>Data source</strong>: Business Analytics.<br> <br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time)   <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration). | `video.insights` |
##| address_clicks | integer | Address clicks. <br>The total number of times people have clicked the Address button on your profile after viewing this specific post.<br><br><strong>Data source</strong>: Business Analytics.<br> <br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time)  <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration).  | `video.insights` |
##| video_view_retention | object[] | Audience retention. <br>This metric indicates how many of your viewers are still watching after a certain amount of time.<br><br><strong>Data source</strong>: TikTok Studio.<br> <br><b>Updated in</b>: T + 24-48 hrs (UTC time)  | `video.insights` |
###| second | string | A specific second in the video's timeline.   |  |
###| percentage | float | The percentage of viewers who are still watching your video at the specific second.   |  |
##|impression_sources | object[] | Traffic source.<br> This metric shows the places where your viewers discover your posts and helps you understand the breakdown of traffic sources for those who viewed your video, ranked from the largest contribution to the smallest.<br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: `[&#123;impression_source: "For You", percentage: 0.7554 &#125;, &#123;impression_source: "Follow", percentage: 0.2445 &#125;, ...]` <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time) |`video.insights`|
###| impression_source | string | The traffic source type.<br> <br>Enum values:<ul><li>`For You`: For You feed. </li><li>`Follow`: Follow feed. </li><li>`Sound`: sound pages. </li><li>`Personal Profile`: profile pages.. </li><li>`Search`: search pages. </li><li>`Others`: others.</li><li>`Direct Message`: direct message.<br><p><span style="color:darkred"><b>Note</b></span>: The availability of the `Direct Message` value depends on whether you can see the corresponding data within TikTok Studio. If the corresponding data is missing from TikTok Studio, you will not be able to get the data.</li></ul> ||
###| percentage | float | The percentage of views from the source type. ||
##| audience_genders | object[] | Gender.<br>The distribution of your viewers by gender. This data is based on factors including the information viewers provide. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><b>Updated in</b>: T + 24-48 hrs (UTC time)  | `video.insights` |
###| gender | string | Gender.   <br><br> Enum values: `Female`, `Male`, `Other`.   |  |
###| percentage | float | Percentage of viewers associated with the gender.   |  |
##|audience_countries | object[] | Top countries or regions. <br>The distribution of your viewers by the top countries or regions. This data is based on factors including the information viewers provide.<br> <br><strong>Max size</strong>: 10.<br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: `[&#123;"country":"CA","percentage":0.0132&#125;, &#123;"country":"GB","percentage":0.0987&#125;,{"country":"US","percentage":0.7303&#125;, ...]` <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time) |`video.insights`|
###| country | string | [ISO 3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements) country or region code. ||
###| percentage | float | Percentage of viewers registered from a country or region. ||
##| audience_cities | object[] | Top cities.  <br>The distribution of your viewers by the top cities. This data is based on factors including the information viewers provide.<br><br><strong>Max size</strong>: 10. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><b>Example</b>: `[&#123;"city_name": "US Cincinnati","percentage": 0.597&#125;,&#123;"city_name": "US Los Angeles","percentage": 0.597&#125;,&#123;"city_name": "US Other","percentage": 17.4921&#125;,&#123;"city_name": "US Ashburn","percentage": 1.2139&#125;]` <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time)   | `video.insights` |
###| city_name | string | City name.  <br><br><b>Example</b>: US Cincinnati.   |  |
###| percentage | float | Percentage of viewers associated with the city.  <br><br><b>Example</b>: 1.2139  |  |
##| audience_types | object[] | Viewer types.<br> The breakdown of your audience into new viewers versus returning viewers, and followers versus non-followers.<br><br><strong>Data source</strong>: TikTok Studio.<br> <br><b>Example</b>: `[&#123;"percentage": 0,"type": "FOLLOWER_PERCENT"&#125;,&#123;"percentage": 0.98,"type": "NEW_VIEWER"&#125;,&#123;"percentage": 1,"type": "NON_FOLLOWER_PERCENT"&#125;,&#123;"percentage": 0.02,"type": "RETURN_VIEWER"&#125;]` <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time) | `video.insights` |
###| type | string | Audience type. <br><br>Enum values:  <ul><li>`NEW_VIEWER`: New viewers. Viewers who are either viewing a post from you for the first time, or it has been a year since they last viewed your posts. </li><li>`RETURN_VIEWER`: Returning viewers. Viewers who have viewed your posts in the past year.  </li><li>`FOLLOWER_PERCENT`: Followers. Viewers who are following you on TikTok.</li><li> `NON_FOLLOWER_PERCENT`: Non-followers. Viewers who are not following you on TikTok. </li></ul>|  |
###| percentage | float | Percentage of viewers associated with the audience type.   |  |
##| engagement_likes | object[] | Engagement likes.<br>The distribution of your viewers who liked your video at specific points in the video's timeline. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><b>Updated in</b>: T + 24-48 hrs (UTC time)    | `video.insights` |
###| second | string | A specific second in the video's timeline.   |  |
###| percentage | float | Percentage of viewers who liked your video at the specific second.   |  |
#| cursor | integer | Cursor for the next page of results, to be passed in the cursor query parameter of the subsequent API request. <br> <br><strong>Updated in</strong>: Real-time ||
#| has_more | boolean | Whether an additional page of data is available. <br> <br><strong>Updated in</strong>: Real-time ||
```

### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "2021080307532501024502410704A7306F",
    "data": {
        "videos": [
            {
                "item_id": "6990565363377392901",
                "create_time": "1627617835",
                "caption": "little coco",
                "thumbnail_url": "http://p16-sign-va.tiktokcdn.com/tos-maliva-p-0068/c85b70edfca14b7b89cc2e1e884ee268~tplv-noop.image?x-expires=1627998825&x-signature=1pMnzQjXnN%2BMWQHK0Vl2zfT%2BRoY%3D",
                "embed_url": "
https://www.tiktok.com/player/v1/1234567891234567891?music_info=1&description=1&autoplay=1&loop=1&utm_campaign=tt4d_open_api&utm_source=1234567891234567891E",
                "share_url": "https://www.tiktok.com/@feather_in_the_w1nd/video/6990565363377392901?utm_campaign=tt4d_open_api&utm_source=6979330045630218242",
                "likes": 10,
                "comments": 2,
                "shares": 0,
                "video_views": 1231,
                "reach": 154,
                "video_duration": 20.0,
                "total_time_watched": 695.942,
                "average_time_watched": 4.118,
                "full_video_watched_rate": 0.0237,
                "impression_sources": [
                    {
                        "percentage": 0.0113,
                        "impression_source": "Follow"
                    },
                    {
                        "percentage": 0.8983,
                        "impression_source": "For You"
                    },
                    {
                        "percentage": 0.0,
                        "impression_source": "Hashtag"
                    },
                    {
                        "percentage": 0.0904,
                        "impression_source": "Personal Profile"
                    },
                    {
                        "percentage": 0.0,
                        "impression_source": "Sound"
                    }
                ],
                "audience_countries": [
                    {
                        "percentage": 0.0237,
                        "country": "CA"
                    },
                    {
                        "percentage": 0.1775,
                        "country": "GB"
                    },
                    {
                        "percentage": 0.0118,
                        "country": "NL"
                    },
                    {
                        "percentage": 0.0059,
                        "country": "PR"
                    },
                    {
                        "percentage": 0.7574,
                        "country": "US"
                    },
                    {
                        "percentage": 0.0059,
                        "country": "VE"
                    }
                ]  
            }
        ],
        "cursor": 0,
        "has_more": false
    }
}
(/code)
```


