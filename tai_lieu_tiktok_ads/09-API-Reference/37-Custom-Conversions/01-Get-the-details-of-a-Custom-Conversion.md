---
title: "Get the details of a Custom Conversion"
doc_id: 1842225206555074
path: "API Reference / Custom Conversions / Get the details of a Custom Conversion"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the details of a Custom Conversion

Use this endpoint to retrieve the details of a Custom Conversion associated with a Pixel or an App.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/custom_conversion/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|advertiser_id {Required}|string|Advertiser ID.|
|custom_conversion_id {Required}|string|The ID of the Custom Conversion.<br><br>To obtain the list of Custom Conversions associated with a Pixel or an App, use [/custom_conversion/list/](https://business-api.tiktok.com/portal/docs?id=1842225174460673).|
|event_source_type {Required}|string|Event source type.<br><br>Enum values:<ul><li>`PIXEL`: Pixel.</li><li>`APP`: App.</li></ul>|
|event_source_id {Required}|string|Event source ID.<br><br><ul><li>When `event_source_type` is `PIXEL`, specify a Pixel ID.<ul><li>To obtain IDs of Pixels within your ad account, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978) and check the returned `pixel_id`.</li></ul></li><li>When `event_source_type` is `APP`, specify the app platform ID of a Self Attribution Network (SAN) App.<ul><li>To obtain app platform IDs of SAN Apps within your ad account, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786). Confirm that the returned `self_attribution_enabled` for the App is `true` and check the returned `app_platform_id`.</li></ul></li></ul>|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/custom_conversion/get/?advertiser_id={{advertiser_id}}&event_source_type=PIXEL&event_source_id={{event_source_id}}&custom_conversion_id={{custom_conversion_id}}' \
--header 'Access-token: {{Access-token}}'
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
#|custom_conversion_id|string|The ID of the Custom Conversion.|
#|name|string|The name of the Custom Conversion.|
#|description|string|The description of the Custom Conversion.|
#|event_source_type|string|Event source type.<br><br>Enum values:<ul><li>`PIXEL`: Pixel.</li><li>`APP`: App.</li></ul>|
#|event_source_id|string|Event source ID.<br><br><ul><li>When `event_source_type` is `PIXEL`, this field represents a Pixel ID.<ul><li>To obtain IDs of Pixels within your ad account, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978) and check the returned `pixel_id`.</li></ul></li><li>When `event_source_type` is `APP`, this field represents the app platform ID of a Self Attribution Network (SAN) App.<ul><li>To obtain app platform IDs of SAN Apps within your ad account, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786). Confirm that the returned `self_attribution_enabled` for the App is `true` and check the returned `app_platform_id`.</li></ul></li></ul>|
#|activity_status|string|The status of the Custom Conversion.<br><br>Enum values:<ul><li>`NO_RECENT_ACTIVITY`: No recent activity. Activity received, but not in the past 7 days.</li><li>`ACTIVE`: Active. Activity received in the past 7 days.</li><li>`WAITING_FOR_ACTIVITY`: Inactive. No activity received in the past 90 days.</li></ul>|
#|optimization_event|string|The conversion event (standard event) associated with the Custom Conversion.<br><br>For enum values, see [List of values for `optimization_event`](https://business-api.tiktok.com/portal/docs?id=1842225226006530#item-link-List%20of%20values%20for%20optimization_event).|
#|rules|object[]|Details of the conversion rules for the Custom Conversion.<br><br>Only actions that meet these conditions will count as Custom Conversions.|
##|parameter|string|Parameter.<br><br>Enum values:<ul><li>`BRAND`: `brand`.</li><li>`CONTENT_CATEGORY`: `content_category`.</li><li>`CONTENT_ID`: `content_id`.</li><li>`CONTENT_NAME`: `content_name`.</li><li>`CONTENT_TYPE`: `content_type`.</li><li>`CURRENCY`: `currency`.</li><li>`DESCRIPTION`: `description`.</li><li>`EVENT_ID`: `event_id`.</li><li>`NUM_ITEMS`: `num_items`.</li><li>`PRICE`: `price`.</li><li>`SEARCH_STRING`: `search_string`.</li><li>`STATUS`: `status`.</li><li>`URL`: `url`.</li><li>`VALUE`: `value`.</li></ul>|
##|operator|string|Operator.<br><br><ul><li>When `parameter` is `BRAND`, `CONTENT_CATEGORY`, `CONTENT_ID`, `CONTENT_NAME`, `CONTENT_TYPE`, `DESCRIPTION`, `EVENT_ID`, `SEARCH_STRING`, `STATUS`, or `URL`, the enum values are:<ul><li>`CONTAIN`: Contains.</li><li>`NOT_CONTAIN`: Does not contain.</li><li>`START_WITH`: Starts with.</li><li>`EQUAL`: Is equal to.</li><li>`NOT_EQUAL`: Is not equal to.</li></ul></li><li>When `parameter` is `NUM_ITEMS`, `PRICE`, or `VALUE`, the enum values are:<ul><li>`EQUAL`: Is equal to.</li><li>`NOT_EQUAL`: Is not equal to.</li><li>`LT`: Is less than.</li><li>`GT`: Is greater than.</li><li>`LTE`: Is less than or equal to.</li><li>`GTE`: Is greater than or equal to.</li></ul></li><li>When `parameter` is `CURRENCY`, the enum values are:<ul><li>`EQUAL`: Is equal to.</li><li>`NOT_EQUAL`: Is not equal to.</li></ul></li></ul>|
##|values|string[]|Values for the parameter.<br><br><ul><li>When `parameter` is `BRAND`, `CONTENT_CATEGORY`, `CONTENT_ID`, `CONTENT_NAME`, `CONTENT_TYPE`, `DESCRIPTION`, `EVENT_ID`, `SEARCH_STRING`, `STATUS`, `URL`, the field will be an array of strings, each with a maximum length of 100 characters and up to 100 entries.</li><li>When `parameter` is `NUM_ITEMS`, the field will be an integer between 0 and 1,000,000,000.</li><li>When `parameter` is `PRICE`, `VALUE`, the field will be a float kept to two decimal places, within the range from 0.00 to 1,000,000,000.00.</li><li>When `parameter` is `CURRENCY`, the field will be an array of strings. To learn about the enum values, see [List of currency values](https://business-api.tiktok.com/portal/docs?id=1842225226006530#item-link-List%20of%20currency%20values).|
##|create_time|string|The time when the Custom Conversion was created, in the format of `YYYY-MM-DD HH:MM:SS`(UTC+0).<br><br>Example: `2025-01-01 00:00:01`.|
##|update_time|string|The time when the Custom Conversion was last updated, in the format of `YYYY-MM-DD HH:MM:SS`(UTC+0).<br><br>Example: `2025-01-01 00:00:01`.|
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
        "activity_status": "WAITING_FOR_ACTIVITY",
        "create_time": "{{create_time}}",
        "custom_conversion_id": "{{custom_conversion_id}}",
        "description": "{{description}}",
        "event_source_id": "{{event_source_id}}",
        "event_source_type": "PIXEL",
        "name": "{{name}}",
        "optimization_event": "SHOPPING",
        "rules": [
            {
                "operator": "NOT_CONTAIN",
                "parameter": "EVENT_ID",
                "values": [
                    "{{event_id}}",
                    "{{event_id}}"
                ]
            },
            {
                "operator": "START_WITH",
                "parameter": "DESCRIPTION",
                "values": [
                    "{{description_value}}"
                ]
            }
        ],
        "update_time": "{{update_time}}"
    }
}
(/code)
```
