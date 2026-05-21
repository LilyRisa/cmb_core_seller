# (12) Return status change

> Source: https://partner.tiktokshop.com/docv2/page/12-return-status-change
> Section: Webhooks
> Scraped: 2026-05-21T00:31:48.114Z

---

# 1\. Trigger scenario

The **return status change** webhook is triggered when the `return_status` of an order changes:

-   The `BUYER` initiates a return or refund request and is pending `SELLER` review: `RETURN_OR_REFUND_REQUEST_PENDING`
-   The `SELLER` declines the `BUYER`'s return or refund request: `REFUND_OR_RETURN_REQUEST_REJECT`
-   The return request is approved and the `SELLER` is waiting for the `BUYER` to return the approved items: `AWAITING_BUYER_SHIP`. If the `BUYER` doesn't ship the items to the `SELLER` before the deadline, the request will be closed automatically.
-   To return the items to the `SELLER`, the `BUYER` drops off the package successfully or the `BUYER` ships the package and uploads the tracking number: `BUYER_SHIPPED_ITEM`
-   The `SELLER` declines the refund request for the return: `REJECT_RECEIVE_PACKAGE`
-   The `SELLER` accepts the refund request or issues a refund for the return: `RETURN_OR_REFUND_REQUEST_SUCCESS`
-   The `BUYER` or `SYSTEM` closes the return or refund request: `RETURN_OR_REFUND__REQUEST_CANCELLED`
-   The return or refund is successful: `RETURN_OR_REFUND_REQUEST_COMPLETE`

Additionally, a `BUYER` may request an identical replacement item instead of a refund:

-   The `BUYER` initiates a replacement request and is pending `SELLER` review: `REPLACEMENT_REQUEST_PENDING`
-   The `SELLER` declines the `BUYER`'s replacement request: `REPLACEMENT_REQUEST_REJECT`
-   The `SELLER` decides to issue a refund to the `BUYER` without replacement: `REPLACEMENT_REQUEST_REFUND_SUCCESS`
-   The `BUYER` cancels the replacement request: `REPLACEMENT_REQUEST_CANCEL`
-   The `SELLER` approves the replacement request: `REPLACEMENT_REQUEST_COMPLETE`

# 2\. Data business parameters

| **Parameter** | **Description** | **Sample** |
| --- | --- | --- |
| 
order\_id

 | 

The identification of a TikTok Shop order

 | 

577087614418520388

 |
| 

return\_role

 | 

Return or refund request user, with possible values:  
  
\* BUYER  
\* SELLER  
\* SYSTEM

 | 

BUYER

 |
| 

return\_type

 | 

The return or refund request type, with possible values:  
  
\* REFUND  
\* REPLACEMENT  
\* RETURN\_AND\_REFUND

 | 

REFUND

 |
| 

return\_status

 | 

The return status for a request, with possible values:  
  
\* AWAITING\_BUYER\_SHIP  
\* BUYER\_SHIPPED\_ITEM  
\* REFUND\_OR\_RETURN\_REQUEST\_REJECT  
\* REJECT\_RECEIVE\_PACKAGE  
\* REPLACEMENT\_REQUEST\_CANCEL  
\* REPLACEMENT\_REQUEST\_COMPLETE  
\* REPLACEMENT\_REQUEST\_PENDING  
\* REPLACEMENT\_REQUEST\_REFUND\_SUCCESS  
\* REPLACEMENT\_REQUEST\_REJECT  
\* RETURN\_OR\_REFUND\_REQUEST\_CANCEL  
\* RETURN\_OR\_REFUND\_REQUEST\_COMPLETE  
\* RETURN\_OR\_REFUND\_REQUEST\_PENDING  
\* RETURN\_OR\_REFUND\_REQUEST\_SUCCESS

 | 

RETURN\_OR\_REFUND\_REQUEST\_PENDING

 |
| 

return\_id

 | 

The identifier of a specific return

 | 

4035318504086604100

 |
| 

create\_time

 | 

The time when the request was created.

 | 

1627587600

 |
| 

update\_time

 | 

The time when return order status update, represented as a Unix timestamp (seconds).

 | 

1627587600

 |

## Event example

JSON

Word Wrap

```
{  
  "type": 12,  
  "tts_notification_id": "7327112393057371910",  
  "shop_id": "7494049642642441621",  
  "timestamp": 1644412885,  
  "data": {  
    "order_id": "576486316948490001",  
    "return_role": "BUYER",  
    "return_type": "REFUND",  
    "return_status": "RETURN_OR_REFUND_REQUEST_PENDING",  
    "return_id": "576486316948490001",  
    "create_time": 1627587600  
    "update_time": 1644412885  
  }  
}
```
