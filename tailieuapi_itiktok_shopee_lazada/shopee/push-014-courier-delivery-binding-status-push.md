# courier_delivery_binding_status_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Order Push
> Scraped: 2026-05-20T20:44:56.621Z

---

Push Mechanism

Order Push

\>

courier\_delivery\_binding\_status\_push

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

courier\_delivery\_binding\_status\_push

Last Updated: 22 Jul 2025

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

courier\_delivery\_binding\_status\_push

 |
| 

Push Mechanism Code

 | 

37

 |
| 

Push Mechanism Description

 | 

Get notified immediately on all first mile tracking number status updates for courier delivery.

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

Main Push message data.

 |
| 

binding\_id

 | 

string

 | 

DCN249237197572VU

 | 

Binding ID.

 |
| 

first\_mile\_tracking\_number

 | 

string

 | 

DCN249237197572VU

 | 

The first mile tracking number.

 |
| 

status

 | 

string

 | 

ORDER\_RECEIVED

 | 

The logistics status for first-mile tracking number. Status could be:  
CANCELED  
CANCELING  
DELIVERED  
NOT\_AVAILABLE  
ORDER\_CREATED  
ORDER\_RECEIVED  
PICKED\_UP

 |
| 

update\_time

 | 

timestamp

 | 

1660123089

 | 

Timestamp that indicates the last time that there was a change in value of first mile tracking number status, such as status changed from 'PICKED\_UP' to 'ORDER\_RECEIVED'.

 |
| 

shop\_id

 | 

int64

 | 

296363855

 | 

Shopee's unique identifier for a shop.

 |
| 

code

 | 

int32

 | 

37

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

Timestamp that indicates the message was sent.

 |

## Push Contents

Collapse

Json

```
{
    "data":{
        "binding_id": "DCN249237197572VU",
        "first_mile_tracking_number": "DCN249237197572VU",
        "status": "ORDER_RECEIVED",
        "update_time": 1660123089
    },
    "shop_id":296363855,
    "code":37,
    "timestamp":1660123089
}
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

2025-06-27

 | 

New Push

 |
