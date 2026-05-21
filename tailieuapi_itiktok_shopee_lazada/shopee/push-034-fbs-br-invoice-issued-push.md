# fbs_br_invoice_issued_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Fulfillment by Shopee Push
> Scraped: 2026-05-20T20:45:13.901Z

---

Push Mechanism

Fulfillment by Shopee Push

\>

fbs\_br\_invoice\_issued\_push

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

fbs\_br\_invoice\_issued\_push

Last Updated: 13 Jul 2025

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

fbs\_br\_invoice\_issued\_push

 |
| 

Push Mechanism Code

 | 

31

 |
| 

Push Mechanism Description

 | 

fbs\_br\_invoice\_issued\_push

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

30s,600s,1800s

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

document\_type

 | 

string

 | 

 | 

Remessa 

Return

Symbolic Return

Sale

Entrada

Symbolic Remessa

 |
| 

order\_sn

 | 

string

 | 

 | 

if document\_type = sale, then pass the related Order SN

 |
| 

issue\_date

 | 

timestamp

 | 

 | 

Timestamp that indicates the time invoice was issued.

 |
| 

update\_time

 | 

timestamp

 | 

 | 

Indicate when the push message was triggered

 |
| 

shop\_id

 | 

int64

 | 

 | 

Shopee’s unique identifier for shop. 

E.g. 1660123127

 |
| 

code

 | 

int32

 | 

31

 | 

Shopee’s unique identifier for a push notification.

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

2025-07-11

 | 

New Push

 |
