---
title: "Supported filters"
doc_id: 1751617884206210
path: "Marketing API / Reporting / Guides / Report types / Playable ad reports / Supported filters"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported filters

You can use the following filters to narrow down the scope of reporting results. 

Please read the following notes before you start.
* When you pass multiple filters, the filter conditions have `AND` relationships.
* When you use the `IN` filter type, `filter_value` needs to be a valid JSON list character string.

> <span style="color:DodgerBlue">**Note**</span><br>
> -  The reporting data for [Product GMV max ads](https://ads.tiktok.com/help/article/how-to-create-product-gmv-max-ads-in-seller-center) and LIVE GMV max ads created from TikTok Shop is excluded from synchronous Auction basic reports, audience reports, playable ad reports, and DSA reports. Shopping Ads created via API or TikTok Ads Manager are not affected.

```xtable
| Filter Field {20%}| Supported Filter Types {35%} | Description {45%} |
| ---| ---| ---|
| playable_ids | IN | Playable IDs. You can pass up to 100 `playable_id` when using this filter. |
| country_code | IN | Location code. See [Appendix-Location IDs](https://ads.tiktok.com/marketing_api/docs?id=1739311040498689) for details. |
| orientation | IN | Display orientation. Enum values: `PORTRAIT`, `LANDSCAPE`, `BOTH`. |
| playable_status | IN | Status. Enum values: `VALIDATING`, `VALIDATE_FAIL`, `VALIDATE_SUCCESS`, `AUDIT_FAIL`, `AUDIT_SUCCESS`. |
| playable_name | MATCH | Playable name. |
| spend | LOWER_THAN/GREATER_THAN/RANGE | Total cost. |
```

