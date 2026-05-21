# (64) Aftersales Request Status Update

> Source: https://partner.tiktokshop.com/docv2/page/h4cxaxub
> Section: Webhooks
> Scraped: 2026-05-21T00:31:57.489Z

---

# 1\. Trigger scenario

The **Aftersales Request Status Update** webhook is triggered in two scenarios:

1.  Buyer submits a return request in their TikTok user app.
    
2.  A seller makes a decision on whether to approve or reject the request.
    

> **Note:** This is considered the **first-review** stage of the return journey. If the Afterales request is approved, it will enter the **second-review** stage, which is waiting for the seller to make a decision to approve or reject the returned items.

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

**`64`**

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

Unique identifier of the aftersales request.

 |
| 

└ return\_role

 | 

String

 | 

`BUYER`

 | 

Role that initiated the return flow. Possible enumerations:  
  
\* `BUYER`  
\* `SELLER`

 |
| 

└ aftersales\_request\_status

 | 

String

 | 

`PENDING_REQUEST_REVIEW`

 | 

Current aftersales request status. Possible enumerations:  
  
\* `PENDING_REQUEST_REVIEW`  
\* `REQUEST_REVIEW_COMPLETED`

 |
| 

└ aftersales\_request\_create\_time

 | 

Int

 | 

`1627587600`

 | 

Creation time of the aftersales request in Unix seconds.

 |
| 

└ aftersales\_request\_update\_time

 | 

Int

 | 

`1644412885`

 | 

Last update time of the aftersales request in Unix seconds.

 |

## Event example

JSON

Word Wrap

```
{
  "type": 64,
  "tts_notification_id": "1111111111",
  "shop_id": "1234567890",
  "timestamp": 1644412885,
  "data": {
    "aftersales_request_id": "576486316948490001",
    "return_role": "BUYER",
    "aftersales_request_status": "PENDING_REQUEST_REVIEW",
    "aftersales_request_create_time": 1627587600,
    "aftersales_request_update_time": 1644412885
  }
}
```
