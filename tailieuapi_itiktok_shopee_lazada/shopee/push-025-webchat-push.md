# webchat_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Webchat Push
> Scraped: 2026-05-20T20:45:06.298Z

---

Push Mechanism

Webchat Push

\>

webchat\_push

Basics

Push Parameters

Push Contents

Update Log

Product Push

-   reserved\_stock\_change\_push
-   video\_upload\_push
-   brand\_register\_result
-   violation\_item\_push
-   item\_price\_update\_push
-   item\_scheduled\_publish\_failed\_push

Order Push

-   order\_status\_push
-   order\_trackingno\_push
-   shipping\_document\_status\_push
-   booking\_status\_push
-   booking\_trackingno\_push
-   booking\_shipping\_document\_status\_push
-   package\_fulfillment\_status\_push
-   courier\_delivery\_binding\_status\_push
-   package\_info\_push

Return Push

-   return\_updates\_push

Marketing Push

-   item\_promotion\_push
-   promotion\_update\_push

Shopee Push

-   shopee\_updates
-   open\_api\_authorization\_expiry
-   shop\_authorization\_push
-   shop\_authorization\_canceled\_push
-   shop\_penalty\_update\_push
-   video\_upload\_result\_push

Webchat Push

-   webchat\_push

Consignment Service Push

-   inbound\_status\_push
-   supplier\_create\_product\_push
-   supplier\_prouduct\_review\_result\_push
-   purchase\_order\_Push

Fulfillment by Shopee Push

-   fbs\_sellable\_stock
-   fbs\_br\_invoice\_error\_push
-   fbs\_br\_block\_shop\_push
-   fbs\_br\_block\_sku\_push
-   fbs\_br\_invoice\_issued\_push

webchat\_push

Last Updated: 18 Apr 2025

## Basics

Collapse

| 
Property

 | 

Value

 |
| --- | --- |
| 

Category

 | 

Webchat Push

 |
| 

Push Mechanism Name

 | 

webchat\_push

 |
| 

Push Mechanism Code

 | 

10

 |
| 

Push Mechanism Description

 | 

Get the chat message

 |
| 

Push Mechanism Subscription Rules

 | 

Seller In House System/Customer Service/Original/Ads Service App

 |
| 

Time Out Seconds

 | 

2s

 |
| 

Sequence Guaranteed

 | 

Yes

 |
| 

Can Repeated Same Message

 | 

Yes

 |
| 

Retry Seconds

 | 

1s,2s,3s

 |

## Push Parameters

Collapse

| 
Name

 | 

Type

 | 

Sample

 | 

Description

 |
| --- | --- | --- | --- |
| 

data

 | 

object

 | 

 | 

 |
| 

type

 | 

string

 | 

 | 

Can be notification / message

 |
| 

region

 | 

string

 | 

 | 

The region info.

 |
| 

content

 | 

object

 | 

 | 

The detailed message.

 |
| 

user\_id

 | 

string

 | 

 | 

Returned when the type = notification.

 |
| 

conversation\_id

 | 

string

 | 

 | 

Shopee's unique identifier for a conversation.  

 |
| 

type

 | 

string

 | 

 | 

Returned when the type = notification.  

 |
| 

timestamp

 | 

timestamp

 | 

 | 

Returned when the type = notification.  

 |
| 

msg\_id

 | 

int64

 | 

 | 

Returned when the type = notification.  

 |
| 

biz\_id

 | 

int64

 | 

 | 

Returned when the type = notification.  

 |
| 

message\_id

 | 

string

 | 

 | 

Returned when type= message. Shopee's unique identifier for a message.  

 |
| 

shop\_id

 | 

int64

 | 

 | 

Returned when type= message. The shop\_id of the shop message sent to (to\_shop\_id)  

 |
| 

request\_id

 | 

string

 | 

 | 

Returned when type= message. The identifier for an request for error tracking.  

 |
| 

from\_user\_name

 | 

string

 | 

 | 

Returned when type= message. The user name for the message sender in the conversation.  

 |
| 

from\_id

 | 

int64

 | 

 | 

The user id for the message sender in the conversation.  

 |
| 

to\_id

 | 

int64

 | 

 | 

Returned when type= message. The user id for the message recipient in the conversation.  

 |
| 

to\_user\_name

 | 

string

 | 

 | 

Returned when type= message. The user name for the message recipient in the conversation.  

 |
| 

message\_type

 | 

string

 | 

 | 

Returned when type= message. text / video / image / item / faq\_liveagent

 |
| 

content

 | 

object

 | 

 | 

 |
| 

text

 | 

timestamp

 | 

 | 

Returned when message type=text or faq\_liveagent, specific dialog text information.  

 |
| 

translation

 | 

object

 | 

 | 

Returned when message type=text.  

 |
| 

text

 | 

string

 | 

 | 

 |
| 

source

 | 

string

 | 

 | 

 |
| 

target\_language

 | 

string

 | 

 | 

 |
| 

source\_language

 | 

string

 | 

 | 

 |
| 

mid

 | 

object

 | 

 | 

Returned when message type=text.  

 |
| 

text

 | 

string

 | 

 | 

 |
| 

source

 | 

string

 | 

 | 

 |
| 

target\_language

 | 

string

 | 

 | 

 |
| 

source\_language

 | 

string

 | 

 | 

 |
| 

url

 | 

string

 | 

 | 

Returned when message type=image.  

 |
| 

thumb\_url

 | 

string

 | 

 | 

Returned when message type=image or video.  

 |
| 

thumb\_height

 | 

int64

 | 

 | 

Returned when message type=image or video.  

 |
| 

thumb\_width

 | 

int64

 | 

 | 

Returned when message type=image or video.  

 |
| 

file\_server\_id

 | 

int64

 | 

 | 

Returned when message type=image.  

 |
| 

video\_url

 | 

string

 | 

 | 

Returned when message type=video.  

 |
| 

duration\_seconds

 | 

int64

 | 

 | 

Returned when message type=video.  

 |
| 

shop\_id

 | 

int64

 | 

 | 

Returned when message type=item. The shop\_id of the shop message sent to (to\_shop\_id)  

 |
| 

item\_id

 | 

int64

 | 

 | 

Returned when message type=item.  

 |
| 

pass\_through\_data

 | 

string

 | 

 | 

Returned when message type=faq\_liveagent.  

 |
| 

messages

 | 

string\[\]

 | 

\["23234234234","234232423"\]

 | 

Returned when type=bundle\_message.  return all the message\_id in the bundle\_message

 |
| 

shopee\_chatbot\_replied

 | 

boolean

 | 

true

 | 

Return when message\_type=bundle\_message  
if there is Shopee Chatbot session in the bundle message

  

We recommend transfer to manual reply mode if shopee\_chatbot\_replied=true(Shopee Chatbot already involved)

 |
| 

created\_timestamp

 | 

timestamp

 | 

 | 

Returned when type= message. The creation time of conversation.

 |
| 

region

 | 

string

 | 

 | 

Returned when type= message. The region where the conversation take places.

 |
| 

is\_in\_chatbot\_session

 | 

boolean

 | 

 | 

Returned when type= message.  

 |
| 

source\_content

 | 

object

 | 

 | 

Returned when type= message.  

 |
| 

item\_id

 | 

int

 | 

 | 

Returned when message type= item.  

 |
| 

sub\_account\_id

 | 

int64

 | 

 | 

if the message is sent from sub-account/main account, then will indicate the sub\_account\_id

If not, =0

 |
| 

sub\_account\_name

 | 

string

 | 

 | 

if the message is sent from sub-account/main account, then will indicate the sub\_account\_id

If not, =0

 |
| 

quoted\_msg

 | 

object\[\]

 | 

 | 

 |
| 

message\_id

 | 

int64

 | 

 | 

Return message\_id when a quoted message is present.

If there is no quoted message, message\_id will be empty.

 |
| 

business\_type

 | 

int32

 | 

 | 

business\_type=0  means it is a conversation chat between buyer and seller

business\_type=11  means it is a conversation chat between affiliate and seller

  

 |
| 

to\_shop\_id

 | 

int64

 | 

 | 

shopee shop id who receive the message  

<path></path>  

 |
| 

from\_shop\_id

 | 

int64

 | 

 | 

shopee shop id who send the message  

 |
| 

status

 | 

string

 | 

 | 

The message status, the possible values are: normal; auto\_reply; blocked; user\_chat; web\_chat; censored\_whitelist; censored\_blacklist; offwork\_autoreply

 |
| 

shop\_id

 | 

int

 | 

 | 

shop\_id of shop receives the push message

Shopee's unique identifier for a shop.  

 |
| 

code

 | 

int

 | 

 | 

Shopee's unique identifier for a push notification.  

 |
| 

timestamp

 | 

timestamp

 | 

 | 

Timestamp that indicates the message was sent.  

 |

## Push Contents

Collapse

If it is notification type:

  

Json

```
{
"msg_id": null,
"data": {
"type": "notification",
"region": "PH",
"content": {
"user_id": 12252079,
"conversation_id": "4670954831706433",
"type": "mark_as_replied",
"content": {
"conversation_id": "4670954310906433"
},
"timestamp": 1719883961,
"msg_id": 0,
"biz_id": 0,
"from_id": 0
}
```

  

If it is message type and message including text:

  

Json

```
{"msg_id":"","data":{"type":"message","region":"ID","content":{"message_id":"2302748948493123953","shop_id":165103149,"request_id":"35f9478b-7482-46eb-a268-8f828fedb673","from_id":165105353,"from_user_name":"vn_cstoreponorogo","to_id":947151379,"to_user_name":"keelatofficial","message_type":"text","content":{"text":"Baik kak .. 🤗"},"conversation_id":"709122092476686867","created_timestamp":1726044721,"region":"ID","is_in_chatbot_session":false,"source_content":{},"quoted_msg":{"message_id":""},"sub_account_id":0,"sub_account_name":0}},"shop_id":947042923,"code":10,"timestamp":1726044722}
```

  

If it is message type and message including video

  

Json

```
{"data":{"type":"message","region":"VN","content":{"message_id":"2165920666211451249","shop_id":123456789,"request_id":"1091617252119662617","from_id":161057467,"from_user_name":"hyhy2606","to_id":213245905,"to_user_name":"sixhd.vn","message_type":"video","content":{"video_url":"cf03c9e1fe2c0992cdb51c3cb6eab2bd","thumb_url":"6c710d7679c9f3a9a7287250421d17d3_dynamic_tn","thumb_width":399,"thumb_height":713,"duration_seconds":15},"conversation_id":"691736553754845137","created_timestamp":1660799912,"region":"VN","is_in_chatbot_session":false,"source_content":{},"quoted_msg":{"message_id":""},"sub_account_id":0,"sub_account_name":0}},"shop_id":123456789,"code":10,"timestamp":1660799912}
```

  

If it is message type and message including image

  

Json

```
{"data":{"type":"message","region":"VN","content":{"message_id":"2165920671942967665","shop_id":123456789,"request_id":"313F2D/BTMessage/p108","from_id":679422730,"from_user_name":"thutrang290402","to_id":6343861,"to_user_name":"thanhnga_hcm","message_type":"image","content":{"url":"https://cf.shopee.vn/file/09591ecdc9f1dc7bd507817797d826fe_dynamic","thumb_url":"b9591ecdc9f1dc7bd507817797d826fe_dynamic_tn","thumb_height":711,"thumb_width":400,"file_server_id":0},"conversation_id":"27246676204792586","created_timestamp":1660799915,"region":"VN","is_in_chatbot_session":false,"source_content":{},"quoted_msg":{"message_id":""},"sub_account_id":0,"sub_account_name":0}},"shop_id":123456789,"code":10,"timestamp":1660799915}
```

  

If it is message type and message including item info

  

Json

```
{"data":{"type":"message","region":"ID","content":{"message_id":"2165920670806327665","shop_id":123456789,"request_id":"389465101372418716","from_id":163219823,"from_user_name":"fadlyjo.","to_id":119159078,"to_user_name":"zhousijia","message_type":"item","content":{"shop_id":109157255,"item_id":9112503530},"conversation_id":"511784343194732911","created_timestamp":1660799914,"region":"ID","is_in_chatbot_session":false,"quoted_msg":{"message_id":""},"sub_account_id":0,"sub_account_name":0,"source_content":{"item_id":4112503530}}},"shop_id":123456789,"code":10,"timestamp":1660799915}
```

  

If it is message type and message including faq\_liveagent

  

Json

```
{"data":{"type":"message","region":"ID","content":{"message_id":"2165920670296736113","shop_id":123456789,"request_id":"4600339818579251427","from_id":172765311,"from_user_name":"bundhakevinabizar","to_id":94311357,"to_user_name":"madamegieofficial","message_type":"faq_liveagent","content":{"text":"Chat dengan Penjual","pass_through_data":""},"conversation_id":"405064194129145983","created_timestamp":1660799914,"region":"ID","is_in_chatbot_session":false,"quoted_msg":{"message_id":""},"sub_account_id":0,"sub_account_name":0,"source_content":{"order_sn":"220818EGS328B9"}}},"shop_id":123456789,"code":10,"timestamp":1660799915}
```

  

  

## Update Log

Collapse

| 
Date

 | 

Update Details

 |
| --- | --- |
| 

2025-04-18

 | 

Add from\_shop\_id, to\_shop\_id, status

 |
| 

2024-11-05

 | 

Add business\_type for affiliate marketing solution chat Add bundle\_message for FAQ and chatbot message Add shopee\_chatbot\_replied to indicate if Shopee Chatbot involved

 |
| 

2024-09-23

 | 

Add sub\_account\_id and sub\_account\_name; Add quoted\_message

 |
| 

2024-09-04

 | 

Update the definition of the shop\_id inside "content"

 |
| 

2022-09-24

 | 

roll back retry config

 |
