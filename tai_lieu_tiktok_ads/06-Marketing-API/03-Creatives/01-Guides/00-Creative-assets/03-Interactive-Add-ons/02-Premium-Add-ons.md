---
title: "Premium Add-ons"
doc_id: 1749019676181505
path: "Marketing API / Creatives / Guides / Creative assets / Interactive Add-ons / Premium Add-ons"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Premium Add-ons

You can enhance your ads with any of the following three Premium Add-on types as interactive add-ons.
- A Pop-out Showcase consists of an image and a call-to-action text. It pops up after an ad plays for several seconds. 
- A Gesture consists of an image and a pop-up card. It invites the audience to tap or swipe on an ad in order to unveil rewards or more information. This is a strong invitation for the audience to interact since they get a payoff by physically engaging with the ad.
- Superlike consists of an image and a pop-up window. It engages the audience with a surprise visual element after they like a video ad. With Superlike, heart-shaped and customized icons float across the screen when the audience likes or double-taps on the ads.

# Use Pop-out Showcase
To use a Pop-out Showcase in your ad, you need to perform the following steps. 

## Prerequisites
Upload an image as the Premium Badge image using [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642). The `image_id` in the response will be used to create a Pop-out Showcase portfolio. 
<br>Ensure the image meets the following specification requirements:
  - Dimensions: 448 pixels in width and 448 pixels in height.
  - Image format: JPG, JPEG, PNG, or GIF.
  - File size: within 1 MB.
  
## Steps
1. Use [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426) to create a Pop-out Showcase portfolio. 
- Set `creative_portfolio_type` to `PREMIUM_BADGE`.
- Pass in your image ID to the `image_id` field within the `badge_image_info` object.
- Specify the time when the premium badge starts to show via the `badge_show_time`, for example, 5 seconds. The first three seconds and the last five seconds of the video ad cannot be used as the badge start time.
- Set a call-to-action text via the `call_to_action_text` data field.
- Configure the relative coordinates of the top-left corner of the badge via the `badge_position` object. `position_x` is the x coordinate of the top-left corner divided by 720. `position_y` is the y coordinate of the top-left corner divided by 1280. 

This endpoint returns the ID of the portfolio (`creative_portfolio_id`) that has been created. 

2. Use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018) to create or update your ad.  

To use the portfolio in your ad, pass in`creative_portfolio_id` to the `card_id` field. 

> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li>Pop-out Showcases are only available for the advertising objectives <code>APP_PROMOTION</code>, <code>WEB_CONVERSIONS</code>, <code>TRAFFIC</code>, <code>VIDEO_VIEWS</code>, <code>REACH</code>, and <code>RF_REACH</code>.</li><li>Using Pop-out Showcase portfolios in campaigns with the advertising objective <code>APP_PROMOTION</code>, <code>WEB_CONVERSIONS</code> or <code>TRAFFIC</code> are currently separate allowlist-only features. If you would like to access them, please contact your TikTok representative.</li></ul>

# Use Gesture
To use a Gesture in your ad, you need to perform the following steps.
## Prerequisites
- Upload the gesture pop-up card image using [/file/image/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1739067433456642). The `image_id` in the response will be used to create a Gesture portfolio. 
<br>Ensure the image meets the following specification requirements:
  - Recommended dimensions: 620 pixels in width and 788 pixels in height.
  - Image format: JPG, JPEG, PNG, or GIF.
  - File size: within 3MB.
- Upload the gesture background using [/file/music/upload/](https://business-api.tiktok.com/portal/docs?id=1740052650395650). The `url` in the response will be used to create a Gesture portfolio. 
<br>Ensure the image meets the following specification requirements:
  - File size: within 1 MB.
  - File format: MP3.
  - Recommended duration: six seconds.

> <span style="color:DodgerBlue">**Note**</span><br>
>  To ensure a consistent ad experience, use the video ad background music as the Gesture background music.

## Steps
1. Use [/creative/portfolio/create/](https://business-api.tiktok.com/portal/docs?id=1739091950439426) to create a Gesture portfolio.
- Set `creative_portfolio_type` to `GESTURE`.
- Pass in your gesture pop-up card image ID to the `pop_up_window_image_id` field.
- Set an interactive guide text via the `call_to_action_text` field.
- Specify the time when the gesture starts to appear in your video via the `badge_show_time` field. The first three seconds and the last five seconds of the video ad cannot be used as the gesture start time.
- Configure the relative coordinates of the top-left corner of the gesture via the `position_x` and `position_y` parameters.
- Specify the gesture background music using `interactive_music_id`.
- Configure other details depending on the gesture type:
  - For a Click Gesture ( `gesture_type` is `CLICK`), do not pass in `angle`, `slide_length`, and `slide_dimension`.
  - For a Straight Slide Gesture ( `gesture_type` is `STRAIGHT_SLIDE`), specify the gesture angle and length using `angle` and `slide_length`, and do not pass in `slide_dimension`.
  - For a Curved Slide Gesture ( `gesture_type` is `CURVED_SLIDE`), specify the gesture angle and dimensions using `angle` and `slide_dimension`, and do not pass in `slide_length`.

This endpoint returns the ID of the portfolio (`creative_portfolio_id`) that has been created.

2. Use [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354) or [/ad/update/](https://business-api.tiktok.com/portal/docs?id=1739953405142018) to create or update your ad.

To use the portfolio in your ad, pass in `creative_portfolio_id` to the `card_id` field.

> <span style="color:DodgerBlue">**Note**</span><br>
> - The recommended duration of the video ad where a Gesture is used is at least 15 seconds.
> - Gestures are only available for the campaign advertising objectives <code>REACH</code>, <code>TRAFFIC</code>, <code>VIDEO_VIEWS</code> (with <code>optimization_goal</code> as <code>ENGAGED_VIEW</code>), <code>APP_PROMOTION</code>, <code>WEB_CONVERSIONS</code>, and <code>RF_REACH</code>.
> - Using Gesture portfolios in campaigns with the advertising objective <code>APP_PROMOTION</code>, <code>TRAFFIC</code>, or <code>WEB_CONVERSIONS</code> are currently separate allowlist-only features. If you would like to access them, please contact your TikTok representative.


# Use Superlike
To use a Superlike in your ad, you need to perform the following steps.
## Prerequisites
- Upload an image using [/file/image/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1739067433456642). The `image_id` in the response will be used to create a Superlike portfolio.
<br>Ensure the image meets the following specification requirements:
  - Recommended dimensions: 200 pixels in width and 200 pixels in height.
  - Image format: JPG, JPEG, or PNG.
  - File size: within 50KB.
- (Optional) If you want to add a custom pop-up window that will appear after the Superlike finishes floating across the screen, which will take the audience to the ad landing page when they click on it, upload another image for the pop-up window using [/file/image/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1739067433456642). The `image_id` in the response will be used to create a Superlike portfolio.
<br>Ensure the image meets the following specification requirements:
  - Recommended dimensions: 620 pixels in width and 788 pixels in height.
  - Image format: JPG, JPEG, PNG, or GIF.
  - File size: within 3MB.
  
## Steps
1. Use [/creative/portfolio/create/](https://business-api.tiktok.com/portal/docs?id=1739091950439426) to create a Superlike portfolio.
- Set `creative_portfolio_type` to `SUPER_LIKE`.
- Pass in your image ID to the `image_id` field.
- Optionally, specify a pop-up window image via the `pop_up_window_image_id` field.
  
This endpoint returns the ID of the portfolio (`creative_portfolio_id`) that has been created.

2. Use [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354) or [/ad/update/](https://business-api.tiktok.com/portal/docs?id=1739953405142018) to create or update your ad.
- To use the portfolio in your ad, pass in `creative_portfolio_id` to the `card_id` field.

> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li>Superlikes are only available for the campaign advertising objectives <code>REACH</code>, <code>TRAFFIC</code>, <code>VIDEO_VIEWS</code> (with <code>optimization_goal</code> as <code>ENGAGED_VIEW</code>), <code>ENGAGEMENT</code> (with <code>optimization_goal</code> as <code>PAGE_VISIT</code>), <code>APP_PROMOTION</code>, <code>WEB_CONVERSIONS</code>, and <code>RF_REACH</code>.</li><li>Using Superlike portfolios in campaigns with the advertising objective <code>APP_PROMOTION</code> or <code>TRAFFIC</code> are currently separate allowlist-only features. If you would like to access them, please contact your TikTok representative.</li></ul>



