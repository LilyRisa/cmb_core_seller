---
title: "Get form libraries"
doc_id: 1740029915327489
path: "API Reference / Leads / Get form libraries"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get form libraries

Use this endpoint to get form libraries that you have access to. A form library is a container for leads under a Business Center.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/pages/library/get/| /v1.3/page/library/get/|
|New request parameter |/ |`page`: number <br>`page_size`: number |
|Response parameter data type|`library_id`: number<br>`advertiser_id`: number<br>`create_time`: number<br>`update_time`: number |`library_id`: string<br>`advertiser_id`: string<br>`create_time`: datetime<br>`update_time`: datetime |
```


## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/page/library/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**
``` xtable
|Field|Data Type|Description|
|---|---|---|
|page |number|Number of pages in response.|
|page_size |number|Number of records in each page.|
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \

https://business-api.tiktok.com/open_api/v1.3/page/library/get/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#|list|object[]|Libraries|
##| library_id | string| Form library ID |
##| library_name | string | Form library name |
##| advertiser_id | string | Advertiser ID |
##| create_time | datetime| Time when the library is created, in the format of YYYY-MM-DD HH:MM:SS. An example: 2017-01-01 00:00:00.|
##| update_time | datetime| Time when the library is updated, in the format of YYYY-MM-DD HH:MM:SS. An example: 2017-01-01 00:00:00. |
|request_id|string|The log id of the request, which uniquely identifies a request. |
```


### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code)
```




