# brand_register_result

> Source: https://open.shopee.com/push-mechanism/5
> Category: Product Push
> Scraped: 2026-05-20T20:44:47.344Z

---

Push Mechanism

Product Push

\>

brand\_register\_result

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

brand\_register\_result

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

Product Push

 |
| 

Push Mechanism Name

 | 

brand\_register\_result

 |
| 

Push Mechanism Code

 | 

13

 |
| 

Push Mechanism Description

 | 

Get the brand register result

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

 |
| 

shop\_id

 | 

int

 | 

7487788

 | 

Shopee's unique identifier for a shop.  

 |
| 

register\_brand

 | 

object

 | 

 | 

Seller registered brand information.  

 |
| 

brand\_id

 | 

int

 | 

3232262

 | 

The brand id returned by Shopee for the brand registration.  

 |
| 

brand\_name

 | 

string

 | 

ABC

 | 

The brand name applied by the seller.  

 |
| 

register\_result

 | 

object

 | 

 | 

Registration result  

 |
| 

result

 | 

string

 | 

Brand Registration Successfully

 | 

Registration result. Can be Brand Registration Successfully / Brand Registration Reject / Brand combined with an exist brand  

 |
| 

reason

 | 

string

 | 

 | 

More detail about the result.

 |
| 

shop\_id

 | 

int

 | 

7487788

 | 

Shopee's unique identifier for a shop.  

 |
| 

code

 | 

int

 | 

13

 | 

Shopee's unique identifier for a shop.  

 |
| 

timestamp

 | 

timestamp

 | 

1660123176

 | 

Timestamp that indicates the message was sent.  

 |

## Push Contents

Collapse

Whether it is a brand registered by seller centre or open api, you will receive the brand review results (Registration before the toggle is turned on will no longer be pushed)

  

When a seller creates a brand through a seller centre or through v2.product.register\_brand api, the Push mechanism will not push information at this time.

  

The seller will get a brand which status is pending. At this time, it is allowed for the seller to use this brand id to add and update products.

  

1.After the brand is approved, the developer will receive Push messages, such as:

  

Json

```
{"data":{"shop_id":748773,"register_brand":{"brand_id":3232262,"brand_name":"Omiz"},"register_result":{"result":"Brand Registration Successfully","reason":""}},"shop_id":74877883,"code":13,"timestamp":1660123176}
```

  

At this time, the status of the brand will change from pending to normal

  

2\. When the brand is rejected, the developer will receive Push messages, such as:

Json

```
{"data":{"shop_id":832182,"register_brand":{"brand_id":3228538,"brand_name":"Pop Up Squishy"},"register_result":{"result":"Brand Registration Reject","reason":"Merek tidak sah."}},"shop_id":832182994,"code":13,"timestamp":1660122620}
```

  

This brand will no longer be available, and the brand name of products that have used this brand in history will be changed to "No brand"

  

3\. After the brand is merged, the developer will receive Push messages, such as:

Json

```
{"data":{"shop_id":220181,"register_brand":{"brand_id":3228577,"brand_name":"Cavero"},"register_result":{"result":"Brand combined with an exist brand","reason":""},"combined_brand":{"brand_id":1017064,"brand_name":"Cavero"}},"shop_id":220140481,"code":13,"timestamp":1660124291}
```

  

This brand will no longer be available, and product brands that have used this brand in the past will display the new brand name

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
