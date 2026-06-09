---
title: "Manage audiences"
doc_id: 1746998678415361
path: "Marketing API / Audience Management / Guides / Manage audiences"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage audiences

This topic discusses how to manage your audiences, including getting, updating, deleting, sharing audiences, applying audiences to multiple ad groups, etc.
# Get audiences
Use [/dmp/custom_audience/list/](https://ads.tiktok.com/marketing_api/docs?id=1739940506015746) to get a list of specified audiences. For each audience, information like ID, name, type, create time, and expired time will be returned in the response.

Use [/dmp/custom_audience/get/](https://ads.tiktok.com/marketing_api/docs?id=1739940507792385) to get the details of specified audiences. Audience details such as status and modification history will be returned. 

# Update audiences
Use [/dmp/custom_audience/update/](https://ads.tiktok.com/marketing_api/docs?id=1739940572667906) to update details of an audience.
The TikTok API for Business supports three types of audience updates for Customer File audience:
- Replace (`REPLACE`)：Replace (all) previous data files.
- New (`APPEND`): Add new data files.
- Remove (`REMOVE`): Remove specified data files.

You can also update the details of an audience, like the audience name and audience file. 

# Delete audiences
1. Use [/dmp/custom_audience/apply/log/](https://business-api.tiktok.com/portal/docs?id=1758615737388033) to check which ad groups are associated with the audience you want to delete. 
2. Use [/dmp/custom_audience/apply/](https://business-api.tiktok.com/portal/docs?id=1758615097524225) to disconnect the audience from the ad group.
3. After disconnecting, use [/dmp/custom_audience/delete/](https://ads.tiktok.com/marketing_api/docs?id=1739940539757569) to delete an audience.

# Share audiences
You can start or stop sharing a Custom Audience (only created from [Customer file](https://ads.tiktok.com/marketing_api/docs?id=1739940501023746), [Segment](https://ads.tiktok.com/marketing_api/docs?id=1739940504185857), Engagement, App Activity or Website Traffic) or a Lookalike Audience with specified ad accounts within the same TikTok Business Center. It will help you conveniently and efficiently use audience data to fulfill your marketing needs. See [here](https://ads.tiktok.com/help/article?aid=9602) for more details about Shared Audience.

- Use [/dmp/custom_audience/share/](https://ads.tiktok.com/marketing_api/docs?id=1740245803888706) to start sharing audiences with other advertisers under the same Business Center.
- Use [/dmp/custom_audience/share/cancel/](https://ads.tiktok.com/marketing_api/docs?id=1740245829978113) to stop sharing audiences with advertisers.
- Use [/dmp/custom_audience/share/log/](https://ads.tiktok.com/marketing_api/docs?id=1740245827044354) to get an audience sharing log. The log includes the ID and name of the shared advertiser, the audience ID and audience status. 

# Use audiences in ads
After audience creation, you can include or exclude audiences in a **single** ad group, or apply audiences to or disconnect audiences from **multiple** ad groups.

## Include audience in a single ad group

In [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) or [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) endpoints, pass in audience IDs to the `audience_ids` field to include the corresponding audiences in the ad group.

## Exclude audience from a single ad group

In [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) or [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) endpoints, pass in audience IDs to the `excluded_audience_ids` field to exclude the corresponding audiences from the ad group.

## Apply audience to multiple ad groups
Use [/dmp/custom_audience/apply/](https://ads.tiktok.com/marketing_api/docs?id=1758615097524225) to apply the audience to multiple ad groups. You need to set `action_mode` as `Apply`. You can choose whether this audience is included in or excluded from your ad groups by specifying `usage_mode` as `Include` or `Exclude`. Note that the same audience cannot be included in and excluded from the same adgroup at the same time.

## Disconnect audience from multiple ad groups
Use [/dmp/custom_audience/apply/](https://ads.tiktok.com/marketing_api/docs?id=1758615097524225)  to disconnect audience from multiple ad groups. You need to set `action_mode` as `Disconnect`. You can disconnect the audience that is included in or excluded from your ad groups.

# Get the latest application log of custom audiences
Use [/dmp/custom_audience/apply/log/](https://ads.tiktok.com/marketing_api/docs?id=1758615737388033) to get the latest application log for Custom Audience. Please note that this endpoint will only get the log of apply operation (`action_mode` = `Apply`).

# Refresh a Lookalike Audience
Use [/dmp/custom_audience/lookalike/update/](https://ads.tiktok.com/marketing_api/docs?id=1758616888158209) to refresh a Lookalike Audience.

# Get Audience Insights
Audience Insights is based on all TikTok data (paid and organic). Audience demographics, location, interests, device, and activity are estimated based on factors such as user behavior on the app, the information provided by users, and device information. The accuracy of such insights may vary with the information provided. These estimates are not a guarantee of results. We recommend that you use Audience Insights API to find the details of your potential audiences when planning a campaign to maximize your audience reach and efficiency. <br>To learn more about the concepts and best practices for Audience Insights, visit [Best Practices for Audience Insights](https://ads.tiktok.com/help/article/audience-insights-best-practices?lang=en) on TikTok Business Help Center. 
><span style="color:DodgerBlue">**Note**</span><br>At present, we only support endpoints for pre-campaign Audience Insights, which correspond to the 'Potential Audience' feature in the TikTok Ads Manager. We do not support endpoints for post-campaign Audience Insights, corresponding to the 'Reached Audience' feature in the TikTok Ads Manager.
* If you want to get the details of your potential audiences based on some filters like location, language, age, gender, interest & behaviors, or based on a specific custom audience, use [/audience/insight/info/](https://business-api.tiktok.com/portal/docs?id=1797016593782785).  We will return the corresponding data for the audiences of your selected locations and the audience you filtered for. 
* If you want to get the details of audience overlap between your benchmark audience and up to four other TikTok Custom Audience segments, use [/audience/insight/overlap/](https://business-api.tiktok.com/portal/docs?id=1797023590780930).  We will return data that includes the overlap in the benchmark audience, such as the percentage of users from the benchmark audience who are also present in the comparison audience.

