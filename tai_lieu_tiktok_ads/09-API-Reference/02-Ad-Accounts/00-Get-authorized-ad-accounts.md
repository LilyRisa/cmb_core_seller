---
title: "Get authorized ad accounts"
doc_id: 1738455508553729
path: "API Reference / Ad Accounts / Get authorized ad accounts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get authorized ad accounts

Use this endpoint to obtain a list of advertiser accounts that authorized an app.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path |/v1.2/oauth2/advertiser/get/|/v1.3/oauth2/advertiser/get/  |
|Request parameter data type| `app_id`: number |  `app_id`: string |
|Response parameter data type| `advertiser_id`: number |  `advertiser_id`: string |
```

## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/oauth2/advertiser/get/

**Method**  GET

**Header**

```xtable
|field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|field|Data Type|Description|
|---|---|---|
|app_id{Required}|string|The App id applied by the developer, which can be found on the <br>[Application Management](https://ads.tiktok.com/marketing_api/apps/) page. |
|secret{Required}|string|The private key of the developer's application, which can be found on the <br>[Application Management](https://ads.tiktok.com/marketing_api/apps/) page.|
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:<access_token>" \
--data-urlencode "secret=<secret>" \
--data-urlencode "app_id=<app_id>" \
https://business-api.tiktok.com/open_api/v1.3/oauth2/advertiser/get/
(/code)
```

## Response 

```xtable
|Field|Data Type|Description |
|---|---|---|
|code |number|Return code, see [Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)|
|message |string|Return messages, see [Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|data |object|Return data  |
#|list|object|Advertiser information list|
##|advertiser_id|string|Authorising Advertiser ID|
##|advertiser_name|string|Authorising Advertiser Name|
|request_id |string|Request log ID. Uniquely identifying a request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "list": [
            {
                "advertiser_id": "ADVERTISER_ID",
                "advertiser_name": "ADVERTISER_NAME"
            }
        ]
    },
    "request_id": "202003100820230101890492231E063EE3"
}
(/code);
```
