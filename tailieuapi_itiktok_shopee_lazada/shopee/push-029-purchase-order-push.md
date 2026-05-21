# purchase_order_Push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Consignment Service Push
> Scraped: 2026-05-20T20:45:09.680Z

---

Push Mechanism

Consignment Service Push

\>

purchase\_order\_Push

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

purchase\_order\_Push

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

purchase\_order\_Push

 |
| 

Push Mechanism Code

 | 

20

 |
| 

Push Mechanism Description

 | 

When there is a new purchase order, a push will be sent

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

int32

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

int32

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

purchase\_order\_id

 | 

string

 | 

PO00001

 | 

采购单号  

<path></path>  

 |
| 

purchase\_order\_status

 | 

string

 | 

PoStatusPendingSupplierConfirmation

 | 

采购单据的状态枚举： PoStatusPendingSupplierConfirmation-待确认PoStatusPendingPurchaserConfirmation-待买手确认PoStatusPendingAsnCreation-待asn创建PoStatusPendingShipment-待发货PoStatusPartiallyInbound-部分入库 PoStatusInbound-入库完成 PoStatusCancelled-取消 PoStatusClosed-关闭

 |
| 

purchase\_reason

 | 

string

 | 

FirstPurchase

 | 

采购原因可分为两类，用于区分该采购单是首单还是常规补货单枚举如下：FirstPurchase-首次备货 Replenishment-补货tips:不传参数类型，默认返回全部

 |
| 

purchase\_function\_tag\_list

 | 

string\[\]

 | 

\["JIT","Urgent","InTransitSellable"\]

 | 

采购单据的标签，采购单上可以有多个标签参数为枚举值，枚举映射如下：General-普通采购单JIT-准时化采购（just in time）的采购单 Urgent-加急的采购单InTransitSellable-在途可售的采购单tips:普通采购单没有标签

 |
| 

create\_time

 | 

timestamp

 | 

1234567890

 | 

采购单创建时间

<path></path>  

 |

## Push Contents

Collapse

{

   "purchase\_order\_id":"PO00001",

"purchase\_order\_status":"PoStatusPendingSupplierConfirmation",

   "purchase\_reason":"FirstPurchase",

   "purchase\_function\_tag\_list":\["JIT","Urgent","InTransitSellable"\],

   "create\_time":1234567890,

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
