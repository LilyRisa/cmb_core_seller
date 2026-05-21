# shop_authorization_canceled_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Shopee Push
> Scraped: 2026-05-20T20:45:03.647Z

---

Push Mechanism

Shopee Push

\>

shop\_authorization\_canceled\_push

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

shop\_authorization\_canceled\_push

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

shop\_authorization\_canceled\_push

 |
| 

Push Mechanism Code

 | 

2

 |
| 

Push Mechanism Description

 | 

This push allows you to be notified once shops or merchants or users are deauthorized to your app

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

int64

 | 

200000

 | 

Shopee's unique identifier for your App. Partner ID is assigned upon registration is successful.  

 |
| 

code

 | 

int32

 | 

2

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

timestamp: Timestamp that indicates the message was sent.  

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

int64

 | 

600000

 | 

\[**Optional**\]

Shopee's unique identifier for a shop. It indicates which shop has been cancel authorization.  

 |
| 

shop\_id\_list

 | 

int64\[\]

 | 

\[6000000,60000001\]

 | 

\[**Optional**\]

Shopee's unique identifier for a shop. It indicates which shops have been cancel authorization.  

 |
| 

merchant\_id

 | 

int64

 | 

600000

 | 

**\[Optional\]**

Shopee's unique identifier for a merchant. It indicates which merchant has been cancel authorization.

 |
| 

merchant\_id\_list

 | 

int64\[\]

 | 

\[6000000,60000001\]

 | 

**\[Optional\]**

Shopee's unique identifier for a merchant. It indicates which merchants have been cancel authorization.

 |
| 

user\_id

 | 

int64

 | 

368765104

 | 

**\[Optional\]**

Shopee's unique identifier for a user. It indicates which user has been cancel authorization.

 |
| 

user\_id\_list

 | 

int64\[\]

 | 

\[368765100, 368765098, 368765097\]

 | 

**\[Optional\]**

Shopee's unique identifier for a user. It indicates which users have been cancel authorization.

 |
| 

authorize\_type

 | 

string

 | 

shop authorization by user

 | 

In which way the authorization is cancelled  

 |
| 

extra

 | 

string

 | 

shop id 600000 (SG) has been authorized

 | 

Details of the deauthorization  

 |
| 

main\_account\_id

 | 

int64

 | 

60000

 | 

\[**Optional**\]

Shopee's unique identifier for a main account. If a seller uses a main account to cancel authorize, it would have this parameter to indicate which main account seller use to cancel authorize.  

 |
| 

success

 | 

int32

 | 

1

 | 

Indicates if the push is success  

 |

## Push Contents

Collapse

Shop authorization canecled by seller

Json

```
{"data":{"authorize_type":"user cancel shop authorization","success":1,"extra":"shop id 22000000 (VN) has been cancelled its authorization"},"code":2,"partner_id":800000,"timestamp":1653394175}
```

  

Seller uses a main account to cancel the authorization

Json

```
{"data":{"authorize_type":"user cancel merchant authorization","merchant_id_list":[1001000],"main_account_id":19000,"success":1,"extra":"merchant shop cancelled its authorization"},"code":2,"partner_id":800000,"timestamp":1653026849}
```

  

Authorization expired

Json

```
{"data":{"authorize_type":"expiry","shopid":22000000,"success":1,"extra":"The authorization is expired."},"code":2,"partner_id":800000,"timestamp":1653026985}
```

  

Deauthorization because of abnormal shop status

Json

```
{"data":{"authorize_type":"App status is abnormal","shopid":22000000,"success":1,"extra":"Shop ID 22000000 is currently frozen. The authorization cannot be completed."},"code":2,"partner_id":800000,"timestamp":1653026985}
```

  

Deauthorization because Shop and main account are disconnected

Json

```
{"data":{"authorize_type":"shop and main account is disconnected","shopid":22000000,"success":1,"extra":"Shop (ID: 22000000) is disconnected from the main seller account (ID:30000). The authorization cannot be completed."},"code":2,"partner_id":800000,"timestamp":1653026985}
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
