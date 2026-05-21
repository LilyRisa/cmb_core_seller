# (16) Product creation

> Source: https://partner.tiktokshop.com/docv2/page/16-product-creation
> Section: Webhooks
> Scraped: 2026-05-21T00:29:47.014Z

---

# 1\. Trigger scenario

Triggers a webhook message when a product is created

# 2\. Data business parameters

| **Parameter name** | **Data Type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
product\_id

 | 

int

 | 

1X2X3X4X5

 | 

The identification of a TikTok Shop product

 |
| 

product\_types

 | 

\[\]string

 | 

\["GPR\_PRODUCT"\]

 | 

The type of product, including:  
  
\* COMBINED\_PRODUCT: Virtual bundle product.  
\* GPR\_PRODUCT: Target products created using the GPR tool

 |
| 

update\_time

 | 

int

 | 

1627587600

 | 

The time when the product was created, represented as a Unix timestamp (seconds).

 |

## Event example

JSON

Word Wrap

```
{
  "type": 16,
  "tts_notification_id": "7327112393057371910",
  "shop_id": "7494049642642441621",
  "timestamp": 1644412885,
  "data": {
    "product_id": 576486316948490000,
    "product_types": [
      "GPR_PRODUCT"
    ],
    "update_time": 1644412885
  }
}
```
