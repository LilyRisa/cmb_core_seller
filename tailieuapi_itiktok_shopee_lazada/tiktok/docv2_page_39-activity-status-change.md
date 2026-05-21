# (39) Activity status change

> Source: https://partner.tiktokshop.com/docv2/page/39-activity-status-change
> Section: Webhooks
> Scraped: 2026-05-21T00:31:34.140Z

---

## Trigger scenario

When a promotion activity of the specified type starts, expires, or is deactivated, you'll receive this webhook.  
This webhook will only be triggered when all the following conditions are met:

-   Your App has been authorized the `Promotion Information` scope.
-   The activities are of the following types:
    -   `FIXED_PRICE`
    -   `DIRECT_DISCOUNT`
    -   `SHIPPING_DISCOUNT`
    -   `BUY_MORE_SAVE_MORE`
    -   `FLASHSALE` (Only available in SEA markets: SG, TH, ID, MY, PH, VN)
-   One of the following status changes happens:
    -   The activity starts: the status changes from `NOT_START` to `ONGOING`.
    -   The activity expires: the status changes from `ONGOING` to `EXPIRED`.
    -   The activity is deactivated: the status changes to `DEACTIVATED`.

## Data business parameters

| **Parameter name** | **Data Type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
type

 | 

int

 | 

`39`

 | 

The ID of the webhook type.

 |
| 

tts\_notification\_id

 | 

string

 | 

`"7327112393057371910"`

 | 

The ID of the notification.

 |
| 

shop\_id

 | 

string

 | 

`"7494049642642441621"`

 | 

The ID of the shop.

 |
| 

timestamp

 | 

int

 | 

`1644412885`

 | 

The time when this webhook was triggered, represented by Unix timestamp.

 |
| 

data

 | 

object

 | 

 | 

 |
| 

└ activity\_id

 | 

string

 | 

`"7136104329798256386"`

 | 

The ID of the promotion activity.

 |
| 

└ update\_time

 | 

int

 | 

`1644412885`

 | 

The time when the status changed, represented as a Unix timestamp (seconds).

 |
| 

└ activity\_type

 | 

string

 | 

`"FIXED_PRICE"`

 | 

The type of the promotion activity. Possible values are:  
  
\* `FIXED_PRICE`  
\* `DIRECT_DISCOUNT`  
\* `SHIPPING_DISCOUNT`  
\* `BUY_MORE_SAVE_MORE`  
\* `FLASHSALE` (Only available in SEA markets: SG, TH, ID, MY, PH, VN)

 |
| 

└ product\_level

 | 

string

 | 

`"PRODUCT"`

 | 

Activity product dimension, values are:  
  
\* `PRODUCT`  
\* `VARIATION`  
\* `SHOP`

 |
| 

└ status

 | 

string

 | 

`ONGOING`

 | 

The current status of the promotion activity. Possible values are:  
  
\* `ONGOING`  
\* `EXPIRED`  
\* `DEACTIVATED`

 |

## Event example

JSON

Word Wrap

```
{
  "type": 39,
  "shop_id": "7494049642642441621",
  "tts_notification_id": "7327112393057371910",
  "timestamp": 1644412885,
  "data": {
    "activity_id": "7136104329798256386",
    "update_time": 1644412885,
    "activity_type": "FIXED_PRICE",
    "product_level": "PRODUCT",
    "status": "ONGOING"
  }
}
```
