# Automated Order Combination — ISV Integration Guide

> Source: https://partner.tiktokshop.com/docv2/page/lm9400zu
> Section: Changelog
> Scraped: 2026-05-21T00:34:55.247Z

---

## Overview

> Introduction in Academy：[https://seller-us.tiktok.com/university/essay?knowledge\_id=5369161871132458](https://seller-us.tiktok.com/university/essay?knowledge_id=5369161871132458)

TikTok Shop currently supports **Automated Order Combination** across all shopping channels in the US market. If the merchant has enabled this feature in the seller center，Eligible shopper orders placed with the same seller across Shop Tab, Video, and Livestream channels are auto-combined within a pre-defined time window when the buyer, buyer address, seller, and seller warehouse are consistent.  
If your app handles order fulfillment for US/UK/EU/JP sellers, you must update your integration to recognize combined orders and ship them correctly. This document explains what is changing in the fulfillment flow and how to adapt your integration for both 3PL (Seller Shipping) and 4PL (TikTok Shipping) models.

**US/UK/EU/JP Local:** Both business and API are supported — ready for integration.  
**US PoP:** API is ready and integration can begin now. The merchant feature is expected to launch in **mid-June**; the exact go-live date is subject to further updates.

## What is changing

### Combination is a recommendation, not automatic shipping

Order combination **does not happen automatically** during the Ready to Ship (RTS) process. When orders are eligible for combination, the system provides a **combination recommendation**. The seller must accept or reject the recommendation before shipping. If accepted, those orders are combined into a single package.  
Your app should present this recommendation to the seller and allow them to confirm, modify, or reject it.

### Key APIs/fields

| API | Field | JSON path | Description | Status |
| --- | --- | --- | --- | --- |
| 
**Get Order Detail**  
**Get Order List API**  

 | 

`auto_combine_group_id`  

 | 

`data.orders[].auto_combine_group_id`  

 | 

A unique identifier for the combination group. Orders sharing the same value should be shipped together. Displays `N/A` if the seller has opted out.  

 | 

Active  

 |
| 

**Create Packages**

 | 

\-

 | 

\-

 | 

\-

 | 

Active（**v202512**）

 |

**Example from Get Order Detail response:**

JSON

Word Wrap

```
{
  "code": 0,
  "data": {
    "orders": [
      {
        "id": "576461413038785752",
        "status": "UNPAID",
        "split_or_combine_tag": "COMBINED",
        "auto_combine_group_id": "12345677",
        "warehouse_id": "6955005333819123123",
        "line_items": [
          {
            "id": "577086512123755123",
            "product_name": "Women's Winter Crochet Clothes",
            "sub_item_info": [
              {
                "id": "577086512123755123",
                "sku_name": "Iphone",
                "display_status": "UNPAID"
              }
            ]
          }
        ]
      }
    ]
  }
}
```

> **Note**: `auto_combine_group_id` is at the **order root level** (`data.orders[]`), not nested inside `packages` or `line_items`.

**Create Packages API request example (combined shipment):**

PLAIN

Word Wrap

```
POST /fulfillment/202512/packages  
  
{  
  "ship_type": "3",  
  "order_id": "576461413038785001",  
  "order_line_item": [  
    {  
      "order_line_id": "577086512123755001"  
    }  
  ],  
  "order_list_ids": [  
    "576461413038785002"  
  ],  
  "dimension": {  
    "length": "30",  
    "width": "20",  
    "height": "15",  
    "unit": "CM"  
  },  
  "shipping_service_id": "288233559123860015",  
  "weight": {  
    "value": "500",  
    "unit": "GRAM"  
  }  
}
```

## How to integrate

Your integration path depends on whether the seller uses **3PL** (Seller Shipping — seller-managed shipping labels) or **4PL** (TikTok Shipping — platform-managed labels).

### 3PL (Seller Shipping) integration flow

For 3PL sellers, order combination is **offline** — the seller physically packs the combined items at their warehouse. Your app identifies which orders belong together and assigns the same tracking number when marking them as shipped. **No dedicated combination API is required for 3PL.**

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/3d94b0f854464bd69aeadaa4d9e3bb25~tplv-k9wyc2ijk0-image.image)

> **Key point**: 3PL combination is achieved by uploading the **same tracking number** across multiple orders. You can use either **Mark Package As Shipped** or **Ship Package** — both accept a tracking number.

### 4PL (TikTok Shipping) integration flow

For 4PL sellers, order combination is **online** — your app submits all orders in a combination group to the **Create Packages API** in a single request, and TikTok Shop generates one shipping label for the combined package.

> If the merchant queries an order with **package consolidation intention** (where `auto_combine_group_id` is not null) and intends to ship in consolidated packages, the merchant shall call the **create package** API to fulfill shipment (the new process).  
> For orders **without package consolidation intention**, or if the merchant prefers to ship each order individually, the **ship package** API remains available.

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/3ab85a57671a4763b119da0fe4aaba0a~tplv-k9wyc2ijk0-image.image)  
When calling the Create Packages API for combined orders:

-   Set `ship_type` to `"3"` to indicate a combined shipment.
    
-   Pass one order as the primary `order_id`, and list the other combined order IDs in `order_list_ids`.
    

### API version requirement

| Fulfillment model | API version requirement |
| --- | --- |
| 
**4PL (TikTok Shipping)**

 | 

Upgrade to **API version v202512 or later** to use the Create Packages API with combination support

 |
| 

**3PL (Seller Shipping)**

 | 

No new API version required. Use the existing Mark Package As Shipped or Ship Package API

 |

### Step-by-step integration guide

#### Step 1: Retrieve order details and identify combination groups

Call the **Get Order Detail API** for each order. In the response, check the `auto_combine_group_id` field:

-   If `auto_combine_group_id` is present and **not** `N/A`, the order is eligible for combination.
-   Group all orders that share the same `auto_combine_group_id` value — these orders should be shipped together.

#### Step 2: Present the combination recommendation to the seller

Based on the `auto_combine_group_id` groupings, your app should:

1.  Display the recommended combination groups to the seller.
2.  Allow the seller to **accept** the recommendation (ship combined) or **reject/modify** it (remove specific orders from the group).
3.  If the seller accepts, proceed to Step 3. If rejected, ship orders individually using your existing flow.

> **Note**: Combination is a recommendation, not an automatic action. The seller must confirm before you proceed with combined shipping.

#### Step 3: Ship the combined package

**For 3PL sellers (offline combination):**

1.  The seller physically packs the combined items into one package at their warehouse.
2.  Assign a single tracking number (e.g., `TN001`) to all orders in the group.
3.  Call **Mark Package As Shipped** or **Ship Package** for each order, passing the same tracking number.

**For 4PL sellers (online combination):**

1.  Call the **Create Packages API** (version v202512 or later).
2.  Include all order IDs from the combination group in a single request.
3.  Set the `ship_type` field appropriately.
4.  TikTok Shop generates a single shipping label for the combined package.

### Summary of integration paths

| Aspect | 3PL (Seller Shipping) | 4PL (TikTok Shipping) |
| --- | --- | --- |
| 
**Combination type**

 | 

Offline (physical packing at warehouse)

 | 

Online (API-driven)

 |
| 

**Dedicated combination API**

 | 

Not required

 | 

Create Packages API (v202512+)

 |
| 

**How to combine**

 | 

Upload the same tracking number for all orders in the group

 | 

Submit all orders in one Create Packages request using `ship_type` and `order_list_ids`

 |
| 

**Ship API**

 | 

Mark Package As Shipped or Ship Package

 | 

Create Packages

 |
| 

**API version requirement**

 | 

No change

 | 

v202512 or later

 |

### Non-compliance impact

If your app does not support automated order combination:

-   **Increased seller costs** — Sellers cannot benefit from combined shipping, leading to higher per-order shipping fees.
-   **Degraded seller experience** — Sellers may need to switch to Seller Center for fulfillment or manually manage combinations, undermining the value of your integration.
-   **Competitive disadvantage** — Sellers may migrate to ISVs that support the feature.

There is no hard API enforcement or error code for non-compliance. However, supporting this feature is critical to maintaining seller satisfaction and retention.

## FAQ

**Q: Can orders with different auto-combine group IDs be combined into the same package?**  
A: **Yes.** Group ID difference does not block combination. As long as the five conditions above (buyer, address, seller, warehouse, logistics service) are met, the orders can still be combined.  
**Q: What does "offline combination" mean for 3PL?**  
A: For Seller Shipping (3PL) sellers, order combination happens physically at the seller's warehouse — the seller packs multiple orders into one box. Your app's role is to identify which orders belong together (via `auto_combine_group_id`) and mark them as shipped with the same tracking number.  
**Q: Does the system automatically combine and ship orders?**  
A: No. The system only provides a combination **recommendation** based on eligible orders. The seller must accept or reject the recommendation before shipping. Your app should present this recommendation and allow the seller to confirm.  
**Q: What if my app already supports livestream order combination?**  
A: The mechanism is the same — your app reads `auto_combine_group_id` from order details and groups orders accordingly. If your existing implementation correctly handles the `auto_combine_group_id` field, no additional code changes are needed.  
**Q: Are API sellers automatically enrolled?**  
A: No. All Seller Shipping (3PL) sellers and TikTok Shipping API sellers are **not automatically enrolled**. They must opt in manually via Seller Center > Orders > Fulfillment Settings > Automated Order Combination.  
**Q: Can orders from different shopping channels be combined?**  
A: Yes. Orders from Shop Tab, Video, and Livestream channels can all be combined, as long as the combination rules (same buyer, address, seller, warehouse, and within the time window) are met.  
**Q: Can a seller separate auto-combined packages?**  
A: Yes, sellers can separate auto-combined packages before RTS. However, they must bear any additional shipping fee expenses. Once an order reaches RTS status, the combination is locked.  
**Q: Can hazmat items be combined?**  
A: Hazmat items are excluded from automated order combination for TikTok Shipping (4PL). For Seller Shipping (3PL), sellers may manually combine and ship items in adherence to their carrier's hazmat policy.  
**Q: For 3PL, can I use Ship Package API instead of Mark Package As Shipped?**  
A: Yes. Both APIs accept a tracking number. Use either one — just ensure all orders in the same combination group share the same tracking number.
