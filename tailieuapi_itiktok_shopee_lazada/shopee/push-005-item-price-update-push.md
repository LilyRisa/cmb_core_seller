# item_price_update_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Product Push
> Scraped: 2026-05-20T20:44:49.018Z

---

Push Mechanism

Product Push

\>

item\_price\_update\_push

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

item\_price\_update\_push

Last Updated: 8 Sep 2025

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

item\_price\_update\_push

 |
| 

Push Mechanism Code

 | 

22

 |
| 

Push Mechanism Description

 | 

Send the push when the seller updates the original\_price of the item or model.

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

item\_id

 | 

int64

 | 

1861418518

 | 

Shopee's unique identifier for an item.  

 |
| 

model\_id

 | 

int64

 | 

8791278571

 | 

Shopee's unique identifier for a model of an item.  

 |
| 

update\_field

 | 

string

 | 

"original\_price"

 | 

The field value we changed. It will be "original\_price" and "local\_price"  

 |
| 

old\_value

 | 

float

 | 

119.99

 | 

The original\_price value before the updates.  

 |
| 

new\_value

 | 

float

 | 

99.99

 | 

The original\_price value after the updates.

 |
| 

update\_time

 | 

timestamp

 | 

1660124246

 | 

The time of original\_price updates.  

 |
| 

shop\_id

 | 

int64

 | 

127449165

 | 

Shopee's unique identifier for a shop.

 |
| 

code

 | 

int32

 | 

22

 | 

Shopee's unique identifier for a push notification.  

 |
| 

timestamp

 | 

timestamp

 | 

1660124246

 | 

Timestamp that indicates the message was sent.  

 |

## Push Contents

Collapse

Action: Seller updates the original\_price of the item or model.

Json

```
{
    "data": {
        "item_id": 1861418518,
        "model_id": 8791278571,
        "update_field": "original_price",
        "old_value": 119.99,
        "new_value": 99.99,
        "update_time": 1660124246
    },
    "shop_id": 127449165,
    "code": 22,
    "timestamp": 1660124246
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

2025-09-08

 | 

The field update\_field now supports a new enum value "local\_price"

 |
| 

2024-06-27

 | 

New Push

 |
