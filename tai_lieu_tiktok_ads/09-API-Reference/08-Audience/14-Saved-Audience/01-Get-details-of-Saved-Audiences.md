---
title: "Get details of Saved Audiences"
doc_id: 1780154619404290
path: "API Reference / Audience / Saved Audience / Get details of Saved Audiences"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get details of Saved Audiences

Use this endpoint to get the details of Saved Audiences associated with an ad account. 


## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/dmp/saved_audience/list/>

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID. |
|saved_audience_ids  |string[]|IDs of Saved Audiences. <br><br>Max size: 100. <br><br>To create Saved Audiences and obtain the Saved Audience IDs, use [/dmp/saved_audience/create/](https://business-api.tiktok.com/portal/docs?id=1780154541898754). |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/dmp/saved_audience/list/?advertiser_id={{advertiser_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```



## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Response message. For details, see [Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id |string| The log ID of a request, which uniquely identifies the request.|
| data | object | Returned data. |
#|  saved_audiences | object[] | A list of Saved Audiences. |
##| saved_audience_id | string | Saved Audience ID. |
##| saved_audience_name | string | Saved Audience name. |
##| location_ids | string[] | IDs of the locations that you want to target. |
##| gender | string | Your targeted gender. <br>Enum values: `GENDER_FEMALE`,`GENDER_MALE`,`GENDER_UNLIMITED` |
##| age_groups | string[] | Your targeted age groups.<br>For enum values, see [Enumeration - Targeting Age Group](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
##| languages | string[] | Codes of the languages that you want to target.  |
##| audience_ids | string[] | List of audience IDs.  |
##| excluded_audience_ids | string[] | List of audience IDs to be excluded.  |
##| interest_category_ids | string[] | Interest classification.  |
##| interest_keyword_ids | string[] | IDs of interest keywords to target audience.  |
##| actions | object[] | A list of action category objects. |
###| action_category_ids  | string[] | IDs of the action categories (behaviors) or hashtags that you want to target.   |
###|  action_scene  | string | Where you can collect information about user actions. <br>Enum values: `VIDEO_RELATED`, `CREATOR_RELATED`, `HASHTAG_RELATED`. |
###|  action_period  | number | The time period to include actions from. <br>Enum values: `0`, `7`, `15`. If `action_scene` is `CREATOR_RELATED` or `HASHTAG_RELATED`, `0` will be used regardless of the value you pass in. `0` means that there is no definite timeframe to select actions from. |
###|  video_user_actions  | string[] | Video-related Actions.<ul><li>If `action_scene` is `VIDEO_RELATED`, the allowed values are: `WATCHED_TO_END`, `LIKED`, `COMMENTED`, `SHARED`.</li><li>If `action_scene` is `CREATOR_RELATED`, the allowed values are: `FOLLOWING`, `VIEW_HOMEPAGE`.</li><li>If `action_scene` is `HASHTAG_RELATED`, the allowed value is `VIEW_HASHTAG`.</li></ul> |
##| operating_systems | string[] | Device operating systems. <br>Enum values: `ANDROID`, `IOS`. Only one value is allowed.  |
##| min_android_version | string | Minimum device Android version. <br>For enum values, see [Enumeration - Minimum Android Version](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
##| min_ios_version | string | Minimum iOS version. <br>For enum values, see [Enumeration - Minimum iOS Version](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).  |
##| device_model_ids | string[] | IDs of the device models.  |
##| network_types | string[] | Device connection types.<br>Default: `unlimited`. For enum values, see [Enumeration - Connection Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
##| carrier_ids | string[] | Carriers.  |
##| device_price_ranges | number[] | Targeting device price range. <br>10000 means 1000+. The numbers must be in multiples of 50.<br> <br><span style="color:darkred"><b>Important</b></span>:The upper limit you set will be added by 50 and the resulting new number will be used as the actual upper limit for device targeting. The actual upper limit is shown in the ad group settings in TikTok Ads Manager. If you set and get the price range of [0, 250], it actually means [0, 300]. |
#|page_info |object| Page information. |
##|page |number| Current page number. |
##|page_size |number| Paging Size. |
##|total_number |number| Total number of results. |
##|total_page |number| Total pages. |
```

### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "saved_audiences": [
            {
                "interest_keyword_ids": [
                    "{{interest_keyword_id}}"
                ],
                "age_groups": [
                    "{{age_group}}"
                ],
                "actions": [
                    {
                        "action_period": 0,
                        "action_category_ids": [
                            "2"
                        ],
                        "video_user_actions": [
                            "FOLLOWING"
                        ],
                        "action_scene": "CREATOR_RELATED"
                    }
                ],
                "languages": [
                    "en"
                ],
                "location_ids": [
                    "6252001",
                    "2077456"
                ],
                "interest_category_ids": [
                    "14",
                    "16"
                ],
                "network_types": [
                    "WIFI"
                ],
                "saved_audience_name": "{{saved_audience_name}}",
                "operating_systems": [
                    "ANDROID"
                ],
                "device_model_ids": [],
                "excluded_audience_ids": [
                    "{{excluded_audience_ids}}",
                    "{{excluded_audience_ids}}"
                ],
                "saved_audience_id": "{{saved_audience_id}}",
                "audience_ids": [
                    "{{audience_ids}}",
                    "{{audience_ids}}"
                ],
                "device_price_ranges": [
                    0,
                    50,
                    100,
                    150,
                    200,
                    250,
                    300,
                    350,
                    400,
                    450,
                    500,
                    550,
                    600,
                    650,
                    700,
                    750,
                    800,
                    850,
                    900,
                    950,
                    1000
                ],
                "carrier_ids": [
                    "{{carrier_id}}"
                ],
                "gender": "GENDER_FEMALE",
                "min_ios_version": "NONE",
                "min_android_version": "10.0"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_page": 1,
            "total_number": 1
        }
    }
}
(/code)
```


