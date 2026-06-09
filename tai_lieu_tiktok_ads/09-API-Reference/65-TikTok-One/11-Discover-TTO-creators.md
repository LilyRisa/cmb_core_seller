---
title: "Discover TTO creators"
doc_id: 1825017307843585
path: "API Reference / TikTok One / Discover TTO creators"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Discover TTO creators

Use this endpoint to search for TikTok One (TTO) creators.

## Comparing TCM and TTO Creator Marketplace endpoints

The following table outlines the differences between TikTok Creator Marketplace (TCM) and TTO Creator Marketplace endpoints.

```xtable
| Changes {24%}| TCM Endpoint{36%} | TTO Creator Marketplace Endpoint{40%} |
|---|---|---|
| Endpoint path | /v1.3/tcm/creator/discover/ | /v1.3/tto/tcm/creator/discover/ |
| Same request parameters | `min_followers`<br> `max_followers`<br> `languages`<br> `min_avg_views`<br> `max_avg_views`<br> `sort_field`<br> `page`<br> `page_size` | `min_followers`<br> `max_followers`<br> `languages`<br> `min_avg_views`<br> `max_avg_views`<br> `sort_field`<br> `page`<br> `page_size` |
| Deprecated request parameters | N/A | `tcm_account_id`<br> `country_code`<br> `engagement_rate`<br> `mobile_os`<br> `creator_categories`<br> `max_campaign_count`<br> `min_campaign_count`<br> `has_responsive_badge`<br> `major_audience_country_code`<br> `major_audience_gender`<br> `major_audience_age`<br> `query` |
| New request parameters | N/A | `tto_tcm_account_id`<br> `country_codes`<br> `state_provinces`<br> `content_label_ids`<br> `industry_label_ids`<br> `min_creator_price`<br> `max_creator_price`<br> `creator_price_currency`<br> `min_median_views`<br> `max_median_views`<br> `min_engagement_rate`<br> `max_engagement_rate`<br> `follower_country_codes`<br> `follower_gender_ratio`<br> `follower_age`<br> `keyword_search`<br> `sort_order` |
| Same response parameters | `creators`<br> `creators.handle_name`<br> `creators.display_name`<br> `creators.profile_image`<br> `creators.followers_count`<br> `creators.videos_count`<br> `creators.likes_count`<br> `page_info`<br> `page_info.page`<br> `page_info.page_size`<br> `page_info.total_number`<br> `page_info.total_page` | `creators`<br> `creators.handle_name`<br> `creators.display_name`<br> `creators.profile_image`<br> `creators.followers_count`<br> `creators.videos_count`<br> `creators.likes_count`<br> `page_info`<br> `page_info.page`<br> `page_info.page_size`<br> `page_info.total_number`<br> `page_info.total_page` |
| Deprecated response parameters |N/A |  `creators.followings_count` |
| New response parameters | N/A | `creators.following_count`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/creator/discover/

**Method** GET

**Header**

```xtable
|Field{32%}|Type{15%}|Description{53%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok One Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
```


**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|tto_tcm_account_id {Required}|string|The ID of the TikTok One Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146).|
|country_codes {Required}|string[]|Filter by the country or region codes of the creators.<br><br>If you pass multiple codes, these codes should be from the same regional category.<br><br>For enum values and regional category for each code, see <a href="#item-link-List of values for country_codes">List of values for `country_codes`</a>.|
|state_provinces|string[]|Valid only when `country_codes` is `["US"]`.<br><br>Filter by the states or provinces of the creators.<br><br>Currently, only U.S. states are supported.<br><br>For enum values, see <a href="#item-link-List of values for state_provinces">List of values for `state_provinces`</a>.|
|content_label_ids|string[]|Filter by the IDs of video content tags.<br>A video content tag is a main focus of the creator’s TikTok account and primarily represents the direction of the creator’s organic content.<br><br>Max size: 100.<br><br>To obtain a list of video content tag IDs, use [/tto/tcm/category/label/](https://business-api.tiktok.com/portal/docs?id=1816372654109698) with `label_type` set to `SEARCH` and retrieve the IDs from the response parameter `label_id` within the object array `content_labels`.|
|industry_label_ids|string[]|Filter by the IDs of industry tags.<br>An industry tag indicates the creator industry that is assigned to creators based on the commercial content they produce, including ad creatives and branded content on their TikTok accounts, and represents their commercial experience in certain industries.<br><br>Max size: 100.<br><br>To obtain a list of industry tag IDs, use [/tto/tcm/category/label/](https://business-api.tiktok.com/portal/docs?id=1816372654109698) with `label_type` set to `SEARCH` and retrieve the IDs from the response parameter `label_id` within the object array `industry_labels`.|
|min_followers|number|Filter by the minimum number of followers.<br><br>Default value: 0.|
|max_followers|number|Filter by the maximum number of followers.<br><br>This field doesn’t have a default value.|
|languages|string[]|Filter by language.<br><br>For enum values, see <a href="#item-link-List of values for languages">List of values for `languages`</a>.<br><br>By default, the results are not filtered by languages.|
|min_creator_price|number|Filter by the minimum creator starting price, in the currency specified by `creator_price_currency`.<br><br>Default value: 0.|
|max_creator_price|number|Filter by the maximum creator starting price, in the currency specified by `creator_price_currency`.<br><br>This field doesn’t have a default value.|
|creator_price_currency|string|The currency for creator starting price filters `min_creator_price` and `max_creator_price`.<br><br>Enum value: `USD`.<br>Default value: `USD`.|
|min_avg_views|number|Filter by the minimum average views.<br><br>Average views represent the number of views that the creator’s videos received.<br>Default value: 0.|
|max_avg_views|number|Filter by the maximum average views.<br><br>This field doesn’t have a default value.|
|min_median_views|number|Filter by the minimum median views.<br><br>Median views represent the middle value in the range of views across a creator’s recent videos, ranked lowest to highest, which showcases the typical video view count of their content.<br><br>Default value: 0.|
|max_median_views|number|Filter by the maximum median views.<br><br>This field doesn’t have a default value.|
|min_engagement_rate|float|Filter by the minimum engagement rate.<br><br>Engagement rate represents how much people interacted with content. It’s calculated by taking the total number of comments, likes, and shares divided by video views.<br><br>Value range: 0-1.<br>Default value: 0.|
|max_engagement_rate|float|Filter by the maximum engagement rate.<br><br>Value range: 0-1.<br>This field doesn’t have a default value.|
|follower_country_codes|string[]|Filter by the codes of followers’ locations.<br><br>For enum values, see <a href="#item-link-List of values for follower_country_codes">List of values for `follower_country_codes`</a>.|
|follower_gender_ratio|string|Filter by followers’ gender ratio.<br><br>Enum values:<ul><li>`FEMALE_50`: Percentage of audience who are female is over 50%.</li><li>`FEMALE_60`: Percentage of audience who are female is over 60%.</li><li>`FEMALE_70`: Percentage of audience who are female is over 70%.</li><li>`MALE_50`: Percentage of audience who are male is over 50%.</li><li>`MALE_60`: Percentage of audience who are male is over 60%.</li><li>`MALE_70`: Percentage of audience who are male is over 70%.</li></ul>|
|follower_age|string|Filter by followers’ majority age group.<br><br>Enum values:<ul><li>`18-24`: 18-24.</li><li>`25-34`: 25-34.</li><li>`35-44`: 35-44.</li><li>`45-54`: 45-54.</li><li>`55+`: 55+.</li></ul>|
|keyword_search|string|The keyword to search for creators.<br><br>Length limit: 100 characters.<br><br>Fuzzy match is supported.|
|sort_field|string|The specific metric by which to sort the results.<br><br>Enum values:<ul><li>`RELEVANCE`: relevance.</li><li>`FOLLOWERS`: followers.</li><li>`MEDIAN_VIEWS`: median views.</li><li>`ENGAGEMENT_RATE`: engagement rate.</li></ul>Default value: `RELEVANCE`.<br><br>You can sort the returned data using this field.|
|sort_order|string|The order in which to sort the results.<br><br>Enum values:<ul><li>`ASC`: Ascending.</li><li>`DESC`: Descending.</li></ul> Default value: `DESC`.|
|page|number|Current page number.<br><br>Value range: ≥1.<br>Default value: 1.|
|page_size|number|Current page size.<br><br>Value range: 1-200.<br>Default value: 24.|
```
### List of values for `country_codes`
<table>
<thead>
  <tr>
    <th style="width:30%">Regional category</th>
    <th style="width:20%"><code>country_codes</code> value</th>
    <th style="width:50%">Description</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>the United States</td>
    <td><code>US</code></td>
    <td>United States</td>
  </tr>
  <tr>
    <td rowspan="5">Europe</td>
    <td><code>DE</code></td>
    <td>Germany</td>
  </tr>
  <tr>
    <td><code>ES</code></td>
    <td>Spain</td>
  </tr>
  <tr>
    <td><code>FR</code></td>
    <td>France</td>
  </tr>
  <tr>
    <td><code>GB</code></td>
    <td>United Kingdom</td>
  </tr>
  <tr>
    <td><code>IT</code></td>
    <td>Italy</td>
  </tr>
  <tr>
    <td rowspan="20">Other countries or regions<br></td>
    <td><code>AE</code></td>
    <td>United Arab Emirates</td>
  </tr>
  <tr>
    <td><code>AR</code></td>
    <td>Argentina</td>
  </tr>
  <tr>
    <td><code>AU</code></td>
    <td>Australia</td>
  </tr>
  <tr>
    <td><code>BR</code></td>
    <td>Brazil</td>
  </tr>
  <tr>
    <td><code>CA</code></td>
    <td>Canada</td>
  </tr>
  <tr>
    <td><code>CO</code></td>
    <td>Colombia</td>
  </tr>
  <tr>
    <td><code>EG</code></td>
    <td>Egypt</td>
  </tr>
  <tr>
    <td><code>ID</code></td>
    <td>Indonesia</td>
  </tr>
  <tr>
    <td><code>IL</code></td>
    <td>Israel</td>
  </tr>
  <tr>
    <td><code>JP</code></td>
    <td>Japan</td>
  </tr>
  <tr>
    <td><code>KR</code></td>
    <td>South Korea</td>
  </tr>
  <tr>
    <td><code>MX</code></td>
    <td>Mexico</td>
  </tr>
  <tr>
    <td><code>MY</code></td>
    <td>Malaysia</td>
  </tr>
  <tr>
    <td><code>PH</code></td>
    <td>Philippines</td>
  </tr>
  <tr>
    <td><code>SA</code></td>
    <td>Saudi Arabia</td>
  </tr>
  <tr>
    <td><code>SG</code></td>
    <td>Singapore</td>
  </tr>
  <tr>
    <td><code>TH</code></td>
    <td>Thailand</td>
  </tr>
  <tr>
    <td><code>TR</code></td>
    <td>Turkey</td>
  </tr>
  <tr>
    <td><code>TW</code></td>
    <td>Taiwan, China</td>
  </tr>
  <tr>
    <td><code>VN</code></td>
    <td>Vietnam</td>
  </tr>
</tbody>
</table>



### List of values for `state_provinces`
``` xtable
| Value{30%} | Description{70%} |
|---|---|
| `Alabama` | Alabama |
| `Alaska` | Alaska |
| `Arizona` | Arizona |
| `Arkansas` | Arkansas |
| `California` | California |
| `Colorado` | Colorado |
| `Connecticut` | Connecticut |
| `Delaware` | Delaware |
| `Florida` | Florida |
| `Georgia` | Georgia |
| `Hawaii` | Hawaii |
| `Idaho` | Idaho |
| `Illinois` | Illinois |
| `Indiana` | Indiana |
| `Iowa` | Iowa |
| `Kansas` | Kansas |
| `Kentucky` | Kentucky |
| `Louisiana` | Louisiana |
| `Maine` | Maine |
| `Maryland` | Maryland |
| `Massachusetts` | Massachusetts |
| `Michigan` | Michigan |
| `Minnesota` | Minnesota |
| `Mississippi` | Mississippi |
| `Missouri` | Missouri |
| `Montana` | Montana |
| `Nebraska` | Nebraska |
| `Nevada` | Nevada |
| `New Hampshire` | New Hampshire |
| `New Jersey` | New Jersey |
| `New Mexico` | New Mexico |
| `New York` | New York |
| `North Carolina` | North Carolina |
| `North Dakota` | North Dakota |
| `Ohio` | Ohio |
| `Oklahoma` | Oklahoma |
| `Oregon` | Oregon |
| `Pennsylvania` | Pennsylvania |
| `Rhode Island` | Rhode Island |
| `South Carolina` | South Carolina |
| `South Dakota` | South Dakota |
| `Tennessee` | Tennessee |
| `Texas` | Texas |
| `Utah` | Utah |
| `Vermont` | Vermont |
| `Virginia` | Virginia |
| `Washington` | Washington |
| `West Virginia` | West Virginia |
| `Wisconsin` | Wisconsin |
| `Wyoming` | Wyoming |
``` 
### List of values for `languages`
``` xtable
| Value{30%} | Description{70%} |
|---|---|
| `ar` | Arabic |
| `de` | German |
| `en` | English |
| `es` | Spanish |
| `fr` | French |
| `id` | Indonesian |
| `it` | Italian |
| `ja` | Japanese |
| `ko` | Korean |
| `ms` | Malay |
| `pt` | Portuguese |
| `th` | Thai |
| `tl` | Tagalog |
| `tr` | Turkish |
| `vi` | Vietnamese |
```

### List of values for `follower_country_codes`
``` xtable
| Value{30%} | Description{70%} |
|---|---|
| `AE` | United Arab Emirates |
| `AU` | Australia |
| `BR` | Brazil |
| `CA` | Canada |
| `DE` | Germany |
| `EG` | Egypt |
| `ES` | Spain |
| `FR` | France |
| `GB` | United Kingdom |
| `ID` | Indonesia |
| `IL` | Israel |
| `IT` | Italy |
| `JP` | Japan |
| `KR` | South Korea |
| `MY` | Malaysia |
| `PH` | Philippines |
| `RU` | Russia |
| `SA` | Saudi Arabia |
| `SG` | Singapore |
| `TH` | Thailand |
| `TR` | Turkey |
| `TW` | Taiwan, China |
| `US` | United States |
| `VN` | Vietnam |
```


### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/creator/discover/?tto_tcm_account_id={{tto_tcm_account_id}}&country_codes=["US"]' \
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
#|creators|object[]|Discovered creators.|
##|handle_name|string|The handle name (username) of the creator.|
##|display_name|string|The display name of the creator.|
##|profile_image|string|Temporary URL for profile photo of the creator. <br><br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. <br><br>Once the URL expires, you need to call [/tto/tcm/creator/discover/](https://business-api.tiktok.com/portal/docs?id=1825017307843585) to obtain a new URL.|
##|followers_count|number|The total number of followers of the creator.|
##|following_count|number|The total number of accounts that the creator follows on TikTok.|
##|likes_count|number|The total number of times people have liked the creator's videos.|
##|videos_count|number|The total number of public videos posted by the creator.|
#|page_info|object|Pagination information.|
##|page|integer|Current page number.|
##|page_size|integer|Page size.|
##|total_number|integer|Total number of results.|
##|total_page|integer|Total pages of results.|
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
                "display_name": "{{display_name}}",
                "followers_count": 4419006,
                "following_count": 428,
                "handle_name": "{{handle_name}}",
                "likes_count": 133618856,
                "profile_image": "{{profile_image}}",
                "videos_count": 606
            },
            {
                "display_name": "{{display_name}}",
                "followers_count": 2149447,
                "following_count": 1309,
                "handle_name": "{{handle_name}}",
                "likes_count": 89181425,
                "profile_image": "{{profile_image}}",
                "videos_count": 1047
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 24,
            "total_number": 1,
            "total_page": 2
        }
    }
}
(/code)
```
