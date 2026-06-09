---
title: "Get TTO video linking requests as a creator"
doc_id: 1837247726007298
path: "API Reference / TikTok One / Get TTO video linking requests as a creator"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get TTO video linking requests as a creator

Use this endpoint to retrieve the requests for linking your public videos to TikTok One (TTO) Creator Marketplace campaigns.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/creator/link/request/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators. <br>Use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Obtain%20a%20Creator%20access%20token) or [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Renew%20a%20Creator%20access%20token) endpoint to get or renew an access token.|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|creator_id {Required}|string|Application specific unique ID of the TikTok account.<br><br> Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Obtain%20a%20Creator%20access%20token) or [/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Renew%20a%20Creator%20access%20token) to this field.|
| tto_invite_link {Required} | string | The invite link associated with a TTO Creator Marketplace campaign that the creator (TikTok account) has been invited to.<br><br>To create a TTO Creator Marketplace campaign as a brand and obtain the associated invite link, use  [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458). |
```


### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/creator/link/request/get/?creator_id={{creator_id}}&tto_invite_link={{tto_invite_link}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data. |
#| campaign_id | string | The ID of the TTO Creator Marketplace campaign. |
#| spark_authorization_days | number | The default Spark Ads authorization days for videos uploaded to the campaign.<br><br>Enum values: 0, 7, 30, 60, 180, 365. |
#| link_requests | object[] | A list of linking requests that the creator has received. |
##| video_id | string | The ID of the creator video that has been requested to be linked to the campaign. |
##| link_request_id | string | The ID of the linking request.<br><br>You can pass the ID to [/tto/creator/link/request/confirm/](https://business-api.tiktok.com/portal/docs?id=1837247744649345) to approve or reject the linking request. |
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
        "campaign_id": "{{campaign_id}}",
        "link_requests": [
            {
                "link_request_id": "{{link_request_id}}",
                "video_id": "{{video_id}}"
            }
        ],
        "spark_authorization_days": 7
    }
}
(/code)
```
