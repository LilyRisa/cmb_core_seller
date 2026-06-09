---
title: "Reporting performance improvements"
doc_id: 1757262274182145
path: "Marketing API / Reporting / Guides / Reporting performance improvements"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Reporting performance improvements

A growing number of business users are now using our Reporting API to run synchronous reports. Sometimes, the way that users call our API causes multiple timeouts, and this impacts the overall performance of our Reporting products. To ensure smooth integration for all users, we are making some improvements in the following four scenarios. 


# Scenario 1
If your API requests meet one of the following conditions, then we have to block them. 
- Your query dimension includes `behavior_id` and the number of queried IDs exceeds 200. 

> <span style="color: DodgerBlue">**Note**</span><br>We have optimized reporting performance for audience reports with the dimension of `behavior_id` via [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353) (without `order_field` and `enable_total_metrics` parameters). This optimization can increase the number of queried IDs to 2000. Please note that the first request may take a long time to generate responses. However, subsequent requests with the same query parameters but different page numbers can be returned quickly.<br>
Currently, this optimization is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. Once you are on the allowlist, there is no need to add a new request parameter, as we will automatically enable it in the downstream.


- Your query dimension includes `interest_category`/`interest_category_tier2`/`interest_category_tier3`/`interest_category_tier4`, and the number of queried IDs exceeds the threshholds below. 

|Dimension|Max ID limit|
|---|---|
interest_category|3000
interest_category_tier2|150
interest_category_tier3|100
interest_category_tier4|100

# Scenario 2
If your developer app has frequent timeouts, we will dynamically lower your QPS limit as below. 
- If there are 10 time-out requests in 10 seconds, then the QPS limit will be temporarily lowered to 5 QPS for 5 minutes. 
- If there are 20 time-out requests in 10 seconds, then the QPS limit will be temporarily lowered to 1 QPS for 5 minutes. 
- If there are 30 time-out requests in 10 seconds, then the QPS limit will be temporarily lowered to 0 QPS for 5 minutes.

# Scenario 3
If you retry the same request and have three or more time-outs in 10 minutes, then we will temporarily block the retry-request on your developer app for 10 minutes. 

# Scenario 4
If your daily timeout rate exceeds 80% in a certain scenario , then we will block the request on your developer app in this scenario. 

Example: When you query the age and gender distribution of the audience over 30 days, and more than 20,000 IDs are returned, causing the daily timeout rate to exceed 80%, then we have to block your request. 

 To avoid reaching timeouts, we suggest that:
- When you query the age/gender/country distribution of the audience over **3 days**, the number of returned IDs should be no more than 5,000.
- When you query the age/gender/country distribution of the audience over **7 days**, the number of returned IDs should be no more than 2,000.

