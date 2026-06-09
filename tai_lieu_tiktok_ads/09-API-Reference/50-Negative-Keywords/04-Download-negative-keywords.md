---
title: "Download negative keywords"
doc_id: 1775104916876290
path: "API Reference / Negative Keywords / Download negative keywords"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Download negative keywords

Use this endpoint to download the list of negative keywords for Search Ads.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/search_ad/negative_keyword/download/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| object_type {Required} | string | Object type.  Use this field to specify whether you want to download the list of negative keywords for a campaign or ad group.  <br><br> Enum values: <ul><li>`CAMPAIGN`: campaign.</li><li>`ADGROUP`: ad group.</li></ul> |
| object_id {Required} | string | Object ID. Use this field to pass in a campaign ID or ad group ID that you want to download the list of negative keywords for. <br><br> Specify this field according to `object_type`: <ul><li> If `object_type` is `CAMPAIGN`, assign to this field a campaign ID.</li><li> If `object_type` is `ADGROUP`, assign to this field an ad group ID.</li></ul>|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/search_ad/negative_keyword/download/?advertiser_id={{advertiser_id}}&object_type=CAMPAIGN&object_id={{object_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response
If the download is successfully completed, the xlsx file stream will be returned, and you can see three columns entitled `<keyword>`, `<keyword_name>`, and `<match_type>` in the returned file.
  
If the API call fails, the below parameters will be returned.
  
``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
```

