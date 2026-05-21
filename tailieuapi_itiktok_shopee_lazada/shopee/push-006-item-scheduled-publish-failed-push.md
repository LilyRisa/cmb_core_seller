# item_scheduled_publish_failed_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Product Push
> Scraped: 2026-05-20T20:44:49.853Z

---

Push Mechanism

Product Push

\>

item\_scheduled\_publish\_failed\_push

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

item\_scheduled\_publish\_failed\_push

Last Updated: 25 Sep 2024

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

Product Push

 |
| 

Push Mechanism Name

 | 

item\_scheduled\_publish\_failed\_push

 |
| 

Push Mechanism Code

 | 

27

 |
| 

Push Mechanism Description

 | 

Get notified when the product fails to publish at scheduled publish time

 |
| 

Push Mechanism Subscription Rules

 | 

ERP System/Seller In House System/Product Management/Swam ERP

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

Main Push message info.

 |
| 

shop\_id

 | 

int64

 | 

220904434

 | 

Shopee's unique identifier for a shop.

 |
| 

item\_id

 | 

int64

 | 

885138337

 | 

Shopee's unique identifier for an item.

 |
| 

scheduled\_publish\_time

 | 

timestamp

 | 

1725922200

 | 

Scheduled publish time of this item.

 |
| 

shop\_id

 | 

int64

 | 

220904434

 | 

Shopee's unique identifier for a shop.

 |
| 

code

 | 

int32

 | 

27

 | 

Shopee's unique identifier for a push notification.

 |
| 

timestamp

 | 

timestamp

 | 

1725922200

 | 

Timestamp that indicates the message was sent.

 |

## Push Contents

Collapse

If the product fails to publish at scheduled publish time：

  

Json

```
{"data":{"shop_id":220904434,"item_id":885138337,"scheduled_publish_time":1725922200},"shop_id":220904434,"code":27,"timestamp":1725922200}
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

2024-09-25

 | 

New Push

 |
