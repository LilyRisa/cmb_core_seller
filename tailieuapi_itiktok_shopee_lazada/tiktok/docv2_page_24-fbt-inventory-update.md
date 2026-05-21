# (24) FBT inventory update

> Source: https://partner.tiktokshop.com/docv2/page/24-fbt-inventory-update
> Section: Webhooks
> Scraped: 2026-05-21T00:28:48.115Z

---

## Trigger scenario

This webhook is triggered when the FBT inventory updates.

> Prerequisite: The **Fulfilled by TikTok(FBT) Info** API scope is enabled in Partner Center. For more information, refer to [Access Scope](access-scope).

## Data business parameters

| Parameter Name | Data Type | Sample | Description |
| --- | --- | --- | --- |
| 
type

 | 

int

 | 

`24`

 | 

The ID of this webhook topic, which is 24.

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

└ goods\_id

 | 

string

 | 

`"732357708734418520388"`

 | 

FBT goods ID.

 |
| 

└ sku\_id

 | 

string

 | 

`"123513234"`

 | 

TikTok Shop SKU ID.

 |
| 

└ fbt\_warehouse\_inventory

 | 

\[\]object

 | 

 | 

Inventory in different FBT warehouses.

 |
| 

└└ fbt\_warehouse\_id

 | 

string

 | 

`"73823232239293999999"`

 | 

ID of the FBT warehouse.

 |
| 

└└ on\_hand\_detail

 | 

object

 | 

 | 

The number of units physically in the warehouse, excluding those in transit or damaged.

 |
| 

└└└ total\_quantity

 | 

int

 | 

`7`

 | 

The total number of units.

 |
| 

└└└ reserved\_quantity

 | 

int

 | 

`5`

 | 

The number of units reserved for confirmed orders and yet to be outbound.

 |
| 

└└└ available\_quantity

 | 

int

 | 

`2`

 | 

The number of units available for sale. This does not include reserved units.

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
  "type": 24,
  "seller_open_id": "VIyStQAAAADCBQ40s4TzOSSOEIW-bM5O9cod3vK8OytW8m-bnBYlXw",
  "tts_notification_id": "7327112393057371910",
  "timestamp": 1644412885,
  "data": {
    "goods_id": "732357708734418520388",
    "sku_id": "123513234",
    "fbt_warehouse_inventory": [
      {
        "fbt_warehouse_id": "73823232239293999999",
        "on_hand_detail": {
          "total_quantity": 7,
          "reserved_quantity": 5,
          "available_quantity": 2
        }
      }
    ],
    "update_time": 1644412845
  }
}
```
