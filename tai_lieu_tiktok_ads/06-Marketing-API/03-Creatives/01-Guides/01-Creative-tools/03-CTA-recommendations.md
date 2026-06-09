---
title: "CTA recommendations"
doc_id: 1740307296329730
path: "Marketing API / Creatives / Guides / Creative tools / CTA recommendations"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# CTA recommendations

Due to various reasons, the call-to-action (CTA) texts selected by advertisers might not have the best results. You may want to try CTAs that are recommended by TikTok based on advertiser industry and ads purposes. 

There are two types of recommended CTAs, the standard recommended CTAs and the Dynamic CTAs.

## Standard Recommended CTAs

A standard CTA recommendation is a fixed CTA that can be directly used in creating or updating ads. 

To use standard CTA recommendations, make a call to the [/creative/cta/recommend/](https://ads.tiktok.com/marketing_api/docs?id=1739362202742785) endpoint and select the `CTA_NORMAL` CTA type for the `asset_type` field. The call returns a group of recommended CTA, which can then be used in the `call_to_action` field in the [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354) or [/ad/update/](https://business-api.tiktok.com/portal/docs?id=1739953405142018) endpoint. 

## Dynamic CTAs

Dynamic CTAs are a group of candidate CTAs that can be used later. With this enabled, the system will automatically select CTA text based on your industry and the objective of your ads. Different CTA texts will be shown to different users in order to achieve optimal results.

 To use dynamic CTAs, you need to complete the following steps: 

1. Obtain a group of dynamic CTAs using [/creative/cta/recommend/](https://business-api.tiktok.com/portal/docs?id=1739362202742785). 

 You can select either the updated version or the legacy version of the endpoint. 

 **(Recommended) Option A: Using the updated version endpoint**

- Set `new_version` to `true`.
- Omit `asset_type` and `content_type`.
- Specify valid values for `advertiser_id`, `objective_type`, and `promotion_type`.
- (Optional) You can also specify valid values for other fields, including `language`, `app_id`, `placements`, `region_codes`, `optimization_goal`, `ad_texts`, and `landing_page_url`.

2. Create a portfolio of CTAs for the group of dynamic CTAs that you have obtained from Step 1 using [/creative/portfolio/create/](https://business-api.tiktok.com/portal/docs?id=1739091950439426).

- Set `creative_portfolio_type` to `CTA`.
- Set `asset_content` and `asset_ids` to the `asset_content` and `asset_ids` returned from `/creative/cta/recommend/` in Step 1.

 The response will include the ID of the portfolio (`creative_portfolio_id`) that has just been created.

3. Use the dynamic CTA portfolio in your ads.
- For Upgraded Smart+ Ads: Set `call_to_action_id` in the [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad/v1.3) or [/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs/update-an-upgraded-smart-ad/v1.3) endpoint to the dynamic CTA portfolio ID (`creative_portfolio_id`) that you have obtained from Step 2.
- For Manual Ads: Set `call_to_action_id` in the [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354) or [/ad/update/](https://business-api.tiktok.com/portal/docs?id=1739953405142018) endpoint to the dynamic CTA portfolio ID (`creative_portfolio_id`) that you have obtained from Step 2.

**Option B: Using the legacy version endpoint**

- Set `new_version` to `false`, or leave the field unspecified. 
- Set `asset_type` to `CTA_AUTO_OPTIMIZED`.
- Specify valid values for `advertiser_id` and `content_type`.


