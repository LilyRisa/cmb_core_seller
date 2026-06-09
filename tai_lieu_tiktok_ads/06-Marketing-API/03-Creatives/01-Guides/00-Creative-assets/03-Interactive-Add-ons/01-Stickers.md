---
title: "Stickers"
doc_id: 1749019667506177
path: "Marketing API / Creatives / Guides / Creative assets / Interactive Add-ons / Stickers"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Stickers

# Countdown Sticker
You can now use Countdown Sticker type in your ads. Countdown Sticker is an add-on creative type that allows you to add a timer to your video ad to catch your audience's attention and create a feeling of urgency. See [here](https://ads.tiktok.com/help/article?aid=10007621) to find out the detailed introductions and screenshots of Countdown Sticker. 

> <span style="color: DodgerBlue">**Note**</span><br>
>  <ul><li>Regular Countdown Sticker (`sticker_type` as `COUNTDOWN`) can only be used for auction ads with the advertising objective (`objective_type`) as Reach (`REACH`), Traffic (`TRAFFIC`), Video views (`VIDEO_VIEW`), App promotion (`APP_PROMOTION`), Lead generation (`LEAD_GENERATION`), Website conversions (`WEB_CONVERSIONS`), or Product sales (`PRODUCT_SALES`), as well as for R&F (`RF_REACH`) ads.</li><li>Reminder Countdown Sticker (`sticker_type` as `REMINDER_COUNTDOWN` or `LIVE_REMINDER_COUNTDOWN`) can only be used for auction ads with the advertising objective (`objective_type`) as Reach (`REACH`), Traffic (`TRAFFIC`), Video views (`VIDEO_VIEW`), Community interaction (`ENGAGEMENT`), App promotion (`APP_PROMOTION`), Lead generation (`LEAD_GENERATION`), Website conversions (`WEB_CONVERSIONS`), or Product sales (`PRODUCT_SALES`), as well as for R&F (`RF_REACH`) ads. <ul><li>Reminder Countdown Sticker (`sticker_type` as `REMINDER_COUNTDOWN` or `LIVE_REMINDER_COUNTDOWN`) is an allowlist-only feature for Traffic (`TRAFFIC`), App promotion (`APP_PROMOTION`), Website conversions (`WEB_CONVERSIONS`) ads. If you would like to access it for ads with these objectives, please contact your TikTok representative. </li></ul></li><li> If you want to use Countdown Stickers in Lead Generation ads, you can only set the Optimization location as Website at the ad group level. To learn about how to create such ads, see [Create a Lead Generation ad with optimization location as Website](https://business-api.tiktok.com/portal/docs?id=1774482976097281). </li></ul>


## Use Countdown Sticker
To use a Countdown Sticker in your ad, you need to perform the following steps. 
1. Use [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426) to create a Countdown Sticker portfolio.
- Set `creative_portfolio_type` to `STICKER`.
- Pass the value `COUNTDOWN`, `REMINDER_COUNTDOWN` or `LIVE_REMINDER_COUNTDOWN` to the `sticker_type` field according to the type of sticker you want to create.
- Configure the basic settings of the sticker via `title`, `cutoff_time`, `color`, `display_angle`, `position_x`, `position_y` , `size` (or `predefined_placement` instead of `position_x`, `position_y` and `size`), and `opacity`.
- Configure the special settings for stickers with reminders (when `sticker_type` is not `COUNTDOWN`):
  - For LIVE stickers with reminder:  set `reminder_time`  as one minute, five minutes, or ten minutes after the LIVE event starts, and pass in `live_tiktok_user_id`.
  - For non-LIVE stickers with reminder: set `reminder_time`  as one minute, one hour, or one day before the non-LIVE event, and pass in `landing_page_url`.
  
  This endpoint returns the ID of the portfolio (`creative_portfolio_id`) that has been created. 
2.  Use the portfolio in your ad.
- For Upgraded Smart+ Ads: Pass the value of `creative_portfolio_id` to the `card_id` field when you use [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522) or [/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs?id=1843317411665921) to create or update your ad.
- For Manual Ads: Pass the value of `creative_portfolio_id` to the `card_id` field when you use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018) to create or update your ad.
  
# Gift Code Sticker
You can now use Gift Code Sticker type in your ads. Gift Code Sticker is an add-on creative type that allows you to add an exclusive promotional code to your video ad. See [here](https://ads.tiktok.com/help/article/interactive-add-on-create-ad-gift-code-sticker?lang=en) to find out the detailed introductions and screenshots of Gift Code Sticker.

> <span style="color: DodgerBlue">**Note**</span><br>
> Gift Code Stickers can only be used for auction ads with the advertising objective (`objective_type`) as Reach (`REACH`), Traffic (`TRAFFIC`), Video views(`VIDEO_VIEW`), App promotion (`APP_PROMOTION`),  Lead generation(`LEAD_GENERATION`), Website conversions (`WEB_CONVERSIONS`), or Product sales (`PRODUCT_SALES`), as well as for R&F ads with the advertising objective(`objective_type`) as Reach (`RF_REACH`). <ul><li> If you want to use Gift Code Stickers in Lead Generation ads, you can only set the Optimization location as Website. To learn about how to create such ads, see [Create a Lead Generation ad with optimization location as Website](https://business-api.tiktok.com/portal/docs?id=1774482976097281).</li></ul>

## Use Gift Code Sticker
To use a Gift Code Sticker in your ad, you need to perform the following steps.
1. Use [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426) to create a Gift Code Sticker portfolio.
- Set `creative_portfolio_type` to `STICKER`.
- Pass in `GIFTCODE` value to the `sticker_type` field.
- Configure the basic settings of the sticker via `title`, `giftcode`, `color`,  `position_x` and `position_y` (or `predefined_placement` instead of `position_x` and `position_y`).
- This endpoint returns the ID of the portfolio (`creative_portfolio_id`) that has been created.
2.  Use the portfolio in your ad.
- For Upgraded Smart+ Ads: Pass the value of `creative_portfolio_id` to the `card_id` field when you use [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522) or [/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs?id=1843317411665921) to create or update your ad.
- For Manual Ads: Pass the value of `creative_portfolio_id` to the `card_id` field when you use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018) to create or update your ad.
  
