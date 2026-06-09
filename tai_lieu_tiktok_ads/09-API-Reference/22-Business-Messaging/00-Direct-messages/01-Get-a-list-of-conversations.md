---
title: "Get a list of conversations"
doc_id: 1832184415059970
path: "API Reference / Business Messaging / Direct messages / Get a list of conversations"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get a list of conversations

Use this endpoint to get a list of conversations. 

><span style="color:DodgerBlue">**Note**</span><br> 
> - Only the conversations from the past 90 days are accessible, and a maximum of 100 conversations will be returned.

## Before you start
Because of the sensitive nature of direct message data, TikTok has implemented a higher level of due diligence for developers that will process direct messages on behalf of Business Account users. Ensure that you [have completed the necessary data security and privacy reviews and obtained access to Business Messaging API](https://business-api.tiktok.com/portal/docs?id=1832184145137922) before calling this endpoint.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/message/conversation/list/

**Method** GET

**Header**

```xtable
|Field{35%}|Type{15%}|Description{50%}|
|---|---|---|
|Access-Token {Required}|string|A short-term access token authorized by the TikTok Business Account owner. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1832184175482945).|
```


**Parameters**

``` xtable
|Field{35%}|Type{15%}|Description{50%}|
|--- |--- |--- |
| business_id {Required}| string | Application specific unique identifier for the TikTok Business Account. <br><br> Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1832184175482945#item-link-Obtain%20a%20short-term%20access%20token) to this field. |
| conversation_type  {Required}| string | Conversation type. <br> <br> Currently, the only allowed values are: <ul> <li>`STRANGER`: Conversations with users who have sent a message to the Business Account of the business, but the business has not yet accepted the message request and responded to the message.</li> <li>`SINGLE`: One-on-one conversations with users who have sent a message to the Business Account of the business, and the business has previously responded to the user.</li> </ul> |
| limit | integer | The maximum number of conversations to return for this request. <br> <br> Value range: 1-100. <br> Default value: 100. |
| cursor | integer | Cursor for pagination. <br> <br> If the response parameter `has_more` is `true`, pass the returned `cursor` value in this parameter in the subsequent request to receive the next page of conversations. <br> <br> Default value: 0. |
```


### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/message/conversation/list/?business_id={{business_id}}&conversation_type=SINGLE' \
--header 'Access-Token:{{Access-Token}}' 
(/code)
```


## Response

``` xtable
|Field{35%}|Type{15%}|Description{50%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| conversations | object[] | Conversation List. |
##| conversation_id | string | Conversation ID. <br><br> Example: `a1AbcBdeCfghDijkEF2b3+lmGn==`. |
##| update_time | integer | The time of the last message in the conversation, in the format of an Epoch/Unix timestamp in milliseconds. <br><br> Example: `1719447205701`. |
##| referral | object | Referral information of the conversation. |
###| ad | object[] | Related referral ad information of the conversation, including all historical referral ads of the conversation. |
####| advertiser_id | string | The ID of the advertiser account that is associated with the ad. |
####| ad_id | string | Ad ID of the related ad. <br><br> Example: `1234567891234567`.<br><br>The types of data you can get from this metric include:<ul><li>The ID of a [Manual Ad](https://business-api.tiktok.com/portal/docs?id=1735735524758530).</li><li>The ID of an ad within a [Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1765314260115458).</li><li>The ID of a creative within an [Upgraded Smart+ Ad](https://business-api.tiktok.com/portal/docs?id=1843310530260097).</li></ul> |
####| timestamp | integer | The time of the referral event, in the format of an Epoch/Unix timestamp in seconds. <br> <br>Example: `1714430514`. |
####| ad_name | string | The name of the related ad. |
####| embed_url | string | An embeddable link for the TikTok post used in the related ad. <br> This URL can be used to embed the TikTok post in external websites or applications. <br> If the privacy setting of the post is "Friends" or "Only you", the post will not be viewable through this link. |
####| message_material_id|string|Material ID.<br><br>The encoded ID of a creative within [Upgraded Smart+ Ads](https://business-api.tiktok.com/portal/docs?id=1843310530260097).|
###| short_link | object[] | Information about the tiktok.me link. <br> A tiktok.me link is a shareable TikTok shortlink that allows users to initiate a one-on-one conversation with a business in the TikTok App. |
####| ref | string | The referral parameter (`ref`) that is configured in the tiktok.me link, offering context about the conversation. You can incorporate the referral parameter in your tiktok.me link to monitor various links posted on different channels. <br> This parameter is a URL-encoded string with a maximum length of 2,083 characters. <br><br><p><span style="color:darkred"><b>Note</b></span>: The `ref` parameter only supports alphanumeric characters and the special characters `-`, `_`, and `=`. |
####| prefilled_message | string | The prefilled message that is configured in the tiktok.me link. |
####| prefilled_message_audit_status | string | The audit status of the prefilled message. <br> <br>Enum values: <ul> <li>`REJECT`: The message was rejected during the review. If `prefilled_message_audit_status` is `REJECT`, the `prefilled_message` will not be prefilled into the message input box.</li> <li>`PASS`: The message passed the review.</li> </ul> |
#| has_more | boolean | Whether an additional page of conversations is available. <br><br> Example: `false`. |
#| cursor | integer | Cursor for the next page of conversations, to be passed in the `cursor` parameter of the subsequent API request. <br><br> Example: 13. |
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
    "conversations": [
           {
                "referral": {},
                "update_time": 1719447205701,
                "conversation_id": "{{conversation_id}}"
            },
            {
                "referral": {
                    "ad": [
                        {
                            "advertiser_id": "{{advertiser_id}}",
                            "ad_id": "{{ad_id}}",
                            "timestamp": 1719389021,
                            "ad_name": "{{ad_name}}",
                            "embed_url": "https://www.tiktok.com/player/v1/1234567891234567891?music_info=1&description=1&autoplay=1&loop=1&utm_campaign=tt4d_open_api&utm_source=dm"
                        }
                    ]
                },
                "update_time": 1719389022512,
                "conversation_id": "{{conversation_id}}"
            },
            {
                "referral": {
                    "short_link": [
                        {
                            "ref": "{{ref}}", 
                            "prefilled_message": "{{message}}",
                            "prefilled_message_audit_status": "PASS"
                        }
                    ]
                },
                "update_time": 1719389022812,
                "conversation_id": "{{conversation_id}}"
            }
    ],
    "has_more": false,
    "cursor": 3
  }
}
(/code)
```




