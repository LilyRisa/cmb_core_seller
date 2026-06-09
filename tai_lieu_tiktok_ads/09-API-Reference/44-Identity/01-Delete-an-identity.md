---
title: "Delete an identity"
doc_id: 1759155939236865
path: "API Reference / Identity / Delete an identity"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete an identity

Use this endpoint to delete a customized user identity (`identity_type` = `CUSTOMIZED_USER`). If you want to edit an identity, you need to first delete the identity using this endpoint, and then create a new one using [/v1.3/identity/create/](https://ads.tiktok.com/marketing_api/docs?id=1740654203526146).


## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/identity/delete/ 

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|The content type of the request. <br>Allowed value: `"application/json"`.|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| identity_id  {Required} | string | Identity ID.
| identity_type  {Required} | string | Identity type. Currently, this field only supports `CUSTOMIZED_USER`. 
```

### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/identity/delete/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "identity_id":"{{identity_id}}",
    "identity_type":"CUSTOMIZED_USER"
}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
|request_id|string|The log ID of the request, which uniquely identifies a request. |
```

### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
