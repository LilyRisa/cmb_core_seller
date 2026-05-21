# (3) Recipient address update

> Source: https://partner.tiktokshop.com/docv2/page/3-recipient-address-update
> Section: Webhooks
> Scraped: 2026-05-21T00:29:04.424Z

---

# 1\. Trigger scenario

The **recipient address update** webhook triggers when an order recipient's address is updated.

# 2\. Data business parameters

| **Parameter name** | **Sample** | **Description** |
| --- | --- | --- |
| 
order\_id

 | 

1X2X3X4X5

 | 

The identification of a TikTok Shop order

 |
| 

update\_time

 | 

1627587600

 | 

The time when recipient address is updated, represented as a Unix timestamp (seconds).

 |

## Event example

JSON

Word Wrap

```
{
  "type": 3,
  "shop_id": "7494049642642441621",
  "timestamp": 1644412885,
  "data": {
    "order_id": "576486316948490001",
    "update_time": 1644412885
  }
}
```
