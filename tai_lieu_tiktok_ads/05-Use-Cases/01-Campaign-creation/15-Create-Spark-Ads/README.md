---
title: "Create Spark Ads"
doc_id: 1739470744631298
path: "Use Cases / Campaign creation / Create Spark Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Spark Ads

This article walks you through the steps to create Spark Ads. 

> <span style="color:DarkRed">**Important**</span><br>
> - To improve the business and user experience, TikTok is phasing out the use of Custom Identity in ads, that is, ads with custom profile images or display names in ad groups that deliver to Automatic Placement or Select Placement when TikTok is included. 
>   - New ad accounts created on or after January 15, 2026 cannot create TikTok ad campaigns using Custom Identities (non-Spark Ads) for these placements. 
>   - Existing ad accounts can no longer create TikTok ad campaigns using Custom Identities (non-Spark Ads) for these placements. 
>   - Ad campaigns that deliver only to Pangle or Global App Bundle placements are not be affected by Custom Identity deprecation. 
>- <b>Changes to ads-only mode have launched:</b>: <br>Starting January 27, 2026, the default value of `dark_post_status` request parameter in all ads creation API endpoints ([/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522), [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354), and [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362)) has been changed from `OFF` to `ON`. If not specified, ads-only mode, also known as "Show through ads only", will be enabled by default when you create ads via API. This means that a TikTok post will not be displayed in the associated TikTok account's profile when you create Spark Ads with the push method. We recommend setting "Show through ads only" as the default in your integration to match the ad creation process on TikTok Ads Manager.

# Introduction
Spark Ads (formerly known as Boosted TikToks) is a native solution for TikTok In-Feed ads and Reservation ads (for example, TopView). It uses organic TikTok posts as ads, including video posts and photo posts. Advertisers need to get the authorization from the TikTok post owners before they can promote the posts. You can learn more about the differences between Spark Ads and non-Spark Ads, and Spark Ads case studies [here](https://ads.tiktok.com/help/article/spark-ads?lang=en).

**You can use Campaign Management API to create Spark Ads in one of the following ways, and this helps you streamline your ad creation experience, and elevate operational efficiency and scalability.**
- If you want to boost new TikTok posts while you create them, you can push posts into a linked Business Account or an authorized TikTok account in the Business Center.
- If you want to boost the existing TikTok posts from a linked Business Account, you can pull organic posts from a linked Business Account.
- If you want to boost any existing TikTok posts from unlinked accounts (e.g. Creator's account), you can pull authorized posts.
- If you want to boost any existing TikTok posts from authorized accounts (Business Center authorization), you can pull organic posts from authorized TikTok accounts in the Business Center.

Spark Ads has the following benefits:
* The viewing flow is uninterrupted, which provides a better experience for visitors.
* Organic TikTok posts are more trustworthy than typical ads, as they are from actual accounts that are created and run by ordinary people.
* Longer-term conversions can be achieved. Long-term cooperation between post owners and advertisers makes long-term conversions possible.
* As TikTok posts come in diversified forms, they tend to have higher retention rates.
* Ads only mode (`dark_post_status`) enables creators to create ads that are hidden in their own homepage, allowing creators to maintain the consistency of their account style, while still delivering ads for advertisers.

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937&rid=7llhcla7zmh) for details. 
  - To create Spark Ads, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946&rid=7llhcla7zmh) to find out how to configure permissions.  


# Steps

## How to create Spark Ads
Spark Ads can be created using two methods: Spark Ads Push and Spark Ads Pull. Each method allows for different ways of creating and managing ads on TikTok.
- Spark Ads Push
  - This method involves creating new TikTok posts within a TikTok account using provided creatives.
  - Two ways to create Spark Ads using the Push method include:
    - Pushing posts to linked Business Accounts.
    - Pushing posts to authorized TikTok accounts in the Business Center.
- Spark Ads Pull:
  - This method involves retrieving existing posts from TikTok accounts.
  - Three ways to create Spark Ads using the Pull method include:
    - Pulling organic posts from linked Business Accounts.
    - Pulling organic posts from authorization codes.
    - Pulling organic posts from authorized TikTok accounts in the Business Center.

See the sections below to learn about the different steps of creating Spark Ads.  
### Spark Ads Push

#### Push a post to linked Business Accounts

1. Bind your TikTok For Business account to a Business Account.
<br>You can see how to bind the accounts [here](https://ads.tiktok.com/help/article/link-tiktok-account-to-tiktok-for-business-account). Note that if you have bound the TikTok For Business account to a regular TikTok account, you need to upgrade the regular TikTok account to a Business Account. 

2. Find out the ID of the <strong>TikTok User</strong> identity that you want to push posts to.
<br>To filter TikTok User identities, make a request to [/identity/get/](https://business-api.tiktok.com/portal/docs?id=1740218420781057) and specify `identity_type` as `TT_USER`. The response will include TikTok User identity IDs (`identity_id`). From these IDs, select one that supports pushing posts (where `can_push_video` is `true`).

Example

Request
```xcodeblock

(code curl http)
curl --location --request POST  'https://business-api.tiktok.com/open_api/v1.3/identity/get/?advertiser_id={{advertiser_id}}&identity_type=TT_USER' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "identity_list": [
            {
                "identity_id": "{{identity_id}}",
                "identity_authorized_bc_id": null,
                "can_push_video": true,
                "profile_image": "{{profile_image}}",
                "can_use_live_ads": false,
                "can_pull_video": true,
                "available_status": "AVAILABLE",
                "display_name": "{{display_name}}",
                "identity_type": "TT_USER"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 20,
            "total_number": 1,
            "total_page": 1
        }
    }
}
(/code)
```

3. Get the the ad creatives that you want to use in the Spark Ad.
- To push a video post to the linked Business Account, you need a video ID and the ID of the video cover image.
  - To obtain the video ID, you can search for the existing videos in the Asset Library of your ad account by using [/file/video/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740050472224769), or upload a new video to the Asset Library using [/file/video/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1737587322856449). 
  - To obtain video cover image ID, you can search for the existing images in the Asset Library of your ad account by using [/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506), or upload a new image to the Asset Library using [/file/image/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1739067433456642). 
- To push a photo post to the linked Business Account, you need one to 35 images supported in the Carousel Ad, and a piece of music to create Carousel Ads. To learn about the detailed requirements for ad creatives in Carousel Ads, see [Create Carousel Ads - Steps for creating Standard Carousel Ads](https://business-api.tiktok.com/portal/docs?id=1766217791987713#item-link-Steps%20for%20creating%20Standard%20Carousel%20Ads).

4. Create a Spark Ad.

**For Manual Ads:**
<br>When calling [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354) to create a Manual Ad, you need to specify the following in the request:
- Identity ID (`identity_id`)
- Identity type (`identity_type` as `TT_USER`)
- Ad creatives obtained from step 3
- Ad text (`ad_text`)
- Call-to-action (`call_to_action` or `call_to_action_id`)

**For Upgraded Smart+ Ads:**
<br>When calling [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522) to create an Upgraded Smart+ Ad, you need to specify the following in the request:
- Identity ID (`creative_info.identity_id`)
- Identity type (`creative_info.identity_type` as `TT_USER`)
- Ad creatives obtained from step 3
- Ad text (`ad_text_list`)
- Call-to-action (`call_to_action_id`)


Example for Manual Ads

Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
             "ad_name": "{{ad_name}}",
             "identity_type": "TT_USER",
             "identity_id": "{{identity_id}}",
             "ad_format": "SINGLE_VIDEO",
             "video_id":"{{video_id}}",
             "image_ids":["{{image_id}}"],
             "ad_text": "{{ad_text}}",
             "call_to_action": "LEARN_MORE"
         }]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "viewability_postbid_partner": "UNSET",
                "deeplink_type": "NORMAL",
                "modify_time": "{{modify_time}}",
                "click_tracking_url": null,
                "music_id": null,
                "identity_type": "TT_USER",
                "playable_url": "",
                "display_name": "{{display_name}}",
                "viewability_vast_url": null,
                "create_time": "{{create_time}}",
                "creative_authorized": false,
                "identity_id": "{{identity_id}}",
                "ad_id": "{{ad_id}}",
                "ad_name": "{{ad_name}}",
                "ad_format": "SINGLE_VIDEO",
                "tracking_pixel_id": 0,
                "branded_content_disabled": false,
                "call_to_action_id": null,
                "adgroup_id": "{{adgroup_id}}",
                "page_id": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "campaign_id": "{{campaign_id}}",
                "campaign_name": "{{campaign_name}}",
                "is_new_structure": true,
                "deeplink": "",
                "impression_tracking_url": null,
                "landing_page_urls": null,
                "creative_type": null,
                "operation_status": "ENABLE",
                "advertiser_id": "{{advertiser_id}}",
                "is_aco": false,
                "ad_texts": null,
                "dark_post_status": "OFF",
                "optimization_event": "ACTIVE",
                "card_id": null,
                "landing_page_url": "",
                "app_name": "{{app_name}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "adgroup_name": "{{adgroup_name}}",
                "vast_moat_enabled": false,
                "avatar_icon_web_uri": "",
                "brand_safety_vast_url": null,
                "tracking_app_id": "{{tracking_app_id}}",
                "profile_image_url": "",
                "call_to_action": "LEARN_MORE",
                "ad_text": "{{ad_text}}",
                "video_id": "{{video_id}}"
            }
        ]
    }
}
(/code)
```

#### Push a post to authorized TikTok accounts in the Business Center
1. Add the TikTok account that you want to push posts into to your Business Center as assets.
Generate a QR code from your Business Center and share the code with the TikTok account owner to request access to the TikTok account. Make sure that your Business Center has the necessary permissions, specifically **TikTok posts publishing permissions**, from the TikTok account.
<br>See the procedure in [How to Manage TikTok Accounts in Business Center-Step 1: Request TikTok account access](https://ads.tiktok.com/help/article/manage-tiktok-accounts-business-center). 
2. (Optional) Share TikTok accounts with Business Center members
- First, call [/bc/asset/admin/get/](https://business-api.tiktok.com/portal/docs?id=1739433007779841) to get the Asset ID (`asset_id`) of the TikTok account.
- Next, call [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121) to assign the TikTok account as asset to the Business Center member who needs to create Spark Ads in the following steps.
3. Find out the ID of the **TikTok Account User in Business Center** identity.
To filter TikTok Account User in Business Center identities, make a request to [/identity/get/ ](https://business-api.tiktok.com/portal/docs?id=1740218420781057)and specify `identity_type` as `BC_AUTH_TT`.  The response will include TikTok Account User in Business Center identity IDs (`identity_id`). From these IDs, select one that supports pushing posts (where `can_push_video` is `true`).

Example

Request
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/get/?advertiser_id={{advertiser_id}}&identity_type=BC_AUTH_TT' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "identity_list": [
            {
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "available_status": "AVAILABLE",
                "can_pull_video": true,
                "display_name": "{{display_name}}",
                "can_use_live_ads": true,
                "profile_image": "{{profile_image}}",
                "can_push_video": true,
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            }
        ],
        "page_info": {
            "page_size": 0,
            "total_page": 0,
            "page": 0,
            "total_number": 0
        }
    }
}
(/code)
```
4. Get the ad creatives that you want to use in the Spark Ad.
- To push a video post to the authorized TikTok account, you need a video ID and the ID of the video cover image.
  - To obtain the video ID, you can search for the existing videos in the Asset Library of your ad account by using [/file/video/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740050472224769), or upload a new video to the Asset Library using [/file/video/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1737587322856449). 
  - To obtain video cover image ID, you can search for the existing images in the Asset Library of your ad account by using [/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506), or upload a new image to the Asset Library using [/file/image/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1739067433456642). 
- To push a photo post to the authorized TikTok account, you need one to 35 images supported in the Carousel Ad, and a piece of music to create Carousel Ads. To learn about the detailed requirements for ad creatives in Carousel Ads, see [Create Carousel Ads - Steps for creating Standard Carousel Ads](https://business-api.tiktok.com/portal/docs?id=1766217791987713#item-link-Steps%20for%20creating%20Standard%20Carousel%20Ads).

5. Create a Spark Ad.

**For Manual Ads:**
<br>When calling [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354) to create a Manual Ad, you need to specify the following in the request:
- Identity ID (`identity_id`)
- Identity type (`identity_type` as `BC_AUTH_TT`)
- Business Center ID (`identity_authorized_bc_id`)
- Ad creatives obtained from step 4
- Ad text (`ad_text`)
- Call-to-action (`call_to_action` or `call_to_action_id`)

**For Upgraded Smart+ Ads:**
<br>When calling [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522) to create an Upgraded Smart+ Ad, you need to specify the following in the request:
- Identity ID (`creative_info.identity_id`)
- Identity type (`creative_info.identity_type` as `BC_AUTH_TT`)
- Business Center ID (`creative_info.identity_authorized_bc_id`)
- Ad creatives obtained from step 4
- Ad text (`(ad_text_list`)
- Call-to-action (`call_to_action_id`)



Example for Manual Ads:

Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
             "ad_name": "{{ad_name}}",
             "identity_type": "BC_AUTH_TT",
             "identity_id": "{{identity_id}}",
             "identity_authorized_bc_id":"{{identity_authorized_bc_id}}",
             "ad_format": "SINGLE_VIDEO",
             "video_id":"{{video_id}}",
             "image_ids":["{{image_id}}"],
             "ad_text": "{{ad_text}}",
             "call_to_action": "LEARN_MORE"
         }]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "creative_type": null,
                "is_new_structure": true,
                "click_tracking_url": null,
                "advertiser_id": "{{advertiser_id}}",
                "is_aco": false,
                "viewability_postbid_partner": "UNSET",
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "identity_type": "BC_AUTH_TT",
                "landing_page_url": "",
                "brand_safety_vast_url": null,
                "ad_text": "{{ad_text}}",
                "deeplink": "",
                "avatar_icon_web_uri": "",
                "deeplink_type": "NORMAL",
                "ad_format": "SINGLE_VIDEO",
                "ad_texts": null,
                "video_id": "{{video_id}}",
                "call_to_action_id": null,
                "playable_url": "",
                "ad_name": "{{ad_name}}",
                "campaign_id": "{{campaign_id}}",
                "branded_content_disabled": false,
                "display_name": "{{display_name}}",
                "viewability_vast_url": null,
                "create_time": "{{create_time}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "page_id": null,
                "app_name": "{{app_name}}",
                "call_to_action": "LEARN_MORE",
                "adgroup_name": "{{adgroup_name}}",
                "tracking_app_id": "{{tracking_app_id}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "card_id": null,
                "dark_post_status": "OFF",
                "campaign_name": "{{campaign_name}}",
                "vast_moat_enabled": false,
                "music_id": null,
                "modify_time": "{{modify_time}}",
                "identity_id": "{{identity_id}}",
                "impression_tracking_url": null,
                "ad_id": "{{ad_id}}",
                "tracking_pixel_id": 0,
                "adgroup_id": "{{adgroup_id}}",
                "profile_image_url": "",
                "creative_authorized": false,
                "landing_page_urls": null,
                "optimization_event": "ACTIVE",
                "operation_status": "ENABLE"
            }
        ]
    }
}
(/code)
```


### Spark Ads Pull
#### Pull organic posts from linked Business Accounts
1. Bind your TikTok For Business account to a Business Account.

You can see how to bind the accounts [here](https://ads.tiktok.com/help/article/link-tiktok-account-to-tiktok-for-business-account). Note that if you have bound your TikTok For Business account to a regular TikTok account, you need to upgrade the regular TikTok account to a Business Account. 

2. Find out the ID of the <strong>TikTok User</strong> identity that you want to retrieve posts from.

To filter TikTok User identities, make a request to [/identity/get/](https://business-api.tiktok.com/portal/docs?id=1740218420781057) and specify `identity_type` as `TT_USER`. The response will include TikTok User identity IDs (`identity_id`). From these IDs, select one that supports pulling posts (where `can_pull_video` is `true`).

Example

Request
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/get/?advertiser_id={{advertiser_id}}&identity_type=TT_USER' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "identity_list": [
            {
                "identity_id": "{{identity_id}}",
                "identity_authorized_bc_id": null,
                "can_push_video": false,
                "profile_image": "{{profile_image}}",
                "can_use_live_ads": false,
                "can_pull_video": true,
                "available_status": "AVAILABLE",
                "display_name": "{{display_name}}",
                "identity_type": "TT_USER"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 20,
            "total_number": 1,
            "total_page": 1
        }
    }
}
(/code)
```

3. Get the TikTok posts under the identity. 

Call [/identity/video/get/](https://ads.tiktok.com/marketing_api/docs?id=1740218475032577) and get the ID of the TikTok post that you want to use in the Spark Ad via `item_id` in the response.  You need to specify `identity_type` as `TT_USER` and pass the `identity_id` that you get from step 2 in the request.

By default, you will obtain TikTok video posts from `/identity/video/get/`. To filter TikTok photo posts, you need to set `item_type` to `CAROUSEL`.

Example

Request
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/video/get/?advertiser_id={{advertiser_id}}&identity_id={{identity_id}}&identity_type=TT_USER' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "has_more": true,
        "cursor": "1697448007",
        "video_list": [
            {
                "anchor_list": null,
                "item_id": "{{item_id}}",
                "text": "",
                "auth_info": null,
                "status": "ITEM_STATUS_HESITATE_RECOMMEND",
                "video_info": {
                    "bit_rate": 1223985,
                    "format": "mp4",
                    "width": 720,
                    "url": "{{url}}",
                    "duration": 1.6,
                    "height": 1280,
                    "size": 244797,
                    "signature": "{{signature}}",
                    "poster_url": "{{poster_url}}"
                }
            }
        ]
    }
}
(/code)
```

4. Create a Spark Ad.

**For Manual Ads:**
<br>When calling [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) to create an ad, specify `identity_id`, `identity_type`, `tiktok_item_id`, and call-to-action (`call_to_action` or `call_to_action_id`) in the request. You need to specify `identity_type` as `TT_USER` and `tiktok_item_id` as the `item_id` value that you get from step 3.

**For Upgraded Smart+ Ads:**
<br>When calling [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522)  to create an Upgraded Smart+ Ad, specify `creative_info.identity_id`, `creative_info.identity_type`, `creative_info.tiktok_item_id`, and call-to-action (`call_to_action_id`) in the request. You need to specify `creative_info.identity_type` as `TT_USER` and `creative_info.tiktok_item_id` as the `item_id` value that you get from step 3.



Example for Manual Ads

Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
             "ad_name": "{{ad_name}}",
             "identity_type": "TT_USER",
             "identity_id": "{{identity_id}}",
             "ad_format": "SINGLE_VIDEO",
             "tiktok_item_id":"{{tiktok_item_id}}",             
             "call_to_action": "LEARN_MORE"
         }]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "promotional_music_disabled": true,
                "tracking_app_id": "{{tracking_app_id}}",
                "ad_text": "{{ad_text}}",
                "is_new_structure": true,
                "modify_time": "{{modify_time}}",
                "display_name": "{{display_name}}",
                "vast_moat_enabled": false,
                "landing_page_urls": null,
                "image_ids": [],
                "deeplink_type": "NORMAL",
                "optimization_event": "ACTIVE",
                "app_name": "{{app_name}}",
                "operation_status": "ENABLE",
                "call_to_action": "LEARN_MORE",
                "music_id": null,
                "deeplink": "",
                "is_aco": false,
                "creative_type": null,
                "landing_page_url": "",
                "campaign_name": "{{campaign_name}}",
                "video_id": null,
                "identity_type": "TT_USER",
                "create_time": "{{create_time}}",
                "tiktok_item_id": "{{tiktok_item_id}}",
                "profile_image_url": "",
                "viewability_vast_url": null,
                "avatar_icon_web_uri": "",
                "ad_texts": null,
                "brand_safety_vast_url": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "page_id": null,
                "adgroup_id": "{{adgroup_id}}",
                "viewability_postbid_partner": "UNSET",
                "advertiser_id": "{{advertiser_id}}",
                "impression_tracking_url": null,
                "playable_url": "",
                "creative_authorized": false,
                "call_to_action_id": null,
                "click_tracking_url": null,
                "ad_format": "SINGLE_VIDEO",
                "ad_name": "{{ad_name}}",
                "campaign_id": "{{campaign_id}}",
                "adgroup_name": "{{adgroup_name}}",
                "ad_id": "{{ad_id}}",
                "card_id": null,
                "identity_id": "{{identity_id}}",
                "tracking_pixel_id": 0
            }
        ]
    }
}
(/code)
``` 
#### Pull organic posts from authorization codes
1. Determine the TikTok post that you want to use in the Spark Ad and request the TikTok post owner to generate the authorization code for the post.

2. Get the authorization code for the TikTok post from the TikTok post owner and apply for authorization. 
<br>The procedures for authorization code generation on TikTok App and for authorization application are clarified in [Spark Ads Creation Guide-Method 2: Use Authorized Accounts or Posts from Other Creators](https://ads.tiktok.com/help/article/spark-ads-creation-guide?redirected=1).

3. Call [/tt_video/authorize/](https://ads.tiktok.com/marketing_api/docs?id=1738376435339265) to bind the authorized post to your ad account.

Example

Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/tt_video/authorize/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "auth_code":"{{auth_code}}"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```


4. Call [/tt_video/info/](https://ads.tiktok.com/marketing_api/docs?id=1738376324021250) or [/tt_video/list/](https://ads.tiktok.com/marketing_api/docs?id=1738376465972226) to get the Identity ID (`identity_id`) and the TikTok post ID (`item_id`). 

Example

Request
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/tt_video/list/?advertiser_id={{advertiser_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "page": 1,
            "total_number": 1,
            "total_page": 1,
            "page_size": 20
        },
        "list": [
            {
                "item_info": {
                    "status": "HESITATE_RECOMMEND",
                    "auth_code": "{{auth_code}}",
                    "text": "{{text}}",
                    "item_id": "{{item_id}}",
                    "anchor_list": null
                },
                "auth_info": {
                    "auth_end_time": "{{auth_end_time}}",
                    "ad_auth_status": "AUTHORIZED",
                    "invite_start_time": "{{invite_start_time}}",
                    "auth_start_time": "{{auth_start_time}}"
                },
                "video_info": {
                    "duration": 13.538,
                    "width": 720,
                    "size": 13861358,
                    "preview_url": "{{preview_url}}",
                    "height": 1280,
                    "signature": "{{signature}}",
                    "bit_rate": 8191081,
                    "poster_url": "{{poster_url}}"
                },
                "user_info": {
                    "identity_id": "{{identity_id}}",
                    "identity_type": "AUTH_CODE",
                    "tiktok_name": "{{tiktok_name}}"
                }
            }
        ]
    }
}
(/code)
```

5. Create a Spark Ad.

**For Manual Ads:**
<br>When calling [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) to create a Manual Ad, specify `identity_id`, `identity_type`, `tiktok_item_id`, and call-to-action (`call_to_action` or `call_to_action_id`) in the request. You need to specify `identity_type` as `AUTH_CODE` and `tiktok_item_id` as the `item_id` value that you get from step 4.

**For Upgraded Smart+ Ads:**
<br>When calling [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522) to create an Upgraded Smart+ Ad, specify `creative_info.identity_id`, `creative_info.identity_type`, `creative_info.tiktok_item_id`, and call-to-action (`call_to_action_id`) in the request. You need to specify `creative_info.identity_type` as `AUTH_CODE` and `creative_info.tiktok_item_id` as the `item_id` value that you get from step 4.


Example for Manual Ads

Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{Access-Token}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
             "ad_name": "{{ad_name}}",
             "identity_type": "AUTH_CODE",
             "identity_id": "{{identity_id}}",
             "ad_format": "SINGLE_VIDEO",
             "tiktok_item_id":"{{tiktok_item_id}}",             
             "call_to_action": "LEARN_MORE"
         }]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "is_aco": false,
                "viewability_postbid_partner": "UNSET",
                "creative_type": null,
                "tiktok_item_id": "{{tiktok_item_id}}",
                "landing_page_urls": null,
                "music_id": null,
                "app_name": "{{app_name}}",
                "playable_url": "",
                "call_to_action": "LEARN_MORE",
                "identity_id": "{{identity_id}}",
                "avatar_icon_web_uri": "",
                "ad_texts": null,
                "ad_format": "SINGLE_VIDEO",
                "click_tracking_url": null,
                "brand_safety_vast_url": null,
                "ad_name": "{{ad_name}}",
                "landing_page_url": "",
                "page_id": null,
                "tracking_pixel_id": 0,
                "tracking_app_id": "{{tracking_app_id}}",
                "create_time": "{{create_time}}",
                "advertiser_id": "{{advertiser_id}}",
                "vast_moat_enabled": false,
                "identity_type": "AUTH_CODE",
                "creative_authorized": false,
                "deeplink_type": "NORMAL",
                "promotional_music_disabled": true,
                "display_name": "{{display_name}}",
                "profile_image_url": "",
                "modify_time": "{{modify_time}}",
                "campaign_name": "{{campaign_name}}",
                "campaign_id": "{{campaign_id}}",
                "is_new_structure": true,
                "card_id": null,
                "operation_status": "ENABLE",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "ad_id": "{{ad_id}}",
                "image_ids": [],
                "adgroup_name": "{{adgroup_name}}",
                "ad_text": "",
                "call_to_action_id": null,
                "deeplink": "",
                "optimization_event": "ACTIVE",
                "adgroup_id": "{{adgroup_id}}",
                "viewability_vast_url": null,
                "impression_tracking_url": null,
                "video_id": null
            }
        ]
    }
}
(/code)
```

#### Pull organic posts from authorized TikTok accounts in the Business Center
1. Add the TikTok account that you want to retrieve posts from to your Business Center as assets.
<br>Generate a QR code from your Business Center and share the code with the TikTok account owner to request access to the TikTok account. Make sure that your Business Center has the necessary permissions, specifically **Video permissions (existing posts)**, from the TikTok account.
<br>See the procedure in [How to Manage TikTok Accounts in Business Center-Step 1: Request TikTok account access]( https://ads.tiktok.com/help/article/manage-tiktok-accounts-business-center). 



2. (Optional) Share TikTok accounts with Business Center members
- First, call [/bc/asset/admin/get/](https://business-api.tiktok.com/portal/docs?id=1739433007779841) to get the Asset ID (`asset_id`) of the TikTok account.
- Next, call [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121) to assign the TikTok account as asset to the Business Center member who needs to create Spark Ads in the following steps.


3. Find out the ID of the <strong>TikTok Account User in Business Center</strong> identity.
To filter TikTok Account User in Business Center identities, make a request to [/identity/get/ ](https://business-api.tiktok.com/portal/docs?id=1740218420781057) and specify `identity_type` as `BC_AUTH_TT`.  The response will include TikTok Account User in Business Center identity IDs (`identity_id`) and Business Center IDs (`identity_authorized_bc_id`) . From the returned identity IDs, select one that supports pulling posts (where `can_pull_video` is `true`).

Example

Request
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/get/?advertiser_id={{advertiser_id}}&identity_type=BC_AUTH_TT' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "identity_list": [
            {
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "available_status": "AVAILABLE",
                "can_pull_video": true,
                "display_name": "{{display_name}}",
                "can_use_live_ads": true,
                "profile_image": "{{profile_image}}",
                "can_push_video": false,
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            }
        ],
        "page_info": {
            "page_size": 0,
            "total_page": 0,
            "page": 0,
            "total_number": 0
        }
    }
}
(/code)
```

4. Get the TikTok posts under the identity. 

Call [/identity/video/get/](https://business-api.tiktok.com/portal/docs?id=1740218475032577) to obtain the ID of the TikTok post that you want to use in the Spark Ad via `item_id` in the response. Specify `identity_type` as `BC_AUTH_TT` and include the `identity_id` and `identity_authorized_bc_id` obtained from step 3 in the request.

By default, you will obtain TikTok video posts from `/identity/video/get/`. To filter TikTok photo posts, you need to set `item_type` to `CAROUSEL`.

Example

Request
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/video/get/?advertiser_id={{advertiser_id}}&identity_type=BC_AUTH_TT&identity_id={{identity_id}}&identity_authorized_bc_id={{identity_authorized_bc_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "has_more": false,
        "cursor": "1629342953",
        "video_list": [
            {
                "anchor_list": null,
                "text": "{{text}}",
                "item_id": "{{item_id}}",
                "video_info": {
                    "signature": "{{signature}}",
                    "bit_rate": 4002871,
                    "duration": 6.268,
                    "poster_url": "{{poster_url}}",
                    "format": "mp4",
                    "height": 1280,
                    "size": 3136250,
                    "width": 720,
                    "url": "{{url}}"
                },
                "status": "ITEM_STATUS_HESITATE_RECOMMEND",
                "auth_info": null
            }
        ]
    }
}
(/code)
```

5. Create a Spark Ad.

**For Manual Ads:**
<br>When calling [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) to create a Manual Ad, specify `identity_id`, `identity_type`, `identity_authorized_bc_id`,  `tiktok_item_id`, and call-to-action (`call_to_action` or `call_to_action_id`)  in the request. You need to specify `identity_type` as `BC_AUTH_TT` and `tiktok_item_id` as the `item_id` value from step 4.

**For Upgraded Smart+ Ads:**
<br>When calling [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522) to create an Upgraded Smart+ Ad, specify `creative_info.identity_id`, `creative_info.identity_type`, `creative_info.identity_authorized_bc_id`,  `creative_info.tiktok_item_id`, and call-to-action (`call_to_action_id`)  in the request. You need to specify `creative_info.identity_type` as `BC_AUTH_TT` and `creative_info.tiktok_item_id` as the `item_id` value from step 4.


Example for Manual Ads

Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
             "ad_name": "{{ad_name}}",
             "identity_type": "BC_AUTH_TT",
             "identity_id": "{{identity_id}}",
             "identity_authorized_bc_id":"{{identity_authorized_bc_id}}",
             "ad_format": "SINGLE_VIDEO",
             "tiktok_item_id":"{{tiktok_item_id}}",             
             "call_to_action": "LEARN_MORE"
         }]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "identity_type": "BC_AUTH_TT",
                "call_to_action": "LEARN_MORE",
                "landing_page_urls": null,
                "click_tracking_url": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "brand_safety_vast_url": null,
                "card_id": null,
                "ad_name": "{{ad_name}}",
                "ad_id": "{{ad_id}}",
                "operation_status": "ENABLE",
                "video_id": null,
                "is_new_structure": true,
                "campaign_id": "{{campaign_id}}",
                "music_id": null,
                "advertiser_id": "{{advertiser_id}}",
                "creative_authorized": false,
                "impression_tracking_url": null,
                "display_name": "{{display_name}}",
                "viewability_vast_url": null,
                "is_aco": false,
                "viewability_postbid_partner": "UNSET",
                "campaign_name": "{{campaign_name}}",
                "promotional_music_disabled": true,
                "vast_moat_enabled": false,
                "playable_url": "",
                "modify_time": "{{modify_time}}",
                "ad_texts": null,
                "adgroup_id": "{{adgroup_id}}",
                "identity_id": "{{identity_id}}",
                "adgroup_name": "{{adgroup_name}}",
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "app_name": "{{app_name}}",
                "tracking_pixel_id": 0,
                "optimization_event": "ACTIVE",
                "image_ids": [],
                "creative_type": null,
                "ad_text": "{{ad_text}}",
                "profile_image_url": "",
                "ad_format": "SINGLE_VIDEO",
                "tracking_app_id": "{{tracking_app_id}}",
                "create_time": "{{create_time}}",
                "tiktok_item_id": "{{tiktok_item_id}}",
                "deeplink": "",
                "page_id": null,
                "landing_page_url": "",
                "deeplink_type": "NORMAL",
                "call_to_action_id": null,
                "avatar_icon_web_uri": ""
            }
        ]
    }
}
(/code)
```

# Related docs
- [Spark Ads API](https://ads.tiktok.com/marketing_api/docs?id=1738376238825474&rid=p04eokr366)
- [Campaign Management API](https://ads.tiktok.com/marketing_api/docs?id=1739381823123458&rid=24jiu7kp2km)
- [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097&rid=p04eokr366)
- [Spark Ads Guide](https://ads.tiktok.com/help/article/spark-ads?lang=en) on TikTok Ads Manager Help Center

# API reference

Use these endpoints to manage Spark Ads.

* [Get Spark Ad post](https://ads.tiktok.com/marketing_api/docs?id=1738376324021250)
* [Apply authorization code](https://ads.tiktok.com/marketing_api/docs?id=1738376435339265)
* [Get All Spark Ad posts](https://ads.tiktok.com/marketing_api/docs?id=1738376465972226)
* [Unbind Spark Ad post](https://ads.tiktok.com/marketing_api/docs?id=1738376509936641)
* [Get music authorization info](https://ads.tiktok.com/marketing_api/docs?id=1740218495869954)
