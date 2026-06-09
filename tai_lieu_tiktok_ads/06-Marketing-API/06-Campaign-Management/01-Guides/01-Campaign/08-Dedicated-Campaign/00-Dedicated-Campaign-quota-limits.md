---
title: "Dedicated Campaign quota limits"
doc_id: 1752256248260610
path: "Marketing API / Campaign Management / Guides / Campaign / Dedicated Campaign / Dedicated Campaign quota limits"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Dedicated Campaign quota limits

By default,  Dedicated Campaigns are all SKAN Dedicated Campaigns and the campaign is bound by Dedicated Campaign quota rules. However, if your app qualifies for Advanced Dedicated Campaigns, you have the option to create a Dedicated non-SKAN Campaign by setting disable_skan_campaign to true at the campaign level. With this setting, the SKAN attribution for the campaign will be disabled and the campaign will not be bound by Dedicated Campaign quota limits.

You can create up to 15 or 25 iOS SKAN Dedicated Campaigns for each ad network in an iOS App.

- If the iOS App is not allowlisted for the 25 iOS SKAN Dedicated Campaign quota feature, you can use the App to create a total of 15 `PLACEMENT_TIKTOK` only or `PLACEMENT_GLOBAL_APP_BUNDLE` only or both SKAN Dedicated Campaigns, **AND** also 15 `PLACEMENT_PANGLE` only SKAN Dedicated Campaigns. 
- If the iOS App is allowlisted for the 25 iOS SKAN Dedicated Campaign quota feature, you can use the App to create a total of 25 `PLACEMENT_TIKTOK` only or `PLACEMENT_GLOBAL_APP_BUNDLE` only or both SKAN Dedicated Campaigns, **AND** also 25 `PLACEMENT_PANGLE` only SKAN Dedicated Campaigns. 

> <span style="color:DodgerBlue">**Note**</span><br>
> Expanding the quota of active SKAN Dedicated Campaigns to 25 per ad network for an iOS App is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

You can use [/campaign/quota/info/](https://business-api.tiktok.com/portal/docs?id=1752256376677378) to get the quota details, including the number of launched campaigns, the number of campaigns that will be released, and the number of campaigns that are available for use.


 
## SKAN Dedicated Campaign quota rules 
### iOS 14 SKAN Dedicated Campaign quota
If you have not enabled SKAN 4.0 for your App, the SKAN Dedicated Campaigns you create are iOS 14 SKAN Dedicated Campaigns. Before launching a new iOS 14 SKAN Dedicated Campaign, you need to ensure that the quota is not used up. 

iOS 14 SKAN Dedicated Campaign quota rules:
* An active iOS 14 SKAN Dedicated Campaign takes up a quota slot once an active ad group is created within it. When you pause or delete an iOS 14 SKAN Dedicated Campaign, the campaign quota will be released 72 hours after the pause time or deletion time. If you pause or delete an ad group under the iOS 14 SKAN Dedicated Campaign, the ad group quota will be released immediately. 
* During the 72 hours, the campaign is counted in `releasing_quota`.
* If both the campaign and the ad group are paused, 72 hours is counted from the campaign pause time. For example, if you pause the ad group first, then pause the campaign within 72 hours of the first action, then we count the 72 hours from the time when you paused the campaign.
* If the campaign delivery time ends or the budget is used up, the slot is not automatically released.
* When the quota is not used up, you can reactivate a paused campaign. When the quota is used up, you cannot reactivate a paused campaign. You will get an error message in the response.

### SKAN 4.0 Dedicated Campaign quota
If you have enabled SKAN 4.0 for your App, the SKAN Dedicated Campaigns you create are SKAN 4.0 Dedicated Campaigns. Before launching a new SKAN 4.0 Dedicated Campaign, you need to ensure that the quota is not used up. 
 
SKAN 4.0 Dedicated Campaign quota rules:
- An active SKAN 4.0 DC takes up a quota slot once an active ad group is created within it. When you pause or delete a SKAN 4.0 DC, the campaign quota will be released with a random delay after the pause time or deletion time, according to the attribution window you have specified through `postback_window_mode`. During the random delay, the campaign will count towards `releasing_quota`. To learn about the range of random delay times for each attribution window and the corresponding impact on the release of campaign quota, refer to the table below.

```xtable
| Attribution window {20%}| Random delay in sending the postback {30%}| Impact on DC campaign quota release{50%} |
|---|---|---|
| 0-2 days | 24-48 hours | Up to 4 days to release campaign quota |
| 3-7 days | 24-144 hours | Up to 13 days to release campaign quota |
| 8-35 days | 24-144 hours | Up to 41 days to release campaign quota |
```
- If you pause or delete an ad group under the SKAN 4.0 DC, the ad group quota will be released immediately.
- If both the campaign and the ad group are paused, a random delay is counted from the campaign pause time according to the attribution window. 
- If the campaign delivery time ends or the budget is used up, the slot is not automatically released.
- When the quota is not used up, you can reactivate a paused campaign. When the quota is used up, you cannot reactivate a paused campaign. You will get an error message in the response.


## Ad network and placements

 As shown below, the placements available for TikTok ad network are `PLACEMENT_TIKTOK` and `PLACEMENT_GLOBAL_APP_BUNDLE`, while the placement available for Pangle ad network is `PLACEMENT_PANGLE`. 
 
 > <span style="color:DodgerBlue">**Note**</span><br>
> Expanding the quota of active SKAN Dedicated Campaigns to 25 per ad network for an iOS App is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.


``` xtable
| Network {13%} | Supported placement {37%}| Number of campaign quota <br>for iOS 14 SKAN Dedicated Campaigns{25%} | Number of campaign quota <br>for SKAN 4.0 Dedicated Campaigns{25%} |
|---|---|---|---|
| TikTok | TikTok (`PLACEMENT_TIKTOK`)<br> Global App Bundle (`PLACEMENT_GLOBAL_APP_BUNDLE`) | 15 or 25 | 15 or 25|
| Pangle | Pangle (`PLACEMENT_PANGLE`) | 15 or 25 | 15 or 25|
```

## Campaign level 

The exact SKAN Dedicated Campaign quota that will be occupied depends on the placement setting you configure:
- If you use **Select Placement** (`placement_type` is `PLACEMENT_TYPE_NORMAL`) to create ad groups within a SKAN Dedicated Campaign, the campaign will occupy one quota for the specific placement. For instance, if you set `placements` to `["PLACEMENT_TIKTOK"]`, the corresponding campaign will occupy one quota for the TikTok placement.
- If you use **Automatic Placement** (`placement_type` is `PLACEMENT_TYPE_AUTOMATIC`) create ad groups within a SKAN Dedicated Campaign, the campaign will occupy one quota for the TikTok ad network and one quota for the Pangle ad network simultaneously.



### Get the campaign quota
Use [/campaign/quota/info/](https://ads.tiktok.com/marketing_api/docs?id=1752256376677378) to get campaign quota on each ad network for an App via the returned `campaign_quota_info`.

## Ad group level 
The placement of the historical first ad group (including the deleted or inactive one) will determine the placement for subsequent ad groups. 

Scenario 1: 
<table>
  <thead>
  <tr>
    <th style="width:50%">Placement of the first ad group</th>
    <th style="width:50%">Placement of the subsequent ad groups</th>
  </tr>
  </thead>
<tbody>
  <tr>
    <td>Any of the following settings that occupy the quotas for both TikTok and Pangle ad networks:<br><ul><li><code>PLACEMENT_TYPE_AUTOMATIC</code></li><li><code>["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"]</code></li><li><code>["PLACEMENT_TIKTOK","PLACEMENT_GLOBAL_APP_BUNDLE","PLACEMENT_PANGLE"]</code></li><li><code>["PLACEMENT_GLOBAL_APP_BUNDLE","PLACEMENT_PANGLE"]</code></li></ul></td>
    <td>No limitation </td>
  </tr>
</tbody>
</table>



Scenario 2: 
<table><thead>
  <tr>
    <th style="width:50%">Placement of the first ad group</th>
    <th style="width:50%">Placement of the subsequent ad groups</th>
  </tr></thead>
<tbody>
  <tr>
    <td>Any of the following settings that only occupy the quota for the TikTok ad network:<br><ul><li><code>["PLACEMENT_TIKTOK","PLACEMENT_GLOBAL_APP_BUNDLE"]</code></li><li><code>["PLACEMENT_TIKTOK"]</code></li><li><code>["PLACEMENT_GLOBAL_APP_BUNDLE"]</code></li></ul></td>
    <td>Any of the following settings that only occupy the quota for the TikTok ad network:<br><ul><li><code>["PLACEMENT_TIKTOK","PLACEMENT_GLOBAL_APP_BUNDLE"]</code></li><li><code>["PLACEMENT_TIKTOK"]</code></li><li><code>["PLACEMENT_GLOBAL_APP_BUNDLE"]</code></li></ul></td>
  </tr>
</tbody>
</table>



Scenario 3: 

<table><thead>
  <tr>
    <th style="width:50%">Placement of the first ad group</th>
    <th style="width:50%">Placement of the subsequent ad groups</th>
  </tr></thead>
<tbody>
  <tr>
    <td>Any of the following settings that only occupy the quota for the Pangle ad network:<br><ul><li><code>["PLACEMENT_PANGLE"]</code></li></ul></td>
    <td>Any of the following settings that only occupy the quota for the Pangle ad network:<br><ul><li><code>["PLACEMENT_PANGLE"]</code></li></ul></td>
  </tr>
</tbody>
</table>



### Get the ad group quota
Use [/campaign/quota/info/](https://ads.tiktok.com/marketing_api/docs?id=1752256376677378) to get the ad group quota under a Dedicated Campaign and the allowed placements for your ad groups via the returned `adgroup_quota_info`.
