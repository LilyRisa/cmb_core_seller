# order_status_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Order Push
> Scraped: 2026-05-20T20:44:50.692Z

---

Push Mechanism

Order Push

\>

order\_status\_push

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

order\_status\_push

Last Updated: 14 Aug 2023

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

order\_status\_push

 |
| 

Push Mechanism Code

 | 

3

 |
| 

Push Mechanism Description

 | 

Get notified immediately on all order status updates. This includes order cancellations that occur before shipping, so that you can take the necessary steps in time.

 |
| 

Push Mechanism Subscription Rules

 | 

Original/ERP System/Seller In House System/Order Management/Customer Service/Swam ERP

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

Main Push message data  

 |
| 

ordersn

 | 

string

 | 

220810QSK8S7BX

 | 

Return by default. Shopee's unique identifier for an order.  

 |
| 

status

 | 

string

 | 

PROCESSED

 | 

Return by default. Enumerated type that defines the current status of the order.  

 |
| 

completed\_scenario

 | 

string

 | 

NORMAL

 | 

To indicate which COMPLETED status order is in.  

  

NORMAL: The order has been completed.  

  

RRAOC: The whole RRAOC (raise return&refund after order completed) progress has been completed.  

 |
| 

update\_time

 | 

timestamp

 | 

1660123127

 | 

Return by default. Timestamp that indicates the last time that there was a change in value of order, such as order status changed from 'Paid' to 'Completed'.  

 |
| 

shop\_id

 | 

int

 | 

727720655

 | 

Shopee's unique identifier for a shop. Required param for most APIs.  

 |
| 

code

 | 

int

 | 

3

 | 

Shopee's unique identifier for a push notification.  

 |
| 

timestamp

 | 

timestamp

 | 

1660123127

 | 

Timestamp that indicates the message was sent.  

 |

## Push Contents

Collapse

Json

```
{"data":{"items":[],"ordersn":"220810QSK8S7BX","status":"PROCESSED","completed_scenario":"","update_time":1660123127},"shop_id":727720655,"code":3,"timestamp":1660123127}
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

2023-08-14

 | 

Shopee now support buyer to raise return& refund after order completed, for order\_status\_push add new field "completed\_scenario" to indicate which COMPLETED status order is in.

 |
| 

2022-08-18

 | 

New Push Mechanism

 |
