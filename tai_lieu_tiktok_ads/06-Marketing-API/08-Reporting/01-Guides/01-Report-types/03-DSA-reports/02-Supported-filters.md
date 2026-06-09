---
title: "Supported filters"
doc_id: 1751625296192514
path: "Marketing API / Reporting / Guides / Report types / DSA reports / Supported filters"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported filters

You can use the following filters to narrow down the scope of reporting results. 

Please read the following notes before you start.

* When you pass multiple filters, the filter conditions have `AND` relationships.
* When you use the `IN` filter type, `filter_value` needs to be a valid JSON list character string. <br>Example: `"[\"ID1\",\"ID2\",\"ID3\"]"`.
* When you use the `BETWEEN` filter type, `filter_value` needs to be a valid JSON array of 2-element string. <br>Example: `"[\"2022-02-24 11:57:51\",\"2022-03-24 11:57:51\"]"`.

> <span style="color:DodgerBlue">**Note**</span><br>
> -  If the provided filter field `field_name` and `data_level` don't match, the system will return an error: "Invalid value for filter field: xxxx is not supported". For example, if `data_level` is `AUCTION_AD`, you can only set `field_name` to a filter field at the ad level (such as `ad_ids`).
> -  In asynchronous DSA reports, the only supported filter fields are `campaign_ids`, `adgroup_ids`, and `ad_ids`.
> -  The reporting data for [Product GMV max ads](https://ads.tiktok.com/help/article/how-to-create-product-gmv-max-ads-in-seller-center) and LIVE GMV max ads created from TikTok Shop is excluded from synchronous Auction basic reports, audience reports, playable ad reports, and DSA reports. Shopping Ads created via API or TikTok Ads Manager are not affected.

```xtable
| Filter Field  {18%}   | Supported Filter Types{12%} | Supported Advertising Levels {18%} | Notes {52%}   |
|---|---|---|
| campaign_ids | IN | Campaign/Ad group/Ad | Campaign IDs, you can pass up to 100 `campaign_id` when using this filter. Filter value example: `"[123,456,789]"`. |
| campaign_type | IN | Campaign/Ad group | The campaign type. Enum values: `REGULAR_CAMPAIGN`, `SKAN_CAMPAIGN`(iOS 14 Dedicated Campaigns). 
| campaign_status | IN | Campaign | Campaign status. For enum values, see [Enumeration-Ad Management-Primary Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). <br><p><span style="color:darkred"><b>Note</b></span>: If not set, this filter field will default to `STATUS_NOT_DELETE` in synchronous DSA reports.|
| adgroup_ids | IN | Ad group/Ad | Ad Group IDs, you can pass up to 100 `adgroup_id` when using this filter. |
| adgroup_status | IN | Ad group | Ad Group status. For enum values, see [Enumeration-Ad Management-Primary Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).<br><p><span style="color:darkred"><b>Note</b></span>: If not set, this filter field will default to `STATUS_NOT_DELETE` in synchronous DSA reports. |
| ad_ids_v2 | IN | Ad | <ul><li>The types of IDs you can filter include:<ul><li>the ID of a Manual Ad.</li><li>the ID of an Upgraded Smart+ <b>Ad</b> (<b>Asset Group</b>).</li></ul></li><li>You need to set `data_level` to `AUCTION_AD`and include `ad_id_v2` in `dimensions`.</li><li>You can specify up to 100 values when using this filter.</li><li>You cannot use it together with the dimension `ad_id` or the filter `ad_ids`.</li></ul> |
| ad_ids | IN | Ad | Ad IDs. You can pass up to 100 `ad_id` when using this filter. |
| ad_status | IN | Ad | Ad Status. For enum values, see [Enumeration-Ad Management-Primary Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). <br><p><span style="color:darkred"><b>Note</b></span>: If not set, this filter field will default to `STATUS_NOT_DELETE` in synchronous DSA reports.|
| campaign_label_ids | IN, NOT_IN | Campaign/Ad group/Ad | A list of campaign label IDs.<br><br>Max size: 50.<br><br>Each label ID must be a 19-digit numeric string.<br><br><p><span style="color:darkred"><b>Note</b></span>: This filter is only supported in synchronous reports.</p> |
| campaign_label_names | CONTAIN_ANY_OF | Campaign/Ad group/Ad | A list of campaign label names.<br><br>Fuzzy matching is supported.<br><br>Max size: 10.<br><br><p><span style="color:darkred"><b>Note</b></span>: This filter is only supported in synchronous reports.</p> |
| campaign_label_types | IN, NOT_IN | Campaign/Ad group/Ad | A list of campaign label types.<br><br>Enum values:<ul><li>`GENERAL`: General label.</li><li>`MARKETING_EVENT`: Marketing event label.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: This filter is only supported in synchronous reports.</p> |
|promotion_type| IN | Campaign/Ad group/Ad| Promotion type (Optimization location).<br><br> Enum values: <ul><li>`APP`: App.</li><li>`WEBSITE`: Landing page.</li><li>`INSTANT_FORM`: Instant Form.</li><li>`LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE`: TikTok direct message.</li><li>`LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE`: social media app.</li><li>`LEAD_GEN_CLICK_TO_CALL`: Phone call.</li><li>`OTHERS`: Others.</li></ul><br> This filter can only be used in synchronous reports.|
|dpa_target_audience_type| IN | Campaign/Ad group/Ad | The audience type for DPA. Enum values: `RETARGETING`, `PROSPECTING`. This filter can only be used in synchronous reports.|
|create_time| BETWEEN | Campaign/Ad group/Ad | Time range of creation time of requested ID dimension (`campaign_id`/`adgroup_id`/`ad_id`). The value needs to be a valid JSON array of 2-element string. The 1st element is start time, the 2nd element is end time. E.g. `"[\"2022-02-24 11:57:51\",\"2022-03-24 11:57:51\"]"`. This filter can only be used in synchronous reports.<br>Suggestion: A time range within 6 months is suggested when applying a creation time filter, to ensure that the success and speed of the task won't be affected.|
|create_time| GREATER_THAN | Campaign/Ad group/Ad | Creation start time of requested ID dimension (`campaign_id`/`adgroup_id`/`ad_id`). The value is in `YYYY-MM-DD HH:MM:SS` format (UTC time zone). E.g. `"2022-02-24 11:57:51"` - dimensions created later than this time will be returned. This filter can only be used in synchronous reports.<br>Suggestion: A time range within 6 months is suggested when applying a creation time filter, to ensure that the success and speed of the task won't be affected.|
|create_time| LOWER_THAN | Campaign/Ad group/Ad | Creation end time of requested ID dimension (`campaign_id`/`adgroup_id`/`ad_id`). The value is in `YYYY-MM-DD HH:MM:SS` format (UTC time zone). E.g. `"2022-02-24 11:57:51"` - dimensions created earlier than this time will be returned. This filter can only be used in synchronous reports.<br>Suggestion: A time range within 6 months is suggested when applying a creation time filter, to ensure that the success and speed of the task won't be affected.|
```
