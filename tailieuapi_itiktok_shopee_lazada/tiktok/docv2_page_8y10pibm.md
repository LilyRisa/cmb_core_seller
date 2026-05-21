# (67) Refund Success

> Source: https://partner.tiktokshop.com/docv2/page/8y10pibm
> Section: Webhooks
> Scraped: 2026-05-21T00:32:14.909Z

---

Use this webhook to identify successful refund completion and the exact refunded line-item references.

# 1\. Trigger scenario

Once a refund of an RMA is successfully completed.

# 2\. Data business parameters

| **Properties** | **Type** | **Example** | **Description** |
| --- | --- | --- | --- |
| 
shop\_id

 | 

String

 | 

`7494049642642441621`

 | 

TikTok Shop identifier associated with the event.

 |
| 

timestamp

 | 

Int

 | 

`1732526400`

 | 

The time when this webhook was triggered, represented by Unix timestamp.

 |
| 

tts\_notification\_id

 | 

String

 | 

`7327112393057371910`

 | 

Unique notification identifier for deduplication and tracing.

 |
| 

type

 | 

Int

 | 

**`67`**

 | 

Numeric webhook event type.

 |
| 

data

 | 

Object

 | 

 | 

 |
| 

└ aftersales\_request\_id

 | 

String

 | 

`576486316948490001`

 | 

Parent aftersales request identifier.

 |
| 

└ line\_items

 | 

\[\]object

 | 

 | 

Line-item level identifiers included in the successful refund.

 |
| 

└└ main\_order\_id

 | 

String

 | 

`576486316948490003`

 | 

Main forward order identifier.

 |
| 

└└ return\_line\_item\_id

 | 

String

 | 

`4035312491762651201`

 | 

Return line-item identifier.

 |
| 

└└ sku\_id

 | 

String

 | 

`1732309230575784932`

 | 

SKU identifier.

 |
| 

└└ sku\_return\_request\_id

 | 

String

 | 

`4035312491762585666`

 | 

SKU-level return request identifier.

 |
| 

└└ sub\_return\_line\_item\_id

 | 

String

 | 

`4035312491762651202`

 | 

Optional child line-item identifier, only applicable for Virtual Bundle returns.

 |
| 

└ refund\_currency

 | 

String

 | 

`USD`

 | 

Currency of the refunded amount.

 |
| 

└ refund\_status

 | 

String

 | 

`REFUND_SUCCESS`

 | 

Refund result. Current example shows `REFUND_SUCCESS`.

 |
| 

└ refund\_timestamp

 | 

Int

 | 

`1776102211`

 | 

Refund completion time in Unix seconds.

 |
| 

└ refund\_total

 | 

String

 | 

`1.25`

 | 

Total refunded amount.

 |
| 

└ rma\_id

 | 

String

 | 

`576486316948490002`

 | 

Associated RMA identifier.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 67,
  "tts_notification_id": "7628321701399676688",
  "shop_id": "123456",
  "timestamp": 1776107052,
  "data": {
    "aftersales_request_id": "111111111",
    "line_items": [
      {
        "main_order_id": "789456123",
        "return_line_item_id": "999999",
        "sku_id": "147852",
        "sku_return_request_id": "321654987",
        "sub_return_line_item_id": "888888"
      },
      {
        "main_order_id": "789456123",
        "return_line_item_id": "999999",
        "sku_id": "147853",
        "sku_return_request_id": "321654987",
        "sub_return_line_item_id": "777777"
      },
      {
        "main_order_id": "789456123",
        "return_line_item_id": "666666",
        "sku_id": "147853",
        "sku_return_request_id": "321654987"
      }
    ],
    "refund_currency": "USD",
    "refund_status": "REFUND_SUCCESS",
    "refund_timestamp": 1776102211,
    "refund_total": "1.25",
    "rma_id": "222222222"
  }
}
```
