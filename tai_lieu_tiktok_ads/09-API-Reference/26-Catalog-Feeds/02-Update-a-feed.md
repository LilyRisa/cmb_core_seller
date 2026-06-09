---
title: "Update a feed"
doc_id: 1740665197662210
path: "API Reference / Catalog Feeds / Update a feed"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a feed

Use this endpoint to update a feed.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/catalog/feed/update/| /v1.3/catalog/feed/update/|
|Request parameter data type |`bc_id`: number <br>`catalog_id`: number|`bc_id`: string <br>`catalog_id`: string|
```
## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/catalog/feed/update/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {required}|string|Request message type.<br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {required}|string| Business Center ID. |
|catalog_id {required}|string| Catalog ID.|
|feed_id {required}|string| Feed ID.|
|update_mode {required}|string| The update mode. <br><br>Enum values: <ul><li>`OVERWRITE`: In this mode, a full replacement will be performed to the catalog. Products not present in the feed will be removed. New products will be created. Existing products will be updated.</li><li>`INCREMENTAL`: In this mode, new products will be created, and existing products will be updated.</li><li>`SUPPLEMENT`: In this mode,  new products will be created, and only the specified parameters for the existing products will be updated.</li></ul> |
|schedule_param |object| Schedule data.|
#|source  |object|Feed source.|
##|uri {+Conditional} |string| Required if you pass in `interval_type` or `timezone`.<br><br>Data Feed URL, the URL where your file is hosted. <br><br>It should start with http, https, ftp or sftp. <br><br>Files can be up to 8 GB and in the CSV, TSV, or XML (RSS/ATOM) format.|
##|username|string| If the feed is password protected, you need to specify a user name for authentication.|
##|password|string| If the feed is password protected, you need to specify a password for authentication.|
#|interval_type {+Conditional}|string| Required if you pass in `uri` or `timezone`.<br><br>Schedule interval. <br><br>Enum values: `HOURLY`, `DAILY`, `MONTHLY`.|
#|interval_count |number| Number of intervals between two consecutive feed runs.<br><br><ul><li> If `interval_type` is `HOURLY`, the allowed values are 1-23.</li><li>If `interval_type` is `DAILY`, the allowed values are 1-30. </li><li>If `interval_type` is `MONTHLY`, the allowed values are 1-12.</li></ul>|
#|timezone {+Conditional}|string|Required if you pass in `uri` or `interval_type`.<br><br> Time zone of the schedule. <br><br>Example: `Africa/Accra`. <br><br>For the list of time zones, see [Time Zone](https://ads.tiktok.com/marketing_api/docs?id=1737586324313089).|
#|day_of_month |number| For monthly schedules, the day of the month to fetch feed.|
#|hour|number|Hour of the day to fetch feed. <br><br><ul><li> If the interval type is hourly, this is the hour of the first schedule.</li><li>If the interval type is daily or monthly, this is the hour of the schedule.</li></ul><br>Value range: 0-23. <br><br>Default value: 0.  |
#|minute|number|Minute of the day to fetch feed.  <br><br><ul><li>If the interval type is hourly, this is the minute of the first schedule. </li><li>If the interval type is daily or monthly, this is the minute of the schedule. </li></ul><br>Value range: 0-59.<br><br> Default value: 0.|
```

### Example

```xcodeblock
(code cURL cURL)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/feed/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
	"feed_id": "{{feed_id_id}}",
    "feed_name": "{{feed_name}}",
    "update_mode": "INCREMENTAL",
    "schedule_param": {
        "source": {
            "uri": "{{uri}}"
        },
        "interval_type": "HOURLY",
        "interval_count": 1,
        "timezone": "Asia/Singapore",
        "minute":22
    }
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| The log id of a request, which uniquely identifies the request.|
|data|object| Returned data.|
#|feed_id|string| Feed ID.|
#|feed_name|string| Feed name.|
#|status |string | The schedule status of the feed. Enum values: <ul><li>`ON`: The schedule for the feed is with the  "ON" status.</li><li>`OFF`: The schedule for the feed is with the  "OFF" status, or there is no schedule for the feed.</li></ul>|
#|last_update_param|object| Information about the last update.|
##|uri|string|Data Feed URL, the URL where your file is hosted.|
##|update_mode|string|The update mode. <br><br>Enum values: <ul><li>`OVERWRITE`: In this mode, a full replacement will be performed to the catalog. Products not present in the feed will be removed. New products will be created. Existing products will be updated.</li><li>`INCREMENTAL`: In this mode, new products will be created, and existing products will be updated.</li><li>`SUPPLEMENT`: In this mode,  new products will be created, and only the specified parameters for the existing products will be updated.</li></ul>|
##|timezone|string| Time zone of the schedule. <br><br>Example: `Africa/Accra`.<br><br>For the list of time zones, see [Appendix-Time Zone](https://ads.tiktok.com/marketing_api/docs?id=1737586324313089).|
##|interval_type|string| Schedule interval.<br><br> Enum values: `HOURLY`, `DAILY`, `MONTHLY`.|
##|interval_count |number| Number of intervals between two consecutive feed runs.<br><br><ul><li> If `interval_type` is `HOURLY`, the allowed values are 1-23.</li><li> If `interval_type` is `DAILY`, the allowed values are 1-30. </li><li>If `interval_type` is `MONTHLY`, the allowed values are 1-12.</li></ul>|
##|day_of_month |number| For monthly schedules, the day of the month to fetch feed.|
##|hour|number|Hour of the day to fetch feed. <br><br><ul><li>If the interval type is hourly, this is the hour of the first schedule. </li><li>If the interval type is daily or monthly, this is the hour of the schedule.</li></ul>|
##|minute|number|Minute of the day to fetch feed.  <br><br><ul><li> If the interval type is hourly, this is the minute of the first schedule.</li><li>If the interval type is daily or monthly, this is the minute of the schedule. </li></ul> |
#|next_update_time|string|Date and time for the next feed run. <br><br>Example: "2021-05-23 16:33:23".|
#|number_of_products|number|Number of products in the feed.|
```

### Example
```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "next_update_time": "{{next_update_time}}",
        "number_of_products": 0,
        "last_update_param": {
            "interval_count": 1,
            "timezone": "Asia/Singapore",
            "day_of_month": 1,
            "hour": 0,
            "update_mode": "INCREMENTAL",
            "interval_type": "HOURLY",
            "minute": 22,
            "uri": "{{uri}}"
        },
        "feed_id": "{{feed_id}}",
        "status": "ON",
        "feed_name": "{{feed_name}}"
    }
}
(/code)
```

