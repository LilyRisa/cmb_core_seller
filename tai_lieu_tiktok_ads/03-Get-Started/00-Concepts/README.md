---
title: "Concepts"
doc_id: 1738928364967937
path: "Get Started / Concepts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Concepts

This article explains the concepts used throughout API For Business. 

# Platform
```xtable
|Concepts{30%} | Definition {70%}|
|---|---|
|TikTok for Business|TikTok for Business is a global platform that provides products and solutions to help brands of all sizes drive business impact. Visit its [website](https://www.tiktok.com/business/en-US) to learn more. |
|TikTok Creator Marketplace|TikTok Creator Marketplace (TTCM) is a platform that allows influencer platforms and agencies to accelerate the growth of creator and branded content opportunities on TikTok. Visit its [website](https://creatormarketplace.tiktok.com/ad#/dashboard) to learn more. |
|TikTok Ads Manager|TikTok Ads Manager is an ad platform where advertisers can create and manage TikTok ad campaigns and ad creatives, view and analyze ad performance reports. Visit its [website](https://ads.tiktok.com/i18n/login/?redirect=https%3A%2F%2Fads.tiktok.com%2Fi18n%2Fdashboard%3Faadvid%3D72391499277%26is_new_connect%3D0%26is_new_user%3D0) to learn more. |
|TikTok Business Center|TikTok Business Center is a one-stop business hub that enables organizations to centralize assets management, permission allocation, also allows advertisers to manage multiple TikTok ad accounts among multiple users in a safe, efficient way. Visit its [website](https://business.tiktok.com/) to learn more. |
|TikTok Business Account|TikTok Business Account is a unique type of TikTok account intended for use by businesses on TikTok. It offers an extended suite of functionality that services their promotional and commercial needs.Visit its [website](https://www.tiktok.com/business/en/solutions/business-account) to learn more. |
|TikTok Personal Account|TikTok Personal Account is a type of TikTok account intended for general users on TikTok. |
```
      
# API Platform
```xtable
|Concepts{30%} | Definition {70%}|
|---|---|
|TikTok API for Business|TikTok API for Business is a series of interface services provided by TikTok for Business to developers. Through integrating and calling the TikTok API for Business interface, developers can leverage our interface to interact with TikTok Ads Manager, TikTok Business Center  and TikTok Creator Marketplace functionalities. Technology Company, Direct Advertiser and Agency can all leverage TikTok API for Business to create and manage campaigns. |
|TikTok for Developers|TikTok for Developers provide tools for creators and researchers. It provides features including Login Kit, Video Kit, Embeded Videos, and Greenscreen Kit. Visit [https://developers.tiktok.com/](https://developers.tiktok.com/) to learn more.|
```

# Authorization
```xtable
|Concepts{30%} | Definition {70%}|
|---|---|
|app_id|An Application identifier used when making API calls. To get `app_id`, follow the instructions in [Get Started - Create a developer app](https://ads.tiktok.com/marketing_api/docs?id=1738855242728450).|
|secret|Each application has a unique secret key. The `app_id` and `secret` are obtained together when your application has been approved.|
|callback address|Each application has a callback address which is invoked when the authorization is done successfully. After the app has been successfully authorized, the callback address will load and the authorization code `auth_code` and `state` will be available in the URL. <br><p><span style="color:darkred"><b>Note</b></span>: Please ensure that the callback address can successfully receive the callback information from the internet. Example of the authorization URL： https://ads.tiktok.com/marketing_api/auth?app_id=XXX&state=xxx&scope=xxx&redirect_uri= Callback address.|
|scope|Authorization permission scope. If the scope is not defined in the authorization URL, all permissions in the current application will be granted upon authorization.|
|redirect URL|Used to pass the callback address when setting the authorization URL. It is shown as `redirect_url` in the authorization URL. It must be the same as the callback address.|
|auth_code|After a successful authorization, the authorization code `auth_code` is obtained by the developer through the callback address. It can be used to [Generate an access token](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).<br><p><span style="color:darkred"><b>Note</b></span>: The `auth_code` is only valid for 1 hour and can only be used once.|
|access token|Access token is the authorized credential for accessing the API interface. It is used by the server to authenticate API requests. It can be obtained through the [Generate an access token](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162) endpoint.|
|core_user_id|The TikTok for Business user ID for the App developer. You can get this ID via the [/user/info/](https://ads.tiktok.com/marketing_api/docs?rid=7xusz43oh3h&id=1739665513181185) endpoint. 
```

# Campaign 
```xtable
|Concepts{30%} | Definition {70%}|
|---|---|
|advertiser_id|The ID of advertisers that authorized a developer application. You can use [/oauth2/advertiser/get/](https://ads.tiktok.com/marketing_api/docs?id=1738455508553729) to obtain a list of advertiser accounts that authorized an app.|
|campaign_id|Campaign ID. It is used in campaign related operations as a unique identifier.|
|adgroup_id|Ad group ID. It is used in adgroup related operations as a unique identifier of an ad group.|
|ad_id|Ad ID.  It is used in ad related operations as a unique identifier of an ad.|
```

# Creatives
```xtable
|Concepts{30%} | Definition {70%}|
|---|---|
|tiktok_item_id|The ID of the TikTok post to be used when you create [Spark Ads](https://ads.tiktok.com/marketing_api/docs?id=1739470744631298). You can get this ID via the `item_id` from the response of [/identity/video/get/](https://ads.tiktok.com/marketing_api/docs?id=1740218475032577) endpoint. |
|video_id|Video ID. Video is a type of creative assets you can use to create non-Spark Ads or Spark Ads([by pushing video ads to linked Business Account](https://ads.tiktok.com/marketing_api/docs?id=1739470744631298#item-link-Push%20a%20video%20ad%20to%20linked%20Business%20Account)). See [here](https://ads.tiktok.com/marketing_api/docs?id=1738963828459521) to find our how to upload videos. |
|image_id|Image ID. Image is a type of creative assets you can use to create an ad. See [here](https://ads.tiktok.com/marketing_api/docs?id=1738963828459521) to find our how to upload images. |
|music_id|Music ID. Music is a type of creative assets you can use to create an ad. See [here](https://ads.tiktok.com/marketing_api/docs?id=1738963828459521) to find our how to upload music.|
|file_id|The ID of the file that you have uploaded. You can upload video, image and music files to TikTok's temporary file repository. See [here](https://ads.tiktok.com/marketing_api/docs?id=1738963828459521) to find our how to upload files.|
|page_id|Page (Instant Form, Custom TikTok Instant Page, App Profile Page, or Storefront page) ID. Page is a type of creative assets you can use to create an ad. You can get page ID via the [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) endpoint. |
|creative_portfolio_id|The ID of the creative portfolio that you want to get. You can get the portfolio id (`creative_portfolio_id`) via the [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426) endpoint.|
|card_id|Card (Display Card, Premium Badge, Countdown Sticker, or Download Card) ID. Card is a type of creative assets you can use to create an ad. See [here](https://ads.tiktok.com/marketing_api/docs?id=1749019605861377) to find out how to get and use card ID. |
|call_to_action_id|The ID of the Call-to-action(CTA) portfolio. A CTA portfolio is a group of auto-optimized CTA that you want to use in your ads. See [here](https://ads.tiktok.com/marketing_api/docs?id=1749018853745665&rid=5ck9pjpg16j) to find out how to get and use `call_to_action_id`.|
```
