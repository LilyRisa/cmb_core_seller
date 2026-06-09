---
title: "FAQs"
doc_id: 1776983576127490
path: "Organic API / Accounts API / Guides / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

This article summarizes FAQs about the Accounts API. 


> <span style="color:DodgerBlue">**Note**</span><br>
> To ensure proper use of the Accounts API, starting March 20, 2026 at 00:00 (GMT+0), developers must complete the [Accounts API Access Application Form](https://bytedance.sg.larkoffice.com/share/base/form/shrlgu4WEvtSXpEDLcCw56u4Rfc) before submitting a new developer app or requesting a scope increase that includes the "TikTok Accounts" permission scope. Please follow the instructions carefully and provide clear, accurate information to help facilitate the application review process. For more details on Accounts API use cases, please refer to the [Accounts API](https://business-api.tiktok.com/portal/docs?id=1737944384433218) documentation.

## Data incompleteness
**Q1: When I set the ****`start_date`**** to a certain date (for instance a date in July) in **[**/business/get/**](https://business-api.tiktok.com/portal/docs?id=1762228399168514)**, why do I only see profile data from some time later (for instance August) onwards?**

A: It might be due to the following reason:
The data you can get from `/business/get/` depends on the availability of corresponding TikTok Analytics data in the app and at [https://www.tiktok.com/analytics](https://www.tiktok.com/analytics). In this case, it's likely that the TikTok account enabled TikTok Analytics only some time after the `start_date` you set. Therefore, any profile data prior to the actual date when TikTok Analytics is enabled won't be available through the API.


## Data discrepancy
**Q1: Why does the ****`shares`**** in **[**/business/video/list/**](https://business-api.tiktok.com/portal/docs?id=1762228421622786)** show more shares than the share count displayed on the TikTok web UI?**

A: It might be due to the following reason:
The share count displayed in the `shares` field from `/business/video/list/` corresponds to the number of shares shown in the For You feed on the TikTok mobile app. It includes both external shares and internal shares sent to friends using the "Send to friends" button. In contrast, the share count displayed on the TikTok web UI only includes external shares. For example, if a video is shared externally 30 times and shared with friends within the TikTok app 20 times, the value of `shares` from the API will be 50, but the share count displayed on the TikTok web UI will be 30.


## Authorization
**Q1: How can I revoke the Accounts API permissions for a developer app?**

A: To revoke the Accounts API permissions, you can use one of the following methods:
- Method 1: Call [/tt_user/oauth2/revoke/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Revoke%20a%20TikTok%20account%20access%20token) to revoke the access token for the TikTok account.
- Method 2: Revoke the permissions from within the TikTok mobile App:
	- 1. Open the TikTok mobile App and log in to the TikTok account for which you want to revoke permissions.
	- 2. Click **Settings and privacy** > **Security** > **Manage app permissions** > Select the developer app you want to revoke permissions from > **Remove access**.

**Q2: Why can't I find the TikTok account holder authorization URL?**

A: It might be due to the following reason:

You have not enabled the "TikTok Accounts" permission for your developer App. To resolve this, follow these steps:
1. Navigate to **Authorization** > **Scope of permission**.
2. Edit your permission settings and ensure that you have enabled the "TikTok Accounts" permission.
3. Once the permission is enabled, you should be able to find the **TikTok account holder authorization URL** under  **[My Apps](https://ads.tiktok.com/marketing_api/apps/)** > **App Detail** > **Basic Information**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2c06bcd2811d3e521fe7b56501636957" alt="Scope of permission" width="36%" style="color:#808080" />

## Miscellaneous
**Q1: Does IP address affect the reach of a post when I publish the post through **[**/business/video/publish/**](https://business-api.tiktok.com/portal/docs?id=1762228496095234)** or **[**/business/photo/publish/**](https://business-api.tiktok.com/portal/docs?id=1803630424390658)**?**

A: No, IP address has no impact on these post publishing endpoints:
- The [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234) and [/business/photo/publish/](https://business-api.tiktok.com/portal/docs?id=1803630424390658) endpoints have no IP-based restrictions.
- Your API call's IP address does not affect post reach. Post reach is determined solely by your TikTok account's region.


