# shop_authorization_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Shopee Push
> Scraped: 2026-05-20T20:45:02.815Z

---

Push Mechanism

Shopee Push

\>

shop\_authorization\_push

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

shop\_authorization\_push

Last Updated: 2 Sep 2022

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

shop\_authorization\_push

 |
| 

Push Mechanism Code

 | 

1

 |
| 

Push Mechanism Description

 | 

This push allows you to be notified once shops or merchants are authorized to your app

 |
| 

Push Mechanism Subscription Rules

 | 

Original/ERP System/Seller In House System/Product Management/Order Management/Accounting And Finance/Marketing/Customer Service/Customized APP/Ads Service/Consignment Service System/Seller Logistics/Custom APP/Swam ERP/Livestream Management/Ads Facil/Affiliate Marketing Solution Management/Shopee Video Management/Auto Parts Installation (ISP)

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

partner\_id

 | 

int

 | 

200000

 | 

Shopee's unique identifier for your App. Partner ID is assigned upon registration is successful.  

 |
| 

code

 | 

int

 | 

1

 | 

Shopee's unique identifier for a push notification  

 |
| 

timestamp

 | 

timestamp

 | 

1610000000

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

shop\_id

 | 

int

 | 

600000

 | 

\[**Optional**\]

Shopee's unique identifier for a shop. It indicates which shop has been authorized.  

 |
| 

shop\_id\_list

 | 

int\[\]

 | 

\[6000000,60000001\]

 | 

\[**Optional**\]  
If the seller uses a main account to authorize multiple shops at the same time, there will be this parameter to indicate all authorized shops  

 |
| 

merchant\_id

 | 

int

 | 

600000

 | 

\[**Optional**\]

Shopee's unique identifier for a merchant. It indicates which merchant has been authorized.  

 |
| 

merchant\_id\_list

 | 

int\[\]

 | 

\[6000000,60000001\]

 | 

\[**Optional**\]

If the seller uses a main account to authorize multiple merchants at the same time, there will be this parameter to indicate all authorized merchants  

 |
| 

authorize\_type

 | 

string

 | 

shop authorization by user

 | 

In which way shops are authorized to App

 |
| 

extra

 | 

string

 | 

shop id 600000 (SG) has been authorized

 | 

Details of the authorization  

 |
| 

main\_account\_id

 | 

int

 | 

60000

 | 

\[**Optional**\]

Shopee's unique identifier for a main account.  
If a seller uses a main account to authorize, it would have this parameter to indicate which main account seller use to authorize  

 |
| 

success

 | 

int

 | 

1

 | 

Indicates if the push is success

 |

## Push Contents

Collapse

Seller shop authorization

Json

```
{"data":{"authorize_type":"shop authorization by user","extra":"shop id 600000 (SG) has been authorized successfully","shop_id":60011111,"success":1},"partner_id":2000002,"code":1,"timestamp":1660616278}
```

  

Seller uses main account to authorize shops

Json

```
{"data":{"authorize_type":"shop authorization by user","extra":"Shop has been authorized successfully","main_account_id":68272,"shop_id_list":[62000001,62000002,62000003,62000004],"success":1},"partner_id":2000002,"code":1,"timestamp":1660616631}
```

  

Seller uses main account to authorize a merchant

Json

```
{"data":{"authorize_type":"merchant authorization by user","extra":"merchant id 600000 has been authorized successfully","merchant_id":600222872,"success":1},"partner_id":2000007,"code":1,"timestamp":1660616278}
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
