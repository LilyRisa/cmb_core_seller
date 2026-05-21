# shopee_updates

> Source: https://open.shopee.com/push-mechanism/5
> Category: Shopee Push
> Scraped: 2026-05-20T20:45:01.100Z

---

Push Mechanism

Shopee Push

\>

shopee\_updates

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

shopee\_updates

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

Shopee Push

 |
| 

Push Mechanism Name

 | 

shopee\_updates

 |
| 

Push Mechanism Code

 | 

5

 |
| 

Push Mechanism Description

 | 

The push refers to the Shopee updates under "My Inbox" in Seller Center. The push notification will include the title and content of the update but not the details.

 |
| 

Push Mechanism Subscription Rules

 | 

Original/ERP System/Seller In House System/Customer Service/Brand Membership/Customized APP/Ads Service/Consignment Service System/Custom APP/Swam ERP/Livestream Management/Affiliate Marketing Solution Management/Shopee Video Management

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

int

 | 

5

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

shop\_id

 | 

int

 | 

1231234

 | 

Shopee's unique identifier for a shop. It indicates which shop has been authorized.  

 |
| 

data

 | 

object\[\]

 | 

 | 

 |
| 

actions

 | 

object\[\]

 | 

 | 

It may include multiple Shopee Updates notifications.

 |
| 

content

 | 

string

 | 

Boost your growth when you engage a Shopee-Certified enabler ⭐ Learn how we can help optimise your performance during campaign season 👉

 | 

Content of the Shopee Updates notification

 |
| 

update\_time

 | 

timestamp

 | 

1610000000

 | 

The push time of the Shopee Updates notification.  

 |
| 

title

 | 

string

 | 

Find a Shopee-endorsed enabler

 | 

Title of the Shopee Updates notification  

 |
| 

url

 | 

string

 | 

https://shopee.sg/m/shopee-certified-enablers-q2-2022

 | 

The URL of the Shopee Updates notification  

 |

## Push Contents

Collapse

Json

```
{"code":5, "timestamp":1610000000 ,"shop_id":1231234, "data":{"actions":[{"content":"Boost your growth when you engage a Shopee-Certified enabler ⭐ Learn how we can help optimise your performance during campaign season 👉", "update_time":1610000000, "title":"Find a Shopee-endorsed enabler", "url":"https://shopee.sg/m/shopee-certified-enablers-q2-2022"}, {"content":"Your feedback matters. Help us shape your Shopee experience! Take part in our survey now.", "update_time":16100002222, "title":"We want to hear from you", "url":"https://shopee.qualtrics.com/"}]}
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
