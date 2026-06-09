---
title: "Migrate leads to a BC"
doc_id: 1740029931324417
path: "API Reference / Leads / Migrate leads to a BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Migrate leads to a BC

Use this endpoint to migrate leads from an ad account to a Business Center. During the migration, a form library will be created to hold the leads from the ad account.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/pages/library/transfer/| /v1.3/page/library/transfer/|
|Request parameter data type|`advertiser_id`: number<br>`bc_id`: number |`advertiser_id`: string<br>`bc_id`: string |
|Response parameter data type|`library_id`: number |`library_id`: string |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/page/library/transfer/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|The content type of the request.<br>Allowed value: `"application/json"`  |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required} | string| ID of the advertiser that owns the leads. |
|bc_id {Required} | string | ID of the Business Center that you want to migrate leads to. |
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "bc_id": "BC_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/page/library/transfer/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#|library_id|string|ID of the form library that was created to hold the leads for the ad account.|
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



