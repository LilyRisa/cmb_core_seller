---
title: "Supported filters"
doc_id: 1775747484045313
path: "Marketing API / Reporting / Guides / Report types / Business Center reports / Supported filters"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported filters

You can use the following filters to narrow down the scope of reporting results.

Read the following notes before you start.

* When passing multiple filters, the filter conditions have `AND` relationships.
* When using `IN` filter type, `filter_value` needs to be a valid JSON list character string. <br>E.g. `"[\"ID1\",\"ID2\",\"ID3\"]"`.

```xtable
| Filter Field {20%}| Supported Filter Types{20%} | Notes  {60%}|
|---|---|---|
| advertiser_name | MATCH | Name of the ad account. |
| advertiser_id | IN | ID of the ad account. |
| registered_area | IN | The location code for the place of registration for the ad account. <br><br>To find out the filterable location codes for the Business Center, use [/bc/advertiser/attribute/](https://business-api.tiktok.com/portal/docs?id=1775752357139457). |
| platform_of_account | IN | Platform of ad accounts. <br><br> Supported values: <ul><li>`ADS_MANGER`: TikTok Ads Manager. </li><li> `RAP`: RAP (Reservation Ads Platform). </li></ul> |
| owner_of_account | IN | Owner of ad accounts. <br><br>Supported values: <ul><li>`OWNED_BY_CURRENT_BUSINESS_CENTER`: Owned by the current Business Center. </li><li>`SHARED_BY_OTHER_BUSINESS_CENTER`: Shared by another Business Center.</li></ul> |
| currency_of_account | IN | Currency code of ad accounts. <br><br> To find out the filterable currency codes for the Business Center, use [/bc/advertiser/attribute/](https://business-api.tiktok.com/portal/docs?id=1775752357139457). |
| cost | <ul><li> GREATER_THAN </li><li>GREATER_EQUAL</li><li> LOWER_THAN </li><li> LOWER_EQUAL </li></ul>| Cost. <br><br> This filter will be applied to all ad accounts and is measured in USD. <br> For instance, the filtering setting `"filtering=[\"field_name\": \"cost\", \"filter_type\": \"GREATER_EQUAL\"},{\"filter_value\": \"500\"]"` will filter ad accounts with a cost greater than or equal to 500 USD. |
| account_status | IN | Status of ad accounts. <br><br> Supported values: <ul><li>`APPROVED`: Approved.</li><li> `NOT_APPROVED`: Not approved.</li><li>`IN_REVIEW`: In review.</li><li>`SUSPENDED`: Suspended. </li><li>`DISABLED`: Disabled.</li><li>`CONTRACT_INACTIVE`: Contract has not taken effect.</li></ul>|
```


