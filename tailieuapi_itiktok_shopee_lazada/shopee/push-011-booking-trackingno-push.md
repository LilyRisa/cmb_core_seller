# booking_trackingno_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Order Push
> Scraped: 2026-05-20T20:44:54.138Z

---

Push Mechanism

Order Push

\>

booking\_trackingno\_push

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

booking\_trackingno\_push

Last Updated: 2 Jul 2024

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

booking\_trackingno\_push

 |
| 

Push Mechanism Code

 | 

24

 |
| 

Push Mechanism Description

 | 

Get notified immediately when booking tracking numbers are updated so that you can ship promptly, and avoid having to query the v2.logistics.get\_booking\_tracking\_number API repeatedly. This can be useful when logistics partners take some time to update tracking numbers which may be required on shipping documents.

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

booking\_sn

 | 

string

 | 

220809MDBFYFT2

 | 

Shopee's unique identifier for a booking.  

 |
| 

tracking\_number

 | 

string

 | 

BR222263688572VSPXLM71894

 | 

The tracking number of this booking.  

 |
| 

shop\_id

 | 

int64

 | 

296363855

 | 

Shopee's unique identifier for a shop. Required param for most APIs.  

 |
| 

code

 | 

int32

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

## Update Log

Collapse

| 
Date

 | 

Update Details

 |
| --- | --- |
| 

2024-07-02

 | 

New Push

 |
