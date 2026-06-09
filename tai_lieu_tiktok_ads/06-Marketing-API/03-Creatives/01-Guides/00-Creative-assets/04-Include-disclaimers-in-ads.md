---
title: "Include disclaimers in ads"
doc_id: 1739953274550273
path: "Marketing API / Creatives / Guides / Creative assets / Include disclaimers in ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Include disclaimers in ads

To promote certain products (like pharmaceutical products), advertisers are required by law to display disclaimers in ads. TikTok For Business supports adding disclaimers in ads. Both text-only and clickable disclaimer messages are supported.

This article describes the steps to add disclaimers in ads, and the requirements for using disclaimers. 


## Requirements

* Supported objectives: 
  - For Manual Ads: `APP_PROMOTION`, `WEB_CONVERSIONS`, `REACH`, `TRAFFIC`, `VIDEO_VIEWS`, `ENGAGEMENT`, `LEAD_GENERATION`(with `catalog_enabled` as `false` or not specified), `RF_REACH`.
  - For Upgraded Smart+ Ads: `APP_PROMOTION`, `WEB_CONVERSIONS`, `LEAD_GENERATION`(with `catalog_enabled` as `false` or not specified).
* Placements: TikTok only
* Not supported in Automated Creative Optimization ads.

## Steps

1. Determine the type of disclaimer. You can use either clickable disclaimers or text-only disclaimers, but not both in the same ad. If you want to use clickable disclaimers, ensure the links are accessible on the Internet.
2. When creating or updating your ad, configure the disclaimer type via the `disclaimer_type` field. The enum values are `TEXT_LINK` (clickable disclaimers) and `TEXT_ONLY` (text-only disclaimers).
3. Complete disclaimer settings.
	* For clickable disclaimers, pass in the text and URL to the `text` and `url` data fields in `disclaimer_clickable_texts` object. You can include up to three disclaimer objects. `disclaimer_text` must not be specified. Each text cannot include more than 40 characters. If multiple disclaimers are used, the total characters in these texts cannot exceed 40.
	* For text-only disclaimers, pass in the text to the `text` field in the `disclaimer_text` object. The text cannot include more than 90 characters. You can have only one `disclaimer_text` object in one ad. `disclaimer_clickable_texts` must not be specified.

>  <span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>When updating your ad, you cannot change the type of disclaimer. However, you can add a disclaimer to an existing ad, or update the text or url or both for the disclaimer in an existing ad. </li><li>Once added to an ad, the disclaimer cannot be deleted.</li></ul>

- For details about the relevant fields for Upgraded Smart+ Ads, see [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522), [/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs?id=1843317411665921), and [/smart_plus/ad/get/](https://business-api.tiktok.com/portal/docs?id=1843317378982914).
- For details about the relevant fields for Manual Ads, see [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354), [/ad/update/](https://business-api.tiktok.com/portal/docs?id=1739953405142018), and [/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770).


