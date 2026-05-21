# violation_item_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Product Push
> Scraped: 2026-05-20T20:44:48.186Z

---

Push Mechanism

Product Push

\>

violation\_item\_push

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

violation\_item\_push

Last Updated: 20 Feb 2025

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

violation\_item\_push

 |
| 

Push Mechanism Code

 | 

16

 |
| 

Push Mechanism Description

 | 

Get notified when item status becomes BANNED or SHOPEE\_DELETE, or marked as deboost, including the violation type, violation reason, suggestion and fix deadline time.

 |
| 

Push Mechanism Subscription Rules

 | 

ERP System/Seller In House System/Product Management/Marketing/Swam ERP

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

Main Push message data  

 |
| 

item\_id

 | 

int64

 | 

3400133011

 | 

Shopee's unique identifier for an item.  

 |
| 

item\_name

 | 

string

 | 

test

 | 

Name of the item.  

 |
| 

item\_status

 | 

string

 | 

BANNED

 | 

Enumerated type that defines the current status of the item. Applicable values: NORMAL, BANNED, UNLIST, SELLER\_DELETE, SHOPEE\_DELETE, REVIEWING.  

 |
| 

deboost

 | 

boolean

 | 

true

 | 

If deboost is true, means that the item's search ranking is lowered.  

 |
| 

item\_status\_details

 | 

object\[\]

 | 

 | 

 |
| 

violation\_type

 | 

string

 | 

Prohibited Listing

 | 

Violation types defined by Shopee. Applicable values:   

Prohibited Listing

Counterfeit and IP Infringement

Spam

Inappropriate Image

Insufficient Information

Mall Listing Improvement

Other Listing Improvement

 |
| 

violation\_reason

 | 

string

 | 

License Reason

 | 

The reason for violation.  

 |
| 

suggestion

 | 

string

 | 

Upload license

 | 

Shopee provides you with suggestions for modifying items.  

 |
| 

fix\_deadline\_time

 | 

timestamp

 | 

1705227588

 | 

Action required deadline. Empty if no deadline.  

 |
| 

update\_time

 | 

timestamp

 | 

1705054788

 | 

Latest update time.  

 |
| 

deboost\_details

 | 

object\[\]

 | 

 | 

 |
| 

violation\_type

 | 

string

 | 

Prohibited Listing

 | 

Violation types defined by Shopee. Applicable values:   

Prohibited Listing

Counterfeit and IP Infringement

Spam

Inappropriate Image

Insufficient Information

Mall Listing Improvement

Other Listing Improvement

 |
| 

violation\_reason

 | 

string

 | 

Wrong Category

 | 

The reason for violation.  

 |
| 

suggestion

 | 

string

 | 

The item is in wrong category, please update to the suggested\_category

 | 

Shopee provides you with suggestions for modifying items.  

 |
| 

suggested\_category

 | 

object\[\]

 | 

 | 

 |
| 

category\_id

 | 

int64

 | 

107478

 | 

ID for Shopee suggested category.  

 |
| 

category\_name

 | 

string

 | 

Personal Care

 | 

Default name for Shopee suggested category.  

 |
| 

fix\_deadline\_time

 | 

timestamp

 | 

1705202227

 | 

Action required deadline. Empty if no deadline.  

 |
| 

update\_time

 | 

timestamp

 | 

1704943027

 | 

Latest update time.  

 |
| 

shop\_id

 | 

int64

 | 

 | 

Shopee's unique identifier for a shop.  

 |
| 

code

 | 

int32

 | 

 | 

Shopee's unique identifier for a push notification.  

 |
| 

timestamp

 | 

timestamp

 | 

 | 

Timestamp that indicates the message was sent.  

 |

## Push Contents

Collapse

1\. When the item is banned by Shopee:

Java

```
{
    "data":{
        "item_id":3903127451,
        "item_name":"MZH制造商品1",
        "item_status":"BANNED",
        "deboost":false,
        "item_status_details":[
            {
                "violation_type":"Prohibited Listing",
                "violation_reason":"License Reason",
                "suggestion":"Upload license",
                "fix_deadline_time":1705228281,
                "update_time":1705055481
            },
            {
                "violation_type":"Inappropriate Image",
                "violation_reason":"Image Reason",
                "suggestion":"Update image",
                "fix_deadline_time":1705228281,
                "update_time":1705055481
            }
        ]
    },
    "shop_id":602228340,
    "code":16,
    "timestamp":1705055481
}
```

  

2\. When the item is deleted by Shopee:

Java

```
{
    "data":{
        "item_id":3903127452,
        "item_name":"MZH制造商品2",
        "item_status":"SHOPEE_DELETE",
        "deboost":false,
        "item_status_details":[
            {
                "violation_type":"Prohibited Listing",
                "violation_reason":"License Reason",
                "suggestion":"Upload license",
                "fix_deadline_time":1700638193,
                "update_time":1699428593
            }
        ]
    },
    "shop_id":602228340,
    "code":16,
    "timestamp":1699428593
}
```

  

3\. When the item is marked as deboost by Shopee:

Json

```
{
    "data":{
        "item_id":3603542555,
        "item_name":"MZH制造商品3",
        "item_status":"NORMAL",
        "deboost":true,
        "deboosted_details":[
            {
                "violation_type":"Inappropriate Image",
                "violation_reason":"Image Reason",
                "suggestion":"Update image",
                "fix_deadline_time":1704943027,
                "update_time":1704943027
            },
            {
                "violation_type":"Prohibited Listing",
                "violation_reason":"Wrong Category",
                "suggestion":"The item is in wrong category, please update to the suggested_category",
                "suggested_category":[
                    {
                        "category_id":100005,
                        "category_name":"Health"
                    },
                    {
                        "category_id":107478,
                        "category_name":"Personal Care"
                    }
                ],
                "fix_deadline_time":1704943027,
                "update_time":1704943027
            }
        ]
    },
    "shop_id":605748501,
    "code":16,
    "timestamp":1704943027
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

2024-01-18

 | 

New Push

 |
