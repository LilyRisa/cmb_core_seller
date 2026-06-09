---
title: "Create a Saved Audience"
doc_id: 1780154541898754
path: "API Reference / Audience / Saved Audience / Create a Saved Audience"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Saved Audience

Use this endpoint to create a Saved Audience. An advertiser account can have up to 400 Saved Audiences.

## Request 
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/saved_audience/create/

**Method** POST


**Header**
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type. <br>Accepted format: `"application/json"` |
```

**Parameters**
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID.  |
| saved_audience_name {Required} | string | Saved Audience name.<br>Character limit is 512 and cannot contain emoji. |
| location_ids {Required}| string[] | IDs of the locations that you want to target. To get the available locations and corresponding IDs, use the [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) or [/tool/targeting/search/](https://ads.tiktok.com/marketing_api/docs?id=1761236883355649) endpoint. <br><span style="color:darkred"><b>Note</b></span>: <ul><li>Overlapping targeted locations are not supported. For instance, you cannot target the U.S. and the state of California at the same time.</li></ul> |
| gender | string | Gender that you want to target. <br>Enum values: `GENDER_FEMALE`,`GENDER_MALE`,`GENDER_UNLIMITED` |
| age_groups | string[] | Age groups you want to target. <br>For enum values, see [Enumeration - Targeting Age Group](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
| languages | string[] | Codes of the languages that you want to target.  You can get language codes via [/tool/language/](https://ads.tiktok.com/marketing_api/docs?id=1737188554152962), and if you don't want to limit the languages you target, assign an empty value to this field or do not pass in this field. |
| audience_ids | string[] | List of audience IDs. <br>You can get audience IDs via the [/dmp/custom_audience/list/](https://ads.tiktok.com/marketing_api/docs?id=1739940506015746) endpoint. |
| excluded_audience_ids | string[] | List of audience IDs to be excluded. <br>You can get audience IDs via the [/dmp/custom_audience/list/](https://ads.tiktok.com/marketing_api/docs?id=1739940506015746) endpoint. |
| interest_category_ids | string[] | Interest classification. <br>You can use [/tool/target_recommend_tags/](https://ads.tiktok.com/marketing_api/docs?id=1736275204260866) to get a list of recommended interest categories based on your targeting regions and your industries, or use [/tool/interest_category/](https://ads.tiktok.com/marketing_api/docs?id=1737174348712961) endpoint to get the complete list of interest categories.<br>If the interest is specified, users who do not meet the interest target will be excluded during delivery. Do not specify if you wish to target everyone.  |
| interest_keyword_ids | string[] | IDs of interest keywords that you want to use to target audience. <br>You can use [/tool/interest_keyword/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1763590884474882) to get recommended interest keywords. |
| actions | object[] | A list of action category objects. |
#| action_category_ids {+Conditional} | string[] | Required when actions has been specified and valid only when TikTok placement is the only placement selected in your ad group. <br>IDs of the action categories (behaviors) or hashtags that you want to target.  <br>You can get action category IDs via [/tool/action_category/](https://business-api.tiktok.com/portal/docs?id=1737166752522241).  |
#| action_scene {+Conditional} | string | Required when `actions` has been specified.<br>Where you can collect information about user actions. <br>Enum values: `VIDEO_RELATED`, `CREATOR_RELATED`, `HASHTAG_RELATED`. |
#| action_period {+Conditional} | number | Required when `actions` has been specified.<br>The time period to include actions from. <br>Enum values: `0`, `7`, `15`. If `action_scene` is `CREATOR_RELATED` or `HASHTAG_RELATED`, `0` will be used regardless of the value you pass in. `0` means that there is no definite timeframe to select actions from. |
#| video_user_actions {+Conditional} | string[] | Required when `actions` has been specified.<br>Video-related Actions.<ul><li>If `action_scene` is `VIDEO_RELATED`, the allowed values are: `WATCHED_TO_END`,`LIKED`,`COMMENTED`,`SHARED`.</li><li>If `action_scene` is `CREATOR_RELATED`, the allowed values are: `FOLLOWING`, `VIEW_HOMEPAGE`.</li><li>If `action_scene` is `HASHTAG_RELATED`, the allowed value is `VIEW_HASHTAG`. </li></ul>|
| operating_systems <br>  | string[] | Device operating systems that you want to target. <br>Enum values: `ANDROID`, `IOS`. Only one value is allowed.  |
| min_android_version | string | Minimum device Android version. <br>For enum values, see [Enumeration - Minimum Android Version](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
| min_ios_version | string | Minimum iOS version. <br>For enum values, see [Enumeration - Minimum iOS Version](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).  |
| device_model_ids | string[] | IDs of the device models that you want to target. <br>Use [/tool/device_model/](https://ads.tiktok.com/marketing_api/docs?id=1737172880570369) to get the complete list of device model IDs and their statuses, and only active devices (`is_active` = `true` in the response of [/tool/device_model/](https://ads.tiktok.com/marketing_api/docs?id=1737172880570369)) can be used to create ads.<br><br><span style="color:darkred"><b>Note</b></span>: Device model (`device_model_ids`) and device price (`device_price_ranges`) cannot be set at the same time. |
| network_types | string[] | Device connection types that you want to target.<br>Default: `unlimited`. For enum values, see [Enumeration - Connection Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Connection%20Type). |
| carrier_ids | string[] | Carriers that you want to target. <br>Use [/tool/carrier/](https://ads.tiktok.com/marketing_api/docs?id=1737168013095938) endpoint to get a list of carriers.<br>A carrier is valid only when the `in_use` field for the carrier is `true`. The carriers must be consistent with the location(s) that you want to target.  |
| device_price_ranges | number[] | Targeting device price range. <br>10000 means 1000+. The numbers must be in multiples of 50.<br> <br><span style="color:darkred"><b>Important</b></span>: The upper limit you set will be added by 50 and the resulting new number will be used as the actual upper limit for device targeting. The actual upper limit is shown in the ad group settings in TikTok Ads Manager. If you set and get the price range of [0, 250], it actually means [0, 300]. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/dmp/saved_audience/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "saved_audience_name": "{{saved_audience_name}}",
    "location_ids": [
        "{{location_id}}",
        "{{location_id}}"
    ],
    "languages": [
        "en"
    ],
    "gender": "GENDER_FEMALE",
    "age_groups": [
        "AGE_18_24"
    ],
    "audience_ids": [
        "{{audience_id}}",
        "{{audience_id}}"
    ],
    "excluded_audience_ids": [
        "{{excluded_audience_id}}",
        "{{excluded_audience_id}}"
    ],
    "interest_category_ids": [
        "14",
        "16"
    ],
    "interest_keyword_ids": [
        "{{interest_keyword_ids}}"
    ],
    "interest_keyword_language": [
        "en"
    ],
    "network_types": [
        "WIFI"
    ],
    "actions": [
        {
            "video_user_actions": [
                "FOLLOWING"
            ],
            "action_period": 0,
            "action_scene": "CREATOR_RELATED",
            "action_category_ids": [
                "2"
            ]
        }
    ],
    "carrier_ids": [
        "{{carrier_id}}"
    ],
    "device_price_ranges": [
        0,
        1000
    ],
    "operating_systems": [
        "ANDROID"
    ],
    "min_android_version": "10.0"
}'
(/code)
```

## Response
```xtable
|Field{30%}|Type {15%}|Description{55%}|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Response message. For details, see [Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id |string| The log ID of a request, which uniquely identifies the request.|
|data |object|Returned data.  |
#|saved_audience_id |string|Saved Audience ID.|
```


### Example
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "saved_audience_id": "{{saved_audience_id}}"
    }
}
(/code)
```




