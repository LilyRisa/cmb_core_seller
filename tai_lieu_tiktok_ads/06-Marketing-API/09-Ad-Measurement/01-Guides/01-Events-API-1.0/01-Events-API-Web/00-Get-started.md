---
title: "Get started"
doc_id: 1739584855420929
path: "Marketing API / Ad Measurement / Guides / Events API 1.0 / Events API : Web / Get started"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get started

> <span style="color:DarkRed">**Important**</span><br>
> By using Events API, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

This guide walks you through the process of setting up Events API.

# Before you begin
- You should have access to TikTok Ads Manager that contains test event functionality and diagnostic tools that would help you during development testing and post integration validation.
- You must have created a pixel in the TikTok Ads Manager under **Tools** > **Events**.
- Once you create a pixel, you can send events via website through [installing the pixel base code](https://ads.tiktok.com/marketing_api/docs?id=1739585702922241) or send events via Events API. The recommended best practice is to send both that would maximize your event coverage and campaign performance. Furthermore, with the dual channels setup, your campaign performance is protected against event disruption as the event signals will still come in from one channel when the other fails. If you are sending events from both Pixel SDK and Events API, you will need to set up [event deduplication](https://ads.tiktok.com/marketing_api/docs?id=1739584864945154).


If you want to create a new Pixel, please start from the first step. If you want to use an existing pixel, you can click the pixel in the Events Manager and start from the second step.

## 1. Create a Pixel
a. In [TikTok Ads Manager](https://ads.tiktok.com/i18n/home), navigate to **Tools > Events**.



b. Click the **Manage** button for **Web Events** and click the **Set Up Web Events** button.


c. Enter the **Pixel Name** and select **Events API** as the **Connection Method**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/736984417a11af722aa78a5e88eebd7c" width="60%" style="color:#808080"/>

d. Select the **Installation Type**.
	- **Manually Set Up Events API**: select this option for integrating directly with TikTok for server-to-server reporting.
	
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ab5d06d5deea6713067c5d80e2b022e1" width="60%" style="color:#808080"/>
	
  
## 2. Generate an access token
An access token is needed to authorize Events API calls. 

### Option 1: Directly through Events Manager [Recommended]
An Admin or Operator permission of this ad account is required to generate an access token through Events Manager.

<b>Steps</b>:
1. In the Events Manager, navigate to the Pixel and click the **Settings** tab.
2. Click **Generate Access Token**. The token will be generated immediately. You can then copy the access token and start to make API requests.


### Option 2: Using Authentication API 
You will need a developer app first. See how to create a developer app [here](https://ads.tiktok.com/marketing_api/docs?id=1738855242728450).

1. Enable "Measurement > Report Pixel Event " permission for the developer app under My Apps.
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b61a19c7df417327c2bea8ff214058b9" width=650 />

2. Please follow the steps in  [Authorization](https://ads.tiktok.com/marketing_api/docs?id=1738373141733378) and [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162) to generate the access token using the API.



# Share events with Events API
Events are actions a website visitor takes to achieve a business goal, like adding an item to a cart, filling out a form or making a purchase. Prior to integrating with the Events API, we recommend predefining which Events you are interested in sharing with TikTok to achieve your marketing objectives. You can add events and parameters to be sent with your payload.

For details of supported web events and parameters, see [Report web events and parameters](https://ads.tiktok.com/marketing_api/docs?id=1741601162187777).

# Share matching data within the events
Events API for web uses customer information like TikTok Click IDs (`ttclid`), Advanced Matching and additional signals like IP address and User Agent to match web visitor events with TikTok users. Once matched, information shared via the Events API can be used to build targeting audiences, inform the optimization algorithm and better measure campaign results. 

><span style="font-size:14px">**Related Links**</span> 
>* <span style="font-size:14px">[Report web events and parameters](https://ads.tiktok.com/marketing_api/docs?id=1741601162187777) 
*[Advanced Matching Data with Events API](https://ads.tiktok.com/marketing_api/docs?id=1749819614433282) 
*[Set up TikTok Click ID and Cookies](https://ads.tiktok.com/marketing_api/docs?id=1739584860883969)</span>

# Verify Events setup
After creating the event, you can verify web data connection setup and monitor activity. If you are sending events from both Pixel SDK and Events API, you will need to set up [event deduplication](https://ads.tiktok.com/marketing_api/docs?id=1739584864945154). 

For best practices of verifying events API setup, see [Verify Events setup](https://ads.tiktok.com/marketing_api/docs?id=1739584863252481).



