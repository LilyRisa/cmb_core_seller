# (33) New message listener

> Source: https://partner.tiktokshop.com/docv2/page/33-new-message-listener
> Section: Webhooks
> Scraped: 2026-05-21T00:27:48.369Z

---

## Trigger scenario

When a creator sends a message to the seller.

## Data business parameters

| Parameter name | Data Type | Sample | Description |
| --- | --- | --- | --- |
| 
type

 | 

int64

 | 

`33`

 | 

Webhook type, fixed to `33` for this webhook.

 |
| 

shop\_id

 | 

string

 | 

`"7494049642642441621"`

 | 

TikTok shop ID.

 |
| 

tts\_notification\_id

 | 

string

 | 

`"7327112393057371910"`

 | 

ID of the webhook.

 |
| 

timestamp

 | 

int

 | 

`1644412885`

 | 

The UNIX timestamp when this webhook was triggered.

 |
| 

data

 | 

object

 | 

 | 

 |
| 

└index

 | 

string

 | 

`"1735032460678899"`

 | 

Index of the message in the conversation. A large index number corresponds to newer message.

 |
| 

└message\_id

 | 

string

 | 

`"7451907556216407553"`

 | 

Message ID.

 |
| 

└conversation\_id

 | 

string

 | 

`"7451873679308620048"`

 | 

ID of the conversation to which the message belongs.

 |
| 

└msg\_type

 | 

string

 | 

`"TEXT"`

 | 

Message type, with possible values:  
  
\* TEXT  
\* PRODUCT\_CARD  
\* TARGET\_COLLABORATION\_CARD  
\* FREE\_SAMPLE\_CARD

 |
| 

└content

 | 

string

 | 

`"Hello"`

 | 

Message content, in JSON serialized string.  
  
\* TEXT: {"content": "simple text"}  
\* PRODUCT\_CARD: {"product\_id": "12345"}  
\* TARGET\_INVITATION\_CARD: {"invitation\_group\_id": "1234"}  
\* FREE\_SAMPLE\_CARD: {"apply\_id": "1234"}

 |
| 

└create\_time

 | 

string

 | 

`1691411573`

 | 

Message creation time, represented as a Unix timestamp (seconds).

 |
| 

└sender

 | 

object

 | 

 | 

 |
| 

└└sender\_im\_user\_id

 | 

string

 | 

`"2368694990397660924"`

 | 

ID in IM system for the sender.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 33,
  "shop_id": "7494049642642441621",
  "tts_notification_id": "7327112393057371910",
  "timestamp": 1644412885,
  "data": {
    "index": "1735032460678899",
    "message_id": "7451907556216407553",
    "conversation_id": "7451873679308620048",
    "msg_type": "TEXT",
    "content": "Hello",
    "create_time": "1691411573",
    "sender": {
      "sender_im_user_id": "2368694990397660924"
    }
  }
}
```
