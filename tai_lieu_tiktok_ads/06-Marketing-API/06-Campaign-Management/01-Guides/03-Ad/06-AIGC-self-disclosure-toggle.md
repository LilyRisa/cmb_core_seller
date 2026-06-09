---
title: "AIGC self-disclosure toggle"
doc_id: 1799186631031809
path: "Marketing API / Campaign Management / Guides / Ad / AIGC self-disclosure toggle"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# AIGC self-disclosure toggle

The AIGC (Artificial Intelligence Generated Content) self-disclosure toggle, also known as "This ad contains Al-generated content" setting, allows you to transparently disclose AI-generated content in your ads. Once you turn on the toggle for your ad, your ad will carry an "Advertiser labeled as Al-generated" label when users click the ad thumbnail in the TikTok search results page and view the ad in full. By turning on this toggle, you confirm that your content conforms to [our guidelines for Al-generated content](https://ads.tiktok.com/help/article/tiktok-ads-policy-misleading-and-false-content?lang=en).

You can turn on the toggle exclusively for non-Spark Ads but not for Spark Ads. Spark Ads will use the "AI-generated" label instead and TikTok may apply the "AI-generated" label to Spark Ads content that is detected to be completely AI-generated or significantly edited by AI. This ensures appropriate labeling for AI-generated content in different types of ads.



## Supported advertising objectives and ad formats
The following table outlines the supported advertising objectives and ad formats for the AIGC self-disclosure toggle:

```xtable
| Advertising objective<br> (<code>objective_type</code>){20%} | Supported ad formats{30%} | Ad-level configurations {50%}|
|---|---|---|
| <ul><li>`REACH`</li><li>`TRAFFIC`</li><li>`VIDEO_VIEWS`</li><li>`APP_PROMOTION` </li><li>`WEB_CONVERSIONS`  </li></ul> |<ul><li> Single Video </li><li> Standard Carousel </li></ul>| Set `ad_format` to any of the following values: <ul><li>`SINGLE_VIDEO`</li><li>`CAROUSEL_ADS`</li></ul>|
| `RF_REACH` | Single Video | Set `ad_format` to `SINGLE_VIDEO` |
| `LEAD_GENERATION` | Single Video | Set `ad_format` to `SINGLE_VIDEO` |
| `PRODUCT_SALES` | For Video Shopping Ads for Catalog: <ul><li>Dynamic Format</li><li> Single Video</li><li> Catalog Video</li><li>Catalog Carousel</li></ul>| If `campaign_product_source` is set to `CATALOG` at the campaign level and `shopping_ads_type` is set to `VIDEO` at the ad group level, use any of the following configurations: <ul><li>Set `dynamic_format` to `DYNAMIC_CREATIVE`</li><li>Set `ad_format` to `SINGLE_VIDEO` and `vertical_video_strategy` to `SINGLE_VIDEO`</li><li>Set `ad_format` to `SINGLE_VIDEO` and `vertical_video_strategy` to `CATALOG_VIDEOS`</li><li>Set `ad_format` to `CATALOG_CAROUSEL`</li></ul>|
```

## Steps
Based on your preference for the ad type where you want to turn on the AIGC self-disclosure toggle, you can create a standard ad, a Smart+ Campaign, or Smart Creative ads. For detailed instructions on creating each type, refer to their respective sections: "**For standard ads**", "**For Smart+ Campaigns**", and "**For Smart Creative ads**".

### For standard ads
To turn on the toggle for a standard ad, follow the following steps:

1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). 
- Set `objective_type` to a supported advertising objective based on <a href="#item-link-Supported advertising objectives and ad formats">Supported advertising objectives and ad formats</a>.

2. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). 
- Set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC` , or set `placement_type` to `PLACEMENT_TYPE_NORMAL` and include `PLACEMENT_TIKTOK` in the value of `placements`.

3. Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354).
- Specify a non-Spark Ads identity by setting `identity_type` to `CUSTOMIZED_USER`.
- Set `ad_format` to a supported ad format based on <a href="#item-link-Supported advertising objectives and ad formats">Supported advertising objectives and ad formats</a>.
- Set `aigc_disclosure_type` to `SELF_DISCLOSURE`.

### For Smart+ Campaigns

To turn on AIGC self-disclosure toggle for a Smart+ Campaign, follow the following steps while creating a Smart+ Campaign using [/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362):
- Set `objective_type` to `APP_PROMOTION` or `WEB_CONVERSIONS`.
- Set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC` , or set `placement_type` to `PLACEMENT_TYPE_NORMAL` and include `PLACEMENT_TIKTOK` in the value of `placements`.
- Specify a non-Spark Ads identity by setting `identity_type` to `CUSTOMIZED_USER`.
- Specify the videos and video covers via `video_info` and `image_info`.
- Set `aigc_disclosure_type` within the object `media_info` to `SELF_DISCLOSURE`.

### For Smart Creative ads
To turn on AIGC self-disclosure toggle for Smart Creative ads, follow the following steps while creating Smart Creative ads based on the instructions provided in [Smart Creative](https://business-api.tiktok.com/portal/docs?id=1767322784934914):
- When creating an ad group by using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114), set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC` , or set `placement_type` to `PLACEMENT_TYPE_NORMAL` and include `PLACEMENT_TIKTOK` in the value of `placements`.
- When creating Smart Creative ads by using [/ad/aco/create/](https://ads.tiktok.com/marketing_api/docs?id=1739473063234626):
	- Specify a non-Spark Ads identity by setting `identity_type` within the object `common_material` to `CUSTOMIZED_USER`.
	- Specify the videos and video covers via `video_info` and `image_info`.
	- Set `aigc_disclosure_type` within the object `media_info` to `SELF_DISCLOSURE`.
