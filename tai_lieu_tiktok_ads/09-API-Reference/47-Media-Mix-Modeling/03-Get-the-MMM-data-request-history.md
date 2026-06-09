---
title: "Get the MMM data request history"
doc_id: 1825299591414850
path: "API Reference / Media Mix Modeling / Get the MMM data request history"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the MMM data request history

Use this endpoint to retrieve the details of historical Media Mix Modeling (MMM) data requests.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/mmm/api/history/

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
| from_date {Required} | string | Start date, in the format of `YYYY-MM-DD` (UTC+0 time).<br><br>The start date cannot be earlier than four years ago. |
| end_date {Required} | string | End date, in the format of `YYYY-MM-DD` (UTC+0 time). |
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/mmm/api/history/?from_date=2024-11-01&end_date=2024-12-03' \
--header 'Access-Token: {{Access-Token}}'
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
#| historical_requests | string[] | A JSON-formatted string array that displays historical requests, providing details for each request.<br><br> For the fields `media_type` and `Status`, you might need to convert the returned integer values to string values in other MMM endpoints based on the following mapping: <br><br><ul><li>Supported values for `media_type`: <ul><li>`0`: corresponds to `All`.</li><li> `1`: corresponds to `Earned`.</li><li>`2`: corresponds to `Paid`. </li></ul></li><li>Supported values for `Status`: <ul><li>`1`: corresponds to `request received`.</li><li> `3`: corresponds to `in progress`.</li><li>`5`: corresponds to `completed - ready for download`.</li><li>`2`: corresponds to `failed`.</li><li>`4`: corresponds to `failed`.</li></ul></li></ul>|
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
        "historical_requests": [
            "{\"RequestID\":\"{{RequestID}}\",\"CreateTime\":\"2024-11-07T22:03:39Z\",\"Message\":\"{\\\"targets\\\":[{\\\"advertiser_id\\\":\\\"{{advertiser_id}}\\\"}],\\\"from_date\\\":\\\"20240729\\\",\\\"to_date\\\":\\\"20240829\\\",\\\"media_type\\\":2}\",\"Status\":5}",
            "{\"RequestID\":\"{{RequestID}}\",\"CreateTime\":\"2024-11-19T19:54:58Z\",\"Message\":\"{\\\"targets\\\":[{\\\"advertiser_id\\\":\\\"{{advertiser_id}}\\\"}],\\\"from_date\\\":\\\"20241104\\\",\\\"to_date\\\":\\\"20241108\\\",\\\"country_code\\\":[\\\"JP\\\"],\\\"media_type\\\":2}\",\"Status\":1}"
        ]
    }
}
(/code)
```

