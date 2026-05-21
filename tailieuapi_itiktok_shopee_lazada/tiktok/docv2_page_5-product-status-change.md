# (5) Product status change

> Source: https://partner.tiktokshop.com/docv2/page/5-product-status-change
> Section: Webhooks
> Scraped: 2026-05-21T00:29:33.885Z

---

# 1\. Trigger scenario

The **product status change** webhook triggers when a product audit status changes or is completed:

-   Product passes the first audit: `PRODUCT_FIRST_PASS_REVIEW`
-   Product status changes by platform: `PRODUCT_STATUS_CHANGED`
-   Product audit fails: `PRODUCT_AUDIT_FAILURE`

# 2\. Data business parameters

| **Parameter name** | **Sample** | **Description** |
| --- | --- | --- |
| 
product\_id

 | 

1X2X3X4X5

 | 

The identification of a TikTok Shop product

 |
| 

status

 | 

Live-On sell

 | 

Product audit status, with possible values:  
  
\* PRODUCT\_FIRST\_PASS\_REVIEW  
\* PRODUCT\_STATUS\_CHANGED  
\* PRODUCT\_AUDIT\_FAILURE

 |
| 

suspended\_reason

 | 

1X2X3X4X5

 | 

The reason a product audit was rejected or frozen

 |
| 

update\_time

 | 

1627587600

 | 

The time when the status changed, represented as a Unix timestamp (seconds).

 |

## Event example

JSON

Word Wrap

```
{
  "type": 5,
  "tts_notification_id": "7327112393057371910",
  "shop_id": "7494049642642441621",
  "timestamp": 1644412885,
  "data": {
    "product_id": 576486316948490000,
    "status": "PRODUCT_FIRST_PASS_REVIEW",
    "suspended_reason": "",
    "update_time": 1644412885
  }
}
```
