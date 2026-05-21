# Quality engine incident reason code

> Source: https://partner.tiktokshop.com/docv2/page/quality-engine-incident-reason-code
> Section: Developer Guide
> Scraped: 2026-05-21T00:26:48.345Z

---

For developers who have integrated with Quality Engine, when there are some incidents, developers need to sync incident reason by reason code of the list. It can help the system to identify risk order and A-LOC order with higher accuracy and then we can provide more efficient suggestions for sellers/developers to resolve the issues. And it also is very helpful to improve our system performance.  
To sync reason code, developers need:

1.  Syncing enumeration of reason code on the list via Quality Engine data exchanging API.
2.  Using codes on the list, any codes out of the list will be forbidden to sync by Quality Engine.
3.  Using correct code, developers need to pay attention to the description of code and sync the most reasonable code for the specific incident.
4.  Using the code which is suitable for the rule, every reason code has its suitable rules, developers need to follow the mapping relationship on the list.

Reason Code List:

1.  Reason codes for incident that there is no related order in DTC channel. (Orphan order)

| Reason Category | Suitable for Rules | Reason Code | Orphan Reason Desc |
| --- | --- | --- | --- |
| 
Orphan Order

 | 

R1/R2/A1

 | 

**SELLER\_TIKTOKSHOP\_AUTHORIZATION\_REVOKED**

 | 

Seller revoked TikTok shop authorization for the App

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**SELLER\_UNINSTALLED\_DTC\_APP**

 | 

Seller uninstalled the App from the Channel platform

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**SELLER\_NOT\_GRANT\_DTC\_PERMISSION**

 | 

Seller did not grant corresponding APIs permission of the Channel platform for App

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**SELLER\_DTC\_STORE\_UNAVAILABLE**

 | 

Seller's Channel store is unavailable

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**SELLER\_TIKTOKSHOP\_STORE\_INACTIVE**

 | 

Seller's TikTok shop store is inactive

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**SELLER\_DTC\_NO\_SKU**

 | 

No corresponding SKU found for seller's Channel products

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**SELLER\_DTC\_ABNORMAL\_PRODUCT\_STATUS**

 | 

Abnormal product status on seller's Channel platform

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**SELLER\_DTC\_PRODUCT\_OUT\_OF\_STOCK**

 | 

Products on seller's Channel platform are out of stock

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**SELLER\_TIKTOKSHOP\_ORDER\_AMOUNT\_ZERO**

 | 

Order amount in seller's TikTok shop orders is 0

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**SELLER\_APP\_OWE\_FEE**

 | 

Seller used the App but did not renew or out of quota

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**SELLER\_ABANDON\_SYNC\_TIKTOKSHOP\_ORDER**

 | 

Seller opted not to synchronize TikTok shop orders

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**APP\_MISSED\_WEBHOOK**

 | 

App missed TikTok shop order webhooks

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**APP\_ORDER\_RETRIEVE\_API\_FAILED**

 | 

App failed to retrieve orders by calling TikTok shop API

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**APP\_ORDER\_RETRIEVE\_API\_NOT\_MATCH\_DTC\_FIELD**

 | 

The order information retrieved by the App through the TikTok shop API is incomplete

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**APP\_CALL\_DTC\_CREATE\_ORDER\_API\_FAILED**

 | 

App failed to create orders by calling Channel API

 |
| 

Orphan Order

 | 

R1/R2/A1

 | 

**OTHER**

 | 

Other

 |

1.  Reason code for incident that order is shipped in DTC channel but not shipped in TikTok Shop.

| Reason Category | Suitable for Rules | Reason Code | Pre-RTS Desc |
| --- | --- | --- | --- |
| 
Pre-RTS

 | 

A3/R5/R6

 | 

**INVALID\_TRACKING\_NUMBER**

 | 

Invalid Tracking Number entered in DTC:  
\- tracking number has extra spaces or tabs  
\- tracking number is incorrect but accepted by DTC  
TTS rejects this fulfillment

 |
| 

Pre-RTS

 | 

A3/R5/R6

 | 

**UNSUPPORTED\_CARRIER\_TRACKING\_NUMBER**

 | 

Supported carrier by DTC but unsupported carrier in TTS  
TTS rejects this fulfillment

 |
| 

Pre-RTS

 | 

A3/R5/R6

 | 

**ORDER\_NOT\_ELIGIBLE\_FOR\_FULFILLMENT**

 | 

Package shipped in DTC but not TTS because TTS has a  
cancellation or refund request for that order.  
TTS rejects this fulfillment

 |
| 

Pre-RTS

 | 

A3/R5/R6

 | 

**FULFILL\_UNIT\_OR\_ORDER\_NUMBER\_NOT\_COMBINED\_CORRECTLY**

 | 

Package or Order fulfillment call to TTS failed because  
the tracking number has been used in another order without  
combining orders.

 |
| 

Pre-RTS

 | 

A3/R5/R6

 | 

**FULFILL\_UNIT\_OR\_ORDER\_NUMBER\_NOT\_FOUND**

 | 

Package or Order fulfillment call to TTS failed because seller  
combined or split order in TTS or the DTC before fulfillment.  
TTS rejects this fulfillment

 |
| 

Pre-RTS

 | 

A3/R5/R6

 | 

**FULFILLMENT\_FAILURE\_DUE\_TO\_RATE\_LIMIT**

 | 

Package or Order fulfillment call to TTS failed because app  
rate limit was exceeded and there was no retry

 |
| 

Pre-RTS

 | 

A3/R5/R6

 | 

**FULFILLMENT\_FAILURE\_DUE\_TO\_NO\_RETRY\_LOGIC**

 | 

order fulfillment call was made for this order from the connector app  
but TTS system was down and threw an internal error. Order fulfillment  
was not retried by the connector app

 |
| 

Pre-RTS

 | 

A3/R5/R6

 | 

**FULFILLMENT\_FAILURE\_DUE\_TO\_NO\_API\_CALL**

 | 

No order fulfillment call was made for this order from the connector

 |
