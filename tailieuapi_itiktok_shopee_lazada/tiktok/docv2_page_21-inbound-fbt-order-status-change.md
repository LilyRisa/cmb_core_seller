# (21) Inbound FBT order status change

> Source: https://partner.tiktokshop.com/docv2/page/21-inbound-fbt-order-status-change
> Section: Webhooks
> Scraped: 2026-05-21T00:28:30.154Z

---

## Trigger scenario

This webhook is triggered when the status of an FBT inbound order changes.

> Prerequisite: The **Fulfilled by TikTok(FBT) Info** API scope is enabled in Partner Center. For more information, refer to [Access Scope](access-scope).

## Data business parameters

| **Parameter Name** | **Data Type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
type

 | 

int

 | 

`21`

 | 

The ID of this webhook topic, which is 21.

 |
| 

tts\_notification\_id

 | 

string

 | 

`"7327112393057371910"`

 | 

The ID of this webhook notification.

 |
| 

seller\_open\_id

 | 

string

 | 

`"VIyStQAAAADCBQ40s4TzOSSOEIW-bM5O9cod3vK8OytW8m-bnBYlXw"`

 | 

The open\_id of the seller. For more information, refer to [Authorization overview](authorization-overview-202407).

 |
| 

timestamp

 | 

int

 | 

`1644412885`

 | 

The time when this webhook is triggered. Unix timestamp.

 |
| 

data

 | 

object

 | 

 | 

 |
| 

└ inbound\_order\_id

 | 

string

 | 

`"IBR577087614418520388"`

 | 

FBT inbound order ID.

 |
| 

└ order\_status

 | 

string

 | 

`"CANCELLED"`

 | 

Inbound order status. Possible enumerations:  
\- TO\_BE\_RECEIVED  
\- ARRIVE\_HUB,  
\- RECEIVING,  
\- PARTIALLY\_RECEIVED,  
\- RECEIVED,  
\- CANCELLED

 |
| 

└ update\_time

 | 

int

 | 

`1644412845`

 | 

The time when the change occurred. Unix timestamp.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 21,
  "seller_open_id": "VIyStQAAAADCBQ40s4TzOSSOEIW-bM5O9cod3vK8OytW8m-bnBYlXw",
  "tts_notification_id": "7327112393057371910",
  "timestamp": 1644412885,
  "data": {
    "inbound_order_id": "IBR577087614418520388",
    "order_status": "CANCELLED",
    "update_time": 1644412845
  }
}
```
