# 【Important Notice】Enable Support for the New “On Hold” Order Status

> Source: https://partner.tiktokshop.com/docv2/page/yb76cyzi
> Section: Changelog
> Scraped: 2026-05-21T00:38:04.696Z

---

# **Mandatory Activation for All Apps in All Global Markets, Q1 2026**

To enhance buyer experience and improve overall order processing stability, TikTok Shop introduced a new order status — **“On Hold”** — effective **December 13, 2023**.  
To ensure ecosystem-wide consistency, **TikTok Shop will automatically activate “On Hold” orders for all apps that have not yet enabled support starting in Q1 2026**.  
This means:  
**All apps that have not completed integration will begin receiving On Hold orders**, which may lead to fulfillment failures, missing address fields, and other processing issues.  
Please complete the necessary updates as soon as possible to avoid disruption for your merchants.

* * *

# 1\. What Is the “On Hold” Status?

“On Hold” is a new order status applied **after payment**, lasting **1 hour**. During this window:

-   Buyers may cancel the order without seller approval
-   Sellers cannot take any action on the order (e.g., print labels, ship packages)
-   The API will return empty values for **full\_address**, **recipient\_address**, and **district\_info**
-   A new field **is\_on\_hold\_order** is added in API v202309 to distinguish this flow

This update applies to **cross-border and local sellers**.

* * *

# 2\. Why You Must Support It Now

Starting **Q1 2026**:

-   All apps (including those not directly processing orders) **will be automatically enabled for On Hold orders**
-   Apps that are *not* prepared may experience:
    -   Fulfillment errors (Error Code 21011044)
    -   Missing address data
    -   Incorrect handling of the 1-hour customer remorse window
    -   Merchant complaints and disrupted operations

**To avoid these risks, please complete your integration before Q1 2026.**

* * *

# 3\. Required Developer Actions

### **3.1 - Upgrade to API v202309 (Strongly Recommended)**

Only v202309 includes the new field **is\_on\_hold\_order**, which enables apps to correctly identify whether an order has passed the On Hold stage.

> Apps using legacy API versions cannot reliably process On Hold orders.

### **3.2 - Support Both Order Status Flows**

Depending on the shop and category, merchants may still receive **two types of order flows**:

| **Order Flow** | **Status Path** | **is\_on\_hold\_order** | **Required Handling** |
| --- | --- | --- | --- |
| 
**New Flow**

 | 

`Unpaid` → `On Hold` → `Awaiting Shipment`

 | 

`TRUE`

 | 

Can fulfill immediately after status becomes `Awaiting Shipment`

 |
| 

**Existing Flow**

 | 

`Unpaid` → `Awaiting Shipment`

 | 

`FALSE`

 | 

Must hold the order for 1 hour before shipping

 |
| 

Your app must support **both** flows in parallel.

 | 

 | 

 | 

 |

### **3.3 - Complete the “On Hold Status Developer Declaration” in Partner Center**

After finishing development & testing:  
Navigate to:  
**Partner Center → App & Service → App Detail → On Hold Status Developer Declaration**  
Click **“Declare”** to confirm support.  
Once the declaration is completed:

-   The “App does not support On Hold” warning will be removed
    
-   The app will be marked as fully compatible
    
-   Merchants can install the app without receiving warnings
    

### **3.4 - Test Using Sandbox 2.0**

-   Sandbox 2.0 shops **always include On Hold** in eligible categories
    
-   Some categories allow testing of the **existing** order flow (without On Hold)
    
-   All developers should verify their app handles both flows correctly
    

* * *

# 4\. Key Timeline

| **Timeframe** | **Requirement** |
| --- | --- |
| 
**Now – Q1 2026**

 | 

Developers must complete On Hold support + declaration

 |
| 

**Q1 2026**

 | 

TikTok Shop will activate On Hold orders for all non-compliant apps

 |
| 

**After activation**

 | 

Apps without support may fail to fulfill orders or return errors

 |

* * *

# 5\. Impact / Potential Risks if Not Updated

If the app does not support On Hold before the enforcement date:

-   **API error 21011044 will prevent shipment**
    
-   **Missing address data may cause logistics failures**
    
-   **Merchants may be unable to fulfill orders**
    
-   **App installation pages will continue to show warning banners**
    
-   **Increased merchant complaints and operational risk**
    

* * *

# 6\. Additional Resources

If you encounter any issues during the integration process, please refer to:

-   [Order Integration Solution Guide](https://bytedance.us.larkoffice.com/docx/T8HXdaOH2oJXdtxMkk9u5Sh1sCb)
-   [Upgrading to API version v202309](https://partner.tiktokshop.com/docv2/page/upgrading-to-api-version-202309)
-   [Sandbox 2.0 Test Guide](https://partner.tiktokshop.com/docv2/page/seller-center-development-shops)

If you have any further questions, please contact us via Partner Center or Developer Support.

#
