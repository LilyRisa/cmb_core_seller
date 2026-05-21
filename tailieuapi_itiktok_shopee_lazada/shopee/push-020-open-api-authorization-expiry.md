# open_api_authorization_expiry

> Source: https://open.shopee.com/push-mechanism/5
> Category: Shopee Push
> Scraped: 2026-05-20T20:45:01.956Z

---

Push Mechanism

Shopee Push

\>

open\_api\_authorization\_expiry

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

open\_api\_authorization\_expiry

Last Updated: 29 Dec 2025

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

Shopee Push

 |
| 

Push Mechanism Name

 | 

open\_api\_authorization\_expiry

 |
| 

Push Mechanism Code

 | 

12

 |
| 

Push Mechanism Description

 | 

Push shops, merchants, and users whose authorization expires within a week.

 |
| 

Push Mechanism Subscription Rules

 | 

ERP System/Seller In House System/Product Management/Order Management/Accounting And Finance/Marketing/Customer Service/Customized APP/Ads Service/Consignment Service System/Seller Logistics/Custom APP/Swam ERP/Livestream Management/Ads Facil/Affiliate Marketing Solution Management/Shopee Video Management/Auto Parts Installation (ISP)

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

12

 | 

Shopee's unique identifier for a push notification  

 |
| 

timestamp

 | 

timestamp

 | 

1568606634

 | 

Timestamp that indicates the message was sent.  

 |
| 

data

 | 

object\[\]

 | 

 | 

 |
| 

merchant\_expire\_soon

 | 

int64\[\]

 | 

\[123123,123123,4342,3242342\]

 | 

The merchant id of the merchants whose authorization expires within one week.

 |
| 

shop\_expire\_soon

 | 

int64\[\]

 | 

\[23213,243242,342343,42342345656,45345\]

 | 

The shop id of the shops whose authorization expires within one week.  

 |
| 

user\_expire\_soon

 | 

int64\[\]

 | 

\[368765104, 368765105, 368765106\]

 | 

The user\_id of the users whose authorization expires within one week.

 |
| 

expire\_before

 | 

timestamp

 | 

1619740800

 | 

The expiration time of pushed merchants and shops is before this time  

 |
| 

page\_no

 | 

int32

 | 

1

 | 

 |
| 

total\_page

 | 

int32

 | 

2

 | 

 |

## Push Contents

Collapse

  

Json

```
{
    "code": 12,
    "timestamp": 1568606634,
    "data": {
        "merchant_expire_soon":[123123,123123,4342,3242342],
        "shop_expire_soon": [23213,243242,342343,42342345656,45345],
        "user_expire_soon": [368765104,368765105,368765106],
        "expire_before": 1619740800, 
        "page_no":1,
        "total_page": 2 
    }
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

2022-08-18

 | 

New Push Mechanism

 |
