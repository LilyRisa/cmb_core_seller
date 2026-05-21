# Overview

> Source: https://partner.tiktokshop.com/docv2/page/tts-webhooks-overview
> Section: Developer Guide
> Scraped: 2026-05-21T00:25:21.696Z

---

Webhooks enable real-time HTTP notifications for TikTok Shop events and actions, transmitted securely over HTTPS to the subscribed HTTP server.  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/a7684affb194494c963ffde1f486a741~tplv-k9wyc2ijk0-image.image)

1.  The app subscribes to the `Order Status Update` topic for a shop and listens for order status update events
2.  The app specifies an HTTPS endpoint hosted by the app server to receive events for the topic.
3.  An order in the shop is created or the status has changed.
4.  The event is published to the `Order Status Update` topic.
5.  TikTok Shop sends the webhook with an order status upload payload to the registered subscription endpoint.

## Use cases

Common webhook use cases include the following:

-   Receive a notification when order status has been updated
-   Receive a notification when the order recipient address has been updated
-   Receive a notification when return status has been updated
-   Receive a notification when product status has been updated

## Attention

Due to possible network problems and other uncertainties, you cannot completely rely on webhooks. You must have business logic, such as pulling orders through scheduled tasks, etc.

## Webhooks Header and Body

### Header

TikTok Shop Partners Center pushes notification to developers and places signature in the "**Authorization**" field of the http request header.  
The signature is generated with **HMAC-SHA256** and it should be verified by developers.  
Signature is generated as follows:

1.  Suppose your app key is **abcdef**.
2.  Suppose the webhook payload is:

PLAINTEXT

Word Wrap

```
{"type":1,"tts_notification_id":"7380066284010030890","shop_id":"7495540735365777507","timestamp":1718305585,"data":{"is_on_hold_order":true,"order_id":"576653688135258178","order_status":"UNPAID","update_time":1718305585}}
```

1.  Concatenate {app key}{webhook payload} as signature base string with no formatting when pasting the strings:

PLAINTEXT

Word Wrap

```
abcdef{"type":1,"tts_notification_id":"7380066284010030890","shop_id":"7495540735365777507","timestamp":1718305585,"data":{"is_on_hold_order":true,"order_id":"576653688135258178","order_status":"UNPAID","update_time":1718305585}}
```

1.  Suppose your app secret is **123** to use as the signing key.
2.  Calculate the signature by passing the signature base string and signing key to the hmac-sha256 hashing algorithm. The resulting sign is:

PLAINTEXT

Word Wrap

```
5dec0f11ec2f6783b8deee53c9ffbf8d024302f7c7e7fa55a35d17629031ac05
```

## Body

All webhook notifications from TikTok Shop will include the following payload parameters

| Param Name | Sample | Description |
| --- | --- | --- |
| 
type

 | 

1

 | 

The identification of each type of notification

 |
| 

shop\_id

 | 

123455

 | 

The identification of the TikTok Shop

 |
| 

timestamp

 | 

1627587506

 | 

The timestamp when the notification is pushed

 |
| 

data

 | 

"data": {  
"order\_id": "1X2X3X4X5",  
"order\_status": "CANCEL",  
"update\_time": 1627587505}

 | 

The object contains business parameters related to the specific notification type.

 |

### Sample

Sample curl request:

JSON

Word Wrap

```
curl   --location --request POST 'https://www.bing.com/webhook' \  
--header 'Content-Type: application/json' \  
--header 'Authorization: 46c0d0a3299661dc92888f7da8ba2321f240e460e2b005389353432a1062b777'\  
--data-raw '{  
    "type": 1,  
    "shop_id": "7494049642642441621",  
    "timestamp": 1644412885,  
    "data": {  
        "order_id": "576486316948490001",  
        "order_status": "UNPAID",  
        "update_time": 1644412885  
    }  
}'
```

➡️ See a list of our webhooks [here](1-order-status-change)
