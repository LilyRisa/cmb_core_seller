---
title: "Get the log of a feed"
doc_id: 1740665225631810
path: "API Reference / Catalog Feeds / Get the log of a feed"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the log of a feed

Use this endpoint to return the last 10 operations to the feed.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/catalog/feed/log/| /v1.3/catalog/feed/log/|
|Request parameter data type |`bc_id`: number <br>`catalog_id`: number  <br>`feed_id`: number|`bc_id`: string  <br>`catalog_id`: string <br> `feed_id`: string|
|Response parameter name|`log`<br>`status`|`feed_logs`<br>`process_status`|
```
## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/catalog/feed/log/

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
|feed_id {required}| string| ID of the feed that you want to get log for. |
```


### Example

```xcodeblock
(code HTTP HTTP)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/catalog/feed/log/?bc_id=6883084703654477826&catalog_id=6824450688219612934&feed_id=1521' \
--header 'access-token: xxx' \
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
#|feed_logs|object[]|Log entry list.|
##|update_status|object|Update data.|
###|add_count|number| Number of products that were added.|
###|error_count|number|Number of products that failed to add, update, or remove.|
###|remove_count|number|Number of products that were removed.|
###|process_status|string|Status of the update operation. Enum values: `PROCESSING`, `SUCCESS`, `FAILED`, `WAITING`.|
###|update_count|number|Number of products that were updated.|
###|warn_count|number|Number of warnings received.|
##|update_time|object| Update time data.|
###|end_time|string| Date and time when the update operation ended. For example, "2021-05-23 16:33:30".|
###|start_time|string|Date and time when the update operation started. For example, "2021-05-23 16:33:30".|
```

### Example

```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "202107081636450101152311250A1E0E1F",
    "data": {
        "feed_logs": [
            {
                "last_update_status": {
                    "add_count": 0,
                    "error_count": 0,
                    "remove_count": 1,
                    "process_status": "SUCCESS",
                    "update_count": 0,
                    "warn_count": 0
                },
                "last_update_time": {
                    "end_time": "2021-06-03 13:02:52",
                    "start_time": "2021-06-03 13:02:52"
                }
            },
            {
                "last_update_status": {
                    "add_count": 0,
                    "error_count": 0,
                    "remove_count": 0,
                    "process_status": "SUCCESS",
                    "update_count": 1,
                    "warn_count": 1
                },
                "last_update_time": {
                    "end_time": "2021-06-03 13:02:41",
                    "start_time": "2021-06-03 13:02:41"
                }
            },
            {
                "last_update_status": {
                    "add_count": 0,
                    "error_count": 0,
                    "remove_count": 1,
                    "process_status": "SUCCESS",
                    "update_count": 0,
                    "warn_count": 0
                },
                "last_update_time": {
                    "end_time": "2021-06-03 12:40:46",
                    "start_time": "2021-06-03 12:40:46"
                }
            },
            {
                "last_update_status": {
                    "add_count": 0,
                    "error_count": 0,
                    "remove_count": 0,
                    "process_status": "SUCCESS",
                    "update_count": 1,
                    "warn_count": 1
                },
                "last_update_time": {
                    "end_time": "2021-06-03 12:40:36",
                    "start_time": "2021-06-03 12:40:36"
                }
            },
            {
                "last_update_status": {
                    "add_count": 0,
                    "error_count": 0,
                    "remove_count": 1,
                    "process_status": "SUCCESS",
                    "update_count": 0,
                    "warn_count": 0
                },
                "last_update_time": {
                    "end_time": "2021-06-03 12:32:37",
                    "start_time": "2021-06-03 12:32:37"
                }
            },
            {
                "last_update_status": {
                    "add_count": 0,
                    "error_count": 0,
                    "remove_count": 0,
                    "process_status": "SUCCESS",
                    "update_count": 1,
                    "warn_count": 1
                },
                "last_update_time": {
                    "end_time": "2021-06-03 12:32:27",
                    "start_time": "2021-06-03 12:32:27"
                }
            },
            {
                "last_update_status": {
                    "add_count": 0,
                    "error_count": 0,
                    "remove_count": 1,
                    "process_status": "SUCCESS",
                    "update_count": 0,
                    "warn_count": 0
                },
                "last_update_time": {
                    "end_time": "2021-06-03 12:03:18",
                    "start_time": "2021-06-03 12:03:18"
                }
            },
            {
                "last_update_status": {
                    "add_count": 0,
                    "error_count": 0,
                    "remove_count": 0,
                    "process_status": "SUCCESS",
                    "update_count": 1,
                    "warn_count": 1
                },
                "last_update_time": {
                    "end_time": "2021-06-03 12:03:08",
                    "start_time": "2021-06-03 12:03:08"
                }
            },
            {
                "last_update_status": {
                    "add_count": 0,
                    "error_count": 0,
                    "remove_count": 1,
                    "process_status": "SUCCESS",
                    "update_count": 0,
                    "warn_count": 0
                },
                "last_update_time": {
                    "end_time": "2021-06-03 11:32:48",
                    "start_time": "2021-06-03 11:32:48"
                }
            },
            {
                "last_update_status": {
                    "add_count": 0,
                    "error_count": 0,
                    "remove_count": 0,
                    "process_status": "SUCCESS",
                    "update_count": 1,
                    "warn_count": 1
                },
                "last_update_time": {
                    "end_time": "2021-06-03 11:32:38",
                    "start_time": "2021-06-03 11:32:37"
                }
            }
        ]
    }
}
(/code)
```
