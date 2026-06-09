---
title: "Get TTO Public Account Insights"
doc_id: 1828185358712897
path: "API Reference / TikTok One / Get TTO Public Account Insights"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get TTO Public Account Insights

Use this endpoint to get the basic profile information of a creator on [TikTok One (TTO) Creator Marketplace](https://ads.tiktok.com/creative/creatormarketplace).

## Comparing TCM and TTO Creator Marketplace endpoints

The following table outlines the differences between TikTok Creator Marketplace (TCM) and TTO Creator Marketplace endpoints.

```xtable
| Changes {24%}| TCM Endpoint{36%} | TTO Creator Marketplace Endpoint{40%} |
|---|---|---|
| Endpoint path | /v1.3/tcm/creator/public/ | /v1.3/tto/tcm/creator/public/ |
| Same request parameters | `handle_name` | `handle_name` |
| Deprecated request parameters | N/A | `tcm_account_id` |
| New request parameters | N/A | `tto_tcm_account_id` |
| Same response parameters | `profile_image`<br>`handle_name`<br>`display_name`<br>`followers_count`<br>`likes_count`<br>`videos_count`<br>`creator_id`<br>`bio` | `profile_image`<br>`handle_name`<br>`display_name`<br>`followers_count`<br>`likes_count`<br>`videos_count`<br>`creator_id`<br>`bio` |
| New response parameters | N/A | `following_count`<br>`median_views` <br>`engagement_rate`<br>`creator_price`<br> `currency`<br>`industry_labels` <br>`industry_labels.label_id`<br>`industry_labels.label_name`<br>`content_labels`<br>`content_labels.label_id`<br>`content_labels.label_name` |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/creator/public/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TTO Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
```


**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|tto_tcm_account_id {Required}|string|The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146).|
|handle_name {Required}|string|The handle name of the TTO Creator Marketplace creator that you want to search for.<br><br>The exact match is supported.<br><br><span style="color:darkred"><b>Note</b></span>: If you specify the handle name of a creator that is not on TTO Creator Marketplace yet, an error will occur.|
``` 

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/creator/public/?tto_tcm_account_id={{tto_tcm_account_id}}&handle_name={{handle_name}}' \
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
#|handle_name|string|The unique handle name of the creator.|
#|display_name|string|The display name of the creator.|
#|profile_image|string|Temporary URL for profile photo of the creator.<br><br>Validity period: around seven days.<br><br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.<br><br>Once the URL expires, you can call [/tto/tcm/creator/public/](https://business-api.tiktok.com/portal/docs?id=1828185358712897) to obtain a new URL.|
#|bio|string|The bio description of the creator.<br><br><span style="color:darkred"><b>Note</b></span>: If the bio spans multiple lines, any line breaks within the bio will be represented as `\n` in the field value.|
#|followers_count|number|The number of followers for the creator.<br><br>Example: 100.|
#|following_count|number|The number of accounts that the creator is following.<br><br>Example: 100.|
#|videos_count|number|The number of public videos posted by the creator.<br><br>Example: 100.|
#|likes_count|number|The number of likes on public videos posted by the creator.<br><br>Example: 100.|
#|creator_id|string|The unique ID of the creator.<br><br>Example: `1234567891234567891`.|
#|median_views|number|Median views.<br><br>The middle value in the range of views across the creator's recent 30 videos, ranked lowest to highest, which showcases the typical video view count of their content.|
#|engagement_rate|float|Engagement rate.<br><br>The percentage of people interacted with content across the creator's recent 30 videos. It's calculated by taking the total number of comments, likes, and shares divided by video views.<br><br>Example: 0.13669180870056152.|
#|creator_price|number|The starting price of the creator.<br><br>Example: 500.|
#|currency|string|The currency for the starting price, in ISO 4217 format.<br><br>Example: `USD`.|
#|industry_labels|object[]|The list of creator industry labels, which can be used to obtain Branded Content creator rankings on TTO Creator Leaderboard.<br><br>Creator industry labels are assigned to creators based on the commercial content they produce, including ad creatives and branded content on their TikTok accounts, and represent their commercial experience in certain industries.<br><br>Each `label_id` can be used in [/tto/tcm/rank/](https://business-api.tiktok.com/portal/docs?id=1816378813274113) to obtain a list of up to 100 US creators who rank highest for that label.|
##|label_id|string|The ID of the creator industry label.<br><br>Example: `20000000000`.|
##|label_name|string|The name of the creator industry label.<br><br>Example: `Apps`.|
#|content_labels|object[]|The list of content tag labels, which can be used to obtain Organic Content creator rankings on TTO Creator Leaderboard.<br><br>Content tag labels are assigned to creators based on the main focus of the creator’s TikTok account, and represent the direction of the creator’s organic content.<br><br>Each `label_id` can be used in [/tto/tcm/rank/](https://business-api.tiktok.com/portal/docs?id=1816378813274113) to obtain a list of up to 100 US creators who rank highest for that label.|
##|label_id|string|The ID of the content tag label.<br><br>Example: `11005001007`.|
##|label_name|string|The name of the content tag label.<br><br>Example: `Shooting Games`.|
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
        "bio": "{{bio}}",
        "content_labels": [
            {
                "label_id": "11005001007",
                "label_name": "Shooting Games"
            }
        ],
        "creator_id": "{{creator_id}}",
        "creator_price": 100,
        "currency": "USD",
        "display_name": "{{display_name}}",
        "engagement_rate": 0.08472570776939392,
        "followers_count": 4721794,
        "following_count": 231,
        "handle_name": "{{handle_name}}",
        "industry_labels": [
            {
                "label_id": "20000000000",
                "label_name": "Apps"
            }
        ],
        "likes_count": 153119665,
        "median_views": 426141,
        "profile_image": "{{profile_image}}",
        "videos_count": 1803
    }
}
(/code)
```

