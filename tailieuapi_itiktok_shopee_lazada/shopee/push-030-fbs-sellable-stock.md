# fbs_sellable_stock

> Source: https://open.shopee.com/push-mechanism/5
> Category: Fulfillment by Shopee Push
> Scraped: 2026-05-20T20:45:10.525Z

---

Push Mechanism

Fulfillment by Shopee Push

\>

fbs\_sellable\_stock

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

fbs\_sellable\_stock

Last Updated: 7 Jul 2025

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

fbs\_sellable\_stock

 |
| 

Push Mechanism Code

 | 

36

 |
| 

Push Mechanism Description

 | 

Get notified on the sellable stock in warehouse when there is any changing update in the stock. For the fbs\_sellable\_stock, which means that the sellable stock in the warehouse, exclude the stock in isolated zone and damage zone from the warehouse.

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

data

 | 

object

 | 

 | 

Main Push message info.

 |
| 

list

 | 

object\[\]

 | 

 | 

 |
| 

mt\_sku\_id

 | 

string

 | 

901240351\_4259251776

 | 

Warehouse SKU ID

 |
| 

whs\_id

 | 

string

 | 

BRFSP1

 | 

Warehouse ID

 |
| 

shop\_id

 | 

int64

 | 

302235160

 | 

Shop ID

 |
| 

whs\_region

 | 

string

 | 

BR

 | 

The region that warehouse located

 |
| 

whs\_sellable\_qty

 | 

int64

 | 

50

 | 

Warehouse sellable stock qty

 |
| 

update\_time

 | 

timestamp

 | 

1750746789

 | 

Update time

 |
| 

shop\_id

 | 

int64

 | 

302235160

 | 

Shop ID

 |
| 

code

 | 

int32

 | 

36

 | 

Shopee's unique identifier for a push notification.  

 |
| 

timestamp

 | 

timestamp

 | 

1750746789

 | 

Timestamp that indicates the message was sent.  

 |

## Push Contents

Collapse

Java

```
{
  "data": {
      "list": [
            {
                "mt_sku_id": "901240351_4259251776",
                "whs_id": "BRFSP1",
                "shop_id": 302235160,
                "whs_region": "BR",
                "whs_sellable_qty": 50,
                "update_time": 1750746789
            },
            {
                "mt_sku_id": "901240351_4259251776",
                "whs_id": "BRX",
                "shop_id": 302235160,
                "whs_region": "BR",
                "whs_sellable_qty": 370,
                "update_time": 1750746789
            },
            {
                "mt_sku_id": "901240351_4259251776",
                "whs_id": "BRK",
                "shop_id": 302235160,
                "whs_region": "BR",
                "whs_sellable_qty": 720,
                "update_time": 1750746789
            }
        ]
  },
  "shop_id": 302071623,
  "code": 36,
  "timestamp": 1751860958
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

2025-07-07

 | 

New Push

 |
