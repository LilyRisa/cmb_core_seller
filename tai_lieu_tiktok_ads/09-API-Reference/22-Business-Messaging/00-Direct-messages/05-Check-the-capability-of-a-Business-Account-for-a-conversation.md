---
title: "Check the capability of a Business Account for a conversation"
doc_id: 1836061597865986
path: "API Reference / Business Messaging / Direct messages / Check the capability of a Business Account for a conversation"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Check the capability of a Business Account for a conversation

Use this endpoint to check whether a TikTok Business Account can send images and/or receive images in a specific conversation. 


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/message/capabilities/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|A short-term access token authorized by the TikTok Business Account owner. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1832184175482945).|
```


**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required}| string | Application specific unique identifier for the TikTok Business Account. <br><br> Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) to this field. |
|capability_types {Required} |string[]|A list of capability types that you want to query for the Business Account.<br><br>Enum value:<ul><li>`IMAGE_SEND`: The Business Account can send images and/or receive images in the specified conversation.</li></ul>|
|conversation_id {+Conditional}|string|Required when the value of `capability_types` includes `IMAGE_SEND`. <br><br>Conversation ID.<br><br>To get a list of conversation IDs, use [/business/message/conversation/list/](https://business-api.tiktok.com/portal/docs?id=1832184415059970).<br><br><p><span style="color:darkred"><b>Note</b></span>: If you receive a `"Param conversation_id is invalid."` error, it may be due to encoding issues. Try replacing any "+" characters with "%2B" to resolve potential encoding issues.</p>|
|conversation_type{+Conditional}|string|Required when the value of `capability_types` includes `IMAGE_SEND`. <br><br>Conversation type.<br><br>Currently, the only allowed values are:<ul><li>`STRANGER`: Conversations with users who have sent a message to the Business Account of the business, but the business has not yet accepted the message request and responded to the message.</li><li>`SINGLE`: One-on-one conversations with users who have sent a message to the Business Account of the business, and the business has previously responded to the user.</li></ul>|
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET  'https://business-api.tiktok.com/open_api/v1.3/business/message/capabilities/get/?business_id={{business_id}}&conversation_id={{conversation_id}}&conversation_type=SINGLE&capability_types=["IMAGE_SEND"]' \
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
|data |object|Returned data.|
#|capability_infos|object[]|The results regarding the queried capabilities for the Business Account.|
##|capability_type|string|The capability type.<br><br>Enum value:<ul><li>`IMAGE_SEND`: The Business Account can send images and/or receive images in the specified conversation.</li></ul>|
##|capability_result|boolean|Whether the Business Account has this capability. <br><br>Supported values: `true`, `false`.|
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
        "capability_infos": [
            {
                "capability_result": true,
                "capability_type": "IMAGE_SEND"
            }
        ]
    }
}
(/code)
```
