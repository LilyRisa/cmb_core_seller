# Upgrade Customer Service API to 202309

> Source: https://partner.tiktokshop.com/docv2/page/upgrade-customer-service-api-to-202309
> Section: Developer Guide
> Scraped: 2026-05-21T00:24:23.282Z

---

# Mapping

## Path mapping

| **CS API v1.0 (old)** | **CS API v2.0 (new)** |
| --- | --- |
| 
\[POST\]/api/seller/im/send\_message

 | 

\[POST\]/customer\_service/202309/conversations/:conversation\_id/messages

 |
| 

\[GET\]/api/seller/im/customer\_service/status

 | 

\[GET\]/customer\_service/202309/agents/settings

 |
| 

\[POST\]/api/seller/im/customer\_service/status/update

 | 

\[PUT\]/customer\_service/202309/agents/settings

 |
| 

\[POST\]/api/seller/im/list\_conversations

 | 

\[GET\]/customer\_service/202309/conversations

 |
| 

\[POST\]/api/seller/im/get\_conversation\_messages

 | 

\[GET\]/customer\_service/202309/conversations/:conversation\_id/messages

 |
| 

\[POST\]/api/seller/im/img/upload

 | 

\[POST\]/customer\_service/202309/images/upload

 |
| 

\[POST\]/api/seller/im/mark\_read

 | 

\[POST\]/customer\_service/202309/conversations/:conversation\_id/messages/read

 |
| 

\[POST\]/api/seller/im/create\_conversation

 | 

\[POST\]/customer\_service/202309/conversations

 |

## Field mapping

### \[POST\]/api/seller/im/send\_message

New path: \[POST\]/customer\_service/202309/conversations/:conversation\_id/messages

#### Request param

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
conv\_short\_id

 | 

string

 | 

conversation\_id

 | 

string

 | 

Move to path variable

 |
| 

msg\_type

 | 

string

 | 

type

 | 

string(enum)

 | 

Enum mapping:  
text->TEXT  
file\_image->IMAGE  
goods\_card->PRODUCT\_CARD  
order\_card->ORDER\_CARD

 |
| 

content

 | 

string

 | 

content

 | 

string

 | 

**text**:  
old:  
{  
"content": "simple text"  
}  
  
new:  
{  
"content": "simple text"  
}  
  
**file\_image**:  
old:  
{  
"imageHeight": "290",  
"imageUrl": "[https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290](https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290)",  
"imageWidth": "304"  
}  
  
new:  
{  
"height": 290,  
"url": "[https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290](https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290)",  
"width": 304  
}  
  
**goods\_card**:  
old:  
{  
"goods\_id": "7494560109732334267"  
}  
  
new:  
{  
"product\_id": "7494560109732334267"  
}  
  
**order\_card**:  
old:  
{  
"order\_id": "7494560109732334267"  
}  
  
new:  
{  
"order\_id": "7494560109732334267"  
}

 |

#### Response

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
msg\_id

 | 

string

 | 

message\_id

 | 

string

 | 

 |

### \[GET\]/api/seller/im/customer\_service/status

New path: \[GET\]/customer\_service/202309/agents/settings

#### Request param

none

#### Response

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
status

 | 

int

 | 

can\_accept\_chat

 | 

bool

 | 

Value mapping:  
1->true  
0->false  
2->false  
  
The old values 0 and 2 mean the customer service agent won't accept any chats.

 |

### \[POST\]/api/seller/im/customer\_service/status/update

New path: \[PUT\]/customer\_service/202309/agents/settings

#### Request param

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
status

 | 

int

 | 

can\_accept\_chat

 | 

bool

 | 

Value mapping:  
1->true  
0->false

 |

#### Response

None

### \[POST\]/api/seller/im/list\_conversations

New path: \[GET\]/customer\_service/202309/conversations

#### Request

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
cursor

 | 

string

 | 

page\_token

 | 

string

 | 

 |
| 

limit

 | 

int

 | 

page\_size

 | 

int

 | 

 |
| 

language

 | 

string

 | 

locale

 | 

string

 | 

 |

#### Response

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
next\_cursor

 | 

string

 | 

next\_page\_token

 | 

string

 | 

2 old fields are mixed to 1 new field.  
An empty `page_token` means `has_more` is `false`.

 |
| 

has\_more

 | 

bool

 | 

 | 

 | 

 |
| 

conv\_with\_last\_msg

 | 

Object\[\]

 | 

conversations

 | 

Object\[\]

 | 

 |
| 

conv\_with\_last\_msg\[\].conv\_info.conv\_short\_id

 | 

string

 | 

conversations\[\].id

 | 

string

 | 

 |
| 

conv\_with\_last\_msg\[\].conv\_info.app\_id

 | 

int

 | 

**Deleted**

 | 

 | 

 |
| 

conv\_with\_last\_msg\[\].conv\_info.member\_count

 | 

int

 | 

conversations\[\].participant\_count

 | 

int

 | 

 |
| 

conv\_with\_last\_msg\[\].conv\_info.can\_send\_message

 | 

bool

 | 

conversations\[\].can\_send\_message

 | 

bool

 | 

 |
| 

conv\_with\_last\_msg\[\].conv\_info.unread\_count

 | 

int

 | 

conversations\[\].unread\_count

 | 

int

 | 

 |
| 

conv\_with\_last\_msg\[\].conv\_info.participants

 | 

Object\[\]

 | 

conversations\[\].participants

 | 

Object\[\]

 | 

 |
| 

conv\_with\_last\_msg\[\].conv\_info.participants\[\].participant\_id

 | 

string

 | 

conversations\[\].participants\[\].im\_user\_id

 | 

string

 | 

 |
| 

conv\_with\_last\_msg\[\].conv\_info.participants\[\].role

 | 

int

 | 

conversations\[\].participants\[\].role

 | 

string(enum)

 | 

Enum mapping:  
1->BUYER  
2->SHOP  
3->CUSTOMER\_SERVICE

 |
| 

conv\_with\_last\_msg\[\].conv\_info.participants\[\].nick

 | 

string

 | 

conversations\[\].participants\[\].nickname

 | 

string

 | 

 |
| 

conv\_with\_last\_msg\[\].conv\_info.participants\[\].avatar

 | 

string

 | 

conversations\[\].participants\[\].avatar

 | 

string

 | 

 |
| 

conv\_with\_last\_msg\[\].conv\_info.participants\[\].outer\_id

 | 

string

 | 

conversations\[\].participants\[\].user\_id

 | 

string

 | 

 |
| 

conv\_with\_last\_msg\[\].latest\_msg

 | 

Object

 | 

conversations\[\].latest\_message

 | 

Object

 | 

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.conv\_short\_id

 | 

string

 | 

**Deleted**

 | 

 | 

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.msg\_id

 | 

string

 | 

conversations\[\].latest\_message.id

 | 

string

 | 

 |
| 

**Newly added**

 | 

 | 

conversations\[\].latest\_message.sender

 | 

Object

 | 

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.sender\_id

 | 

string

 | 

conversations\[\].latest\_message.sender.im\_user\_id

 | 

string

 | 

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.sender\_role

 | 

int

 | 

**Deleted**

 | 

 | 

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.sender\_role\_v2

 | 

int

 | 

conversations\[\].latest\_message.sender.role

 | 

string(enum)

 | 

Enum mapping:  
1->BUYER  
2->SHOP  
3->CUSTOMER\_SERVICE  
4(deprecated)  
5->SYSTEM  
6->ROBOT

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.sender\_nick

 | 

string

 | 

conversations\[\].latest\_message.sender.nickname

 | 

string

 | 

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.sender\_avatar

 | 

string

 | 

conversations\[\].latest\_message.sender.avatar

 | 

string

 | 

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.ref\_msg\_info

 | 

Object

 | 

**Deleted**

 | 

 | 

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.msg\_type

 | 

string(enum)

 | 

conversations\[\].latest\_message.type

 | 

string(enum)

 | 

Enum mapping:  
text->TEXT  
file\_image->IMAGE  
allocated\_service->ALLOCATED\_SERVICE  
notification->NOTIFICATION  
use\_enter\_from\_transfer->BUYER\_ENTER\_FROM\_TRANSFER  
user\_enter\_from\_goods->BUYER\_ENTER\_FROM\_PRODUCT  
user\_enter\_from\_order->BUYER\_ENTER\_FROM\_ORDER  
goods\_card->PRODUCT\_CARD  
order\_card->ORDER\_CARD  
emoticons->EMOTICONS  
video->VIDEO  
other->OTHER

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.content

 | 

string

 | 

conversations\[\].latest\_message.content

 | 

string

 | 

**text, allocated\_service, notification, user\_enter\_from\_transfer**:  
old:  
{  
"content": "simple text"  
}  
  
new:  
{  
"content": "simple text"  
}  
  
**file\_image**:  
old:  
{  
"imageHeight": "290",  
"imageUrl": "[https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290](https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290)",  
"imageWidth": "304"  
}  
  
new:  
{  
"height": 290,  
"url": "[https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290](https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290)",  
"width": 304  
}  
  
**goods\_card, user\_enter\_from\_goods**:  
old:  
{  
"goods\_id": "7494560109732334274"  
}  
  
new:  
{  
"product\_id": "7494560109732334274"  
}  
  
**order\_card, user\_enter\_from\_orders**:  
old:  
{  
"order\_id": "7494560109732336829"  
}  
  
new:  
{  
"order\_id": "7494560109732336829"  
}  
  
**video**:  
old:  
{  
"video\_info": "{"videoUrl":"[https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po","coverUrl":"https://p16-oec-sg.ibyteimg.com/tos-alisg-v-2ea863-sg/oA29Bf4OciNoIyOEIAywiAToE6oBYbIZzAl4WF~tplv-aphluv4xwc-origin-jpeg.jpeg?from=3431008404","videoWidth":1920,"videoHeight":960,"videoDuration":127.254,"vid":"v10394g5000ccnk3m7fog65o44qog4cg","videoExpireTime":1712310441,"videoFormat":"mp4","videoSize":9309252,"videoBitRate":585239,"videoQuality":"original","codecType":"h264","nonOriginal":""}](https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po","coverUrl":"https://p16-oec-sg.ibyteimg.com/tos-alisg-v-2ea863-sg/oA29Bf4OciNoIyOEIAywiAToE6oBYbIZzAl4WF~tplv-aphluv4xwc-origin-jpeg.jpeg?from=3431008404","videoWidth":1920,"videoHeight":960,"videoDuration":127.254,"vid":"v10394g5000ccnk3m7fog65o44qog4cg","videoExpireTime":1712310441,"videoFormat":"mp4","videoSize":9309252,"videoBitRate":585239,"videoQuality":"original","codecType":"h264","nonOriginal":""})"  
}  
  
new:  
{  
"url": "[https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po](https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po)",  
"width": 1920,  
"height": 960,  
"duration": "127.254",  
"vid": "v10394g5000ccnk3m7fog65o44qog4cg",  
"expire\_time": 1712310441,  
"format": "mp4",  
"size": 9309252,  
"bit\_rate": 585239,  
"quality": "original",  
"codec\_type": "h264"  
}

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.index\_in\_conversation

 | 

string

 | 

**Deleted**

 | 

 | 

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.is\_visible

 | 

bool

 | 

conversations\[\].latest\_message.is\_visible

 | 

bool

 | 

 |
| 

conv\_with\_last\_msg\[\].latest\_msg.create\_time

 | 

int

 | 

conversations\[\].latest\_message.create\_time

 | 

int

 | 

 |

### \[POST\]/api/seller/im/get\_conversation\_messages

New path: \[GET\]/customer\_service/202309/conversations/:conversation\_id/messages

#### Request param

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
conv\_short\_id

 | 

string

 | 

conversation\_id

 | 

string

 | 

Move to path variable

 |
| 

pull\_direction

 | 

int

 | 

sort\_order

 | 

string(enum)

 | 

Enum mapping:  
0->ASC  
1->DESC  
  
In the old API, messages will always be sorted from oldest to latest.  
In the new API, messages will be sorted from oldest to latest if the "sort\_order" is "ASC", while they will be sorted from latest to oldest if "sort\_order" is "DESC".

 |
| 

**Newly added**

 | 

 | 

sort\_field

 | 

string

 | 

Sort messages by  
Available value:  
create\_time (default)

 |
| 

cursor

 | 

string

 | 

page\_token

 | 

string

 | 

 |
| 

limit

 | 

int

 | 

page\_size

 | 

int

 | 

 |
| 

language

 | 

string

 | 

locale

 | 

string

 | 

 |

#### Response

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
next\_cursor

 | 

string

 | 

next\_page\_token

 | 

string

 | 

2 old fields are mixed to 1 new field.  
An empty `page_token` means `has_more` is `false`.

 |
| 

has\_more

 | 

bool

 | 

 | 

 | 

 |
| 

unsupport\_msg\_tips

 | 

string

 | 

unsupported\_msg\_tips

 | 

string

 | 

 |
| 

msgs

 | 

Object\[\]

 | 

messages

 | 

Object\[\]

 | 

 |
| 

msgs\[\].conv\_short\_id

 | 

string

 | 

**Deleted**

 | 

 | 

 |
| 

msgs\[\].msg\_id

 | 

string

 | 

messages\[\].id

 | 

string

 | 

 |
| 

**Newly added**

 | 

 | 

messages\[\].sender

 | 

Object

 | 

 |
| 

msgs\[\].sender\_id

 | 

string

 | 

messages\[\].sender.im\_user\_id

 | 

string

 | 

 |
| 

msgs\[\].sender\_role

 | 

int

 | 

**Deleted**

 | 

 | 

 |
| 

msgs\[\].sender\_role\_v2

 | 

int

 | 

messages\[\].sender.role

 | 

string(enum)

 | 

Enum mapping:  
1->BUYER  
2->SHOP  
3->CUSTOMER\_SERVICE  
4(deprecated)  
5->SYSTEM  
6->ROBOT

 |
| 

msgs\[\].sender\_nick

 | 

string

 | 

messages\[\].sender.nickname

 | 

string

 | 

 |
| 

msgs\[\].sender\_avatar

 | 

string

 | 

messages\[\].sender.avatar

 | 

string

 | 

 |
| 

msgs\[\].ref\_msg\_info

 | 

Object

 | 

**Deleted**

 | 

 | 

 |
| 

msgs\[\].msg\_type

 | 

string(enum)

 | 

messages\[\].type

 | 

string(enum)

 | 

Enum mapping:  
text->TEXT  
file\_image->IMAGE  
allocated\_service->ALLOCATED\_SERVICE  
notification->NOTIFICATION  
use\_enter\_from\_transfer->BUYER\_ENTER\_FROM\_TRANSFER  
user\_enter\_from\_goods->BUYER\_ENTER\_FROM\_PRODUCT  
user\_enter\_from\_order->BUYER\_ENTER\_FROM\_ORDER  
goods\_card->PRODUCT\_CARD  
order\_card->ORDER\_CARD  
emoticons->EMOTICONS  
video->VIDEO  
other->OTHER

 |
| 

msgs\[\].content

 | 

string

 | 

messages\[\].content

 | 

string

 | 

**text, allocated\_service, notification, user\_enter\_from\_transfer**:  
old:  
{  
"content": "simple text"  
}  
  
new:  
{  
"content": "simple text"  
}  
  
**file\_image**:  
old:  
{  
"imageHeight": "290",  
"imageUrl": "[https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290](https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290)",  
"imageWidth": "304"  
}  
  
new:  
{  
"height": 290,  
"url": "[https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290](https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290)",  
"width": 304  
}  
  
**goods\_card, user\_enter\_from\_goods**:  
old:  
{  
"goods\_id": "7494560175032334583"  
}  
  
new:  
{  
"product\_id": "7494560175032334583"  
}  
  
**order\_card, user\_enter\_from\_orders**:  
old:  
{  
"order\_id": "7494560109732337395"  
}  
  
new:  
{  
"order\_id": "7494560109732337395"  
}  
  
**video**:  
old:  
{  
"video\_info": "{"videoUrl":"[https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po","coverUrl":"https://p16-oec-sg.ibyteimg.com/tos-alisg-v-2ea863-sg/oA29Bf4OciNoIyOEIAywiAToE6oBYbIZzAl4WF~tplv-aphluv4xwc-origin-jpeg.jpeg?from=3431008404","videoWidth":1920,"videoHeight":960,"videoDuration":127.254,"vid":"v10394g5000ccnk3m7fog65o44qog4cg","videoExpireTime":1712310441,"videoFormat":"mp4","videoSize":9309252,"videoBitRate":585239,"videoQuality":"original","codecType":"h264","nonOriginal":""}](https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po","coverUrl":"https://p16-oec-sg.ibyteimg.com/tos-alisg-v-2ea863-sg/oA29Bf4OciNoIyOEIAywiAToE6oBYbIZzAl4WF~tplv-aphluv4xwc-origin-jpeg.jpeg?from=3431008404","videoWidth":1920,"videoHeight":960,"videoDuration":127.254,"vid":"v10394g5000ccnk3m7fog65o44qog4cg","videoExpireTime":1712310441,"videoFormat":"mp4","videoSize":9309252,"videoBitRate":585239,"videoQuality":"original","codecType":"h264","nonOriginal":""})"  
}  
  
new:  
{  
"url": "[https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po](https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po)",  
"width": 1920,  
"height": 960,  
"duration": "127.254",  
"vid": "v10394g5000ccnk3m7fog65o44qog4cg",  
"expire\_time": 1712310441,  
"format": "mp4",  
"size": 9309252,  
"bit\_rate": 585239,  
"quality": "original",  
"codec\_type": "h264"  
}

 |
| 

msgs\[\].index\_in\_conversation

 | 

string

 | 

**Deleted**

 | 

 | 

 |
| 

msgs\[\].is\_visible

 | 

bool

 | 

messages\[\].is\_visible

 | 

bool

 | 

 |
| 

msgs\[\].create\_time

 | 

int

 | 

messages\[\].create\_time

 | 

int

 | 

 |

### \[POST\]/api/seller/im/img/upload

New path: \[POST\]/customer\_service/202309/images/upload

#### Request param

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
data

 | 

string

 | 

data

 | 

binary

 | 

In the old API, you should encode your image into BASE64 string, then put it in to a JSON request body.  
  
In the new API, you should put your image binary data into the request body, with "Content-Type=multipart/form-data" and a form key named "data".

 |

#### Response

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
url

 | 

string

 | 

url

 | 

string

 | 

 |
| 

width

 | 

int

 | 

width

 | 

int

 | 

 |
| 

height

 | 

int

 | 

height

 | 

int

 | 

 |

### \[POST\]/api/seller/im/mark\_read

New path: \[POST\]/customer\_service/202309/conversations/:conversation\_id/messages/read

#### Request param

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
conv\_short\_id

 | 

string

 | 

conversation\_id

 | 

string

 | 

Move to path variable.

 |

#### Response

None

### \[POST\]/api/seller/im/create\_conversation

New path: \[POST\]/customer\_service/202309/conversations

#### Request param

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
buyer\_id

 | 

string

 | 

buyer\_user\_id

 | 

string

 | 

 |

#### Response

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
conv\_short\_id

 | 

string

 | 

conversation\_id

 | 

string

 | 

 |

## Webhook

### Type mapping

| **Event** | **Old type** | **New type** |
| --- | --- | --- |
| 
New conversation

 | 

8

 | 

13

 |
| 

New messages

 | 

9

 | 

14

 |

### Field mapping

#### New Conversation

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
conv\_short\_id

 | 

string

 | 

conversation\_id

 | 

string

 | 

 |
| 

create\_time

 | 

int

 | 

create\_time

 | 

int

 | 

 |

#### New Messages

| **Old** | **Old type** | **New** | **New type** | **Description** |
| --- | --- | --- | --- | --- |
| 
message\_id

 | 

string

 | 

message\_id

 | 

string

 | 

 |
| 

conv\_short\_id

 | 

string

 | 

conversation\_id

 | 

string

 | 

 |
| 

content

 | 

string

 | 

content

 | 

string

 | 

**text, allocated\_service, notification, user\_enter\_from\_transfer**:  
old:  
{  
"content": "simple text"  
}  
  
new:  
{  
"content": "simple text"  
}  
  
**file\_image**:  
old:  
{  
"imageHeight": "290",  
"imageUrl": "[https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290](https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290)",  
"imageWidth": "304"  
}  
  
new:  
{  
"height": 290,  
"url": "[https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290](https://tosv.boei18n.byted.org/obj/temai-im/FszkJ53nSapYG6KDaJQmqR3jjoZGwww304-290)",  
"width": 304  
}  
  
**goods\_card, user\_enter\_from\_goods**:  
old:  
{  
"goods\_id": "7494560175032334583"  
}  
  
new:  
{  
"product\_id": "7494560175032334583"  
}  
  
**order\_card, user\_enter\_from\_orders**:  
old:  
{  
"order\_id": "7494560109732337395"  
}  
  
new:  
{  
"order\_id": "7494560109732337395"  
}  
  
**video**:  
old:  
{  
"video\_info": "{"videoUrl":"[https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po","coverUrl":"https://p16-oec-sg.ibyteimg.com/tos-alisg-v-2ea863-sg/oA29Bf4OciNoIyOEIAywiAToE6oBYbIZzAl4WF~tplv-aphluv4xwc-origin-jpeg.jpeg?from=3431008404","videoWidth":1920,"videoHeight":960,"videoDuration":127.254,"vid":"v10394g5000ccnk3m7fog65o44qog4cg","videoExpireTime":1712310441,"videoFormat":"mp4","videoSize":9309252,"videoBitRate":585239,"videoQuality":"original","codecType":"h264","nonOriginal":""}](https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po","coverUrl":"https://p16-oec-sg.ibyteimg.com/tos-alisg-v-2ea863-sg/oA29Bf4OciNoIyOEIAywiAToE6oBYbIZzAl4WF~tplv-aphluv4xwc-origin-jpeg.jpeg?from=3431008404","videoWidth":1920,"videoHeight":960,"videoDuration":127.254,"vid":"v10394g5000ccnk3m7fog65o44qog4cg","videoExpireTime":1712310441,"videoFormat":"mp4","videoSize":9309252,"videoBitRate":585239,"videoQuality":"original","codecType":"h264","nonOriginal":""})"  
}  
  
new:  
{  
"url": "[https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po](https://v16m-default.akamaized.net/f4d97c4ca9018602e423366c40e9ccec/660fc8a9/video/tos/alisg/tos-alisg-v-2ea863-sg/okIEwc9EIW4OAJyZTiBOc6iIoB4xf3YEoQA2Po)",  
"width": 1920,  
"height": 960,  
"duration": "127.254",  
"vid": "v10394g5000ccnk3m7fog65o44qog4cg",  
"expire\_time": 1712310441,  
"format": "mp4",  
"size": 9309252,  
"bit\_rate": 585239,  
"quality": "original",  
"codec\_type": "h264"  
}

 |
| 

is\_visible

 | 

bool

 | 

is\_visible

 | 

bool

 | 

 |
| 

msg\_type

 | 

string

 | 

type

 | 

string(enum)

 | 

Enum mapping:  
text->TEXT  
file\_image->IMAGE  
allocated\_service->ALLOCATED\_SERVICE  
notification->NOTIFICATION  
use\_enter\_from\_transfer->BUYER\_ENTER\_FROM\_TRANSFER  
user\_enter\_from\_goods->BUYER\_ENTER\_FROM\_PRODUCT  
user\_enter\_from\_order->BUYER\_ENTER\_FROM\_ORDER  
goods\_card->PRODUCT\_CARD  
order\_card->ORDER\_CARD  
emoticons->EMOTICONS  
video->VIDEO  
other->OTHER

 |
| 

**Newly added**

 | 

 | 

sender

 | 

Object

 | 

 |
| 

sender\_id

 | 

string

 | 

sender.im\_user\_id

 | 

string

 | 

 |
| 

sender\_role

 | 

int

 | 

**Deleted**

 | 

 | 

 |
| 

sender\_role\_v2

 | 

int

 | 

sender.role

 | 

string(enum)

 | 

Enum mapping:  
1->BUYER  
2->SHOP  
3->CUSTOMER\_SERVICE  
4(deprecated)  
5->SYSTEM  
6->ROBOT

 |
| 

create\_time

 | 

int

 | 

create\_time

 | 

int

 | 

 |
