---
title: "Get Custom Conversions associated with an event source"
doc_id: 1842225174460673
path: "API Reference / Custom Conversions / Get Custom Conversions associated with an event source"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Custom Conversions associated with an event source

Use this endpoint to retrieve the Custom Conversions associated with a Pixel or an App.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/custom_conversion/list/

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
|event_source_type {Required}|string|Event source type.<br><br>Enum values:<ul><li>`PIXEL`: Pixel.</li><li>`APP`: App.</li></ul>|
|event_source_id {Required}|string|Event source ID.<br><br><ul><li>When `event_source_type` is `PIXEL`, specify a Pixel ID.<ul><li>To obtain IDs of Pixels within your ad account, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978) and check the returned `pixel_id`.</li></ul></li><li>When `event_source_type` is `APP`, specify the app platform ID of a Self Attribution Network (SAN) App.<ul><li>To obtain app platform IDs of SAN Apps within your ad account, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786). Confirm that the returned `self_attribution_enabled` for the App is `true` and check the returned `app_platform_id`.</li></ul></li></ul>|
|search_keyword|string|The keyword to search for Custom Conversions by.<br>You can search by Custom Conversion name or Custom Conversion ID.<br><br><ul><li>When you search by Custom Conversion name, fuzzy matches are supported.</li><li>When you search by Custom Conversion ID, exact matches are supported.</li></ul>|
|sort_field|string|Sorting field.<br><br>Enum values:<ul><li>`CREATE_TIME`: The time when the Custom Conversion was created.</li><li>`TOTAL_COUNT`: The total conversion count for the Custom Conversion.</li></ul>Default value: `CREATE_TIME`.|
|sort_type|string|Sorting type.<br><br>Enum values:<ul><li>`DESC`: descending.</li><li>`ASC`: ascending.</li></ul>Default value: `DESC`.|
|page|number|Current page number.<br><br>Value range: ≥ 1.<br>Default value: 1.|
|page_size|number|Page size.<br><br>Value range: 1-1,000.<br>Default value: 10.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/custom_conversion/list/?advertiser_id={{advertiser_id}}&event_source_type=PIXEL&event_source_id={{event_source_id}}&page=1&page_size=1000' \
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
#|list|object[]|The list of Custom Conversions associated with the event source.|
##|custom_conversion_id|string|The ID of the Custom Conversion.|
##|name|string|The name of the Custom Conversion.|
##|description|string|The description of the Custom Conversion.|
##|event_source_type|string|Event source type.<br><br>Enum values:<ul><li>`PIXEL`: Pixel.</li><li>`APP`: App.</li></ul>|
##|event_source_id|string|Event source ID.<br><br><ul><li>When `event_source_type` is `PIXEL`, this field represents a Pixel ID.<ul><li>To obtain IDs of Pixels within your ad account, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978) and check the returned `pixel_id`.</li></ul></li><li>When `event_source_type` is `APP`, this field represents the app platform ID of a Self Attribution Network (SAN) App.<ul><li>To obtain app platform IDs of SAN Apps within your ad account, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786). Confirm that the returned `self_attribution_enabled` for the App is `true` and check the returned `app_platform_id`.</li></ul></li></ul>|
##|activity_info|object|Details of the activity for the Custom Conversion.|
###|activity_status|string|The activity status of the Custom Conversion.<br><br>Enum values:<ul><li>`NO_RECENT_ACTIVITY`: No recent activity. Activity received, but not in the past 7 days.</li><li>`ACTIVE`: Active. Activity received in the past 7 days.</li><li>`WAITING_FOR_ACTIVITY`: Inactive. No activity received in the past 90 days.</li></ul>|
###|total_count|number|The total conversion count for the Custom Conversion.|
###|last_record_time|string|The time when activity for the Custom Conversion was last received, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).<br><br>Example: `2025-01-01 00:00:01`.|
##|optimization_event|string|The conversion event (standard event) associated with the Custom Conversion.<br><br>For enum values, see [List of values for `optimization_event`](https://business-api.tiktok.com/portal/docs?id=1842225226006530#item-link-List%20of%20values%20for%20optimization_event).|
##|rules|object[]|Details of the conversion rules for the Custom Conversion.<br><br>Only actions that meet these conditions will count as Custom Conversions.|
###|parameter|string|Parameter.<br><br>Enum values:<ul><li>`BRAND`: `brand`.</li><li>`CONTENT_CATEGORY`: `content_category`.</li><li>`CONTENT_ID`: `content_id`.</li><li>`CONTENT_NAME`: `content_name`.</li><li>`CONTENT_TYPE`: `content_type`.</li><li>`CURRENCY`: `currency`.</li><li>`DESCRIPTION`: `description`.</li><li>`EVENT_ID`: `event_id`.</li><li>`NUM_ITEMS`: `num_items`.</li><li>`PRICE`: `price`.</li><li>`SEARCH_STRING`: `search_string`.</li><li>`STATUS`: `status`.</li><li>`URL`: `url`.</li><li>`VALUE`: `value`.</li></ul>|
###|operator|string|Operator.<br><br><ul><li>When `parameter` is `BRAND`, `CONTENT_CATEGORY`, `CONTENT_ID`, `CONTENT_NAME`, `CONTENT_TYPE`, `DESCRIPTION`, `EVENT_ID`, `SEARCH_STRING`, `STATUS`, or `URL`, the enum values are:<ul><li>`CONTAIN`: Contains.</li><li>`NOT_CONTAIN`: Does not contain.</li><li>`START_WITH`: Starts with.</li><li>`EQUAL`: Is equal to.</li><li>`NOT_EQUAL`: Is not equal to.</li></ul></li><li>When `parameter` is `NUM_ITEMS`, `PRICE`, or `VALUE`, the enum values are:<ul><li>`EQUAL`: Is equal to.</li><li>`NOT_EQUAL`: Is not equal to.</li><li>`LT`: Is less than.</li><li>`GT`: Is greater than.</li><li>`LTE`: Is less than or equal to.</li><li>`GTE`: Is greater than or equal to.</li></ul></li><li>When `parameter` is `CURRENCY`, the enum values are:<ul><li>`EQUAL`: Is equal to.</li><li>`NOT_EQUAL`: Is not equal to.</li></ul></li></ul>|
###|values|string[]|Values for the parameter.<br><br><ul><li>When `parameter` is `BRAND`, `CONTENT_CATEGORY`, `CONTENT_ID`, `CONTENT_NAME`, `CONTENT_TYPE`, `DESCRIPTION`, `EVENT_ID`, `SEARCH_STRING`, `STATUS`, `URL`, the field will be an array of strings, each with a maximum length of 100 characters and up to 100 entries.</li><li>When `parameter` is `NUM_ITEMS`, the field will be an integer between 0 and 1,000,000,000.</li><li>When `parameter` is `PRICE`, `VALUE`, the field will be a float kept to two decimal places, within the range from 0.00 to 1,000,000,000.00.</li><li>When `parameter` is `CURRENCY`, the field will be an array of strings. To learn about the enum values, see [List of currency values](https://business-api.tiktok.com/portal/docs?id=1842225226006530#item-link-List%20of%20currency%20values).|
###|create_time|string|The time when the Custom Conversion was created, in the format of `YYYY-MM-DD HH:MM:SS`(UTC+0).<br><br>Example: `2025-01-01 00:00:01`.|
###|update_time|string|The time when the Custom Conversion was last updated, in the format of `YYYY-MM-DD HH:MM:SS`(UTC+0).<br><br>Example: `2025-01-01 00:00:01`.|
#|page_info|object|Pagination information.|
##|page|number|Current page number.|
##|page_size|number|Page size.|
##|total_number|number|Total number of results.|
##|total_page|number|Total pages of results.|
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
        "list": [
            {
                "activity_info": {
                    "activity_status": "WAITING_FOR_ACTIVITY",
                    "total_count": 0
                },
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
            },
            {
                "activity_info": {
                    "activity_status": "NO_RECENT_ACTIVITY",
                    "last_record_time": "{{last_record_time}}",
                    "total_count": 1
                },
                "create_time": "{{create_time}}",
                "custom_conversion_id": "{{custom_conversion_id}}",
                "description": "",
                "event_source_id": "{{event_source_id}}",
                "event_source_type": "PIXEL",
                "name": "{{name}}",
                "optimization_event": "ON_WEB_CART",
                "rules": [
                    {
                        "operator": "CONTAIN",
                        "parameter": "CONTENT_ID",
                        "values": [
                            "{{content_id}}"
                        ]
                    }
                ],
                "update_time": "{{update_time}}"
            },
            ...
            {
                "activity_info": {
                    "activity_status": "ACTIVE",
                    "last_record_time": "{{last_record_time}}",
                    "total_count": 20
                },
                "create_time": "{{create_time}}",
                "custom_conversion_id": "{{custom_conversion_id}}",
                "description": "",
                "event_source_id": "{{event_source_id}}",
                "event_source_type": "PIXEL",
                "name": "{{name}}",
                "optimization_event": "SHOPPING",
                "rules": [
                    {
                        "operator": "CONTAIN",
                        "parameter": "STATUS",
                        "values": [
                            "{{status_value}}"
                        ]
                    }
                ],
                "update_time": "{{update_time}}"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 98,
            "total_page": 1
        }
    }
}
(/code)
```
