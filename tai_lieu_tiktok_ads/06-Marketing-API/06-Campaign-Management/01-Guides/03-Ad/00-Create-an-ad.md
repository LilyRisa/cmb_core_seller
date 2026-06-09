---
title: "Create an ad"
doc_id: 1745292553358338
path: "Marketing API / Campaign Management / Guides / Ad / Create an ad"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an ad

This article introduces how to create an ad. 

# Steps

Use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) to create an ad. You can define the following settings at the ad level. 

To understand how ad-level features in TikTok Ads Manager correspond to API parameters, see [Mapping between campaign features in TikTok Ads Manager and API configurations](https://business-api.tiktok.com/portal/docs?id=1740673047618561).

- General info 
  - `advertiser_id`：Advertiser ID. 
  - `adgroup_id`: Ad group ID. To obtain an ad group ID, create an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) and obtain the `adgroup_id` from the response.
  - `ad_name`: A descriptive name of your ad. 

- Identity
  - Use `identity_id` and `identity_type` to define an identity. See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) to find out identity types and use cases.  

- Creative 
  - Specify creatives including `call_to_action`/`call_to_action_id`, `image_ids`, `ad_text`/`ad_texts`, `video_id` and `tiktok_item_id`. To learn more about ad creatives, see the [Creatives](https://ads.tiktok.com/marketing_api/docs?id=1736271785207810) chapter. 

- Ad format 
  - Use `ad_format` to define the ad format. The enum values are `SINGLE_IMAGE`, `SINGLE_VIDEO`, `CAROUSEL_ADS`, `CATALOG_CAROUSEL`, `LIVE_CONTENT`.

- Tracking
  - Use `tracking_pixel_id`, `tracking_app_id`, and `tracking_offline_event_set_ids` to track events. 

To find out the details of other parameters, see the [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354) article.

## Example
```xcodeblock
(code)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_type":"CUSTOMIZED_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "{{call_to_action}}",
        "landing_page_url":"{{landing_page_url}}"
    }]
}'
(/code)
```
