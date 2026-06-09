---
title: "About Reach & Frequency"
doc_id: 1744571201117186
path: "Marketing API / Campaign Management / Guides / Campaign / Reach & Frequency / About Reach & Frequency"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# About Reach & Frequency

Reach & Frequency enables predictable reach, controlled frequency and guaranteed delivery with a more flexible and transparent ad experience. Compared with Auction ads where ad delivery is determined by factors such as bid or bid strategy, R&F lets you control the audiences you reach and the number of times they are exposed to your ad messaging before delivery. See [here](https://ads.tiktok.com/help/article?aid=10002045) for more details about Reach & Frequency. 

> <span style="color: DodgerBlue">**Note**</span><br>The Reach and Frequency feature is in GA. Developers can build integrations with this feature. However, the integations would only work if the ad accounts are allowlisted for this feature. To have your ad accounts allowlisted, please reach out to your TikTok representative.

**You can now deliver R&F ads in these countries and regions  (including the city-level locations in the country or region)**: 

Australia, Austria, Argentina, Bahrain, Belarus, Belgium, Brazil, Cambodia, Canada, Chile, Colombia, Czech Republic, Denmark, Ecuador, Egypt, Finland, France, Germany, Greece, Hungary, Indonesia, Iraq, Ireland, Israel, Italy, Japan, Jordan, Kazakhstan, Kuwait, Lebanon, Malaysia, Mexico, Morocco, Netherlands, New Zealand, Norway, Oman, Pakistan, Peru, Philippines, Poland, Portugal, Qatar, Romania, Russia, Saudi Arabia, Singapore, South Africa, South Korea, Spain, Sweden, Switzerland, Taiwan Province, Thailand, Turkey, Ukraine, United Arab Emirates, United Kingdom, United States, Uruguay, Vietnam. 

# Inventory estimate
You can use [/rf/inventory/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1738235487941633) to generate inventory estimates to plan your campaigns. The estimated results are calculated in real-time based on your advertising objectives, placements, targeting, schedule, show frequency and reservation settings. See [Create a Reach & Frequency campaign](https://ads.tiktok.com/marketing_api/docs?id=1744571205201922) to find out  how to use the estimated results to create an ad group. 

- You can predict using fixed budget, impressions or reach. For example, if you set `rf_purchased_type` to `FIXED_BUDGET` and enter an amount in `budget` in your request, then the system will use the budget amount to predict impressions, reach, frequency and CPM, and return these prediction results in the `default_result` object. 
- Or if you don't specify any fixed amounts for `budget`, `purchased_impression` or `purchased_reach`, then from the `results` object in response, you can select budget, impression and reach that most closely approximate to your expectations. 

Below is a mapping between the prediction results returned via [/rf/inventory/estimate/](https://ads.tiktok.com/marketing_api/docs?id=1738235487941633) and the results on TikTok Ads Manager. 
- `default_result`<br>
The returned `base_info` object in `default_result` contains `budget`, `cpm`, `impression`, `reach` and `average_frequency`. If you use `rf_purchased_type` and enter a fixed amount for `budget`, `purchased_impression`, or `purchased_reach` in your request, then the returned data in the `base_info` object in `default_result` is aligned with **Estimated inventory: Purchase by fixed budget/impressions/reach** on TikTok Ads Manager. 
     
![](https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/9d574f83024eae7a31e9f6e11e5b8662)

> <span style="color: DodgerBlue">**Note**</span><br>
If you don't use `rf_purchased_type` and no fixed values are specified for budget, impressions or reach in your request, then the returned data in `default_result` will be the maximum estimate values (i.e. the highest point in the **Impressions & Budget** and **Reach & Budget** curves on TikTok Ads Manager). 

- `results`<br>
`results` contains a list of `base_info` objects that include `budget`, `cpm`, `impression`, `reach` and `average_frequency`. The returned data in `budget`, `impression`, and `reach` are aligned with the **Impressions & Budget** and **Reach & Budget** charts on TikTok Ads Manager. 

![](https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/720e00a0efe130247dca31ba34823ba6)

- `daily_cost` and `frequency_per_person`<br>
`daily_cost` and `frequency_per_person` are aligned with the **Spend per day** and **Frequency per person** charts respectively. 

![](https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/979b02af23b34939f6bf43dab996515f)

# Orders and budgets
Once an ad group has been created, an R&F order will be automatically made to reserve the ad inventory. After an ad group is made and 5 minutes before ad delivery begins, the system will try to lock the required advertising budget from your advertising account balance. The locked budget amount can only be used for R&F ads. This may affect the delivery of your other auction ads.

5 minutes before ad delivery, the system checks if there are any approved ads in the ad group. If not, the ad inventory order will be closed. If the advertiser's account balance is insufficient, the ad inventory order will also be closed.

Once an ad inventory order has been closed, the ad inventory reservation will be canceled, and the status of the ad group will be set to "Closed". To ensure that your ads are properly delivered, after you create an ad group, please ensure that your account balance is sufficient and you have uploaded your ad creatives on time.

# TikTok Pulse
TikTok Pulse is our super-charged contextual targeting product that allows you to show ads after the hottest content at a fixed CPM.  It is only available for Reach & Frequency ads. To learn more about TikTok Pulse, refer to [About TikTok Pulse](https://ads.tiktok.com/help/article/tiktok-pulse?redirected=2).
><span style="color:DodgerBlue">**Note**</span><br>TikTok Pulse is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

## How to Use TikTok Pulse
1. Use [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602) to create a TikTok Pulse campaign. Set `objective_type` as `RF_REACH`, and set `rf_campaign_type` as `PULSE` .
2. Use [/tool/contextual_tag/get/](https://ads.tiktok.com/marketing_api/docs?id=1747747118654465) and set `objective_type` as `RF_REACH`and `rf_campaign_type` as `PULSE` to get available contextual tags (`contextual_tag_id`). The returned contextual tags will include the `PREMIUM` tags, which are identified by the `type` field. If your contextual tags fall under the `PREMIUM` type, you can distinguish between `MAX_PULSE` and `CUSTOM` tags using the `content_lineup_type` field.
3.  Use [/adgroup/rf/create/](https://ads.tiktok.com/marketing_api/docs?id=1738235338194945) to create a TikTok Pulse ad group. Select IDs of the contextual tags you want to target and specify them in the `contextual_tag_ids` field. You need to include `PREMIUM` contextual tags in `contextual_tag_ids`. 

><span style="color:DodgerBlue">**Note**</span><br>
> - For Pulse ads,  `brand_safety_type` is set as `LIMITED_INVENTORY` by default. You are not allowed to use other brand safety values.
> - For `PREMIUM` contextual tags, all the tags you specify should belong to either `MAX_PULSE` or `CUSTOM` type. Otherwise, an error will occur.
> - Currently, you can run Max Pulse targeting `AE` (United Arab Emirates), `AU` (Australia), `BR` (Brazil), `CA` (Canada), `DE` (Germany), `ES` (Spain), `FR` (France), `GB` (United Kingdom), `IT` (Italy), `MX` (Mexico), `SA` (Saudi Arabia), `TR` (Turkey), and `US` (United States), and Category Lineups targeting `US`(the United States), `CA`(Canada), `BR`(Brazil), `AU`(Australia), `GB`(the United Kingdom), `FR`(France), `IT`(Italy), `ES`(Spain), `DE`(Germany). Both features are allowlist only. If you want to use them in the above markets, please contact your TikTok representative. 

## Difference between Contextual targeting and TikTok Pulse 
Contextual targeting is a targeting type that allows advertisers to present ads directly after a certain type of content videos from creators on the For You Page. TikTok Pulse uses contextual targeting capability and also curates the most popular content (using Pulse Score) and layers on the tightest brand safety tier.

Contextual targeting is available both in Auction and Reach & Frequency ads, while TikTok Pulse is only available in Reach & Frequency ads. 
