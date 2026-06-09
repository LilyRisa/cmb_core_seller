---
title: "Get a portfolio by ID"
doc_id: 1739092113671170
path: "API Reference / Creative Portfolios / Get a portfolio by ID"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get a portfolio by ID

Use this endpoint to get an existing creative portfolio by its ID. You can get portfolios that are created with the [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426) endpoint. 

## Comparing v1.2 and v1.3
The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/creative/portfolio/get/|/v1.3/creative/portfolio/get/ |
| Request parameter data type | `advertiser_id`: number <br>`creative_portfolio_id`: number |`advertiser_id`: string <br>`creative_portfolio_id`: string|
| Response parameter name | `advanced_show_time` <br> `advanced_guide_text_bottom` <br> `advanced_position` <br> `advanced_image_info` <br> `portfolio_type`| `badge_show_time` <br> `call_to_action_text` <br> `badge_position` <br> `badge_image_info` <br> `creative_portfolio_type`|
| Response parameter data type | `asset_ids`: number[] <br>`portfolio_content`: object[]| `asset_ids`: string[]<br>`portfolio_content`: object|
| New response parameters |/|`creative_portfolio_id` <br>`sticker_param` (including all the parameters within the object ) <br>`tags`<br>`layouts`<br>`description` <br>`category_label` <br>`app_id` <br>`card_show_price`<br> `card_tags`<br> `card_image_index`<br>`title`<br>`selling_points`<br> `content_url`<br> `pop_up_window_image_id`<br>`gesture_type`<br>`slide_length`<br>`slide_dimension`<br>`interactive_music_id`|
| Response parameters deprecated in v1.3|/|The following parameters are deprecated as a result of `GAME_GIFT` card types no longer being supported: <br> `profile_image`<br> `display_name` <br> `gift_card_title`  <br> `gift_card_detail`  <br> `gift_card_code` <br> `thumbnail_url`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/portfolio/get/

**Method** GET

**Header**

``` xtable
|Field{35%}|Type{15%}|Description{50%}|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{35%}|Type{15%}|Description{50%}|
|--- |--- |--- |
|advertiser_id {required}|string| Advertiser ID|
|creative_portfolio_id {required}|string| ID of the creative portfolio that you want to get. <br>You can find the portfolio id (`creative_portfolio_id`) in the response of [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426).|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET https://business-api.tiktok.com/open_api/v1.3/creative/portfolio/get/?advertiser_id={{advertiser_id}}&creative_portfolio_id={{creative_portfolio_id}}
--header 'Access-Token: {{Access-Token}}'
--header 'Content-Type: application/json'
(/code)
```

## Response

``` xtable
|Field{35%}|Type{15%}|Description{50%}|
|-|-|-|
|message |string|The return message. For example, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|The returned data.|
#|creative_portfolio_id|string|Creative portfolio ID.|
#|creative_portfolio_type|string|Creative portfolio type. <br><br>Enum values: <ul><li>`CTA`: call-to-action text.</li><li>`CARD`: card (Display Card). </li><li>`WEB_INFO_CARD`: Website info card. </li><li>`DOWNLOAD_CARD`: Download Card.</li><li>`INVENTORY_CARD`: Inventory Card.</li><li> `PRODUCT_CARD`: Product Card. </li><li>`PRODUCT_TILE`: Product Tiles.</li><li>`STICKER`: Countdown Sticker or Gift Code Sticker. </li><li>`PREMIUM_BADGE`: Pop-up Showcase.</li><li>`GESTURE`: Gesture.</li><li>`SUPER_LIKE`: Superlike. A Superlike engages the audience with a surprise visual element after they like a video ad. With Superlke, heart-shaped and customized icons float across the screen when the audience likes or double-taps on the ads.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Website info cards can only be used for auction ads with the `objective_type` as `WEB_CONVERSIONS` or `TRAFFIC` (with `promotion_type` set to `WEBSITE`).</li><li>You can only use the Inventory Card portfolio in Automotive Ads.</li><li>Product Cards can only be used in Video Shopping Ads.</li><li>Product Tiles can only be used in Video Shopping Ads with product source as catalog.</li><li>Using a Product Tiles portfolio to create ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Countdown Sticker can only be used for auction ads with the advertising objective (`objective_type`) as Reach (`REACH`), Traffic (`TRAFFIC`), Video views(`VIDEO_VIEW`), App promotion (`APP_PROMOTION`), Lead generation(`LEAD_GENERATION`), Website conversions (`WEB_CONVERSIONS`), or Product sales (`PRODUCT_SALES`), as well as for R&F (`RF_REACH`) ads.<ul><li>Reminder Countdown Sticker (`sticker_type` as `REMINDER_COUNTDOWN` or `LIVE_REMINDER_COUNTDOWN`) is an allowlist-only feature for Traffic (`TRAFFIC`), App promotion (`APP_PROMOTION`), Website conversions (`WEB_CONVERSIONS`) ads. If you would like to access it for ads with these objectives, please contact your TikTok representative. </li></ul></li><li>Gift Code Sticker can only be used for auction ads with the advertising objective (`objective_type`) as Reach (`REACH`), Traffic (`TRAFFIC`), Video views(`VIDEO_VIEW`),  App promotion (`APP_PROMOTION`),  Lead generation(`LEAD_GENERATION`), Website conversions (`WEB_CONVERSIONS`), or Product sales (`PRODUCT_SALES`), as well as for R&F ads with the advertising objective(`objective_type`) as Reach (`RF_REACH`).</li><li>Gestures can only be used for auction ads with the `objective_type` (advertising objective) as `REACH`, `TRAFFIC`, `VIDEO_VIEWS` (with `optimization_goal` as `ENGAGED_VIEW`), `APP_PROMOTION`, `WEB_CONVERSIONS`, as well as for R&F ads with the advertising objective (`objective_type`) as Reach (`RF_REACH`).<ul>  <li>Using Gesture portfolios in campaigns with the advertising objective `APP_PROMOTION`, `TRAFFIC`, or `WEB_CONVERSIONS` are currently separate allowlist-only features. If you would like to access them, please contact your TikTok representative.</li></ul></li><li>Superlikes can only be used for auction ads with the `objective_type` (advertising objective) as `REACH`, `TRAFFIC`, `VIDEO_VIEWS` (with `optimization_goal` as `ENGAGED_VIEW`), `ENGAGEMENT` (with `optimization_goal` as `PAGE_VISIT`), `APP_PROMOTION`, `WEB_CONVERSIONS`, as well as for R&F ads with the advertising objective (`objective_type`) as Reach (`RF_REACH`).  <ul><li>Using Superlike portfolios in campaigns with the advertising objective `APP_PROMOTION` or `TRAFFIC` are currently separate allowlist-only features. If you would like to access them, please contact your TikTok representative. </li></ul></li></ul> |
#|portfolio_content|object[]|The content of the creative portfolio.|
##|asset_content|string|A call-to-action text. <br><br>For example, `"Learn More"`.|
##|asset_ids|string[]|A list of CTAs. <br><br>For example, `[201781, 201535]`.|
##|card_type| string| Card type. <br><br>Enum value: `IMAGE`(Display Card).|
##| gesture_type | string | Returned when `creative_portfolio_type` is `GESTURE`.<br><br>Gesture type.<br><br>Enum values:<ul>  <li>`CLICK`: Click Gesture.</li>  <li>`STRAIGHT_SLIDE`: Straight Slide Gesture.</li>  <li>`CURVED_SLIDE`: Curved Slide Gesture.</li></ul>|
##|image_id | string| Returned only when `creative_portfolio_type` is `CARD`, `WEB_INFO_CARD`, `INVENTORY_CARD`, or `SUPER_LIKE`.<br><br>Image ID. |
##| pop_up_window_image_id | string | Returned in any of the following scenarios:<ul> <li>`creative_portfolio_type` is `GESTURE`.</li>  <li>`creative_portfolio_type` is `SUPER_LIKE` and a `pop_up_window_image_id` is specified during portfolio creation..</li></ul><br>The gesture pop-up card in the Gesture or the ID of the pop-up window image in the Superlike.<br><br><ul>  <li>When `creative_portfolio_type` is `GESTURE`, this field represents the gesture pop-up card that will appear after the viewer follows the gesture.</li>  <li>When `creative_portfolio_type` is `SUPER_LIKE`, this field represents the pop-up window that will appear after the Superlike finishes floating across the screen and will take the audience to the ad landing page when they click on it.</li></ul>|
##| title | string| Returned only when `creative_portfolio_type` is `WEB_INFO_CARD`.<br><br>The title of the card. |
##| selling_points | string[]| Returned only when `creative_portfolio_type` is `WEB_INFO_CARD`.<br><br>The reasons why your products are worth buying. |
##| content_url | string| Returned only when `creative_portfolio_type` is `WEB_INFO_CARD`.<br><br>The website URL of your landing page. <br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Specifying the `content_url` in a website info card via API is currently not supported. You can only retrieve the `content_url` of website info cards created via the TikTok Ads Manager.</li><li>For website info cards created via API, the value of this field will be an empty string (`""`).</li></ul> |
##|thumbnail_id | string| Returned only when `creative_portfolio_type` is `DOWNLOAD_CARD`, `PRODUCT_CARD`, `PRODUCT_TILE`, or `PREMIUM_BADGE`.<br><br>Thumbnail ID. |
##|call_to_action_text|string| Returned when `creative_portfolio_type` is `GESTURE` or `PREMIUM_BADGE`.<br><br>Interactive guide text for the Gesture or Pop-out Showcase.|
##|badge_show_time|integer| Returned when `creative_portfolio_type` is `GESTURE` or `PREMIUM_BADGE`.<br><br>The time when the Gesture icon within the Gesture or the Premium Badge within the Pop-out Showcase will appear in your video, in seconds.|
##|badge_position|object| Returned when `creative_portfolio_type` is `GESTURE` or `PREMIUM_BADGE`.<br><br>Information about the position of the Gesture icon within a Gesture or the Premium Badge within a Pop-out Showcase.|
###|position_x| float| Relative X-axis value of the top-left corner of the Gesture icon within a Gesture or the Premium Badge within a Pop-out Showcase.|
###|position_y| float| Relative Y-axis value of the top-left corner of the Gesture icon within a Gesture or the Premium Badge within a Pop-out Showcase.|
###| angle  | number | Returned when `badge_position` is specified and any of the following conditions is met:<ul> <li>`creative_portfolio_type` is `GESTURE` and `gesture_type` is `STRAIGHT_SLIDE` or `CURVED_SLIDE`.</li> <li>`creative_portfolio_type` is `PREMIUM_BADGE`.</li></ul><br>The angle of the Straight Slide Gesture or Curved Slide Gesture icon within a Gesture or the Premium Badge within a Pop-out Showcase.|
##|badge_image_info|object|Returned when `creative_portfolio_type` is `PREMIUM_BADGE`.<br><br>Information about the badge image in the Pop-out Showcase.|
###|image_id|string|ID of the badge image in the Pop-out Showcase.|
##| slide_length  | number | Returned when `creative_portfolio_type` is `GESTURE` and `gesture_type` is `STRAIGHT_SLIDE`.<br><br>The length of the Straight Slide Gesture.|
##| slide_dimension  | object | Returned when `creative_portfolio_type` is `GESTURE` and `gesture_type` is `CURVED_SLIDE`.<br><br>The dimensions of the Curved Slide Gesture.|
###| dimension_width  | number | The width of the Curved Slide Gesture.|
###| dimension_height  | number | The height of the Curved Slide Gesture.|
##| interactive_music_id  | string | Returned when `creative_portfolio_type` is `GESTURE`.<br><br>The URL of the background music for the Gesture.|
##| layouts | string[] | Returned when `creative_portfolio_type`= `DOWNLOAD_CARD`.<br>The layout type of the Download Card. Enum values: <ul><li>`TYPE_1`: Type 1, showing app description in the Download Card. </li><li> `TYPE_2` : Type 2, not showing app description in the Download Card. </li></ul>  When both `TYPE_1` and `TYPE_2` have been passed in this field, one Type 1 Download card and one Type 2 Download card will be created . |
##| description | string | Returned in any of the following scenarios:<ul><li>`creative_portfolio_type` is `DOWNLOAD_CARD` and the value of `layouts` includes `TYPE_1`. </li><li>`creative_portfolio_type` is `INVENTORY_CARD`.</li></ul> <br>Description of the app that you want to promote in the Download Card or text of the Inventory Card. |
##| tags | string[] | Returned when `creative_portfolio_type`= `DOWNLOAD_CARD`. <br>Tags for the Download Card. Enum values: <ul><li> `CATEGORIES`：Showing the category of the app you want to promote in the Download Card. </li><li>`FILESIZE`：Showing the file size of the app you want to promote in the Download Card. </li><li>`RATING`：Showing the rating of the app you want to promote in the Download Card. </li><li>`RANKING`：Showing the ranking of the app you want to promote in the Download Card. </li><li> `COMMENT_VOLUME`：Showing the number of comments for the app you want to promote in the Download Card. </li></ul> |
##| category_label | string | Returned when `creative_portfolio_type`= `DOWNLOAD_CARD` and `category_label` is specified during card creation.  <br>Category name of the app you want to promote in the Download Card. |
##| app_id | string | Returned when `creative_portfolio_type`= `DOWNLOAD_CARD`. <br> ID of the app that you want to promote in the Download Card. It's the App ID from Events Manager. |
##| sticker_param | object | Details of the Countdown Sticker or Gift Code Sticker you want to create. You can see an example of Countdown Sticker in [Countdown Sticker](https://ads.tiktok.com/help/article?aid=10007423) and an example of Gift Code Sticker in [Gift Code Sticker](https://ads.tiktok.com/help/article/tiktok-interactive-add-on-gift-code-sticker?lang=zh). <br><br>Returned only when `creative_portfolio_type`=`STICKER`.<br><p><span style="color:darkred"><b>Note</b></span>: You can only create one Countdown Sticker or Gift Code Sticker at a time. |
###| sticker_type | string | Sticker type. <br><br>Enum values: <ul><li>`COUNTDOWN`: A Countdown Sticker with no reminder.</li><li>`REMINDER_COUNTDOWN`: A Countdown Sticker with reminder for a non-LIVE event.</li><li>`LIVE_REMINDER_COUNTDOWN`: A Countdown Sticker with reminder for a LIVE event. </li><li>`GIFTCODE`: A Gift Code Sticker that aims to gain new customers and engage existing customers by providing virtual gifts or coupon redemption codes.</li></ul> |
###| title | string | Sticker title. <br><ul><li>For a Countdown Sticker, the maximum length is 54, in UTF-8 bytes, and the hashtag symbol (#) is not supported.</li><li>For a Gift Code Sticker, the maximum length is 20 in UTF-8 bytes.</li></ul> |
###| giftcode | string | Gift code. <br>This field is only applicable for Gift Code Stickers. The maximum length is 10 in UTF-8 bytes (10 letters and numbers). |
###| cutoff_time | string | Countdown deadline (when `sticker_type`= `COUNTDOWN` or `REMINDER_COUNTDOWN`) or LIVE start time (when `sticker_type`=`LIVE_REMINDER_COUNTDOWN`) for the Countdown Sticker, in the format of "2022-10-30 00:00:00" (UTC+0 Time). <br><p><span style="color:darkred"><b>Note</b></span>: The time your pass in via this field is regarded as UTC+0 time by default and you cannot modify the default time zone. |
###| color | string | Background color of the sticker. <br><ul><li>For a Countdown Sticker, the supported enum values are `ORANGE`(orange), `BLACK`(black), `RED`(red), and`BLUE`(blue).</li><li>For a Giftcode Sticker created through the API, only `BLACK` and `BLUE` will be returned. However, `ORANGE` and `RED` may be returned for a Gift Code Sticker created on TTAM when orange and red were supported for Gift Code Stickers.</li></ul>|
###| display_angle | integer | Sticker display angle. Value range: `[-180, +180]`. `+90` means to rotate the sticker clockwise by 90 degrees. <br><span style="color:darkred"><b>Note</b></span>: This field is not returned for a Gift Code Sticker created through the API. However, it may be returned for a Gift Code Sticker created on TTAM when the `display_angle` setting was supported for Gift Code Stickers.|
###| predefined_placement | string | <ul><li>If the portfolio is a Gift Code Sticker and the predefined placement of the sticker corresponds to an enum value in [Predefined placements](https://business-api.tiktok.com/portal/docs?id=1739091950439426#item-link-Predefined%20placements), this field returns  x-axis and y-axis coordinates. </li><li>If the portfolio is a Countdown Sticker and the predefined placement of the sticker corresponds to an enum value in [Predefined placements](https://business-api.tiktok.com/portal/docs?id=1739091950439426#item-link-Predefined%20placements), this field returns  x-axis and y-axis coordinates, and size. </li></ul><br>For example:<br>If the x-axis coordinate, y-axis coordinate and size of the Countdown Sticker are 196, 148, and 0.8, respectively, which corresponds to the enum value `196x148x0.8` in [Predefined placements](https://business-api.tiktok.com/portal/docs?id=1739091950439426#item-link-Predefined%20placements), this field will be returned as `196x148x0.8`.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the sticker does not correspond to any enum value in [Predefined placements](https://business-api.tiktok.com/portal/docs?id=1739091950439426#item-link-Predefined%20placements),  this field returns `null`. |
###| position_x | integer | The x-axis coordinate relative to the top-left corner of the screen. |
###| position_y | integer | The y-axis coordinate relative to the top-left corner of the screen. |
###| size | string | Sticker size by percentage. Enum values: `0.6`(60%), `0.7`(70%), `0.8`(80%), `0.9`(90%), `1`(100%), `1.1`(110%). <br><span style="color:darkred"><b>Note</b></span>: This field is not returned for a Gift Code Sticker created through the API. However, it may be returned for a Gift Code Sticker created on TTAM when the `size` setting was supported for Gift Code Stickers.|
###| opacity | string | Sticker opacity. Enum values: `0.7`, `0.8`, `0.9`, `1`. The lower the value, the more transparent the sticker will be. <br><span style="color:darkred"><b>Note</b></span>: This field is not returned for a Gift Code Sticker created through the API. However, it may be returned for a Gift Code Sticker created on TTAM when the `opacity` setting was supported for Gift Code Stickers.|
###| reminder_time| string | The time set for the reminder. Returns value when `sticker_type`= `REMINDER_COUNTDOWN` or `LIVE_REMINDER_COUNTDOWN`.<br><br>For non-LIVE stickers (when `sticker_type`= `REMINDER_COUNTDOWN`), the enum values are:<ul><li>`ONE_MINUTE_EARLIER`: send the reminder one minute before the non-LIVE event.</li><li>`ONE_HOUR_EARLIER `: send the reminder one hour before the non-LIVE event.</li><li>`ONE_DAY_EARLIER`: send the reminder one day before the non-LIVE event.</li></ul><br><br>For LIVE stickers (when `sticker_type`= `LIVE_REMINDER_COUNTDOWN`), the enum values are:<ul><li> `ONE_MINUTE_AFTER`: send the reminder one minute after the LIVE event starts.</li><li>`FIVE_MINUTES_AFTER`: send the reminder five minutes after the LIVE event starts.</li><li>`TEN_MINUTES_AFTER`: send the reminder ten minutes after the LIVE event starts.</li></ul> |
###| landing_page_url| string | The landing page URL you will be redirected to when you click the reminder after the countdown for an non-LIVE event ends. Returns value when `sticker_type` is `REMINDER_COUNTDOWN`. |
###| live_tiktok_user_id | string | TikTok user ID of the LIVE event host. After specifying this field, you will be redirected to the host's LIVE room when you click the reminder for a LIVE event. <br>Returns value when `sticker_type` is `LIVE_REMINDER_COUNTDOWN`.  |
##| card_show_price | boolean | Whether to show the price of products on the Product Card or not. 
##| card_tags | string[] | The product tags that are shown on the Product Card. <br>Enum values: `BRAND` (Brand Name), `DESC` (Description). |
##| card_image_index | integer | Used to specify the image used in the Product Card or Product Tiles. <br><br> The number set via this field is used as index to query the Image URLs you have passed through `additional_image_urls` in [/catalog/product/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740497429681153). <br><br> For instance, `card_image_index` = 1 means that you will use the image via the second image URL in `additional_image_urls`. |
|request_id |string|Request log ID|
```

### Example

``` xcodeblock
(code JSON python)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "portfolio_content": [
            {
                "asset_ids": [
                    "201781",
                    "201535"
                ],
                "asset_content": "learn_more"
            },
            {
                "asset_ids": [
                    "201902",
                    "201404"
                ],
                "asset_content": "download_now"
            }
        ]
    },
    "request_id": "2020123008315601023125104010685"
}
(/code)
```
