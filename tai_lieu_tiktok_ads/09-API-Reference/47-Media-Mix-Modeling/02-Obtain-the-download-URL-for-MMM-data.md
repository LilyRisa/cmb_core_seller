---
title: "Obtain the download URL for MMM data"
doc_id: 1825299584678017
path: "API Reference / Media Mix Modeling / Obtain the download URL for MMM data"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Obtain the download URL for MMM data

Use this endpoint to obtain the download URL for Media Mix Modeling (MMM) data.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/mmm/api/download/

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
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/mmm/api/download/?mmm_request_id={{mmm_request_id}}' \
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
#| download_url| string | A URL to download the compressed CSV file for MMM data. <br><br> Validity period: seven days. <br>Once the URL expires, you need to call [/mmm/api/download/](https://business-api.tiktok.com/portal/docs/obtain-the-download-url-for-mmm-data/v1.3) to obtain a new URL. <br><br> To learn about the available MMM data fields, see [MMM templated data schema](https://bytedance.sg.larkoffice.com/sheets/YDwDsKe5whGQWdtaZVrlD9BHgIc). |
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
        "download_url": "{{download_url}}"
    }
}
(/code)
```

