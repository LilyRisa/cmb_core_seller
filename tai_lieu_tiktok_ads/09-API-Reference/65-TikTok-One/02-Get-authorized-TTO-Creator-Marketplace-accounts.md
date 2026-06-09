---
title: "Get authorized TTO Creator Marketplace accounts"
doc_id: 1815693478150146
path: "API Reference / TikTok One / Get authorized TTO Creator Marketplace accounts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get authorized TTO Creator Marketplace accounts

Use this endpoint to obtain a list of TikTok One (TTO) Creator Marketplace accounts that authorized the developer app.

><span style="color:DodgerBlue">**Note**</span><br> 
> To obtain information of the user who authorized the developer app, use the [/user/info/](https://business-api.tiktok.com/portal/docs?rid=5mfkgk5o698&id=1739665513181185) endpoint.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/oauth2/tcm/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok One Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| app_id {Required} | string | ID of your developer application. <br><br>To find the <strong>App ID</strong>, navigate to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
| secret {Required} | string | Secret of your developer application. <br><br>To find the <strong>Secret</strong>, navigate to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/oauth2/tcm/?app_id={{app_id}}&secret={{secret}}' \
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
#| tto_tcm_account_ids | string[]| The ID list of TikTok One Creator Marketplace accounts that authorized the developer app (`app_id`).|
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
        "tto_tcm_account_ids": [
            "{{tto_tcm_account_id}}"
        ]
    }
}
(/code)
```
