---
title: "Placements"
doc_id: 1745292441739265
path: "Marketing API / Campaign Management / Guides / Ad group / Placements"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Placements

Placements allow you to choose where your ad will be shown. 

You can use the following two parameters to set placements at the ad group level. 

# Placement type
We provide the following two placement types (`placement_type`) . See [here](https://ads.tiktok.com/help/article?aid=9585) for detailed introductions.
- Automatic placement (`PLACEMENT_TYPE_AUTOMATIC`): Automatic placement uses all the available placements to offer the best result for your ad. 
- Select placement (`PLACEMENT_TYPE_NORMAL`): Select placement lets you manually choose the apps you wish to deliver your ads on. 

# Placements
Placements (`placements`) determine the apps where your ads will be shown. This field is required when you set `placement_type` to `PLACEMENT_TYPE_NORMAL`. We provide the following placements: 
- TikTok (`PLACEMENT_TIKTOK`): In-feed ads on the TikTok "For You" page. See [here](https://ads.tiktok.com/help/article/tiktok-placement?lang=en) for detailed introductions.
- Pangle (`PLACEMENT_PANGLE`): Pangle. See [here](https://ads.tiktok.com/help/article/pangle-placement?lang=en) for detailed introductions. 
- Global App Bundle (`PLACEMENT_GLOBAL_APP_BUNDLE`): A series of apps covering a wide variety of consumer interests and industries, including Capcut and Fizzo for now. See [here](https://ads.tiktok.com/help/article/global-app-bundle-placement?lang=en) for detailed introductions. 

For the complete list of supported enum values, refer to the [Enumerations-Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) section.


> <span style="color: DodgerBlue">**Note**</span><ul><li>Placement availability depends on the ad type, targeting, ad creation and country/region of delivery.</li><li>The Global App Bundle placement does not support the optimization goal Landing Page View (`optimization_goal` = `TRAFFIC_LANDING_PAGE_VIEW`). To learn about the optimization goal, refer to the section [Enumerations-Optimization goal](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).</li><li>We have deprecated BuzzVideo and Babe (`PLACEMENT_TOPBUZZ`) placements.  You can no longer place ads on BuzzVideo and Babe, and existing campaigns with `PLACEMENT_TOPBUZZ` placement will not be placed on BuzzVideo and Babe anymore. Moreover, if you update the existing adgroups which select `PLACEMENT_TOPBUZZ` as the only placement AND include `JP` or `TW` as locations,  an error will occur. 
  
## Available locations for different placements
The locations you can target with a certain placement are based on the place of registration for the ad account. To find out the locations available for a particular placement, refer to the Help Center article [Placements and Available Locations](https://ads.tiktok.com/help/article/placements-available-locations?lang=en#anchor-1).
> <span style="color:DodgerBlue">**Note**</span><br>
> The available locations you can target with the Global App Bundle placement (`PLACEMENT_GLOBAL_APP_BUNDLE`) are: Brazil, Indonesia, Vietnam, the Philippines, Thailand, Malaysia, Mexico, Saudi Arabia, and Japan.


## Supported ad specifications for different placements
Different placements have varying ad specifications. To learn about the image ad specifications for Pangle and Global App Bundle placements, refer to [Image Ads Specification](https://ads.tiktok.com/help/article/image-ads-specification?lang=en). Similarly, to learn about video ad specifications for TikTok, Pangle and Global App Bundle placements, refer to [Video Ads Specifications](https://ads.tiktok.com/help/article/video-ads-specifications?lang=en).

