---
title: "Get details of a popular hashtag"
doc_id: 1825119047070721
path: "API Reference / Discovery / Get details of a popular hashtag"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get details of a popular hashtag

Use this endpoint to get detailed information about a popular hashtag, such as audience insights and hashtag status.

If the returned `hashtag_status` is ONLINE, you can use the [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) endpoint and pass the returned `hashtag_id` to the `action_category_ids` field. This will set the hashtag as a targeting action category for the ad group.

You can also discover similar hashtags by using the [/tool/hashtag/recommend/](https://business-api.tiktok.com/portal/docs?id=1736271339521025) endpoint. Pass the returned `hashtag_name` to the `keywords` field to get similar hashtags. 

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/discovery/detail/

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
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.<br><br>Pass one of the advertiser IDs returned via `advertiser_ids` from [/oauth2/access_token/](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|discovery_type {Required}|string|The type of discovery item.<br><br>Currently, we only support `HASHTAG`.|
|hashtag_id {Required}|string|Hashtag ID.<br><br>Pass in a hashtag ID that is returned via the [/discovery/trending_list/](https://business-api.tiktok.com/portal/docs?id=1825119032526849) endpoint.<br> If the hashtag you provide isn't included in the list returned by the `/discovery/trending_list/` endpoint, you will receive an empty list in response.<br><br>Example: `4268426520694`.|
|country_code {Required}|string|Location code.<br><br>Specify the same `country_code` as the one you input in the `/discovery/trending_list/` endpoint. <br>If the `country_code` you provide is not associated with the hashtag, we will return an error.<br><br>See [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010) for the supported values.<br><br>Example: `DE`.|
|date_range {Required}|string|The timeframe in which the items have been popular.<br><br>Specify the same `date_range` as the one you input in the `/discovery/trending_list/` endpoint. <br>If the `date_range` you provide is not associated with the hashtag, we will return an error.<br><br>Enum values:<ul><li>`1DAY`: last 1 day.</li><li>`7DAY`: last 7 days.</li><li>`30DAY`: last 30 days.</li><li>`120DAY`: last 120 days.</li></ul>|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/discovery/detail/?advertiser_id={{advertiser_id}}&discovery_type=HASHTAG&hashtag_id={{hashtag_id}}&country_code=US&date_range=7DAY' \
--header 'Access-Token: {{Access-Token}}' \
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
#|hashtag_id|string|Hashtag ID.<br><br>Example: `4268426520694`.|
#|hashtag_name|string|Hashtag name.<br><br>Example: `yummy`.|
#|views|integer|The number of views for related videos that feature this hashtag in the selected region and timeframe.<br><br>Example: 134333716.|
#|views_global_lifetime|integer|The number of views for related videos that feature this hashtag, globally and across their whole lifetime.<br><br>Example: 91639649280.|
#|posts|integer|The number of posts for related videos that feature this hashtag in the selected region and timeframe.<br><br>Example: 21856.|
#|posts_global_lifetime|integer|The number of posts for related videos that feature this hashtag, globally and across their whole lifetime.<br><br>Example: 7846729.|
#|top_country_list|string[]|The location codes of the top five locations in the world where this hashtag is most popular.<br><br>To learn about the location that a location code represents, see [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010).<br><br>Example: `["US", "GB", "PH", "CA", "DE"]`.|
#|hashtag_status|string|The status of the hashtag. <br><br>Enum values:<ul><li>`ONLINE`: available for ad creation.</li><li>`OFFLINE`: unavailable for ad creation.</li></ul>|
#|trending_history|object[]|The historical ranking data for the hashtag, spanning up to the past 30 days.|
##|date|string|Date, in the format of `YYYY-MM-DD`.<br><br>Example: `2024-01-01`.|
##|rank_position_daily|string|The rank position of the hashtag among hashtags across all categories or industries in the selected region (`country_code`) on the date.<br><br>Example: `73`.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the hashtag does not perform well compared to the last time period, the hashtag will not be considered a trending hashtag and the value of this field will be null.|
##|views_daily|integer|The number of views for related videos that feature this hashtag in the selected region (`country_code`) on the date.<br><br>Example: 39409290.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the daily views are less than 1,000, the value of this field will be null.|
#|audience_insights|object|Information about viewers that watch videos associated with this hashtag.<br><br><p><span style="color:darkred"><b>Note</b></span>: If `hashtag_status` is `OFFLINE` or the expected audience size for the hashtag is less than 1,000, `audience_insights` will be returned as empty.|
##|audience_ages|object[]|Information about viewers' age.|
###|age|string|Age group. <br><br>Enum values:`18-24`,`25-34`,`35+`.|
###|percentage|number|The approximate percentage of viewers associated with different age groups.|
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
        "hashtag_status": "ONLINE",
        "posts": 31927,
        "hashtag_id": "{{hashtag_id}}",
        "audience_insights": {
            "audience_ages": [
                {
                    "percentage": 0.36926615840545485,
                    "age": "25-34"
                },
                {
                    "percentage": 0.3550167061571056,
                    "age": "18-24"
                },
                {
                    "percentage": 0.2757171047811277,
                    "age": "35+"
                }
            ]
        },
        "hashtag_name": "tattoo",
        "views_global_lifetime": 157770993216,
        "posts_global_lifetime": 12543346,
        "views": 216408760,
        "top_country_list": [
            "US",
            "MX",
            "BR",
            "VN",
            "ID"
        ],
        "trending_history": [
            {
                "date": "2024-03-09",
                "rank_position_daily": "121",
                "views_daily": 39181186
            },
            ...
            {
                "date": "2024-04-06",
                "rank_position_daily": "85",
                "views_daily": 49419265
            },
            {
                "date": "2024-04-07",
                "rank_position_daily": "128",
                "views_daily": 45524768
            }
        ]
    }
}
(/code)
```
