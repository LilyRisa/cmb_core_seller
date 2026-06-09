---
title: "Playable Ads"
doc_id: 1737730803189826
path: "API Reference / Playable Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Playable Ads

Playable Ad is an interactive ad format that provides a short, engaging experience attracting users and getting them to download your app or game. With playable ads, users experience an app or game before they decide to download. This ad format is an effective way to optimize cost per impression (CPM) by gamifying your ad and getting the most out of each impression.

See [Playable Ads](https://ads.tiktok.com/help/article?aid=606210606111561159)  to learn about the use cases for Playable Ads, and how to make a Playable Ad.

><span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li> Starting September 30, 2024, you will no longer be able to create, update or copy Playable Ads delivered to the TikTok placement. Existing campaigns that contain Playable Ads delivered to the TikTok placement will stop running by September 30, 2024, at the latest. You can continue to retrieve the data of or run reports on these ads. Additionally, you can create, update or copy Playable Ads delivered to the Pangle placement (<code>PLACEMENT_PANGLE</code>) without any restrictions.</li> <li>Starting September 30, 2024, ad groups that contain [Playable Ads](https://business-api.tiktok.com/portal/docs?id=1737730803189826) delivered to TikTok placement can no longer be used to create split tests.</li></ul>  

## How to create playable ads
Creating Playable Ads can be achieved in three steps: 
1. Call the `/playable/upload/` endpoint to upload material package. After uploading, the package will be validated based on the specs listed in the Request parameters - Description section of `playable_package` field. If validation succeeds, the package will proceed into audit process.
2. Call the `/playable/validate/` endpoint to check the validation and audit status of the uploaded package. 
3. For playable whose status is `VALIDATE_SUCCESS` or `AUDIT_SUCCESS`, call the `/playable/save/` endpoint to complete the upload process. Note that only playable whose `status` is `AUDIT_SUCCESS` can be used for ad creation.

This group consists of the following endpoints:
* [Upload a playable creative](https://ads.tiktok.com/marketing_api/docs?id=1737730926765057)
* [Check the status of a playable creative](https://ads.tiktok.com/marketing_api/docs?id=1737732054892545)
* [Save a playable creative](https://ads.tiktok.com/marketing_api/docs?id=1737732203851777)
* [Get playable creatives](https://ads.tiktok.com/marketing_api/docs?id=1737732758495234)
* [Delete a playable creative](https://ads.tiktok.com/marketing_api/docs?id=1737733368940546)


