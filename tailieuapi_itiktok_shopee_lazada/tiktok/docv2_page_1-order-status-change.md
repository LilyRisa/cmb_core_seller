# (1) Order status change

> Source: https://partner.tiktokshop.com/docv2/page/1-order-status-change
> Section: Webhooks
> Scraped: 2026-05-21T00:29:22.070Z

---

# 1\. Trigger scenario

The **order status change** webhook is triggered when the `order_status` of an order changes.

# 2\. Data business parameters

| **Parameter name** | **Sample** | **Description** |
| --- | --- | --- |
| 
order\_id

 | 

576462377512830168

 | 

The identification of a TikTok Shop order

 |
| 

order\_status

 | 

CANCEL

 | 

The most recent order status, with possible values:  
  
\* `UNPAID`  
\* `ON_HOLD`  
\* `AWAITING_SHIPMENT`  
\* `AWAITING_COLLECTION`  
\* `CANCEL`  
\* `IN_TRANSIT`  
\* `DELIVERED`  
\* `COMPLETED`

 |
| 

is\_on\_hold\_order

 | 

false

 | 

Indicates whether the order has experienced or will experience `ON_HOLD` status

 |
| 

update\_time

 | 

1627587505

 | 

The order status update time, represented as a Unix timestamp (seconds).

 |

## Event example

JSON

Word Wrap

```
{
  "type": 1,
  "tts_notification_id": "7327112393057371910",
  "shop_id": "7494049642642441621",
  "timestamp": 1644412885,
  "data": {
    "order_id": "576486316948490001",
    "order_status": "UNPAID",
    "is_on_hold_order": false,
    "update_time": 1644412885
  }
}
```
