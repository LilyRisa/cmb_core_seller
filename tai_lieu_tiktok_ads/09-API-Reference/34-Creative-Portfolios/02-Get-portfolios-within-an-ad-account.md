---
title: "Get portfolios within an ad account"
doc_id: 1766324010279938
path: "API Reference / Creative Portfolios / Get portfolios within an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get portfolios within an ad account

Use this endpoint to retrieve portfolios created within an ad account. You can filter the results based on the types or IDs of creative portfolios.

><span style="color:DodgerBlue">**Note**</span><br> 
> This endpoint supports retrieving the following types of portfolios created within an ad account: <ul><li>Display Card portfolio</li><li>Website info card portfolio</li><li>Download Card portfolio</li><li>Inventory Card portfolio</li><li>Product Card portfolio</li><li>Product Tiles portfolio</li><li>Countdown Sticker portfolio</li><li>Gift Code Sticker portfolio</li><li>Pop-up Showcase portfolio</li><li>Gesture portfolio</li><li>Superlike portfolio</li></ul>


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/portfolio/list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| filtering | object | Filtering conditions.<br> If not specified, you can retrieve all types of creative portfolios excluding CTA portfolios under the specified advertiser account. |
#| creative_portfolio_types | string[] | The types of creative portfolios that you want to filter the results by. <br>  Enum values: <ul><li>`CARD`: Display Card portfolio. </li><li>`WEB_INFO_CARD`: Website info card portfolio. </li><li>`DOWNLOAD_CARD`: Download Card portfolio.</li><li>`INVENTORY_CARD`: Inventory Card.</li><li>`PRODUCT_CARD`: Product Card portfolio.</li><li>`PRODUCT_TILE`: Product Tiles portfolio.</li><li>`COUNTDOWN_STICKER`: portfolio of a Countdown Sticker with no reminder.</li><li> `REMINDER_COUNTDOWN_STICKER`: portfolio of a Countdown Sticker with reminder for a non-LIVE event.</li><li>`LIVE_REMINDER_COUNTDOWN_STICKER`: portfolio of a Countdown Sticker with reminder for a LIVE event. </li><li>`GIFTCODE_STICKER`: Gift Code Sticker portfolio.</li><li>`PREMIUM_BADGE`: Pop-up Showcase portfolio (premium badge portfolio).</li><li>`GESTURE`: Gesture portfolio.</li><li>`SUPER_LIKE`: Superlike portfolio.</li></ul>|
#| creative_portfolio_ids | string[] | The list of creative portfolio IDs that you want to filter the results by. <br><br>Max size: 100.<br><br>To create a creative portfolio and obtain a creative portfolio ID, use [/creative/portfolio/create/](https://business-api.tiktok.com/portal/docs?id=1739091950439426). <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>CTA portfolio ID and type filtering are not supported.</li><li> You cannot use `creative_portfolio_types` to filter Product Card portfolios and Product Tiles portfolios. To filter these two types of portfolios, specify the portfolio IDs in `creative_portfolio_ids`. |
| page | integer | Current page number. <br> Default value: 1. <br> Value range: ≥ 1. |
| page_size | integer | Page size.<br> Default value: 20.<br>  Value range: [1, 100]. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/creative/portfolio/list/?advertiser_id={{advertiser_id}}&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| creative_portfolios | object[] | Information about the creative portfolios within the specified ad account. |
##| creative_portfolio_id | string | ID of the creative portfolio. |
##| creative_portfolio_type | string | Type of the creative portfolio. <br>  Enum values: <ul><li>`CARD`: Display Card portfolio. </li><li>`WEB_INFO_CARD`: Website info card portfolio. </li><li>`DOWNLOAD_CARD`: Download Card portfolio.</li><li>`INVENTORY_CARD`: Inventory Card.</li><li>`PRODUCT_CARD`: Product Card portfolio.</li><li>`PRODUCT_TILE`: Product Tiles portfolio.</li><li>`COUNTDOWN_STICKER`: portfolio of a Countdown Sticker with no reminder.</li><li> `REMINDER_COUNTDOWN_STICKER`: portfolio of a Countdown Sticker with reminder for a non-LIVE event.</li><li>`LIVE_REMINDER_COUNTDOWN_STICKER`: portfolio of a Countdown Sticker with reminder for a LIVE event. </li><li>`GIFTCODE_STICKER`: Gift Code Sticker portfolio.</li><li>`PREMIUM_BADGE`: Pop-up Showcase portfolio (premium badge portfolio).</li><li>`GESTURE`: Gesture portfolio.</li><li>`SUPER_LIKE`: Superlike. A Superlike engages the audience with a surprise visual element after they like a video ad. With Superlke, heart-shaped and customized icons float across the screen when the audience likes or double-taps on the ads.</li></ul> |
##| creative_portfolio_preview_url | string | Preview link for the creative portfolio. The link won't expire. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When `creative_portfolio_type` is `SUPER_LIKE`, the value of this field will be the default image of a heart-shaped icon in Superlikes. </li><li>The preview links of Product Tiles and Inventory Card portfolios are not available. Therefore, when `creative_portfolio_type` is `PRODUCT_TILE` or `INVENTORY_CARD`, the value of this field will be an empty string (`""`).</li></ul>|
##|card_type| string| Returned only when `creative_portfolio_type` is `CARD`.<br><br>Card type. <br><br>Enum value: `IMAGE` (Display Card).|
##| gesture_type | string | Returned when `creative_portfolio_type` is `GESTURE`.<br><br>Gesture type.<br><br>Enum values:<ul>  <li>`CLICK`: Click Gesture.</li>  <li>`STRAIGHT_SLIDE`: Straight Slide Gesture.</li>  <li>`CURVED_SLIDE`: Curved Slide Gesture.</li></ul>|
##|image_id | string| Returned only when `creative_portfolio_type` is `CARD`, `WEB_INFO_CARD`, `INVENTORY_CARD`, or `SUPER_LIKE`.<br><br>Image ID. |
##| pop_up_window_image_id | string | Returned in any of the following scenarios:<ul> <li>`creative_portfolio_type` is `GESTURE`.</li>  <li>`creative_portfolio_type` is `SUPER_LIKE` and a `pop_up_window_image_id` is specified during portfolio creation..</li></ul><br>The gesture pop-up card in the Gesture or the ID of the pop-up window image in the Superlike.<br><br><ul>  <li>When `creative_portfolio_type` is `GESTURE`, this field represents the gesture pop-up card that will appear after the viewer follows the gesture.</li>  <li>When `creative_portfolio_type` is `SUPER_LIKE`, this field represents the pop-up window that will appear after the Superlike finishes floating across the screen and will take the audience to the ad landing page when they click on it.</li></ul>|
##| title | string| Returned only when `creative_portfolio_type` is `WEB_INFO_CARD`.<br><br>The title of the card. |
##| selling_points | string[]| Returned only when `creative_portfolio_type` is `WEB_INFO_CARD`.<br><br>The reasons why your products are worth buying. |
##| content_url | string| Returned only when `creative_portfolio_type` is `WEB_INFO_CARD`.<br><br>The website URL of your landing page. <br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Specifying the `content_url` in a website info card via API is currently not supported. You can only retrieve the `content_url` of website info cards created via the TikTok Ads Manager.</li><li>For website info cards created via API, the value of this field will be an empty string (`""`).</li></ul> |
##|thumbnail_id | string| Returned only when `creative_portfolio_type` is `DOWNLOAD_CARD`, `PRODUCT_CARD`, `PRODUCT_TILE`, or `PREMIUM_BADGE`.<br><br> Thumbnail ID. |
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
##| layouts | string[] | Returned when `creative_portfolio_type`= `DOWNLOAD_CARD`.<br>The layout type of the Download Card. <br><br>Enum values: <ul><li>`TYPE_1`: Type 1, showing app description in the Download Card. </li><li> `TYPE_2` : Type 2, not showing app description in the Download Card. </li></ul>  When both `TYPE_1` and `TYPE_2` have been passed in this field, one Type 1 Download card and one Type 2 Download card will be created . |
##| description | string |Returned in any of the following scenarios:<ul><li>`creative_portfolio_type` is `DOWNLOAD_CARD` and the value of `layouts` includes `TYPE_1`. </li><li>`creative_portfolio_type` is `INVENTORY_CARD`.</li></ul><br>Description of the app that you want to promote in the Download Card. |
##| tags | string[] | Returned when `creative_portfolio_type` is `DOWNLOAD_CARD`. <br><br>Tags for the Download Card. <br><br>Enum values: <ul><li> `CATEGORIES`：Showing the category of the app you want to promote in the Download Card. </li><li>`FILESIZE`：Showing the file size of the app you want to promote in the Download Card. </li><li>`RATING`：Showing the rating of the app you want to promote in the Download Card. </li><li>`RANKING`：Showing the ranking of the app you want to promote in the Download Card. </li><li> `COMMENT_VOLUME`：Showing the number of comments for the app you want to promote in the Download Card. </li></ul> |
##| category_label | string | Returned when `creative_portfolio_type` is `DOWNLOAD_CARD` and `category_label` is specified during card creation.  <br><br>Category name of the app you want to promote in the Download Card. |
##| app_id | string | Returned when `creative_portfolio_type` is `DOWNLOAD_CARD`. <br><br> ID of the app that you want to promote in the Download Card. It's the App ID from Events Manager. |
##| sticker_param | object | Returned only when `creative_portfolio_type` is `COUNTDOWN_STICKER`, `REMINDER_COUNTDOWN_STICKER`, `LIVE_REMINDER_COUNTDOWN_STICKER`, or `GIFTCODE_STICKER`.<br><br>Details of the Countdown Sticker or Gift Code Sticker you want to create. You can see an example of Countdown Sticker in [Countdown Sticker](https://ads.tiktok.com/help/article?aid=10007423) and an example of Gift Code Sticker in [Gift Code Sticker](https://ads.tiktok.com/help/article/tiktok-interactive-add-on-gift-code-sticker?lang=zh). <br><br><p><span style="color:darkred"><b>Note</b></span>: You can only create one Countdown Sticker or Gift Code Sticker at a time. |
###| sticker_type | string | Sticker type. <br><br>Enum values: <ul><li>`COUNTDOWN`: A Countdown Sticker with no reminder.</li><li>`REMINDER_COUNTDOWN`: A Countdown Sticker with reminder for a non-LIVE event.</li><li>`LIVE_REMINDER_COUNTDOWN`: A Countdown Sticker with reminder for a LIVE event. </li><li>`GIFTCODE`: A Gift Code Sticker that aims to gain new customers and engage existing customers by providing virtual gifts or coupon redemption codes.</li></ul> |
###| title | string | Sticker title. <br><ul><li>For a Countdown Sticker, the maximum length is 54, in UTF-8 bytes, and the hashtag symbol (#) is not supported.</li><li>For a Gift Code Sticker, the maximum length is 20 in UTF-8 bytes.</li></ul> |
###| giftcode | string | Gift code. <br>This field is only applicable for Gift Code Stickers. The maximum length is 10 in UTF-8 bytes (10 letters and numbers). <br><br><p><span style="color:darkred"><b>Note</b></span>:  This field is returned only when `creative_portfolio_type` is `GIFTCODE_STICKER`.|
###| cutoff_time | string | Countdown deadline (when `sticker_type` is `COUNTDOWN` or `REMINDER_COUNTDOWN`) or LIVE start time (when `sticker_type` is `LIVE_REMINDER_COUNTDOWN`) for the Countdown Sticker, in the format of "2022-10-30 00:00:00" (UTC+0 Time). <br><p><span style="color:darkred"><b>Note</b></span>:<ul><li> The time your pass in via this field is regarded as UTC+0 time by default and you cannot modify the default time zone.</li><li>This field is returned only when `creative_portfolio_type` is `COUNTDOWN_STICKER`, `REMINDER_COUNTDOWN_STICKER`, or `LIVE_REMINDER_COUNTDOWN_STICKER`.</li></ul> |
###| color | string | Background color of the sticker. <br><ul><li>For a Countdown Sticker, the supported enum values are `ORANGE`(orange), `BLACK`(black), `RED`(red), and`BLUE`(blue).</li><li>For a Giftcode Sticker created through the API, only `BLACK` and `BLUE` will be returned. However, `ORANGE` and `RED` may be returned for a Gift Code Sticker created on TTAM when orange and red were supported for Gift Code Stickers.</li></ul>|
###| display_angle | integer | Sticker display angle.  <br> <br>Value range: `[-180, +180]`. `+90` means to rotate the sticker clockwise by 90 degrees. <br> <br><span style="color:darkred"><b>Note</b></span>: This field is not returned for a Gift Code Sticker created through the API. However, it may be returned for a Gift Code Sticker created on TTAM when the `display_angle` setting was supported for Gift Code Stickers.|
###| predefined_placement | string | <ul><li>If the portfolio is a Gift Code Sticker and the predefined placement of the sticker corresponds to an enum value in [Predefined placements](https://business-api.tiktok.com/portal/docs?id=1739091950439426#item-link-Predefined%20placements), this field returns  x-axis and y-axis coordinates. </li><li>If the portfolio is a Countdown Sticker and the predefined placement of the sticker corresponds to an enum value in [Predefined placements](https://business-api.tiktok.com/portal/docs?id=1739091950439426#item-link-Predefined%20placements), this field returns  x-axis and y-axis coordinates, and size. </li></ul><br>For example:<br>If the x-axis coordinate, y-axis coordinate and size of the Countdown Sticker are 196, 148, and 0.8, respectively, which corresponds to the enum value `196x148x0.8` in [Predefined placements](https://business-api.tiktok.com/portal/docs?id=1739091950439426#item-link-Predefined%20placements), this field will be returned as `196x148x0.8`.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the sticker does not correspond to any enum value in [Predefined placements](https://business-api.tiktok.com/portal/docs?id=1739091950439426#item-link-Predefined%20placements),  this field returns `null`. |
###| position_x | integer | The x-axis coordinate relative to the top-left corner of the screen. |
###| position_y | integer | The y-axis coordinate relative to the top-left corner of the screen. |
###| size | string | Sticker size by percentage. Enum values: `0.6`(60%), `0.7`(70%), `0.8`(80%), `0.9`(90%), `1`(100%), `1.1`(110%). <br> <br><span style="color:darkred"><b>Note</b></span>: This field is not returned for a Gift Code Sticker created through the API. However, it may be returned for a Gift Code Sticker created on TTAM when the `size` setting was supported for Gift Code Stickers.|
###| opacity | string | Sticker opacity. Enum values: `0.7`, `0.8`, `0.9`, `1`. The lower the value, the more transparent the sticker will be. <br><br><span style="color:darkred"><b>Note</b></span>: This field is not returned for a Gift Code Sticker created through the API. However, it may be returned for a Gift Code Sticker created on TTAM when the `opacity` setting was supported for Gift Code Stickers.|
###| reminder_time| string | The time set for the reminder. <br><br>This field returns a non-null value when `creative_portfolio_type` is `REMINDER_COUNTDOWN_STICKER` or `LIVE_REMINDER_COUNTDOWN_STICKER`.<br><br>For non-LIVE stickers (when `sticker_type`= `REMINDER_COUNTDOWN`), the enum values are:<ul><li>`ONE_MINUTE_EARLIER`: send the reminder one minute before the non-LIVE event.</li><li>`ONE_HOUR_EARLIER `: send the reminder one hour before the non-LIVE event.</li><li>`ONE_DAY_EARLIER`: send the reminder one day before the non-LIVE event.</li></ul><br>For LIVE stickers (when `sticker_type`= `LIVE_REMINDER_COUNTDOWN`), the enum values are:<ul><li> `ONE_MINUTE_AFTER`: send the reminder one minute after the LIVE event starts.</li><li>`FIVE_MINUTES_AFTER`: send the reminder five minutes after the LIVE event starts.</li><li>`TEN_MINUTES_AFTER`: send the reminder ten minutes after the LIVE event starts.</li></ul> |
###| landing_page_url| string | The landing page URL you will be redirected to when you click the reminder after the countdown for an non-LIVE event ends. <br><br>This field returns a non-null value when `creative_portfolio_type` is `REMINDER_COUNTDOWN_STICKER`. |
###| live_tiktok_user_id | string | TikTok user ID of the LIVE event host. After specifying this field, you will be redirected to the host's LIVE room when you click the reminder for a LIVE event. <br><br>This field returns a non-null value when `creative_portfolio_type` is `LIVE_REMINDER_COUNTDOWN_STICKER`. |
##| card_show_price | boolean | Returned only when `creative_portfolio_type` is `PRODUCT_CARD`. <br><br>Whether to show the price of products on the Product Card or not. 
##| card_tags | string[] | Returned only when `creative_portfolio_type` is `PRODUCT_CARD`. <br><br>The product tags that are shown on the Product Card. <br><br>Enum values: `BRAND` (Brand Name), `DESC` (Description). |
##| card_image_index | integer | Returned only when `creative_portfolio_type` is `PRODUCT_CARD` or `PRODUCT_TILE`. <br><br>Used to specify the image used in the Product Card or Product Tiles. <br><br> The number set via this field is used as index to query the Image URLs you have passed through `additional_image_urls` in [/catalog/product/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740497429681153). <br><br> For instance, `card_image_index` = 1 means that you will use the image via the second image URL in `additional_image_urls`. |
#| page_info | object | Pagination information. |
##| page | number | Current page number. |
##| page_size | number | Paging size. |
##| total_number| number | Total number of results. |
##| total_page | number | Total pages of results. |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "creative_portfolios": [
            {
                "image_id": "{{image_id}}",
                "creative_portfolio_id": "{{creative_portfolio_id}}",
                "creative_portfolio_preview_url": "{{creative_portfolio_preview_url}}",
                "card_type": "IMAGE",
                "creative_portfolio_type": "CARD"
            },
            {
                "image_id": "{{image_id}}",
                "creative_portfolio_id": "{{creative_portfolio_id}}",
                "creative_portfolio_preview_url": "{{creative_portfolio_preview_url}}",
                "card_type": "IMAGE",
                "creative_portfolio_type": "CARD"
            }
        ],
        "page_info": {
            "total_page": 1,
            "page": 1,
            "page_size": 100,
            "total_number": 2
        }
    }
}
(/code)
```
