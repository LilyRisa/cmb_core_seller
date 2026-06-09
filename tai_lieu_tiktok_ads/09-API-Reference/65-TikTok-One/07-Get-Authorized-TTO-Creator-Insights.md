---
title: "Get Authorized TTO Creator Insights"
doc_id: 1834913529338882
path: "API Reference / TikTok One / Get Authorized TTO Creator Insights"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Authorized TTO Creator Insights

Use this endpoint to get information and insights about a TikTok creator who has joined TikTok One (TTO).


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/creator/authorized/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators. <br>Use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Obtain%20a%20Creator%20access%20token) or [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Renew%20a%20Creator%20access%20token) endpoint to get or renew an access token.|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|creator_id {Required}|string|Application specific unique ID of the TikTok account.<br><br> Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Obtain%20a%20Creator%20access%20token) or [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Renew%20a%20Creator%20access%20token) to this field.|
|fields|string[]|Requested fields. <br><br>If this field is not set, the default fields (`profile_image` and `display_name`) will be returned.|
```


### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/creator/authorized/?creator_id={{creator_id}}&fields=["handle_name","display_name","profile_image","bio","followers_count","following_count","videos_count","likes_count","creator_rate","country_code","content_labels","industry_labels","audience_countries","audience_genders","audience_ages","audience_devices","audience_usages"]' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data|object|Returned data.|
#|profile_image|string|Temporary URL for the profile photo of the creator. <br>The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. <br><br>Validity period: around seven days. |
#|handle_name|string|The username (handle) of the creator.|
#|display_name|string|The display name (nickname) for the creator.|
#|bio|string|The bio description of the creator.|
#|following_count|number|The total number of accounts that the creator is following.|
#|followers_count|number|The total number of followers for the creator.|
#|likes_count|number|Total likes.<br> The total number of times people have liked the creator's published videos.|
#|videos_count|number|The total number of public videos posted by the creator.|
#|creator_rate|object|Creator starting rate.|
##|rate|number|Amount of the rate.|
##|currency|string|Currency for the rate amount, in [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217) code format. <br><br>Example: `EUR`.|
#|country_code|string|The code of the country or region where the creator is located, in [ISO 3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements) format. <br><br>Example: `US`.|
#|content_labels|object[]|The list of creator industry labels.<br> Creator industry labels are assigned to creators based on the commercial content they produce, including ad creatives and branded content on their TikTok accounts, and represent their commercial experience in certain industries.|
##|label_id|number|The ID of the creator industry label. <br><br>Example: 11015001.|
##|label_name|string|The name of the creator industry label. <br><br>Example: `Tech Products & Tests`.|
#|industry_labels|object[]|The list of content tag labels. <br>Content tag labels are assigned to creators based on the main focus of the creator’s TikTok account, and represent the direction of the creator’s organic content.|
##|label_id|number|The ID of the content tag label. <br><br>Example: 23000000000.|
##|label_name|string|The name of the content tag label.<br><br> Example: `News & Entertainment`.|
#|audience_countries|object[]|Three countries or regions that have the largest audience size.|
##|country|string|Country or region name, in [ISO 3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements) format. <br><br>Example: `US`.|
##|percentage|number|Percentage.|
#|audience_genders|object[]|Audience gender information.|
##|gender|string|Gender.<br><br>Enum values: `Female`, `Male`.|
##|percentage|number|Percentage.|
#|audience_ages|object[]|Audience age distribution.|
##|age|string|Age group.<br><br>Enum values: `25-34`, `35-44`, `55+`, `Other`.|
##|percentage|number|Percentage.|
#|audience_devices|object[]|Audience device information.|
##|device|string|Device.<br><br>Enum values: <ul><li>`APPLE`: Apple.</li><li>`SAMSUNG`: Samsung.</li><li>`XIAOMI`: Xiaomi.</li><li>`OPPO`: Oppo.</li><li>`MOTOROLA`: Motorola.</li><li>`OTHER`: Other.</li></ul>|
##|percentage|number|Percentage.|
#|audience_usages|object[]|Audience TikTok usage.|
##|usage|string|Whether the audience uses TikTok frequently.<br><br>Enum values: <ul><li>`active`: An audience is defined as active if they have logged into the TikTok app within the last 30 days.</li><li>`inactive`: Inactive.</li></ul>|
##|percentage|number|Percentage.|
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
        "audience_ages": [
            {
                "age": "25-34",
                "percentage": 0.375
            },
            {
                "age": "35-44",
                "percentage": 0.375
            },
            {
                "age": "Other",
                "percentage": 0.125
            },
            {
                "age": "55+",
                "percentage": 0.125
            }
        ],
        "audience_countries": [
            {
                "country": "US",
                "percentage": 0.5
            },
            {
                "country": "JP",
                "percentage": 0.375
            },
            {
                "country": "MY",
                "percentage": 0.125
            }
        ],
        "audience_devices": [
            {
                "device": "APPLE",
                "percentage": 0.7777777777777778
            },
            {
                "device": "SAMSUNG",
                "percentage": 0.1111111111111111
            },
            {
                "device": "XIAOMI",
                "percentage": 0.1111111111111111
            },
            {
                "device": "Other",
                "percentage": 0
            }
        ],
        "audience_genders": [
            {
                "gender": "Male",
                "percentage": 0.625
            },
            {
                "gender": "Female",
                "percentage": 0.375
            }
        ],
        "audience_usages": [
            {
                "percentage": 0.875,
                "usage": "active"
            },
            {
                "percentage": 0.125,
                "usage": "inactive"
            }
        ],
        "bio": "{{bio}}",
        "content_labels": [
            {
                "label_id": 11015001,
                "label_name": "Tech Products & Tests"
            }
        ],
        "country_code": "US",
        "creator_rate": {
            "currency": "USD",
            "rate": 1000
        },
        "display_name": "{{display_name}}",
        "followers_count": 8,
        "following_count": 0,
        "handle_name": "{{handle_name}}",
        "industry_labels": [
            {
                "label_id": 23000000000,
                "label_name": "News & Entertainment"
            },
            {
                "label_id": 24000000000,
                "label_name": "Business Services"
            },
            {
                "label_id": 29000000000,
                "label_name": "Health"
            }
        ],
        "likes_count": 0,
        "profile_image": "{{profile_image}}",
        "videos_count": 3
    }
}
(/code)
```
