# return_updates_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Return Push
> Scraped: 2026-05-20T20:44:58.288Z

---

Push Mechanism

Return Push

\>

return\_updates\_push

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

return\_updates\_push

Last Updated: 31 Dec 2024

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

Return Push

 |
| 

Push Mechanism Name

 | 

return\_updates\_push

 |
| 

Push Mechanism Code

 | 

29

 |
| 

Push Mechanism Description

 | 

Get notified when the following fields of Return Refund change: return\_status, return\_solution, seller\_proof\_status, logistics\_status

 |
| 

Push Mechanism Subscription Rules

 | 

ERP System/Seller In House System/Order Management/Customer Service/Ads's Service App/Swam ERP

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

241128EDQ9YKJ0

 | 

Return by default. Shopee's unique serial number identifier for an order.  

 |
| 

return\_sn

 | 

string

 | 

2411280EDT4JRV5

 | 

Return by default. Shopee's unique serial number identifier for a Return Refund request.  

 |
| 

updated\_values

 | 

object\[\]

 | 

 | 

 |
| 

update\_field

 | 

string

 | 

return\_status

 | 

The field whose value is updated. 

\- return\_status

\- return\_solution

\- seller\_proof\_status

\- logistics\_status

 |
| 

old\_value

 | 

string

 | 

JUDGING

 | 

The value before the updates.  

 |
| 

new\_value

 | 

string

 | 

PROCESSING

 | 

The value after the updates.

 |
| 

update\_time

 | 

timestamp

 | 

1732796767

 | 

The time of the updates.

 |
| 

shop\_id

 | 

int64

 | 

220004993

 | 

Shopee's unique identifier for a shop. Required param for most APIs.  

 |
| 

code

 | 

int64

 | 

29

 | 

Shopee's unique identifier for a push notification.  

 |
| 

timestamp

 | 

timestamp

 | 

1732796767

 | 

This is to indicate the timestamp of the request.

 |

## Push Contents

Collapse

Json

```
{
   "data": {
       "order_sn": "241128EDQ9YKJ0",
       "return_sn": "2411280EDT4JRV5",
       "updated_values": [
           {
               "update_field": "return_status",
               "old_value": "JUDGING",
               "new_value": "PROCESSING",
               "update_time": 1732796767
           },
           {
               "update_field": "logistics_status",
               "old_value": "LOGISTICS_NOT_STARTED",
               "new_value": "LOGISTICS_PENDING_ARRANGE",
               "update_time": 1732796767
           }
       ]
   },
   "shop_id": 220004993,
   "code": 29,
   "timestamp": 1732796767
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

2024-12-24

 | 

New Push

 |
