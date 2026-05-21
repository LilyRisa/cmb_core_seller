# package_info_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Order Push
> Scraped: 2026-05-20T20:44:57.460Z

---

Push Mechanism

Order Push

\>

package\_info\_push

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

package\_info\_push

Last Updated: 24 Apr 2026

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

Order Push

 |
| 

Push Mechanism Name

 | 

package\_info\_push

 |
| 

Push Mechanism Code

 | 

47

 |
| 

Push Mechanism Description

 | 

Get notified immediately on package information updates, including Ship By Date and Logistics Channel changes, and Return Code generation for Return To Seller scenarios (Return Code only applies to packages under the SPX Instant & Sameday channel in ID region), so you can take the necessary steps in time.

 |
| 

Push Mechanism Subscription Rules

 | 

ERP System/Seller In House System/Order Management/Swam ERP

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

shop\_id

 | 

int64

 | 

220688102

 | 

Shopee's unique identifier for a shop.

 |
| 

code

 | 

int32

 | 

47

 | 

Shopee's unique identifier for a push notification.

 |
| 

timestamp

 | 

timestamp

 | 

1764569832

 | 

Timestamp that indicates the message was sent.

 |
| 

data

 | 

object

 | 

 | 

 |
| 

order\_sn

 | 

string

 | 

2512017TFPB0HF

 | 

Shopee's unique identifier for an order.

 |
| 

package\_number

 | 

string

 | 

OFG218268963204539

 | 

Shopee's unique identifier for the package under an order.

 |
| 

changed\_fields

 | 

string\[\]

 | 

\["ship\_by\_date"\]

 | 

Returns value: ship\_by\_date, logistics\_channel\_id, or return\_code, depending on which field has been updated. Both values will be returned if both fields have been updated.

 |
| 

old

 | 

object

 | 

 | 

Previous Ship By Date and logistics channel assigned to order.

 |
| 

logistics\_channel\_id

 | 

int64

 | 

70124

 | 

Shopee's unique identifier for the previous logistics channel assigned to the order.

 |
| 

ship\_by\_date

 | 

timestamp

 | 

1764746165

 | 

Previous Ship By Date assigned to the order.

 |
| 

return\_code

 | 

string

 | 

""

 | 

The previous Return Code of the package.

  

Note: Since return\_code is generated only once and will not change, this field will always be empty when return\_code is pushed.

 |
| 

new

 | 

object

 | 

 | 

New Ship By Date and logistics channel assigned to order.

 |
| 

logistics\_channel\_id

 | 

int64

 | 

70124

 | 

Shopee's unique identifier for the new logistics channel assigned to the order.

 |
| 

ship\_by\_date

 | 

timestamp

 | 

1764573365

 | 

New Ship By Date assigned to the order.

 |
| 

return\_code

 | 

string

 | 

1234

 | 

The OTP generated after the package enters the RTS (Return to Seller) process. Sellers need to provide this OTP to the driver to complete the return confirmation.

  

**Note:** This field only applies to packages under the SPX Instant& Sameday channel in ID region.

 |
| 

update\_time

 | 

timestamp

 | 

1764569831

 | 

Timestamp that indicates the last time that there was a change in value of ship\_by\_date or logistics\_channel\_id.

 |

## Push Contents

Collapse

Java

```
{
    "data": {
        "changed_fields": [
            "ship_by_date"
        ],
        "old": {
            "logistics_channel_id": 70124,
            "ship_by_date": 1764573365
        },
        "new": {
            "logistics_channel_id": 70124,
            "ship_by_date": 1764746165
        },
        "order_sn": "2512017TFPB0HF",
        "package_number": "OFG218268963204539",
        "update_time": 1764569831
    },
    "shop_id": 220688102,
    "code": 47,
    "timestamp": 1764569832
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

2026-04-24

 | 

Support return\_code field generation push notification

 |
| 

2025-12-18

 | 

New Push

 |
