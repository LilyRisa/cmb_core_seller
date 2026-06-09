---
title: "Finance"
doc_id: 1754705321657346
path: "Marketing API / Business Center / Guides / Finance"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Finance

You (**users with Finance Manager or Finance Analyst permission**) can use Business Center API to manage payments and invoices in Business Center. Both Agency and Direct Business Centers are able to perform finance-related operations. Below outlines some common use cases. 

> <span style="color: DodgerBlue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in Business Center.  

# View ad account or Business Center transaction records

 To retrieve transaction records of a Business Center or the ad accounts that you have access to, you have two options:
- Use one single endpoint [/bc/account/transaction/get/](https://business-api.tiktok.com/portal/docs?id=1792843845795842).
- Use two separate endpoints, [/advertiser/transaction/get/](https://ads.tiktok.com/marketing_api/docs?id=1739939116353538) for ad accounts and [/bc/transaction/get/](https://ads.tiktok.com/marketing_api/docs?id=1739939140408322) for Business Centers.

When using the `/advertiser/transaction/get/` and `/bc/transaction/get/` endpoints, you can filter the records by fund type using the `funds_type` parameter. The value `FUNDS_TYPE_CASH`represents cash, while `FUNDS_TYPE_GRANT` represents coupons or vouchers.


# Use Billing Groups to manage invoices or payments
A Billing Group is a group of ad accounts that share the same invoicing and payment settings. After you create a Billing Group, you'll get one monthly invoice for all ad accounts within that group showing the amount spent per account.  

Use [/bc/billing_group/create/](https://ads.tiktok.com/marketing_api/docs?id=1738947780033537) to create a Billing Group. If you want to create a billing group for Auction ad accounts, you should add all Auction ad accounts to one Billing Group, except the ad accounts registered in France, Brazil, or Mexico. However, if you want to create a billing group of Reservation ad accounts, you should add one Reservation ad account to one Billing Group. 

# Balance Sharing

Balance Sharing, also known as automatic balance allocation, allows for automatic allocation of campaign costs from a shared Business Center credit balance among ad accounts owned by the Business Center. By enabling Balance Sharing, our system will automatically allocate the Business Center account balance to ad accounts according to their spending. This feature helps prevent campaign suspension due to insufficient ad account balance and reduces the workload of manual allocating balance. 

> <span style="color: DodgerBlue">**Note**</span><br>Balance Sharing is only available to Business Centers with the payment method as [Monthly Invoicing](https://ads.tiktok.com/help/article/credit-line?lang=en).

Once Balance Sharing is enabled for a Business Center, each ad account within the Business Center will have either an **unlimited budget** (default) or **monthly budget**. Ad accounts with an unlimited budget will automatically share the Business Center balance, while ad accounts with a monthly budget can only use the Business Center balance within the pre-set monthly budget. When the amount spent by an ad account reaches the monthly budget, the ads created by the ad account will pause and stop spending money. For instance, if the balance of your Business Center with automatic balance allocation enabled is 1,000 dollars, and you want to limit the cost deducted for one ad account per month to be less than 100 dollars, you can set a 100 monthly budget for that ad account.

To manage Balance Sharing effectively, you can use the Business Center API. With Business Center API, you can check and modify the budget mode and monthly budget of ad accounts and manage the credit balance of a Business Center. To learn more about the steps, see [Manage the balance allocation of a Business Center](https://business-api.tiktok.com/portal/docs?id=1781992772171778). 


# Related endpoints
See [API reference](https://ads.tiktok.com/marketing_api/docs?id=1739562438061058) to find out the available endpoints for Business Center **payments**, **invoices**, and **Billing Groups**.  



 
