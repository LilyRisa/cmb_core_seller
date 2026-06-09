---
title: "Delete a partner from a BC"
doc_id: 1739662779301890
path: "API Reference / BC Partners / Delete a partner from a BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete a partner from a BC

Use this endpoint to delete a partner in the Business Center. You need to be an Admin of the Business Center.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/partner/delete/|/v1.3/bc/partner/delete/|
|Request parameter data type|`bc_id`: number<br>`partner_id`: number|`bc_id`: string <br>`partner_id`: string|
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/partner/delete/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|The content type of the request. <br>Allowed value: `"application/json"`. |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required} | string | Business Center ID |
|partner_id {Required} | string | Partner Business Center ID |
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "bc_id": "BC_ID",
    "partner_id": "PARTNER_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/bc/partner/delete/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
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

