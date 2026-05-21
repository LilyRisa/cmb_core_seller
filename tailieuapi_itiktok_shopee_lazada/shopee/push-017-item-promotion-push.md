# item_promotion_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Marketing Push
> Scraped: 2026-05-20T20:44:59.232Z

---

Push Mechanism

Marketing Push

\>

item\_promotion\_push

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

item\_promotion\_push

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

Marketing Push

 |
| 

Push Mechanism Name

 | 

item\_promotion\_push

 |
| 

Push Mechanism Code

 | 

7

 |
| 

Push Mechanism Description

 | 

Get the promotion update info

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

 |
| 

shop\_id

 | 

int

 | 

180727490

 | 

Shopee's unique identifier for a shop.  

 |
| 

item\_id

 | 

int

 | 

15183813372

 | 

Shopee's unique identifier for an item.  

 |
| 

variation\_id

 | 

int

 | 

12572585702

 | 

Shopee's unique identifier for a variation of an item.  

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

169262197127658

 | 

The identity of item promotion.  

 |
| 

action

 | 

string

 | 

promo\_cancelled

 | 

\`action\` could be one of \`promo\_lock\_stock\`, \`promo\_cancelled\` and \`promo\_end\`.  

 |
| 

update\_time

 | 

timestamp

 | 

1660124045

 | 

Promotion update time.

 |
| 

start\_time

 | 

timestamp

 | 

1660410000

 | 

Promotion start time.

 |
| 

end\_time

 | 

timestamp

 | 

1660453200

 | 

Promotion end time.  

 |
| 

reserved\_stock

 | 

int

 | 

0

 | 

The reserved stock set by the seller for the promotion.

 |
| 

shop\_id

 | 

int

 | 

18072749

 | 

Shopee's unique identifier for a shop.  

 |
| 

code

 | 

int

 | 

7

 | 

Shopee's unique identifier for a push notification.  

 |
| 

timestamp

 | 

timestamp

 | 

1660124045

 | 

Timestamp that indicates the message was sent.  

 |

## Push Contents

Collapse

Push will be triggered whenever an item's stock is locked/unlocked by promotion.

  

Push logic:

1.When the item was added in promotion, the normal stock will deduct the stock set by the promotion stock, At this time, the promotion stock is also reserved\_stock.

action will return promo\_lock\_stock.

Json

```
{"data":{"shop_id":46641242,"item_id":14946910660,"variation_id":58301485734,"promotion_type":"flash_sale","promotion_id":669260829761778,"action":"promo_lock_stock","update_time":1660123421,"start_time":1660453200,"end_time":1660474800,"reserved_stock":100},"shop_id":46641242,"code":7,"timestamp":1660123421}
```

  

2.When the promotion ends or promo\_cancelled, the remaining promotion stock will be added back to the normal stock. At this time, action will return promotion ends or promo\_cancelled.

Json

```
{"data":{"shop_id":180727490,"item_id":15183813372,"variation_id":125725857023,"promotion_type":"flash_sale","promotion_id":669262197127658,"action":"promo_cancelled","update_time":1660124045,"start_time":1660410000,"end_time":1660453200,"reserved_stock":0},"shop_id":180727490,"code":7,"timestamp":1660124045}
```

  

Json

```
{"data":{"shop_id":498881374,"item_id":20109158685,"variation_id":87837495102,"promotion_type":"flash_sale","promotion_id":669083704356214,"action":"promo_end","update_time":1660122009,"start_time":1660114800,"end_time":1660122000,"reserved_stock":0},"shop_id":498881374,"code":7,"timestamp":1660122018}
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
