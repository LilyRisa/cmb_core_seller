# (52) Global listing method change

> Source: https://partner.tiktokshop.com/docv2/page/g2wnrb57
> Section: Webhooks
> Scraped: 2026-05-21T00:31:06.017Z

---

# 1\. Trigger scenario

This webhook is triggered when the global listing method for a shop changes.  
**Applicable only for global sellers.**  
**Prerequisite**: The "Product Basic" API scope is enabled in Partner Center. For more information, refer to [Access Scope](https://partner.tiktokshop.com/docv2/page/access-scope).

# 2\. Data business parameters

| **Parameter name** | **Data type** | **Sample** | **Description** |
| --- | --- | --- | --- |
| 
type

 | 

int

 | 

52

 | 

The ID of this webhook topic, which is 52.

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

timestamp

 | 

int

 | 

1644412885

 | 

The time when this webhook is triggered. Unix timestamp.

 |
| 

seller\_open\_id

 | 

string

 | 

"VIyStQAAAADCBQ40s4TzOSSOEIW-bM5O9cod3vK8OytW8m-bnBYlXw"

 | 

The open\_id of the seller. For more information, refer to [Authorization overview](https://partner.tiktokshop.com/docv2/page/authorization-overview-202407).

 |
| 

data

 | 

object

 | 

 | 

 |
| 

└ global\_product\_id

 | 

string

 | 

"1729715829872102020"

 | 

The global product ID associated with the local product.

 |
| 

└ local\_product\_id

 | 

\[\]string

 | 

\["1731711955927208538"\]

 | 

The local product ID.

 |
| 

└ listing\_method

 | 

string

 | 

"GLOBAL\_PUBLISHING"

 | 

The listing method applicable to the local product.  
Possible values:  
  
\* GLOBAL\_PUBLISHING: Create a global product, then publish it to target local markets.  
\* LOCAL\_REPLICATION: Create local product, then replicate it to other target local markets.

 |

* * *

## Event example

JSON

Word Wrap

```
{  
    "type": 52,  
    "tts_notification_id": "7327112393057371910",  
    "timestamp": 1644412885,  
    "seller_open_id": "VIyStQAAAADCBQ40s4TzOSSOEIW-bM5O9cod3vK8OytW8m-bnBYlXw"  
    "data": {  
        "global_product_id": "1729715829872102020",  
        "local_product_id": ["1731711955927208538"],  
        "listing_method": "GLOBAL_PUBLISHING"  
            }  
        }  
    }  
}
```
