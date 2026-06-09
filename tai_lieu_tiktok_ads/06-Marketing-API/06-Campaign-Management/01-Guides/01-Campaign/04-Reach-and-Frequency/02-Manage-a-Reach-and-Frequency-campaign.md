---
title: "Manage a Reach & Frequency campaign"
doc_id: 1744571207714818
path: "Marketing API / Campaign Management / Guides / Campaign / Reach & Frequency / Manage a Reach & Frequency campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage a Reach & Frequency campaign

This article introduces how to manage an Reach & Frequency (R&F) campaign. 

# Create, update or get an R&F campaign
To acquire, create or modify an R&F campaign, use these API endpoints:
* [Get campaigns](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986)
* [Create a campaign](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602)
* [Update a campaign](https://ads.tiktok.com/marketing_api/docs?id=1739320422086657)

> <span style="color: DodgerBlue">**Note**</span><br>Reach & Frequency campaigns cannot be paused, withdrawn or terminated. 

# Create, update or get an R&F ad group
To acquire, create or modify an R&F ad group, use these API endpoints:
- [Get ad groups](https://ads.tiktok.com/marketing_api/docs?id=1739314558673922)
- [Create R&F ad group](https://ads.tiktok.com/marketing_api/docs?id=1738235338194945)
- [Update R&F ad group](https://ads.tiktok.com/marketing_api/docs?id=1738235402874882)

> <span style="color: DodgerBlue">**Note**</span><br>1. If the budget is not reserved yet, all the fields can be modified. The verification rules for modified fields are the same as the rules for creating a new ad group.<br>
2. If the budget is already reserved (i.e. the ad status is scheduled or active), all the fields that may influence the inventory (including targeting, schedule, and budget) cannot be modified. Only `adgroup_name` and `comment_disabled` can be modified.<br>
3. If the ad status is closed, completed or deleted, none of the fields at the ad group level can be modified.

# Create, update or get an R&F ad
To acquire, create or modify R&F ad creatives, use these API endpoints:
* [Get ads](https://ads.tiktok.com/marketing_api/docs?id=1735735588640770)
* [Create an ad](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354)
* [Update an ad](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018)

> <span style="color: DodgerBlue">**Note**</span><br>1. If there are no approved ads in an ad group on the delivery date, the delivery will fail, and the order will be withdrawn. Therefore, please keep an eye on the status of ads in your ad groups.<br> 
2. When an ad group's status is "Active", advertisers cannot suspend or delete all of the available ads in that group. 

# Withdraw an R&F ad order
Use [/rf/order/cancel/](https://ads.tiktok.com/marketing_api/docs?id=1740489099367425) to cancel an ad group order. Once an R&F order is cancelled, the ad delivery will be suspended.

# Update R&F ad status
- Campaign 

Use [/campaign/status/update/](https://ads.tiktok.com/marketing_api/docs?id=1739320994354178) to update R&F campaign status. 
**Pause** and **Delete** are allowed before delivery, but not allowed in delivery. 

- Ad group

Use [/adgroup/status/update/](https://ads.tiktok.com/marketing_api/docs?id=1739591716326402) to update R&F ad group status. 
**Pause** and **Delete** are allowed before delivery, but not allowed in delivery. 
 
 - Ad
 
Use [/ad/status/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953422970882) to update R&F ad status. 
**Pause** and **Delete** are allowed before delivery. If you want to pause or delete ads in delivery, then you should keep at least one ad active and then pause or delete others. 
 
# Get estimated inventory
Use [/rf/estimated/info/](https://ads.tiktok.com/marketing_api/docs?id=1740489551354881) to query the estimated daily cost and frequency distribution. If the ad group does not have a valid estimate, no corresponding records will be returned.

# Validate contract
Use [/rf/contract/query/](https://ads.tiktok.com/marketing_api/docs?id=1740489855138818) to validate your contract. Since commercial contracts are time-sensitive, you will need to specify a date to check the start and end date of valid contracts that cover that date.

# Get time zone
Use [/rf/delivery/timezone/](https://ads.tiktok.com/marketing_api/docs?id=1740490147129345) to get the time zone based on region codes（for R&F advertising only).

