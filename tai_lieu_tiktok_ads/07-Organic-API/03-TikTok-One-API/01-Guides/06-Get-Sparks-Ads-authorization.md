---
title: "Get Sparks Ads authorization"
doc_id: 1740027794580481
path: "Organic API / TikTok One API / Guides / Get Sparks Ads authorization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Sparks Ads authorization

This article outlines how to use the TikTok One (TTO) API to request Spark Ads authorization for a creator's post.



# Steps

1. Advertiser requests the Spark Ads authorization either during or after campaign creation:
2. Advertiser uses the [/tcm/tt_video/status/](https://ads.tiktok.com/marketing_api/docs?id=1751098935842817) endpoint to confirm the authorization status and grab the authorization code.
3. Advertiser uses the [/tt_video/authorize/](https://ads.tiktok.com/marketing_api/docs?id=1738376435339265) endpoint to add the video to the advertiser account.
4. Advertiser uses the [/tt_video/info/](https://ads.tiktok.com/marketing_api/docs?id=1738376324021250) endpoint to get video information and authorization status of the Spark ad post.

## Step 1: Request authorization
### Option 1: During campaign creation 
When creating a TTO Creator Marketplace campaign using [/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458), pass in `default_spark_ads_requested_authorization_days` to request Spark Ads authorization days for all videos uploaded to the campaign. Then when a creator joins the campaign and upload videos to the campaign, you'll automatically be granted Spark Ads authorization for the videos.


###  Option 2: After campaign creation
After the Creator posts the video, call the [/tcm/tt_video/apply/](https://ads.tiktok.com/marketing_api/docs?id=1751098904942594) endpoint to request the authorization. By calling this endpoint, a notification will be sent to the Creator (in app) asking them to confirm authorization.

## Step 2: Get the authorization code

To confirm the status of the Authorization request, call the [/tcm/tt_video/status/](https://ads.tiktok.com/marketing_api/docs?id=1751098935842817) endpoint. After the Creator has authorized the request, the `auth_code` value will be returned in the API response.


## Step 3: Authorize the ad account for Spark Ads creation
Use [/tt_video/authorize/](https://ads.tiktok.com/marketing_api/docs?id=1738376435339265) and pass in `auth_code` to grant an ad account authorization to use a video in a Spark Ad.



## Step 4: Get video and authorization information

You (developers) can use the [/tt_video/info/](https://ads.tiktok.com/marketing_api/docs?id=1738376324021250) endpoint to get the authorization status of the video in the ad account. The api response includes information related to the video as well as authorization info (start time and end time).


# Next steps

You can grab the `item_id` you get from Step 4, and pass it to the `tiktok_item_id` parameter via [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) to create an ad. 

For detailed steps of creating Spark Ads, see [here](https://ads.tiktok.com/marketing_api/docs?id=1739470744631298).
