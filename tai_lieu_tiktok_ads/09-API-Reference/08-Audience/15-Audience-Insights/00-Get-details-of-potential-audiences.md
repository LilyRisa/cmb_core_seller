---
title: "Get details of potential audiences"
doc_id: 1797016593782785
path: "API Reference / Audience / Audience Insights / Get details of potential audiences"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get details of potential audiences

Use this endpoint to get the details of your potential audiences. 
><span style="color:DodgerBlue">**Note**</span><br>This endpoint uses the POST method.
## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/audience/insight/info/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type. <br>Allowed value: `"application/json"`.|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID.|
|custom_audience_id {+Conditional}|string|You need pass in either `locations`or `custom_audience_id` in the request. <br>Custom audience ID. <br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>When `custom_audience_id` is specified, either `selected_audience` or `locations` cannot be used. Otherwise, an error will occur.</li><li>The audience ID you input must belong to your specified advertisers.</li></ul>|
|locations {+Conditional}|Object[]| You need pass in either `locations`or `custom_audience_id` in the request.  <br>Locations. <br>Max size : 5<br><p><span style="color:darkred"><b>Note</b></span>: When `locations` is specified, `custom_audience_id` cannot be used. Otherwise, an error will occur. |
#|country_code {Required}|string| Country code. See [Appendix-Location IDs](https://business-api.tiktok.com/portal/docs?id=1739311040498689) for details.|
#|location_ids|string[]|Location IDs. <br>By default, all location IDs under the specified country code are selected. The location IDs you input must fall within the range of the specified country code. |
|dimensions{Required}|string[]|Use this field to control which API response dimensions are returned. <br>Enum values: `age`, `gender`, `country`, `top_regions`,  `operating_system`, `operating_system_version`, `device_price`, `top_interests`, `bottom_interests`, `engagement`, `ad_interest_categories`, and `top_hashtags`.<br><p><span style="color:darkred"><b>Note</b></span>: Currently, you can input only one dimension for each API call. |
|selected_audience|Object|The audience you want to filter for.|
#|top_regions_country_code {+Conditional}|string|Required if you have input `top_regions` in the `dimensions` field and specified `locations`.  <br>When the dimension is `top_regions`, the corresponding return fields will contain the sub-regions within the country you specified in `locations`.|
#|languages|string[]|Codes of the languages that you want to target. <br>For the list of language codes supported, see [Enumeration - Language Code](https://business-api.tiktok.com/portal/docs?id=1737174886619138). You can also get language codes via [/tool/language/](https://ads.tiktok.com/marketing_api/docs?id=1737188554152962).|
#|age_groups|string[]|Age groups you want to target. <br>For enum values, see [Enumeration - Age Group](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Age%20Group).|
#|gender|string[]|Genders that you want to target. <br>Enum values: `GENDER_FEMALE`, `GENDER_MALE`. If you don't pass anything, it means that you want to target both males and females. |
#|device_price_ranges|integer[]|Targeting device price range. <br>10000 means 1000+. The numbers must be in multiples of 50.|
#|min_ios_version|string[]|Minimum iOS version. <br>For enum values, see [Enumeration - Minimum iOS Version](https://business-api.tiktok.com/portal/docs?id=1737174886619138).|
#|min_android_version|string[]|Minimum Android version. <br>For enum values, see [Enumeration - Minimum Android Version](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
#|operating_systems{+Conditional}|string[]|Required if you've specified either `min_ios_version` or `min_android_version`. <br>Device operating systems that you want to target. <br>Enum values: `ANDROID`, `IOS`. Only one value is allowed. If you don't pass anything, it means that you want to target both Android and iOS systems.|
#|interest_category_ids|string[]|Interest classification. <br>Use [/tool/interest_category/](https://ads.tiktok.com/marketing_api/docs?id=1737174348712961) to get a list of available interest categories. Use [/tool/target_recommend_tags/](https://ads.tiktok.com/marketing_api/docs?id=1736275204260866) to get a list of recommended interest categories.|
#|video_action_category_ids|string[]|IDs of the video action categories. <br>Use [/tool/action_category/](https://ads.tiktok.com/marketing_api/docs?id=1737166752522241) to get a list of available video interaction categories (`action_scene` = `VIDEO_RELATED` in the response). Use [/tool/targeting_category/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1736275204260866) to get a list of recommended video interaction categories (`scene` = `VIDEO_RELATED` in the response).|
#|creator_action_category_ids|string[]|IDs of the creator action categories. <br>Use [/tool/action_category/](https://ads.tiktok.com/marketing_api/docs?id=1737166752522241) to get a list of available creator interaction categories (`action_scene` = `CREATOR_RELATED` in the response). Use [/tool/targeting_category/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1736275204260866) to get a list of recommended creator interaction categories (`scene` = `CREATOR_RELATED` in the response).|
#|hashtag_action_category_ids|string[]|Hashtag IDs. <br>Use [/tool/hashtag/recommend/](https://business-api.tiktok.com/portal/docs?id=1736271339521025) to get a list of recommended hashtag IDs, or use [/tool/hashtag/get/](https://business-api.tiktok.com/portal/docs?id=1736280889167874) to get hashtags by IDs. |
```
​
### Example
1. When you specify `locations` and `selected_audience`
``` xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/audience/insight/info/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "dimensions": [
        "age"
    ],
    "locations": [
        {
            "country_code": "US",
            "location_ids": []
        },
        {
            "country_code": "AU",
            "location_ids": [
                "{{location_ids}}"
            ]
        }
    ],
    "selected_audience": {

        "gender": [
            "GENDER_MALE"
        ],
        "age_groups": [
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "languages": [
            "fr",
            "en"
        ],
        "operating_systems": [
            "IOS"
        ],

        "device_price_ranges": [
            100,
            950
        ],
        "interest_category_ids": [
            "{{interest_category_ids}}",
            "{{interest_category_ids}}"
        ],
        "video_action_category_ids": [
            "{{video_action_category_ids}}",
            "{{video_action_category_ids}}"
        ],
        "creator_action_category_ids": [
            "{{creator_action_category_ids}}",
            "{{creator_action_category_ids}}"
        ],
        "hashtag_action_category_ids": [
            "{{hashtag_action_category_ids}}"
        ]
    }

}'
(/code)
```
2. When you specify `custom_audience_id`
``` xcodeblock
(code curl http)
curl --location 'https://business-api.tiktok.com/open_api/v1.3/audience/insight/info/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "dimensions": [
        "age"
    ],
  "custom_audience_id": "{{custom_audience_id}}"   
}'
(/code)
```



## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Return message, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log id of a request, which uniquely identifies the request.|
|data |object|Returned Data. |
#|age|object|Age. Returned if you have input `age` in `dimensions` in your request. <br>Within `all` and `selected` objects listed below, there are two fields that convey specific information.  See the code example below for details. <ul><li>`label`: Age group label. It is of string data type. </li><li>`rate`: Percentage of the label. It is of the number data type.</li><ul>|
##|all|object[]|Age data of all audience, that is, the audience for the selected locations. <br>In certain scenarios, `all` will be returned as null. These include instances when:<ul><li>When your audience size is less than 1,000. </li><li>When you specify `custom_audience_id` in your request </li></ul>|
##|selected|object[]|Age data of the audience you filtered for.|
#|gender|object|Gender. Returned if you have input `gender` in `dimensions` in your request. <br>Within `all` and `selected` objects listed below, there are two fields that convey specific information. <ul><li>`label`: Gender label. It is of string data type. </li><li>`rate`: Percentage of the label. It is of the number data type.</li><ul>|
##|all|object[]|Gender data of all audience, that is, the audience for the selected locations. <br>In certain scenarios, `all` will be returned as null. These include instances when:<ul><li>When your audience size is less than 1,000. </li><li>When you specify `custom_audience_id` in your request.</li></ul>|
##|selected|object[]|Gender data of the audience you filtered for.|
#|country|object|Country. Returned if you have input `country` in `dimensions` in your request. <br>Within `all` and `selected` objects listed below, there are two fields that convey specific information. <ul><li>`label`: Country name. It is of string data type. </li><li>`rate`: Percentage of the country. It is of the number data type.</li><ul>|
##|all|object[]|Country data of all audience, that is, the audience for the selected locations. <br>In certain scenarios, `all` will be returned as null. These include instances when:<ul><li>When your audience size is less than 1,000. </li><li>When you specify `custom_audience_id` in your request.</li></ul>|
##|selected|object[]|Country data of the audience you filtered for.|
#|top_regions|object|Country. Returned if you have input `top_regions` in `dimensions` in your request. <br>Within `all` and `selected` objects listed below, there are several fields that convey specific information. <ul><li>`label`: Region name. It is of string data type. </li><li>`rate`: Percentage of the region. It is of the number data type.</li><li>`region_id`: Region ID. It is of string data type. </li><li>`country_code`: Country code. It is of string data type.</li><ul>|
##|all|object[]|Region data of all audience, that is, the audience for the selected locations. <br>In certain scenarios, `all` will be returned as null. These include instances when:<ul><li>When your audience size is less than 1,000. </li><li>When you specify `custom_audience_id` in your request.</li></ul>|
##|selected|object[]|Region data of the audience you filtered for.|
#|operating_system|object|Operating system. Returned if you have input `operating_system` in `dimensions` in your request. <br>Within `all` and `selected` objects listed below, there are several fields that convey specific information. <ul><li>`label`: This field is used to denote the operating system. It is of string data type. </li><li>`rate`: This field is utilized to denote the percentage of the operating system. It is of the number data type.</li><ul>|
##|all|object[]|Operating system data of all audience, that is, the audience for the selected locations. <br>In certain scenarios, `all` will be returned as null. These include instances when:<ul><li>When your audience size is less than 1,000. </li><li>When you specify `custom_audience_id` in your request .</li></ul>|
##|selected|object[]|Operating system data of the audience you filtered for.|
#|operating_system_version|object|Operating system version. Returned if you have input `operating_system_version` in `dimensions` in your request. <br>Within `all` and `selected` objects listed below, there are several fields that convey specific information. <ul><li>`label`: Operating system version. It is of string data type. </li><li>`rate`: Percentage of the operating system version. It is of the number data type.</li><ul>|
##|all|object[]|Operating system version data of all audience, that is, the audience for the selected locations. <br>In certain scenarios, `all` will be returned as null. These include instances when:<ul><li>When your audience size is less than 1,000. </li><li>When you specify `custom_audience_id` in your request.</li></ul>|
##|selected|object[]|Operating system version data of the audience you filtered for.|
#|device_price|object|Device price. Returned if you have input `device_price` in `dimensions` in your request. <br>Within `all` and `selected` objects listed below, there are several fields that convey specific information. <ul><li>`label`: Device price. It is of string data type. </li><li>`rate`: Percentage of the device price. It is of the number data type.</li><ul>|
##|all|object[]|Device price data of all audience, that is, the audience for the selected locations. <br>In certain scenarios, `all` will be returned as null. These include instances when:<ul><li>When your audience size is less than 1,000. </li><li>When you specify `custom_audience_id` in your request .</li></ul>|
##|selected|object[]|Device price data of the audience you filtered for.|
#|top_interests|object|Top interests. Returned if you have input `top_interests` in `dimensions` in your request. <br>Within `all` and `selected` objects listed below, there are several fields that convey specific information. <ul><li>`label`: Interest category label. It is of string data type. </li><li>`rate`: Percentage of the interest category label. It is of the number data type. </li><li>`ad_interest_category`: This field is used to denote the first-level interest category. It is of string data type.</li><ul>|
##|all|object[]|Interest category data of all audience, that is, the audience for the selected locations. <br>In certain scenarios, `all` will be returned as null. These include instances when:<ul><li>When your audience size is less than 1,000. </li><li>When you specify `custom_audience_id` in your request.</li></ul>|
##|selected|object[]|Interest category data of the audience you filtered for.|
#|bottom_interests|object|Bottom interests. Returned if you have input `bottom_interests` in `dimensions` in your request. <br>Within `all` and `selected` objects listed below, there are several fields that convey specific information. <ul><li>`label`: Interest category label. It is of string data type. </li><li>`rate`: Percentage of the interest category label. It is of the number data type. </li><li>`ad_interest_category`: This field is used to denote the first-level interest category. It is of string data type.</li><ul>|
##|all|object[]|Interest category data of all audience, that is, the audience for the selected locations. <br>In certain scenarios, `all` will be returned as null. These include instances when:<ul><li>When your audience size is less than 1,000. </li><li>When you specify `custom_audience_id` in your request.</li></ul>|
##|selected|object[]|Interest category data of the audience you filtered for.|
#|ad_interest_categories|object|Interest categories. Returned if you have input `ad_interest_categories` in `dimensions` in your request. <br>Within `all` and `selected` objects listed below, there are several fields that convey specific information. <ul><li>`label`: Interest category label. It is of string data type. </li><li>`rate`: Percentage of the interest category label. It is of the number data type. </li><li>`type`: Level of the interest category. It is of string data type. Example: "first". </li><ul>|
##|all|object[]|Interest category data of all audience, that is, the audience for the selected locations. <br>In certain scenarios, `all` will be returned as null. These include instances when:<ul><li>When your audience size is less than 1,000. </li><li>When you specify `custom_audience_id` in your request .</li></ul>|
##|selected|object[]|Interest category data of the audience you filtered for.|
#|top_hashtags|object|Top hashtags. Returned if you have input `top_hashtags` in `dimensions` in your request.|
##|hashtag_id|string|Hashtag ID.|
##|hashtag_name|string|Hashtag name.|
##|count|number|The number of times the hashtag is used. |
#|engagement|object|Engagement metrics. Returned if you have input `engagement` in `dimensions` in your request. <br>Within `all` and `selected` objects listed below, there are several fields that convey specific information. <ul><li>`daily_sessions`: Average number of app opens per user per day. It is of the number data type. </li><li>`daily_session_duration_in_minutes`: Average number of minutes used per user per day. It is of the number data type. </li><li> `daily_video_views`: Average number of video views per user per day. It is of the number data type. </li><li>`daily_comments`: Average number of comments per user per day. It is of the number data type. </li><li>`daily_likes`:  Average number of likes per user per day. It is of the number data type. </li><li>`daily_shares`:  Average number of shares per user per day. It is of the number data type. </li><li>`total_video_uploads`: Average uploads of selected/all users per day. It is of the number data type.</li></ul>|
##|all|object[]|Engagement data of all audience, that is, the audience for the selected locations. <br>In certain scenarios, `all` will be returned as null. These include instances when:<ul><li>When your audience size is less than 1,000. </li><li>When you specify `custom_audience_id` in your request.</li></ul>|
##|selected|object[]|Engagement data of the audience you filtered for.|
```

### Example
1. When you have specified `locations` and `selected_audience` in your request, the response will be as follows:
``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "age": {
            "all": [
                {
                    "label": "18-24",
                    "rate": 0.2258
                },
                {
                    "label": "25-34",
                    "rate": 0.3304
                },
                {
                    "label": "35-44",
                    "rate": 0.201
                },
                {
                    "label": "45-54",
                    "rate": 0.1327
                },
                {
                    "label": "≥55",
                    "rate": 0.1102
                }
            ],
            "selected": [
                {
                    "label": "25-34",
                    "rate": 0.5264
                },
                {
                    "label": "35-44",
                    "rate": 0.2073
                },
                {
                    "label": "45-54",
                    "rate": 0.136
                },
                {
                    "label": "≥55",
                    "rate": 0.1302
                }
            ]
        }
    }
}
(/code);
```
2. When you have specified `custom_audience_id` in your request, the response will be as follows:
``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "age": {
            "all": [],
            "selected": [
                {
                    "label": "18-24",
                    "rate": 0.3896
                },
                {
                    "label": "25-34",
                    "rate": 0.3775
                },
                {
                    "label": "35-44",
                    "rate": 0.1485
                },
                {
                    "label": "45-54",
                    "rate": 0.0485
                },
                {
                    "label": "≥55",
                    "rate": 0.0359
                }
            ]
        }
    }
}
(/code)
```


