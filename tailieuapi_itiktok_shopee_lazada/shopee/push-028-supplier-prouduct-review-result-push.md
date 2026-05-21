# supplier_prouduct_review_result_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Consignment Service Push
> Scraped: 2026-05-20T20:45:08.837Z

---

Push Mechanism

Consignment Service Push

\>

supplier\_prouduct\_review\_result\_push

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

supplier\_prouduct\_review\_result\_push

Last Updated: 27 Apr 2025

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

supplier\_prouduct\_review\_result\_push

 |
| 

Push Mechanism Code

 | 

19

 |
| 

Push Mechanism Description

 | 

get message when supplier product review result has change

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

1s,3s,5s

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

18

 | 

Shopee's unique identifier for a push notification  

 |
| 

timestamp

 | 

timestamp

 | 

 | 

Timestamp that indicates the message was sent.  

 |
| 

data

 | 

object\[\]

 | 

 | 

 |
| 

supplier\_product\_id

 | 

string

 | 

 | 

货品id  

 |
| 

supplier\_product\_model\_id

 | 

string

 | 

 | 

货品规格id  

 |
| 

approval\_result

 | 

string

 | 

 | 

审核结果，值为下列结果之一："Pending Info Revision""Pending Sample Providing""Pending Sample Revision""Selected""Rejected"  

 |
| 

approval\_time

 | 

timestamp

 | 

 | 

审核时间  

 |
| 

supplier\_id

 | 

int32

 | 

 | 

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

2024-03-27

 | 

New Push

 |
