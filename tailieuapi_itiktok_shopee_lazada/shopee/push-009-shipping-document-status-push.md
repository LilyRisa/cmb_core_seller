# shipping_document_status_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Order Push
> Scraped: 2026-05-20T20:44:52.418Z

---

Push Mechanism

Order Push

\>

shipping\_document\_status\_push

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

shipping\_document\_status\_push

Last Updated: 4 Apr 2023

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

shipping\_document\_status\_push

 |
| 

Push Mechanism Code

 | 

15

 |
| 

Push Mechanism Description

 | 

shipping\_document\_status\_push

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

Main Push message data.  

 |
| 

order\_sn

 | 

string

 | 

201118BCKPJQQ8

 | 

Shopee's unique identifier for an order.  

 |
| 

package\_number

 | 

string

 | 

2485710696837122445

 | 

Shopee's unique identifier for the package under an order.  

 |
| 

status

 | 

string

 | 

READY

 | 

The status of the shipping document generation.  

\-READY

\-FAILED

 |
| 

shop\_id

 | 

int

 | 

296363855

 | 

Shopee's unique identifier for a shop. Required param for most APIs.  

 |
| 

code

 | 

int

 | 

15

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

Java

```
{"data": {"ordersn":"201118BCKPJQQ8","package_number":"2485710696837122445","status":"READY"},"shop_id":296363855,"code":15,"timestamp":1660123089}
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

2023-01-12

 | 

New Push

 |
