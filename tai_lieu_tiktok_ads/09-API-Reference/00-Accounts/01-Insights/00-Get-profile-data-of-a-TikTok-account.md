---
title: "Get profile data of a TikTok account"
doc_id: 1762228399168514
path: "API Reference / Accounts / Insights / Get profile data of a TikTok account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get profile data of a TikTok account

Use this endpoint to access detailed analytics and insights about a TikTok account's follower base and profile engagement. 


> <span style="color:DodgerBlue">**Note**</span><br>
> To ensure proper use of the Accounts API, starting March 20, 2026 at 00:00 (GMT+0), developers must complete the [Accounts API Access Application Form](https://bytedance.sg.larkoffice.com/share/base/form/shrlgu4WEvtSXpEDLcCw56u4Rfc) before submitting a new developer app or requesting a scope increase that includes the "TikTok Accounts" permission scope. Please follow the instructions carefully and provide clear, accurate information to help facilitate the application review process. For more details on Accounts API use cases, please refer to the [Accounts API](https://business-api.tiktok.com/portal/docs?id=1737944384433218) documentation.

><span style="color:DodgerBlue">**Note**</span><br>
> - There is a 24-48 hour delay for some profile level metrics. To find the data latencies, you can check out the description in the Response table or see [Accounts Insights data latency- Reference table for data latency](https://ads.tiktok.com/marketing_api/docs?id=1746624508278786#item-link-Reference%20table%20for%20data%20latency).
> - The data you can get from `/business/get/` depends on whether you can see the corresponding TikTok Analytics data available in the app and at [https://www.tiktok.com/analytics](https://www.tiktok.com/analytics). If the corresponding metric data are missing from TikTok Analytics, you will not be able to get the data. 
> 	- To learn about the TikTok Analytics platform (TikTok Studio or Business Analytics) that each metric corresponds to, see the field description for each metric in the response.
> 	- To view insights and analytics data, TikTok account owners need to first publish at least one video, then tap the "Turn On" button on the Analytics page of their mobile TikTok app. For more information, refer to [here](https://www.tiktok.com/feedback?id=7133058093574806018&lang=en&type=).
> - The video view data you obtain from `/business/get/` via the field `video_views` represents a total metric, encompassing both organic and paid activities.

## Comparing v1.2 and v1.3
The following table outlines the differences between v1.2 and v1.3 endpoints.
```xtable
|Changes {33%}|v1.2 {33%}|v1.3 {33%}|
|---|---|---|
|Endpoint path|/v1.2/business/get/|/v1.3/business/get/|
|Method|POST|GET|
|New response parameters|/|`is_business_account`<br>`bio_description`<br>`profile_deep_link`<br>`is_verified`<br>`following_count`<br>`total_likes`<br>`videos_count`<br>`unique_video_views`<br>`phone_number_clicks`<br>`lead_submissions`<br>`app_download_clicks`<br>`bio_link_clicks`<br>`email_clicks`<br>`address_clicks`<br>`daily_total_followers`<br>`daily_new_followers`<br>`daily_lost_followers`<br>`audience_ages`<br>`audience_cities`<br>`engaged_audience`|
```

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/get/


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
| start_date | string | Query start date, closed interval, format such as: 2021-06-01. The date is based on the UTC time zone.<br> If not set, then the `start_date` will be [current date - 7 days]. <br> The maximum supported look-back period is 60 days. |
| end_date | string | Query end date, closed interval, format such as: 2021-06-01. The date is based on the UTC time zone. <br> If not set, then `end_date` will be [current date - 1 day]. |
| fields | string[] | Requested fields. If not set, returns the <strong>default</strong> fields only.  <br>Default fields</strong>: `&#91;"display_name", "profile_image"&#93;` <br> <br>To retrieve certain fields in the response, you must have specific permissions. You can check the <strong>"Required permission"</strong> column for each field in the response schema to determine the permissions needed. <br>To confirm that you have the necessary permission scope, you can check the scope returned by the [/tt_user/token_info/get/](https://ads.tiktok.com/marketing_api/docs?id=1765927978092545) endpoint. If the required permission has not been granted to you, follow the steps outlined in [Authorization](https://ads.tiktok.com/marketing_api/docs?id=1738083939371009) to enable the TikTok account user to reauthorize your app with the permission.<br> <br><strong>Example</strong>: `&#91;"display_name", "profile_image"&#93;`. <br> <br><strong> Supported fields</strong>: <ul><li>`is_business_account`：Whether the TikTok account is a TikTok Business Account. This metric is only available when the TikTok account user has granted you the new permission scope "user.account.type". <br><p><span style="color:darkred"><b>Note</b></span>: To determine whether you have obtained the permission scope "user.account.type", you can check the `scope` returned from [/tt_user/token_info/get/](https://ads.tiktok.com/marketing_api/docs?id=1765927978092545). If the permission scope has not been granted to you, follow the steps outlined in [Authorization](https://ads.tiktok.com/marketing_api/docs?id=1738083939371009) to enable the TikTok account user to reauthorize your app with the permission scope.</li><li>`profile_image`: The URL to the profile photo of the TikTok account.</li><li>`username`: The username (handle) of the TikTok account.</li><li>`profile_deep_link`: The link to the profile page of the TikTok account.</li><li>`display_name`: The display name (nickname) of the TikTok account.</li><li>`bio_description`: The bio description of the TikTok account. </li><li>`is_verified`: Whether TikTok has provided a verified badge to the TikTok account after confirming that it belongs to the user it represents.</li><li>`following_count`: The number of accounts that the TikTok account is following. </li><li>`followers_count`: The number of followers for the TikTok account. <br><p><span style="color:darkred"><b>Note</b></span>: If you specify `followers_count` in the value of `fields`, you will obtain `followers_count` as both a lifetime metric and a daily metric in the response.</li><li>`total_likes`: Total likes. The total number of times people have liked your published videos.</li><li>`videos_count`: The lifetime number of public videos posted by the TikTok account. </li><li>`video_views`: Daily video views. The daily number of times your videos have been viewed. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Counts when playback duration >0 and the playback is the first playback in an impression session. </li><li> If the user swipes away from the ad then swipes back, it would be counted as 2 impressions. Therefore, a new video view will be counted again with the new impression session. </li></ul> </li> <li>`unique_video_views`: Daily reached audience. The daily number of people who watched your published content at least once.</li>  <li>`profile_views`: Daily profile views. The daily number of clicks on your profile from users.</li><li>`likes`: Daily likes. The daily number of times people have liked your published videos.</li><li>`comments`: Daily comments. The daily number of times people have commented on your published videos. </li><li>`shares`: Daily shares. The daily number of times people have shared your published videos. </li><li>`phone_number_clicks`: Daily phone number clicks. The daily number of clicks collected on your phone number link in the selected date range.  </li><li>`lead_submissions`: Daily lead submissions. The daily number of leads (e.g. price quotes, newsletter subscriptions, etc.) collected from your consumers in the selected date range.  </li> <li>`app_download_clicks`: Daily app download link clicks. The daily number of clicks collected on your app download link in the selected date range.</li> <li>`bio_link_clicks`: Daily bio link clicks. The daily number of clicks collected on your link-in-bio during the selected time range. </li> <li>`email_clicks`: Daily email clicks. The daily number of clicks collected on the Email button on your profile during the selected time range. </li> <li>`address_clicks`: Daily address clicks. The daily number of clicks collected on the Address button on your profile during the selected time range. </li><li>`daily_total_followers`: Daily net growth. The daily change in the number of followers. </li>  <li>`daily_new_followers`: Daily new followers. The daily number of new followers you have gained.  </li> <li>`daily_lost_followers`: Daily lost followers. The daily number of followers you have lost. </li><li>`audience_activity`: Hourly follower activity. The hourly breakdown of followers active on TikTok during the day.<ul><li>The data for this metric is only available for TikTok accounts with at least 100 followers.</li></ul></li><li>`engaged_audience`: Daily engaged audience. The daily number of people who engaged (liked, commented or shared) with at least one of your published content. </li> <li>`audience_ages`: Follower age. The distribution of your followers by age. This demographic data is based on a number of factors, including information users provide in their profiles. <ul><li>The data for this metric is only available for TikTok accounts with at least 100 followers. </li></ul></li><li>`audience_genders`: Follower gender. The distribution of your followers by gender. This demographic data is based on a number of factors, including information users provide in their profiles.<ul><li>The data for this metric is only available for TikTok accounts with at least 100 followers.</li></ul></li><li>`audience_countries`: Follower top countries or regions. The distribution of your followers by their location (countries or regions). This demographic data is based on a number of factors, including information users provide in their profiles.<ul><li>The data for this metric is only available for TikTok accounts with at least 100 followers.</li></ul></li> <li>`audience_cities`: Follower top cities. The distribution of your followers by their cities. This demographic data is based on a number of factors, including information users provide in their profiles.<ul><li>The data for this metric is only available for TikTok accounts with at least 100 followers.</li></ul></li></ul> |
```

### Example
```xcodeblock
(code curl http)
curl --location -g --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/get/?business_id=b3d2f73d-4b8c-47fb-85c3-cd571287754b&fields=["username","display_name","shares","comments", "video_views", "audience_countries"]&start_date=2021-07-28&end_date=2021-08-01' \
--header 'Access-Token: 1234a16d2e08c3f17d1984a1be07d00406p3LIAF7vEpxliox8GRpCINv70x'
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
|Field{25%}|Type{15%}|Description{40%}|Required permission {20%}|
|---|---|---|---|
| request_id | string | Unique identifier for the API request. <br><p><span style="color:darkred"><b>Note</b></span>: Please record this field for all API requests. Important for issue reports and troubleshooting. ||
| code | integer | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). ||
| message | string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). ||
| data | object | Return data ||
#| is_business_account| boolean | Whether the TikTok account (`business_id`) is a TikTok Business Account. <br>Enum values: <ul><li> `true`: The TikTok account is a TikTok Business Account.</li><li> `false`: The TikTok account is a TikTok Personal Account. </li></ul>| `user.account.type` |
#|profile_image | string | Temporary URL for profile photo of the TikTok account. Expiration date-time included in x-expires query param as Epoch/Unix time in seconds. </strong> <br> <br><strong>Example</strong>: https://p16-sign-va.tiktokcdn.com/tos-maliva-avt-0068/75dec21d63500917fb6ec8bc59415156~c5_300x300.jpeg?x-expires=1614099600&x-signature=PmK%2BWs3LzSzRL2tYs%2FZx7EjG3Gk%3D  <br> <br><strong>Updated in</strong>: Real-time  |`user.info.basic` |
#|username | string | The username (handle) of the TikTok account. <br> <br><strong>Example</strong>: la_flama_blanca  <br> <br><strong>Updated in</strong>: Real-time   |`user.info.username` |
#| profile_deep_link | string | The link to the profile page of the TikTok account. <br> <br><strong>Example</strong>: https://vm.tiktok.com/ABcdEfghi/  <br> <br><strong>Updated in</strong>: Real-time | `user.info.profile` |
#|display_name | string | The display name  (nickname) for the TikTok account.<br> <br><strong>Example</strong>: La Flama Blanca  <br> <br><strong>Updated in</strong>: Real-time | `user.info.basic` |
#| bio_description | string | The bio description of the TikTok account. <br> <br><strong>Updated in</strong>: Real-time <br><br><p><span style="color:darkred"><b>Note</b></span>: If you have not set a bio for the TikTok account, the value of this field will be an empty string (`""`).| `user.info.profile` |
#| is_verified | boolean | Whether TikTok has provided a verified badge to the TikTok account after confirming that it belongs to the user it represents.<br> <br>Supported values: `true`, `false`. <br> <br><strong>Example</strong>: `true` <br> <br><strong>Updated in</strong>: Real-time | `user.info.profile` |
#| following_count | integer | The lifetime number of accounts that the TikTok account is following. <br> <br><strong>Example</strong>: 2  <br> <br><strong>Updated in</strong>: Real-time| `user.info.stats` |
#|followers_count | integer | The total number of followers for the TikTok account.  <br> <br><strong>Example</strong>: 123  <br> <br><strong>Updated in</strong>: Real-time  |`user.info.stats` |
#| total_likes | integer |Total likes. <br> The total number of times people have liked your published videos. <br> <br><strong>Example</strong>: 6 <br> <br><strong>Updated in</strong>: Real-time| `user.info.stats` |
#| videos_count | integer | The lifetime number of public videos posted by the TikTok account. <br> <br><strong>Example</strong>: 6 <br> <br><strong>Updated in</strong>: Real-time| `user.info.stats` |
#|metrics | object[] | All requested daily metrics. <br> <br><p><span style="color:darkred"><b>Note</b></span>: Metrics data will only be returned for dates after the TikTok Account was created. There can be up to a <strong>24-48 hour delay</strong> in metrics availability. | |
##| date | string | The date for this set of daily metrics - in YYYY-MM-DD format - in UTC time zone.  <br> <br><strong>Example</strong>: 2021-08-12  <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time) |N/A |
##| video_views | integer | Daily video views. <br>The daily number of times your videos have been viewed.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Counts when playback duration >0 and the playback is the first playback in an impression session. </li><li> If the user swipes away from the ad then swipes back, it would be counted as 2 impressions. Therefore, a new video view will be counted again with the new impression session. </li></ul> <br> <strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: 123  <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`user.insights` |
##| unique_video_views | integer | Daily reached audience.<br> The daily number of people who watched your published content at least once. <br><br> <strong>Data source</strong>: Business Analytics.<br> <br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time) <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for Business Accounts.| `user.insights` |
##| profile_views | integer | Daily profile views. <br>The daily number of clicks on your profile from users. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: 123  <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`user.insights` |
##| likes | integer | Daily likes.  <br> The daily number of times people have liked your published videos. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: 123  <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  <br><br><p><span style="color:darkred"><b>Note</b></span>:  The likes count displayed in this field does not subtract unlikes (likes that are removed or withdrawn later). There may be a discrepancy between this value and the likes displayed on the <b>Business Suite</b> > <b>Analytics</b> page for Business Accounts.<br>For example, if a post receives 30 likes and 10 unlikes:<ul><li>This field will show 30 likes.</li><li>The Analytics page for Business Accounts will show 20 likes (30 likes - 10 unlikes).</li></ul>  |`user.insights` |
##| comments | integer | Daily comments. <br>The daily number of times people have commented on your published videos. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: 123  <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`user.insights` |
##| shares | integer | Daily shares. <br>The daily number of times people have shared your published videos.<br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: 123  <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)   |`user.insights` |
##| phone_number_clicks | integer | Daily phone number clicks.<br> The daily number of clicks collected on your phone number link in the selected date range.<br><br><strong>Data source</strong>: Business Analytics. <br><br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time)  <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration).| `user.insights` |
##| lead_submissions | integer | Daily lead submissions.<br> The daily number of leads (e.g. price quotes, newsletter subscriptions, etc.) collected from your consumers in the selected date range. <br><br><strong>Data source</strong>: Business Analytics. <br><br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time)  <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration).   | `user.insights` |
##| app_download_clicks | integer | Daily app download link clicks.<br>The daily number of clicks collected on your app download link in the selected date range. <br><br><strong>Data source</strong>: Business Analytics. <br><br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time) <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration).  | `user.insights` |
##| bio_link_clicks | integer | Daily bio link clicks.<br> The daily number of clicks collected on your link-in-bio during the selected time range.<br><br><strong>Data source</strong>: Business Analytics. <br><br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time) <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration).   | `user.insights` |
##| email_clicks | integer | Daily email clicks..<br> The daily number of clicks collected on the Email button on your profile during the selected time range. <br><br><strong>Data source</strong>: Business Analytics. <br><br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time)  <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration). | `user.insights` |
##| address_clicks | integer | Daily address clicks.<br> The daily number of clicks collected on the Address button on your profile during the selected time range.<br><br><strong>Data source</strong>: Business Analytics. <br><br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time)<br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for [Registered Business Accounts](https://ads.tiktok.com/help/article/about-business-registration).  | `user.insights` |
##| daily_total_followers | integer | Daily net growth.<br>The daily change in the number of followers.<br><br><strong>Data source</strong>: Business Analytics. <br><br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time)  <br> <br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for Business Accounts| `user.insights` |
##|  daily_new_followers | integer |Daily new followers. <br>The daily number of new followers you have gained.<br><br><strong>Data source</strong>: Business Analytics.<br><br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time) <br> <br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for Business Accounts | `user.insights` |
##| daily_lost_followers | integer | Daily lost followers.<br>The daily number of followers you have lost.<br><br><strong>Data source</strong>: Business Analytics. <br><br><b>Example</b>: 123 <br><br><b>Updated in</b>: T + 24-48 hrs (UTC time) <br> <br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for Business Accounts | `user.insights` |
##| followers_count | integer | The total number of people who are following your account on the date. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: 123  <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`user.insights` |
##| audience_activity | object[] | Hourly follower activity. The hourly breakdown of followers active on TikTok during the day in UTC+0 time zone.  <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for Business Accounts with at least 100 followers. <br><br><strong>Data source</strong>: TikTok Studio.<br> <br><strong>Example</strong>: `[&#123;"hour": "0", "count": 12&#125;, &#123;"hour": "1", "count": 18&#125;, &#123;"hour": "2", "count": 23&#125;, &#123;"hour": "3", "count": 28&#125;, ...]`  <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)   |`user.insights` |
###| hour | string | The specific hour in the day. | |
###| count | integer | The number of followers active on TikTok during the hour. | |
##| engaged_audience | integer | Daily engaged audience. <br>The daily number of people who engaged (liked, commented or shared) with at least one of your published content.<br><br><strong>Data source</strong>: Business Analytics.<br> <br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for Business Accounts.| `user.insights` |
#| audience_ages | object[] |Follower age. <br>The distribution of your followers by age. This demographic data is based on a number of factors, including information users provide in their profiles..<br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)<br><br><strong>Data source</strong>: TikTok Studio<br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for TikTok accounts with at least 100 followers.| `user.insights` |
##| age | string | Age group.   <br><br> Enum values: `18-24`, `25-34`, `35-44`, `45-54`, `55+`.   ||
##| percentage | float | Percentage of followers associated with the age group.   ||
#|audience_genders | object[] | Follower gender. <br>The distribution of your followers by gender. This demographic data is based on a number of factors, including information users provide in their profiles.<br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for TikTok accounts with at least 100 followers.<br> <br><strong>Data source</strong>: TikTok Studio<br><br><strong>Example</strong>: `[&#123;gender: "Female", percentage: 0.7554470336505679&#125;, &#123;gender: "Male", percentage: 0.24455296634943213&#125;, ...]`  <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)  |`user.insights` |
##| gender | string | Gender. <br><br>Enum values: `Female`, `Male`, `Other`. | |
##| percentage | float | Percentage of followers associated with the gender. | |
#|audience_countries | object[] | Follower top countries or regions. <br>The distribution of your followers by their location (countries or regions). This demographic data is based on a number of factors, including information users provide in their profiles.<br> <br><strong>Data source</strong>: TikTok Studio<br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for TikTok accounts with at least 100 followers. <br> <br><strong>Example</strong>: `[&#123;country: "US", percentage: 0.7554470336505679&#125;, &#123;country: "UK", percentage: 0.24455296634943213&#125;, ...]`  <br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)|`user.insights` |
##| country | string | [ISO 3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements) country or region code. | |
##| percentage | float | Percentage of followers registered from a specified country or region. | |
#| audience_cities | object[] | Follower top cities. <br>The distribution of your followers by their cities. This demographic data is based on a number of factors, including information users provide in their profiles. <br> <br><strong>Data source</strong>: TikTok Studio<br> <br><strong>Updated in</strong>: T + 24-48 hrs (UTC time)<br><br><p><span style="color:darkred"><b>Note</b></span>: The data for this metric is only available for TikTok accounts with at least 100 followers. | `user.insights` |
##| city_name | string | City name.   | |
##| percentage | float | Percentage of followers associated with the city.   | |
```

### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "202108030711080102450310564DA3B2EB",
    "data": {
        "profile_image": "https://p16-sign-va.tiktokcdn.com/tos-maliva-avt-0068/188d468fc1de3f328250ea0c96e98f8b~c5_100x100.webp?x-expires=1628060400&x-signature=BnPoX8rWsYZZ%2BtyzCoWanxYdRyg%3D",
        "username": "la_flama_blanca",
        "display_name": "Display Name",
        "followers_count": 1124,
        "audience_countries": [
            {
                "percentage": 0.1797,
                "country": "MY"
            },
            {
                "percentage": 0.5996,
                "country": "TW"
            },
            {
                "percentage": 0.0629,
                "country": "VN"
            }
        ],
        "audience_genders": [
            {
                "percentage": 0.6031,
                "gender": "Female"
            },
            {
                "percentage": 0.1685,
                "gender": "Male"
            },
            {
                "percentage": 0.2283,
                "gender": "Other"
            }
        ],
        "metrics": [
            {
                "date": "2021-07-30",
                "followers_count": 4,
                "video_views": 1126,
                "comments": 4,
                "shares": 0,
                "likes": 26
            },
            {
                "date": "2021-07-31",
                "followers_count": 6,
                "video_views": 312,
                "comments": 0,
                "shares": 0,
                "likes": 15
            },
            {
                "date": "2021-08-01",
                "followers_count": 8,
                "video_views": 283,
                "comments": 0,
                "shares": 0,
                "likes": 19
            }
        ]
    }
}
(/code)
```
## Field permission changelog
The permissions required to access certain fields have been updated. Refer to the table below for the changelog. 

To ensure a smooth API integration, we recommend you follow the steps outlined in [Authorization](https://ads.tiktok.com/marketing_api/docs?id=1738083939371009) to enable the TikTok account user to reauthorize your app with the updated permission as soon as possible.

To confirm that you have the necessary permission scope, you can check the scope returned by the [/tt_user/token_info/get/](https://ads.tiktok.com/marketing_api/docs?id=1765927978092545) endpoint.

<table>
<thead>
  <tr>
    <th>Field</th>
    <th>Previous permission</th>
    <th>Existing permission</th>
    <th>Updated Time</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>is_business_account</td>
    <td><code>user.account.type</code></td>
    <td><code>user.account.type</code></td>
    <td>-</td>
  </tr>
  <tr>
    <td>username</td>
    <td rowspan="4"><code>user.info.basic</code><br></td>
	<td><code>user.info.username</code></td>
    <td>June, 2023</td>
  </tr>
  <tr>
    <td>display_name</td>
    <td rowspan="2"><code>user.info.basic</code><br></td>
    <td>-</td>
  </tr>
  <tr>
    <td>profile_image</td>
    <td>-</td>
  </tr>
  <tr>
    <td>followers_count</td>
    <td><code>user.info.stats</code></td>
    <td>June, 2023</td>
  </tr>
  <tr>
    <td>audience_countries</td>
    <td rowspan="9"><code>user.insights</code></td>
    <td rowspan="9"><code>user.insights</code><br></td>
    <td>-</td>
  </tr>
  <tr>
    <td>audience_genders</td>
    <td>-</td>
  </tr>
  <tr>
    <td>metrics.followers_count</td>
    <td>-</td>
  </tr>
  <tr>
    <td>metrics.profile_views</td>
    <td>-</td>
  </tr>
  <tr>
    <td>metrics.video_views</td>
    <td>-</td>
  </tr>
  <tr>
    <td>metrics.likes</td>
    <td>-</td>
  </tr>
  <tr>
    <td>metrics.comments</td>
    <td>-</td>
  </tr>
  <tr>
    <td>metrics.shares</td>
    <td>-</td>
  </tr>
  <tr>
    <td>metrics.audience_activity</td>
    <td>-</td>
  </tr>
  <tr>
    <td>metrics.date</td>
    <td>N/A</td>
    <td>N/A</td>
    <td>-</td>
  </tr>
</tbody>
</table>


