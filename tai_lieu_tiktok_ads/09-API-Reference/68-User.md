---
title: "User"
doc_id: 1739665513181185
path: "API Reference / User"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# User

# Get user information

Use this endpoint to obtain information about users who are authorized to access the developer App.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/user/info/|/v1.3/user/info/|
|Response parameter name and data type |`id`: number| `core_user_id` : string|
|New response parameter| /|`avatar_url`|
```

## Request
**Endpoint**  
https://business-api.tiktok.com/open_api/v1.3/user/info/

**Method** GET

**Header**
```xtable
|field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

### Example

``` xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
https://business-api.tiktok.com/open_api/v1.3/user/info/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code|number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)|
|message |string|Return message. For details, see [Appendix-Return message](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)|
|data|object|Returned data. |
#|display_name |string| User's username.|
#|email |string| User's E-mail. <br><p><span style="color:darkred"><b>Note</b></span>: E-mails are data-masked.|
#|core_user_id|string|The TikTok for Business user ID.|
#|create_time|timestamp| User's creation time.|
#| avatar_url | string | The URL of the user's avatar.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
  "message": "OK",
  "code": 0,
  "data": {
    "display_name": "Jane Doe",
    "id": 75960823000,
    "email": "j***e@example.org",
    "create_time":1561518952
  },
  "request_id": "2020051106295001023125009013359"
}
(/code)
```


