# (63) Activity change

> Source: https://partner.tiktokshop.com/docv2/page/x6legjl9
> Section: Webhooks
> Scraped: 2026-05-21T00:31:40.306Z

---

## Trigger scenario

The Activity change webhook is designed to notify partners about modifications related to activities and the products associated with them. This allows applications to stay synchronized with changes in activity details or product inclusion/exclusion lists.

## Data business parameters

| Properties | Type | Example | Description |
| --- | --- | --- | --- |
| 
shop\_id

 | 

String

 | 

"7494049642642441621"

 | 

The ID of the shop.

 |
| 

timestamp

 | 

Int64

 | 

1732526400

 | 

The time when this webhook was triggered, represented by Unix timestamp.

 |
| 

tts\_notification\_id

 | 

String

 | 

"7327112393057371910"

 | 

The ID of the notification.

 |
| 

type

 | 

Int64

 | 

63

 | 

The ID of the webhook type.

 |
| 

data

 | 

Object

 | 

 | 

 |
| 

└ activity\_id

 | 

String

 | 

"7136104329798256386"

 | 

The ID of the promotion activity.

 |
| 

└ update\_time

 | 

Int64

 | 

1732526465

 | 

The time when the status changed, represented as a Unix timestamp (seconds).

 |
| 

└ change\_type

 | 

String

 | 

CREATE

 | 

The type of activity changes.  
  
\* CREATE  
\* UPDATE  
\* DEACTIVATE

 |
| 

└ product\_update\_list

 | 

Object

 | 

 | 

Products included in an updated scope.

 |
| 

└└ product\_ids

 | 

\[\]String

 | 

\["123456","789012"\]

 | 

Included product IDs.

 |
| 

└└ exclude\_product\_ids

 | 

\[\]String

 | 

\["345678"\]

 | 

IDs of the BXGY exclude products to remove.  
Max count: 100.

 |
| 

└└ benefit\_product\_ids

 | 

\[\]String

 | 

\["456789"\]

 | 

TikTokShop product ID list.

 |
| 

└ product\_remove\_list

 | 

Object

 | 

 | 

Products removed from the activity.

 |
| 

└└ product\_ids

 | 

\[\]String

 | 

\["111222","333444"\]

 | 

Removed product IDs.

 |
| 

└└ sku\_ids

 | 

\[\]String

 | 

\["111","222"\]

 | 

Removed SKU IDs.

 |
| 

└└ benefit\_product\_ids

 | 

\[\]String

 | 

\["555666"\]

 | 

TikTokShop product ID list.

 |
| 

└└ exclude\_product\_ids

 | 

\[\]String

 | 

\["345678"\]

 | 

IDs of the BXGY exclude products to remove.  
Max count: 100.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 63,
  "timestamp": 1678886400,
  "shop_id": "789123456789123456",
  "tts_notification_id": "000000000000000001",
  "data": {
    "activity_id": "act_123456789012345678",
    "update_time": 1678886400,
    "change_type": "product_update",
    "product_update_list": {
      "product_ids": [
        "11111111",
        "22222222"
      ],
      "exclude_product_ids": [
        "33333333"
      ],
      "benefit_product_ids": [
        "44444444"
      ]
    },
    "product_remove_list": {
      "product_ids": [
        "55555555"
      ],
      "sku_ids": [
        "66666666"
      ],
      "benefit_product_ids": [],
      "exclude_product_ids": []
    }
  }
}
```
