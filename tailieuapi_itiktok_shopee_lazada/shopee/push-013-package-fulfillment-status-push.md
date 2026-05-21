# package_fulfillment_status_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Order Push
> Scraped: 2026-05-20T20:44:55.795Z

---

Push Mechanism

Order Push

\>

package\_fulfillment\_status\_push

Basics

Push Parameters

Push Contents

Update Log

Product Push

-   reserved\_stock\_change\_push
-   video\_upload\_push
-   brand\_register\_result
-   violation\_item\_push
-   item\_price\_update\_push
-   item\_scheduled\_publish\_failed\_push

Order Push

-   order\_status\_push
-   order\_trackingno\_push
-   shipping\_document\_status\_push
-   booking\_status\_push
-   booking\_trackingno\_push
-   booking\_shipping\_document\_status\_push
-   package\_fulfillment\_status\_push
-   courier\_delivery\_binding\_status\_push
-   package\_info\_push

Return Push

-   return\_updates\_push

Marketing Push

-   item\_promotion\_push
-   promotion\_update\_push

Shopee Push

-   shopee\_updates
-   open\_api\_authorization\_expiry
-   shop\_authorization\_push
-   shop\_authorization\_canceled\_push
-   shop\_penalty\_update\_push
-   video\_upload\_result\_push

Webchat Push

-   webchat\_push

Consignment Service Push

-   inbound\_status\_push
-   supplier\_create\_product\_push
-   supplier\_prouduct\_review\_result\_push
-   purchase\_order\_Push

Fulfillment by Shopee Push

-   fbs\_sellable\_stock
-   fbs\_br\_invoice\_error\_push
-   fbs\_br\_block\_shop\_push
-   fbs\_br\_block\_sku\_push
-   fbs\_br\_invoice\_issued\_push

package\_fulfillment\_status\_push

Last Updated: 17 Jun 2025

## Basics

Collapse

| 
Property

 | 

Value

 |
| --- | --- |
| 

Category

 | 

Order Push

 |
| 

Push Mechanism Name

 | 

package\_fulfillment\_status\_push

 |
| 

Push Mechanism Code

 | 

30

 |
| 

Push Mechanism Description

 | 

Get notified immediately on all package fulfillment status updates. This includes package cancellations that occur before shipping, so that you can take the necessary steps in time.

 |
| 

Push Mechanism Subscription Rules

 | 

ERP System/Seller In House System/Order Management/Swam ERP

 |
| 

Time Out Seconds

 | 

3s

 |
| 

Sequence Guaranteed

 | 

No

 |
| 

Can Repeated Same Message

 | 

Yes

 |
| 

Retry Seconds

 | 

300s,1800s,10800s

 |

## Push Parameters

Collapse

| 
Name

 | 

Type

 | 

Sample

 | 

Description

 |
| --- | --- | --- | --- |
| 

data

 | 

object

 | 

 | 

 |
| 

ordersn

 | 

string

 | 

250421TPSF33R6

 | 

Shopee's unique identifier for an order.

 |
| 

package\_number

 | 

string

 | 

OFG198917831207390

 | 

Shopee's unique identifier for the package under an order.

 |
| 

fulfillment\_status

 | 

string

 | 

LOGISTICS\_REQUEST\_CREATED

 | 

The Shopee fulfillment status for the package. Applicable values: See V2.0 Data Definition - PackageFulfillmentStatus.

 |
| 

update\_time

 | 

int64

 | 

1660123127

 | 

Timestamp that indicates the last time that there was a change in value of package fulfillment status, such as package fulfillment status changed from 'LOGISTICS\_READY' to 'LOGISTICS\_REQUEST\_CREATED'.

 |
| 

shop\_id

 | 

int64

 | 

727720655

 | 

Shopee's unique identifier for a shop.

 |
| 

code

 | 

int64

 | 

30

 | 

Shopee's unique identifier for a push notification.

 |
| 

timestamp

 | 

int64

 | 

1660123127

 | 

Timestamp that indicates the message was sent.  

 |

## Push Contents

Collapse

Json

```
{"data":{"ordersn":"250421TPSF33R6","package_number":"OFG198917831207390","fulfillment_status":"LOGISTICS_REQUEST_CREATED","update_time":1660123127},"shop_id":727720655,"code":30,"timestamp":1660123127}
```

## Update Log

Collapse

| 
Date

 | 

Update Details

 |
| --- | --- |
| 

2025-05-28

 | 

New Push

 |
