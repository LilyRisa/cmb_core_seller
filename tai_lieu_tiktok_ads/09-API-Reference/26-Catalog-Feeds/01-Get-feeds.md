---
title: "Get feeds"
doc_id: 1740665183073281
path: "API Reference / Catalog Feeds / Get feeds"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get feeds

Use this endpoint to get the information about a particular feed or all feeds for a catalog.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/catalog/feed/get/| /v1.3/catalog/feed/get/|
|Request parameter data type |`bc_id`: number <br>`catalog_id`: number  <br>`feed_id`: number|`bc_id`: string  <br>`catalog_id`: string <br> `feed_id`: string|
|Response parameter data type |`feed_id`: number |`feed_id`: string |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/feed/get/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {required}|string| Business Center ID. |
|catalog_id {required}|string| Catalog ID.|
|feed_id| string| ID of the feed that you want to get. If not specified, all feeds for the catalog will be returned.|
```


### Example

```xcodeblock
(code)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/catalog/feed/get/?bc_id={{bc_id}}&catalog_id={{catalog_id}}' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| The log id of a request, which uniquely identifies the request.|
|data |object|Returned data.|
#|feed_list|object[]|Feed list.|
##|feed_id|string|Feed ID.|
##|feed_name|string| Feed name.|
##|status |string | The schedule status of the feed. Enum values: <ul><li>`ON`: The schedule for the feed is with the  "ON" status.</li><li>`OFF`: The schedule for the feed is with the  "OFF" status, or there is no schedule for the feed.</li></ul>|
##|last_update_param|object| Information about the last update.|
###|uri|string|Data Feed URL, the URL where your file is hosted.|
###|update_mode|string|The update mode. <br><br>Enum values: <ul><li>`OVERWRITE`: In this mode, a full replacement will be performed to the catalog. Products not present in the feed will be removed. New products will be created. Existing products will be updated.</li><li>`INCREMENTAL`: In this mode, new products will be created, and existing products will be updated.</li></ul>|
###|timezone|string| Time zone of the schedule. <br><br>Example: `Africa/Accra`.<br><br>For the list of time zones, see [Appendix-Time Zone](https://ads.tiktok.com/marketing_api/docs?id=1737586324313089).|
###|interval_type|string| Schedule interval.<br><br> Enum values: `HOURLY`, `DAILY`, `MONTHLY`.|
###|interval_count |number| Number of intervals between two consecutive feed runs.<br><br><ul><li> If `interval_type` is `HOURLY`, the allowed values are 1-23.</li><li> If `interval_type` is `DAILY`, the allowed values are 1-30. </li><li>If `interval_type` is `MONTHLY`, the allowed values are 1-12.</li></ul>|
###|day_of_month |number| For monthly schedules, the day of the month to fetch feed.|
###|hour|number|Hour of the day to fetch feed. <br><br><ul><li>If the interval type is hourly, this is the hour of the first schedule. </li><li>If the interval type is daily or monthly, this is the hour of the schedule.</li></ul><br> Value range: 0-23. <br><br>Default value: 0. |
###|minute|number|Minute of the day to fetch feed.  <br><br><ul><li> If the interval type is hourly, this is the minute of the first schedule.</li><li>If the interval type is daily or monthly, this is the minute of the schedule. </li></ul> <br>Value range: 0-59. <br><br>Default value: 0.|
##|next_update_time|string|Date and time (UTC + 0 time) for the next feed run. <br><br>Example: "2021-05-23 16:33:23".|
##|number_of_products|number|Number of products in the feed.|
```

### Example

```xcodeblock
(code Success JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "20210708164329010115004015033AF205",
    "data": {
        "feed_list": [
            {
                "feed_id": 8665,
                "feed_name": "feed_test_1",
                "last_update_param": {
                    "day_of_month": 20,
                    "hour": 12,
                    "interval_count": 2,
                    "interval_type": "MONTHLY",
                    "minute": 30,
                    "timezone": "Africa/Accra",
                    "update_mode": "INCREMENTAL",
                    "uri": "ads.tiktok.com"
                },
                "next_update_time": "2021-07-20 12:30:00",
                "number_of_products": 0
            },
            {
                "feed_id": 1521,
                "feed_name": "default",
                "last_update_param": {
                    "day_of_month": null,
                    "hour": null,
                    "interval_count": null,
                    "interval_type": null,
                    "minute": null,
                    "timezone": null,
                    "update_mode": "INCREMENTAL",
                    "uri": null
                },
                "next_update_time": null,
                "number_of_products": 0
            }
        ]
    }
}
(/code)
```
