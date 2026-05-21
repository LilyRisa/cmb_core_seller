# inbound_status_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Consignment Service Push
> Scraped: 2026-05-20T20:45:07.126Z

---

Push Mechanism

Consignment Service Push

\>

inbound\_status\_push

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

inbound\_status\_push

Last Updated: 14 May 2024

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

Consignment Service Push

 |
| 

Push Mechanism Name

 | 

inbound\_status\_push

 |
| 

Push Mechanism Code

 | 

21

 |
| 

Push Mechanism Description

 | 

When the inbound status changed, it will send a push

 |
| 

Push Mechanism Subscription Rules

 | 

Consignment Service System

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

code

 | 

int

 | 

 | 

 |
| 

timestamp

 | 

timestamp

 | 

 | 

 |
| 

supplier\_id

 | 

int

 | 

 | 

 |
| 

data

 | 

object\[\]

 | 

 | 

 |
| 

inbound\_id

 | 

string

 | 

INCNN00001

 | 

inbound 唯一id

 |
| 

inbound\_status

 | 

string

 | 

InboundStatusInTransit

 | 

入库单的状态，枚举：

InboundStatusPendingSupplierDeclare-待申报

InboundStatusInTransit-运输中

 InboundStatusArrived-已到达

 InboundStatusDone-已接收

 InboundStatusRejected-已拒绝

 InboundStatusCancelled-已取消  

 |

## Push Contents

Collapse

{

   "inbound\_id":"INCNN00001",

    "inbound\_status":"InboundStatusInTransit",

}

## Update Log

Collapse

| 
Date

 | 

Update Details

 |
| --- | --- |
| 

2024-03-27

 | 

New Push

 |
