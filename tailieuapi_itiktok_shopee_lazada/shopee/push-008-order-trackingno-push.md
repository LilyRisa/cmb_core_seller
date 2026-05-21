# order_trackingno_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Order Push
> Scraped: 2026-05-20T20:44:51.556Z

---

Push Mechanism

Order Push

\>

order\_trackingno\_push

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

order\_trackingno\_push

Last Updated: 18 Aug 2022

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

order\_trackingno\_push

 |
| 

Push Mechanism Code

 | 

4

 |
| 

Push Mechanism Description

 | 

Get notified immediately when order tracking numbers are updated so that you can ship promptly, and avoid having to query the v2.logistics.get\_tracking\_number API repeatedly. This can be useful when logistics partners take some time to update tracking numbers which may be required on shipping documents.

 |
| 

Push Mechanism Subscription Rules

 | 

Original/ERP System/Seller In House System/Order Management/Swam ERP

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

220809MDBFYFT2

 | 

Shopee's unique identifier for an order.  

 |
| 

forder\_id

 | 

string

 | 

4965804244309504855

 | 

Coming offline

 |
| 

package\_number

 | 

string

 | 

OFG113701539238152

 | 

Shopee's unique identifier for the package under an order.  

 |
| 

tracking\_no

 | 

string

 | 

BR222263688572VSPXLM71894

 | 

The tracking number of this order.  

 |
| 

shop\_id

 | 

int

 | 

296363855

 | 

Shopee's unique identifier for a shop. Required param for most APIs.  

 |
| 

code

 | 

int

 | 

4

 | 

Shopee's unique identifier for a push notification.  

 |
| 

timestamp

 | 

timestamp

 | 

1660123089

 | 

This is to indicate the timestamp of the request.  

 |

## Push Contents

Collapse

Json

```
{"data":{"ordersn":"220809MDBFYFT2","forder_id":"4965804244309504855","package_number":"OFG113701539238152","tracking_no":"BR222263688572VSPXLM71894"},"shop_id":296363855,"code":4,"timestamp":1660123089}
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

2022-08-18

 | 

New Push Mechanism

 |
