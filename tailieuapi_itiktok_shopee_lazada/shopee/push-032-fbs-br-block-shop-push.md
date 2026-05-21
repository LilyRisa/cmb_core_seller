# fbs_br_block_shop_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Fulfillment by Shopee Push
> Scraped: 2026-05-20T20:45:12.233Z

---

Push Mechanism

Fulfillment by Shopee Push

\>

fbs\_br\_block\_shop\_push

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

fbs\_br\_block\_shop\_push

Last Updated: 13 Aug 2025

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

Fulfillment by Shopee Push

 |
| 

Push Mechanism Name

 | 

fbs\_br\_block\_shop\_push

 |
| 

Push Mechanism Code

 | 

34

 |
| 

Push Mechanism Description

 | 

Get notified on FBS shop would be blocked due to invoice error. It is not allowed to raise new Inbound Request and not allow warehouse stock being sellable.

 |
| 

Push Mechanism Subscription Rules

 | 

ERP System/Seller In House System

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

60s,300s,1800s

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

code

 | 

int32

 | 

 | 

Shopee's unique identifier for a push notification.

 |
| 

timestamp

 | 

int64

 | 

 | 

Timestamp that indicates the message was sent.

 |
| 

shop\_id

 | 

int64

 | 

 | 

Shopee's unique identifier for a shop.

 |
| 

data

 | 

object

 | 

 | 

Main Push message data

 |
| 

shop\_id

 | 

int64

 | 

 | 

Shopee's unique identifier for a shop.

 |

## Push Contents

Collapse

## Update Log

Collapse

| 
Date

 | 

Update Details

 |
| --- | --- |
| 

2025-08-13

 | 

New Push

 |
