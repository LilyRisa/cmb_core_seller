# (23) Goods match

> Source: https://partner.tiktokshop.com/docv2/page/23-goods-match
> Section: Webhooks
> Scraped: 2026-05-21T00:28:42.420Z

---

## Trigger scenario

This webhook is triggered when the seller matches / unmatches the TikTok Shop product to the FBT Goods.

> Prerequisite: The **Fulfilled by TikTok(FBT) Info** API scope is enabled in Partner Center. For more information, refer to [Access Scope](access-scope).

## Data business parameters

| **Parameter Name** | **Data Type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
type

 | 

int

 | 

`23`

 | 

The ID of this webhook topic, which is 23.

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

└ match\_type

 | 

string

 | 

`"MATCH"`

 | 

Indicates whether the seller is matching or unmatching products. Possible enumerations are: `MATCH` and `UNMATCH`.

 |
| 

└ product\_type

 | 

string

 | 

`"LOCAL"`

 | 

Possible enumerations are: `LOCAL` and `GLOBAL`.

 |
| 

└ goods\_id

 | 

string

 | 

`"732357708734418520388"`

 | 

FBT Goods ID.

 |
| 

└ product\_id

 | 

string

 | 

`"123513234"`

 | 

TikTok Shop product ID.

 |
| 

└ sku\_id

 | 

string

 | 

`"123563781"`

 | 

TikTok Shop SKU ID.

 |
| 

└ update\_time

 | 

int

 | 

`1644412845`

 | 

The time when the action occurred. Unix timestamp.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 23,
  "seller_open_id": "VIyStQAAAADCBQ40s4TzOSSOEIW-bM5O9cod3vK8OytW8m-bnBYlXw",
  "tts_notification_id": "7327112393057371910",
  "timestamp": 1644412885,
  "data": {
    "match_type": "MATCH",
    "product_type": "LOCAL",
    "goods_id": "732357708734418520388",
    "product_id": "123513234",
    "sku_id": "123563781",
    "update_time": 1644412845
  }
}
```
