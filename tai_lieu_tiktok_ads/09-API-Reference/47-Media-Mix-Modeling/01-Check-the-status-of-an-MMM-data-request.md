---
title: "Check the status of an MMM data request"
doc_id: 1825299576698882
path: "API Reference / Media Mix Modeling / Check the status of an MMM data request"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Check the status of an MMM data request

Use this endpoint to check the status of a Media Mix Modeling (MMM) data request.

Once the `status` of the data request is `completed - ready for download`, you can use [/mmm/api/download/](https://business-api.tiktok.com/portal/docs/obtain-the-download-url-for-mmm-data/v1.3) to obtain a download URL for the data.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/mmm/api/check/

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
| mmm_request_id {Required} | string | Unique identifier for the MMM data request.<br><br> <ul><li>To create an MMM data request and obtain the data request ID, use [/mmm/api/create/](https://business-api.tiktok.com/portal/docs/create-an-mmm-data-request/v1.3).</li><li>To retrieve historical MMM data requests, including data request IDs, use [/mmm/api/history/](https://business-api.tiktok.com/portal/docs/get-the-mmm-data-request-history/v1.3). </li></ul>|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/mmm/api/check/?mmm_request_id={{mmm_request_id}}' \
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
#| status | string | Status of the data request. <br><br> Enum values: <ul><li>`request received`: The request was received but data processing has not started.</li><li>`in progress`: Data processing is in progress.</li><li>`completed - ready for download`: Data processing has completed and the data is ready for download.  <ul><li>To obtain a download URL for the data, use [/mmm/api/download/](https://business-api.tiktok.com/portal/docs/obtain-the-download-url-for-mmm-data/v1.3).</li></ul></li><li>`failed`: The request failed.</li></ul> |
#| message | string | Returned only when `status` is `failed`. <br><br> The reason why the data request failed. |
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
        "status": "completed - ready for download"
    }
}
(/code)
```

