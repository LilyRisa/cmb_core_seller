# shop_penalty_update_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Shopee Push
> Scraped: 2026-05-20T20:45:04.496Z

---

Push Mechanism

Shopee Push

\>

shop\_penalty\_update\_push

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

shop\_penalty\_update\_push

Last Updated: 5 Nov 2024

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

shop\_penalty\_update\_push

 |
| 

Push Mechanism Code

 | 

28

 |
| 

Push Mechanism Description

 | 

Get notified when shop's penalty such as penalty point or punishment tier are updated.

 |
| 

Push Mechanism Subscription Rules

 | 

Seller In House System/Product Management/Order Management/ERP System/Swam ERP/Livestream Management/Affiliate Marketing Solution Management/Shopee Video Management

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

Main Push message info.  

 |
| 

action\_type

 | 

int32

 | 

 | 

The action type of the event：  

1: Penalty Point Issued

2: Penalty Point Removed

3: Punishment Tier Update

 |
| 

points\_issued\_data

 | 

object

 | 

 | 

 |
| 

issued\_points

 | 

int32

 | 

3

 | 

The penalty point issued of the updates.

 |
| 

violation\_type

 | 

int32

 | 

10

 | 

The violation type of the penalty point：

5: High Late Shipment Rate  
6: High Non-fulfillment Rate  
7: High number of non-fulfilled orders  
8: High number of late shipped orders  
9: Prohibited Listings  
10: Counterfeit / IP infringement  
11: Spam  
12: Copy/Steal images  
13: Re-uploading deleted listings with no change  
14: Bought counterfeit from mall  
15: Counterfeit caught by Shopee  
16: High percentage of pre-order listings  
17: Confirmed Fraud attempts (total)  
18: Confirmed Fraud attempts per week (All with vouchers only)  
19: Fake return address  
20: Shipping fraud/abuse  
21: High No. of Non-responded Chat  
22: Rude chat replies  
23: Request buyer to cancel order  
24: Rude reply to buyer's review  
25: Violate Return/Refund policy  
101: Tier Reason  
3026: Misuse of Shopee’s IP  
3028: Violate Shop Name Regulations  
3030: Direct transactions outside of the Shopee platform  
3032: Shipping empty / incomplete parcels  
3034: Severe Violations on Shopee Feed  
3036: Severe Violations on Shopee LIVE  
3038: Misuse of Local Vendor Tag  
3040: Use of misleading shop tag in listing image  
3042","Counterfeit / IP Infringement test  
3044: Repeat Offender - IP infringement and Counterfeit listings  
3046: Violation of Live Animals Selling Policy  
3048: Chat Spam  
3050: High Overseas Return Refunds Rate  
3052: Privacy breach in buyer's review reply  
3054: Order Brushing  
3056: porn image  
3058: Incorrect Product Categories  
3060: Extremely High Non-Fulfilment Rate  
3062: Penalty of Affiliate Marketing Solution (AMS) Overdue Invoice Payment  
3064: Government-related listing  
3066: Listing invalid gifted items  
3068: High non-fulfilment rate (Next Day Delivery Orders)  
3070: High Late Shipment Rate (Next Day Delivery Orders)  
3072: OPFR Violation Value  
3074: Direct transactions outside Shopee platform via chat  
3090: Prohibited Listings-Extreme Violations  
3091: Prohibited Listings-High Violations  
3092: Prohibited Listings-Mid Violations  
3093: Prohibited Listings-Low Violations  
3094: Counterfeit Listings-Extreme Violations  
3095: Counterfeit Listings-High Violations  
3096: Counterfeit Listings-Mid Violations  
3097: Counterfeit Listings-Low Violations  
3098: Spam Listings-Extreme Violations  
3099: Spam Listings-High Violations  
3100: Spam Listings-Mid Violations  
3101: Spam Listings-Low Violations  
3145: Return/Refund Rate (Non-integrated Channel)  

 |
| 

points\_removed\_data

 | 

object

 | 

 | 

 |
| 

removed\_points

 | 

int32

 | 

3

 | 

The penalty point removed of the updates.

 |
| 

violation\_type

 | 

int32

 | 

10

 | 

The violation type of the penalty point：

5: High Late Shipment Rate  
6: High Non-fulfillment Rate  
7: High number of non-fulfilled orders  
8: High number of late shipped orders  
9: Prohibited Listings  
10: Counterfeit / IP infringement  
11: Spam  
12: Copy/Steal images  
13: Re-uploading deleted listings with no change  
14: Bought counterfeit from mall  
15: Counterfeit caught by Shopee  
16: High percentage of pre-order listings  
17: Confirmed Fraud attempts (total)  
18: Confirmed Fraud attempts per week (All with vouchers only)  
19: Fake return address  
20: Shipping fraud/abuse  
21: High No. of Non-responded Chat  
22: Rude chat replies  
23: Request buyer to cancel order  
24: Rude reply to buyer's review  
25: Violate Return/Refund policy  
101: Tier Reason  
3026: Misuse of Shopee’s IP  
3028: Violate Shop Name Regulations  
3030: Direct transactions outside of the Shopee platform  
3032: Shipping empty / incomplete parcels  
3034: Severe Violations on Shopee Feed  
3036: Severe Violations on Shopee LIVE  
3038: Misuse of Local Vendor Tag  
3040: Use of misleading shop tag in listing image  
3042","Counterfeit / IP Infringement test  
3044: Repeat Offender - IP infringement and Counterfeit listings  
3046: Violation of Live Animals Selling Policy  
3048: Chat Spam  
3050: High Overseas Return Refunds Rate  
3052: Privacy breach in buyer's review reply  
3054: Order Brushing  
3056: porn image  
3058: Incorrect Product Categories  
3060: Extremely High Non-Fulfilment Rate  
3062: Penalty of Affiliate Marketing Solution (AMS) Overdue Invoice Payment  
3064: Government-related listing  
3066: Listing invalid gifted items  
3068: High non-fulfilment rate (Next Day Delivery Orders)  
3070: High Late Shipment Rate (Next Day Delivery Orders)  
3072: OPFR Violation Value  
3074: Direct transactions outside Shopee platform via chat  
3090: Prohibited Listings-Extreme Violations  
3091: Prohibited Listings-High Violations  
3092: Prohibited Listings-Mid Violations  
3093: Prohibited Listings-Low Violations  
3094: Counterfeit Listings-Extreme Violations  
3095: Counterfeit Listings-High Violations  
3096: Counterfeit Listings-Mid Violations  
3097: Counterfeit Listings-Low Violations  
3098: Spam Listings-Extreme Violations  
3099: Spam Listings-High Violations  
3100: Spam Listings-Mid Violations  
3101: Spam Listings-Low Violations  
3145: Return/Refund Rate (Non-integrated Channel)  

 |
| 

removed\_reason

 | 

int32

 | 

102

 | 

The reason for removing penalty points：

101: Other Reasons  

102: Shopee System Error

103: Third Party Logistics Issue

104: Weather / Natural disaster

105: Special Exemption

106: Waiver for SBS fulfillment

107: Waiver for SIP listing violation

108: Validated IPR

 |
| 

tier\_update\_data

 | 

object

 | 

 | 

 |
| 

old\_tier

 | 

int32

 | 

3

 | 

The punishment tier before the updates.  

 |
| 

new\_tier

 | 

int32

 | 

4

 | 

The punishment tier after the updates.  

 |
| 

update\_time

 | 

timestamp

 | 

1660124246

 | 

The time of penalty point or punishment tier updates.  

 |
| 

shop\_id

 | 

int64

 | 

127449165

 | 

Shopee's unique identifier for a shop.  

 |
| 

code

 | 

int32

 | 

28

 | 

Shopee's unique identifier for a push notification.

 |
| 

timestamp

 | 

timestamp

 | 

1660124246

 | 

Timestamp that indicates the message was sent.  

 |

## Push Contents

Collapse

Json

```
{"data":{"action_type":1,"points_issued_data": {"issued_points":3,"violation_type":10},"update_time":1660124246},"shop_id":127449165,"code":28,"timestamp":1660124246}
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

2024-10-30

 | 

New Push

 |
