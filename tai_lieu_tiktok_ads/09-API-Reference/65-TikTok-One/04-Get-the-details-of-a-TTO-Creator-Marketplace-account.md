---
title: "Get the details of a TTO Creator Marketplace account"
doc_id: 1831975745134593
path: "API Reference / TikTok One / Get the details of a TTO Creator Marketplace account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the details of a TTO Creator Marketplace account

Use this endpoint to retrieve the details of a TikTok One (TTO) Creator Marketplace account.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/oauth2/info/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TTO Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|tto_tcm_account_id {Required}|string|The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146).|
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/oauth2/info/?tto_tcm_account_id={{tto_tcm_account_id}}' \
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
#| tto_tcm_account_id | string | The ID of the TTO Creator Marketplace account.|
#| account_name | string | The name of the TTO Creator Marketplace account. |
#| timezone | string | The time zone code of the TTO Creator Marketplace account.<br><br>Example: `America/Los_Angeles`.<br><br>To find out the time zone that a time zone code corresponds to, see [Time zone](https://business-api.tiktok.com/portal/docs?id=1737586324313089). |
#| country | string | The country or region code of the TTO Creator Marketplace account.<br><br>Example: `US`.<br><br>To find out the country or region that a country or region code corresponds to, see [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010). |
#| business_verification_status | string | The business verification status of the TTO Creator Marketplace account.<br><br>Enum values:<ul><li>`NOT_SUBMITTED`: The account has not been submitted for business verification review.</li><li>`UNDER_REVIEW`: The account is under business verification review.</li><li>`NOT_PASSED`: The account has failed the business verification review.</li><li>`PASSED`: The account has passed the business verification review.</li><li>`EXPIRED`: The business verification for the account has expired.</li></ul><br>Learn more about [Business Verification on TikTok One](https://ads.tiktok.com/help/article/how-to-verify-your-business-for-tiktok-one). |
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
        "account_name": "{{account_name}}",
        "business_verification_status": "PASSED",
        "country": "US",
        "timezone": "America/Los_Angeles",
        "tto_tcm_account_id": "{{tto_tcm_account_id}}"
    }
}
(/code)
```
