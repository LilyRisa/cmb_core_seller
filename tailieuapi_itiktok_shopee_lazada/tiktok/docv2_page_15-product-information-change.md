# (15) Product information change

> Source: https://partner.tiktokshop.com/docv2/page/15-product-information-change
> Section: Webhooks
> Scraped: 2026-05-21T00:29:41.404Z

---

# 1\. Trigger scenario

This webhook is triggered when changes to a **live** product's properties take effect online.  
**Prerequisite**: The product is **live** and the "Product Basic" API scope is enabled in Partner Center. For more information, refer to [Access Scope](access-scope).

# 2\. Data business parameters

| **Parameter name** | **Data type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
type

 | 

int

 | 

15

 | 

The ID of this webhook topic, which is 15.

 |
| 

tts\_notification\_id

 | 

string

 | 

"7327112393057371910"

 | 

The ID of this webhook notification.

 |
| 

shop\_id

 | 

string

 | 

"7494049642642441621"

 | 

The shop ID.

 |
| 

timestamp

 | 

int

 | 

1644412885

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

└ product\_id

 | 

string

 | 

"123456789"

 | 

The ID of the product that's being changed.

 |
| 

└ change\_source

 | 

string

 | 

"SELLER\_CENTER"

 | 

The originating source of the change.  
Possible values:  
\- `SELLER_CENTER`  
\- `OPEN_API`

 |
| 

└ changed\_fields

 | 

\[\]string

 | 

\[  
"title",  
"description",  
"main\_images",  
"product\_attributes",  
"size\_chart\_image",  
"size\_chart\_template",  
"others"  
\]

 | 

The product properties that were changed.  
Possible values:  
\- `title`: The product name was changed.  
\- `description`: The product description was changed.  
\- `main_images`: Product images that appear in the image gallery were added, removed, or changed.  
\- `product_attributes`: Product attributes were added, removed, or edited.  
\- `size_chart_image`: The size chart image was added, removed, or changed.  
\- `size_chart_template`: The associated size chart template was removed or its details were edited, or a new size chart template was associated.  
\- `others`: Other product properties not specified above were changed.

 |
| 

└ update\_time

 | 

int

 | 

1627587600

 | 

The time when the change occurred. Unix timestamp.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 15,
  "tts_notification_id": "7327112393057371910",
  "shop_id": "7494049642642441621",
  "timestamp": 1644412885,
  "data": {
    "product_id": "123456789",
    "change_source": "SELLER_CENTER",
    "changed_fields": [
      "title",
      "description",
      "main_images",
      "product_attributes",
      "size_chart_image",
      "size_chart_template",
      "others"
    ],
    "update_time": 1627587600
  }
}
```
