# For SEA Market: Recommended Improvement to Support Fast Delivery Service

> Source: https://partner.tiktokshop.com/docv2/page/m5p1u1fo
> Section: Changelog
> Scraped: 2026-05-21T00:34:41.203Z

---

# What is changing?

When sellers choose **++Instant or Sameday shipping option++** to facilitate faster delivery to customers, TikTok Shop will aggressively evaluate the shipping and collection time of the orders to provide a satisfactory user experience.  
In Seller Center, there are several functions to support sellers for better managing these fast delivery orders. We recommend adopting the following changes

1.  Allow sellers to print shipping labels before schedule shipping
2.  Understand new SLA update
3.  Enhance your features to encourage faster actions
    1.  Send push notifications when new orders arrived
    2.  Orders tag or specific filters to easily separate fast delivery services
    3.  Default to sort fast delivery orders on top

# Which markets are affected?

This is applicable for **local sellers in the ID, SG, PH, VN, TH and MY markets.**

# Who is affected?

**All seller types** are affected by this change.

# How to identify fast delivery options?

You can identify fast delivery options using the following fields

| **API** | **Field name** | **Type** | **Field Value** |
| --- | --- | --- | --- |
| 
[Get Order Detail](https://partner.tiktokshop.com/docv2/page/get-order-detail-202309),  
[Get Order List](https://partner.tiktokshop.com/docv2/page/get-order-list-202309)

 | 

`fulfillment_priority_level`  

 | 

`int`  

 | 

`100` = Instant  
`200` = Sameday 8 hours  
`300` = Sameday

 |

# What action is required?

We recommend adopting these changes so sellers can handle orders effectively in a timely manner.

## Allow sellers to print shipping labels before schedule shipping

Applicable only for `fulfillment_priority_level` = `100`,`200`,`300`. These 2 steps should be independent and can be processed without dependencies.  
**Note: only applicable for** **++printed document format is PDF.++**

1.  Use the [Get Package Shipping Document](https://partner.tiktokshop.com/docv2/page/650aa5fac16ffe02b8f112ca?external_id=650aa5fac16ffe02b8f112ca) API to get the shipping label.
2.  Use the [Ship Package](https://partner.tiktokshop.com/docv2/page/650aa4f1defece02be6e7cb1?external_id=650aa4f1defece02be6e7cb1) API to schedule the package handover.

## Understand new SLA update

Applicable only for `fulfillment_priority_level` = `100`,`200`,`300`. **Late Dispatch Rate (LDR)** will be captured based on ++"Arrange Shipment"++ time. It's important to track this time as deadline for sellers to follow the guidelines strictly.

| Delivery option | LDR Assessment when | API | Field name |
| --- | --- | --- | --- |
| 
Instant, Sameday 8 hours, Sameday

 | 

Seller clicked "Arrange Shipment". This is when call [Ship Package](https://partner.tiktokshop.com/docv2/page/650aa4f1defece02be6e7cb1?external_id=650aa4f1defece02be6e7cb1) successfully.

 | 

[Get Order Detail](https://partner.tiktokshop.com/docv2/page/get-order-detail-202309)  

 | 

`rts_sla_time`  

 |
| 

Other delivery options

 | 

Package collection successful and order marked as "Shipped"

 | 

 | 

`tts_sla_time`

 |
| 

For different countries, you can learn more about SLA in the following link

 | 

 | 

 | 

 |

-   [Vietnam](https://seller-vn.tiktok.com/university/essay?identity=1&role=1&knowledge_id=1797134848837393&from=feature_guide)
-   [Malaysia](https://seller-my.tiktok.com/university/essay?identity=1&role=1&knowledge_id=6712877676267265&from=feature_guide)
-   [Thailand](https://seller-th.tiktok.com/university/essay?knowledge_id=5069164268996368&lang=en)
-   [Singapore](https://seller-sg.tiktok.com/university/essay?knowledge_id=500347816036097&default_language=en&identity=1)
-   [Indonesia](https://seller-id.tokopedia.com/university/essay?knowledge_id=7753821275686658)

## Enhance your features to encourage faster actions

It's recommended to use `fulfillment_priority_level` to differentiate fast delivery services and have the following functions available.

1.  Send push notifications when new orders arrived
2.  Orders tag or specific filters to easily separate fast delivery services
3.  Default to sort fast delivery orders on top
