---
title: "Advertising objective"
doc_id: 1737585562434561
path: "Marketing API / Campaign Management / Guides / Campaign / Advertising objective"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Advertising objective

Advertising objective refers to the business goals that you want your campaigns to achieve. For example, you want to use ads to get as many users interested in your business to visit your website or download your App. In the above cases, you can set your advertising objectives to `TRAFFIC` and `APP_PROMOTION` respectively.

Advertising objectives are set at the campaign level. 

> <span style="color: dodgerblue">**Note**</span><br>You cannot modify an ad objective after it has been set.

TikTok API for Business supports the following ad objectives:

## Ad objectives for Auction ads

``` xtable
| Advertising objective<br> (<code>objective_type</code>){20%}| API Value{20%}|Description{60%} |
| --- | --- |---|
|[Reach](https://ads.tiktok.com/help/article?aid=836189882076656982)| `REACH`  |Show your ads with the maximum number of impressions in your targeted audience at the most efficient price. |
|[Traffic](https://ads.tiktok.com/help/article?aid=6715316029142073350)| `TRAFFIC` |Drive people to any URL, such as your website's landing page, a blog post, app etc. |
|[Video views](https://ads.tiktok.com/help/article?aid=834107546545412106)| `VIDEO_VIEWS`  |Maximize plays of your video ads from audiences most likely to engage with them. |
|[Lead generation](https://ads.tiktok.com/help/article?aid=10001624) | `LEAD_GENERATION` | Collect leads for your business with a fast-loading, customizable instant form in the TikTok app. |
|[Community interaction](https://ads.tiktok.com/help/article?aid=10008245) | `ENGAGEMENT`  | Get more people to engage with your TikTok account by driving followers or increasing traffic to your profile page.|
|[App promotion](https://ads.tiktok.com/help/article?aid=10013496)| `APP_PROMOTION`  |Get people to install your mobile app and re-engage your app's current users, or get new users to pre-register before your app launches. |
|[Website conversions](https://ads.tiktok.com/help/article?aid=10013497)| `WEB_CONVERSIONS`  |Send people to your website, or TikTok Instant Page, to perform a specific action like making a purchase or adding an item to their cart. |
|Product sales| `PRODUCT_SALES`|The Product sales objective is designed to offer a more straightforward way for Commerce advertisers to deliver ads and sell products from TikTok Shop, websites, and apps. Shopping ads combines the current video-based ads, image-based ads and live shopping-based ad solutions into one simple objective—Product Sales. <br><br><p><span style="color:darkred"><b>Note</b></span>: [Video Shopping Ads (for Catalog)](https://business-api.tiktok.com/portal/docs?id=1750361698613249) is currently an allowlist-only feature for Commerce App advertisers. If you are a Commerce App advertiser or a non-Commerce advertiser and would like to access it, please contact your TikTok representative. If you are a Web Commerce advertiser, you automatically get access to the feature.|
|Sales| `WEB_CONVERSIONS` or `PRODUCT_SALES`|The Website Conversions and Product Sales advertiser objectives merged into one single Sales objective. Learn more about [Sales objective](https://business-api.tiktok.com/portal/docs?id=1834189283112962).<br><br><p><span style="color:darkred"><b>Note</b></span>: To create a Sales campaign, you need to specify `virtual_objective_type` and `sales_destination` simultaneously. Learn about how to [upgrade from Product Sales or Website Conversions to the Sales objective](https://business-api.tiktok.com/portal/docs?id=1834189985160193).|
```
## Ad objectives for Reach & Frequency ads
```xtable
| Objective{20%}| API Value{30%}|Description{50%} |
| --- | --- |---|
|[Reach](https://ads.tiktok.com/help/article?aid=836189882076656982)| `RF_REACH` (allowlisted)|Show your ads with the maximum number of impressions in your targeted audience at the most efficient price. |
```

