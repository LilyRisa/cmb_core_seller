# reserved_stock_change_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Product Push
> Scraped: 2026-05-20T20:44:45.630Z

---

Push Mechanism

Product Push

\>

reserved\_stock\_change\_push

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

reserved\_stock\_change\_push

Last Updated: 1 Aug 2023

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

reserved\_stock\_change\_push

 |
| 

Push Mechanism Code

 | 

8

 |
| 

Push Mechanism Description

 | 

Get the reserved stock change log

 |
| 

Push Mechanism Subscription Rules

 | 

Original/ERP System/Seller In House System/Product Management/Marketing/Customized APP/Swam ERP

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

Main Push message info

 |
| 

shop\_id

 | 

int

 | 

12744916

 | 

Shopee's unique identifier for a shop.  

 |
| 

item\_id

 | 

int

 | 

1861418518

 | 

Shopee's unique identifier for an item.  

 |
| 

variation\_id

 | 

int

 | 

8791278571

 | 

Shopee's unique identifier for a variation of an item.  

 |
| 

changed\_values

 | 

object\[\]

 | 

 | 

Changed event.

 |
| 

name

 | 

string

 | 

reserved\_stock

 | 

The field value we changed.

 |
| 

old

 | 

int

 | 

4951

 | 

The value before change.

 |
| 

new

 | 

int

 | 

4950

 | 

The value after change.

 |
| 

promotion\_type

 | 

string

 | 

flash\_sale

 | 

Promotion type：seller\_discount / product\_promotion\_SG / product\_promotion\_MY / product\_promotion\_ID / product\_promotion\_VN / product\_promotion\_TW / product\_promotion\_TH / product\_promotion\_PH / flash\_sale (contains: in\_shop\_flash\_sale, flash\_sale, brand\_sale) / add\_on\_deal\_main / add\_on\_deal\_sub / bundle\_deal / group\_buy / Platform Streaming / Seller Streaming / Campaign (contains: deep\_discount, platform\_sale, low\_price\_promotion)  

 |
| 

promotion\_id

 | 

int

 | 

137899002020202

 | 

The identity of item promotion.  

 |
| 

action

 | 

string

 | 

place\_order

 | 

The action of the event. Can be "place\_order" or "cancel\_order"  

 |
| 

ordersn

 | 

string

 | 

210810QXVJM3EX

 | 

The ordersn associated with the event.  

 |
| 

update\_time

 | 

string

 | 

1660124246

 | 

The time of event.  

 |
| 

shop\_id

 | 

int

 | 

127449165

 | 

Shopee's unique identifier for a shop.  

 |
| 

code

 | 

int

 | 

8

 | 

Shopee's unique identifier for a push notification  

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

Action: place\_order

Json

```
{"data":{"shop_id":1274495,"item_id":18614185187,"variation_id":87912785718,"changed_values":[{"name":"reserved_stock","old":4951,"new":4950}],"promotion_type":"flash_sale","promotion_id":104993304719361,"action":"place_order","ordersn":"220810QXVJM3EX","update_time":1660124246},"shop_id":1274495,"code":8,"timestamp":1660124246}
```

  

  

Action:cancel\_order

Json

```
{"data":{"shop_id":1274495,"item_id":19213740442,"variation_id":154918396482,"changed_values":[{"name":"reserved_stock","old":199,"new":200}],"promotion_type":"flash_sale","promotion_id":103363389812736,"action":"cancel_order","ordersn":"220809KS77DXTS","update_time":1660124321},"shop_id":1274495,"code":8,"timestamp":1660124321}
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

2023-08-01

 | 

update promotion\_type

 |
| 

2022-08-18

 | 

New Push Mechanism

 |
