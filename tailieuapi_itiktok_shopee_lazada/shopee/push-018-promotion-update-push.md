# promotion_update_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Marketing Push
> Scraped: 2026-05-20T20:45:00.120Z

---

Push Mechanism

Marketing Push

\>

promotion\_update\_push

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

promotion\_update\_push

Last Updated: 18 Aug 2022

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

promotion\_update\_push

 |
| 

Push Mechanism Code

 | 

9

 |
| 

Push Mechanism Description

 | 

Get the promotion update

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

122406687

 | 

Shopee's unique identifier for a shop.  

 |
| 

promotion\_id

 | 

int

 | 

965808642693807

 | 

The identity of item promotion.  

 |
| 

promotion\_type

 | 

string

 | 

seller\_discount

 | 

The type of promotion.Promotion type：seller\_discount / product\_promotion\_SG / product\_promotion\_MY / product\_promotion\_ID / product\_promotion\_VN / product\_promotion\_TW / product\_promotion\_TH / product\_promotion\_PH / flash\_sale (contains: in\_shop\_flash\_sale, flash\_sale, brand\_sale) / add\_on\_deal\_main / add\_on\_deal\_sub / bundle\_deal / group\_buy  

 |
| 

end\_time

 | 

timestamp

 | 

1660123171

 | 

Promotion end time.  

 |
| 

action

 | 

string

 | 

promo\_time\_updated

 | 

The action of the event. Action: added\_in\_promo / removed\_from\_promo / promo\_time\_updated  

 |
| 

item\_id

 | 

int

 | 

89526059511

 | 

Shopee's unique identifier for an item.  

 |
| 

variation\_id

 | 

int

 | 

163967197734

 | 

Shopee's unique identifier for a variation of an item.  

 |
| 

shop\_id

 | 

int

 | 

122406687

 | 

Shopee's unique identifier for a shop.  

 |
| 

code

 | 

int

 | 

9

 | 

Shopee's unique identifier for a push notification.  

 |
| 

timestamp

 | 

timestamp

 | 

1660123173

 | 

Timestamp that indicates the message was sent.  

 |

## Push Contents

Collapse

We will push messages when these three events occur：

-   item is added in a promotion
-   item is removed from a promotion
-   promotion start /end time is updated

  

Push logic:

1.when item is added in a promotion:

-   for reserved stock promotion（flash\_sale/product\_promotion），reserved\_stock = reserved stock qty ；
-   for non-reserved stock promotion，reserved\_stock field won't return
-   action = added\_in\_promo；

  

Json

```
{"data":{"shop_id":712661865,"promotion_id":22775521,"promotion_type":"bundle_deal","start_time":1660124460,"end_time":1661943600,"action":"added_in_promo","item_id":14662380213,"variation_id":0},"shop_id":712661865,"code":9,"timestamp":1660124466}
```

  

2.when item is removed form a promotion:

-   for reserved stock promotion（FS/PP），reserved\_stock = the stock qty (the latest value)；
-   action= removed\_from\_promo；
-   there is one cases when item is removed form a promotion:  
    item is rejected/deleted，promotion still valid, notification will return the unique item that removed from a promotion;  
    

Json

```
{"data":{"shop_id":526225965,"promotion_id":668908774363077,"promotion_type":"seller_discount","action":"removed_from_promo","item_id":20332329221,"variation_id":145413438373},"shop_id":526225965,"code":9,"timestamp":1660124435}
```

  

3.when promotion start/end time is update (include upcoming and ongoing promotion):

-   promotion\_status = normal；
-   action= promo\_time\_updated;
-   start\_time/ end\_time  return the updated start/end  time；
-   no reserved stock field

  

Json

```
{"data":{"shop_id":722406687,"promotion_id":665808642693807,"promotion_type":"seller_discount","end_time":1660123171,"action":"promo_time_updated","item_id":19526059511,"variation_id":163767197734},"shop_id":722406687,"code":9,"timestamp":1660123173}
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

2022-08-18

 | 

New Push Mechanism

 |
