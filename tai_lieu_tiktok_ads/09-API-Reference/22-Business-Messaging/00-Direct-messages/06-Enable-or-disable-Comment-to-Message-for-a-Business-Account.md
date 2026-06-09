---
title: "Enable or disable Comment-to-Message for a Business Account"
doc_id: 1843697551083522
path: "API Reference / Business Messaging / Direct messages / Enable or disable Comment-to-Message for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Enable or disable Comment-to-Message for a Business Account

Use this endpoint to enable or disable the Comment-to-Message feature for a Business Account.

The Comment-to-Message feature enables businesses to proactively engage with users by sending direct messages in response to high-intent comments on their video posts. A high-intent comment is one that expresses a strong intention to purchase or seek further information. When you enable this feature, the TikTok system will identify those high-intent comments, allowing you to send customized replies using  [/business/message/send/](https://business-api.tiktok.com/portal/docs?id=1832184403754242) and assist these users more effectively.

> <span style="color:DodgerBlue">**Note**</span>
> - The Comment-to-Message feature is only available for Business Accounts registered in Vietnam, Indonesia, and Thailand. 
> - Once you enable Comment-to-Message for a Business Account, the Business Account can only reply to comments published by TikTok accounts that are registered in the APAC, LATAM, and METAP regions. 

## Before you start
Ensure the Business Account meets the following eligibility requirements:

- The account owner is over 18 years old.
- The account is a [Registered Business Account](https://ads.tiktok.com/help/article/about-business-registration) or a Business Account that has run [TikTok Messaging Ads](https://ads.tiktok.com/help/article/about-tiktok-messaging-ads).
- The messaging permissions "Potential connections" and "Other on TikTok" of the account are set to "Requests".

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/message/direct_reply/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok Business Account owner. <br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token).|
|Content-Type {Required}|string|Request message type. <br>Allowed value: `application/json`.|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|business_id {Required}|string|Application specific unique identifier for the TikTok Business Account. <br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token) to this field.|
|direct_reply_type {Required}|string|The type of direct reply. <br><br>Enum value:<ul><li> `COMMENT_TO_MESSAGE`: Comment-to-Message. Once you enable this feature, the TikTok system will identify high-intent comments, enabling you to send customized replies to assist these users more effectively.</li></ul>|
|operation_status {Required}|string|The operation to perform. <br><br>Enum values:<ul><li>`ENABLE`: To enable the Comment-to-Message feature.</li><li>`DISABLE`: To disable the Comment-to-Message feature.</li></ul> |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/message/direct_reply/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "business_id": "{{business_id}}",
    "direct_reply_type": "COMMENT_TO_MESSAGE",
    "operation_status": "ENABLE"
}'
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data. <br>For a successful request, this field will be an empty object (`{}`).|
```

### Example
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
