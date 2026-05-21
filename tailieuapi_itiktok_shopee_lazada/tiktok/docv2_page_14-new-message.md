# (14) New message

> Source: https://partner.tiktokshop.com/docv2/page/14-new-message
> Section: Webhooks
> Scraped: 2026-05-21T00:28:15.989Z

---

# 1\. Trigger scenario

The **new message** webhook is triggered when a new message is sent in a customer service conversation.

# 2\. Data business parameters

| **Parameter name** | **Data type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
message\_id

 | 

string

 | 

7494560109732334263

 | 

The identification of the message

 |
| 

index

 | 

string

 | 

7494560109732334274

 | 

The message index that can be used to sort messages.  
Newer messages have a larger index.

 |
| 

conversation\_id

 | 

string

 | 

576486316948490001

 | 

The identification of the conversation

 |
| 

type

 | 

string(enum)

 | 

TEXT

 | 

Message type, with possible values:  
\* TEXT  
\* IMAGE  
\* ALLOCATED\_SERVICE  
\* NOTIFICATION  
\* BUYER\_ENTER\_FROM\_TRANSFER  
\* BUYER\_ENTER\_FROM\_PRODUCT  
\* BUYER\_ENTER\_FROM\_ORDER  
\* PRODUCT\_CARD  
\* ORDER\_CARD  
\* EMOTICONS  
\* VIDEO  
\* COUPON\_CARD  
\*LOGISTICS\_CARD  
\* OTHER

 |
| 

content

 | 

string

 | 

{"content": "simple text message"}

 | 

Message content, in JSON serialized string.  
Examples:  
  
\* TEXT:  
{"content": "simple text"}  
  
\* IMAGE:  
{  
"height": "290",  
"url": "[https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290](https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290)",  
"width": "304"  
}  
  
\* PRODUCT\_CARD, BUYER\_ENTER\_FROM\_PRODUCT:  
{"product\_id": "12345"}  
  
\* ORDER\_CARD, BUYER\_ENTER\_FROM\_ORDER :  
{"order\_id": "12345"}  
  
\* VIDEO:  
{  
"url": "[https://video-boei18n.byted.org/storage/v1/tos-boei18n-v-c72c01/e8240f35244646428df9c3244d1a7408?x-tos-algorithm=v2&x-tos-authkey=5bf25627da095a5cba28ace592de46cc&x-tos-expires=1681980481&x-tos-signature=r\_bRxtrvGhXAuZgMmNhlZ\_Upqzg](https://video-boei18n.byted.org/storage/v1/tos-boei18n-v-c72c01/e8240f35244646428df9c3244d1a7408?x-tos-algorithm=v2&x-tos-authkey=5bf25627da095a5cba28ace592de46cc&x-tos-expires=1681980481&x-tos-signature=r_bRxtrvGhXAuZgMmNhlZ_Upqzg)",  
"cover": "[https://p-boei18n.byted.org/tos-boei18n-v-c72c01/o8keEOhzTcNCcJyAbkWZwpLIyTfkJxcGbRBvLP~tplv-jvtte31kaf-origin-jpeg.jpeg](https://p-boei18n.byted.org/tos-boei18n-v-c72c01/o8keEOhzTcNCcJyAbkWZwpLIyTfkJxcGbRBvLP~tplv-jvtte31kaf-origin-jpeg.jpeg)?",  
"width": 640,  
"height": 360,  
"duration": "20.504",  
"vid": "v0e30cg700f7cgcmu8jc77u9e2bdp95g",  
"expire\_time": "1681980481",  
"format": "mp4",  
"size": 400000,  
"bit\_rate": 156067,  
"quality": "original",  
"codec\_type": "h264"  
}  
  
\* LOGISTICS\_CARD:  
{  
"order\_id": "580874485811283206",  
"package\_id": "123456" // Optional (recommended for one order with multiple packages; not required for one order with one package)  
}  
  
\* COUPON\_CARD:  
{"coupon\_id": "7262992004278206762"}  
  
  
Note: Use Get Coupon for the details of the coupon.  
  
\* ALLOCATED\_SERVICE, NOTIFICATION, BUYER\_ENTER\_FROM\_TRANSFER, OTHER:  
{"content": "simple text"}

 |
| 

create\_time

 | 

int

 | 

1691411573

 | 

Message creation time, represented as a Unix timestamp (seconds).

 |
| 

is\_visible

 | 

bool

 | 

true

 | 

Whether this message should be displayed to customer service.

 |
| 

sender

 | 

Object

 | 

 | 

The message sender.  
For system and robot roles, the shop is the sender.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 14,
  "tts_notification_id": "7327112393057371910",
  "shop_id": "7494049642642441621",
  "timestamp": 1644412885,
  "data": {
    "content": "{\"content\":\"444\"}",
    "conversation_id": "710688832392260838",
    "create_time": 1681790246,
    "is_visible": true,
    "message_id": "722323407926368819",
    "index": "7494560109732334274",
    "type": "TEXT",
    "sender": {
      "im_user_id": "707885565181165594",
      "role": "BUYER"
    }
  }
}
```
