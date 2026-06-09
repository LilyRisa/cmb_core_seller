---
title: "Authentication"
doc_id: 1771101130925058
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Authentication"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Authentication

 To authenticate Events API 2.0 calls, you need an access token. There are two ways to generate an access token for Events API:
- <a href="#item-link-Option 1: Directly in Events Manager [Recommended]">Using the Events Manager</a> - **Recommended**
  - This method provides one-click token generation. However, the generated token can only authenticate sending events with Pixel Code/App ID/Event Set ID created under the same TikTok ad account. 
- <a href="#item-link-Option 2: With your own developer app">Using your own developer app</a>
  - This method requires setting up a TikTok For Business developer app. It's recommended for more advanced use cases, where the same token is used to authenticate events with Pixel Code/App ID/Event Set ID created under multiple TikTok ad accounts.

## Option 1: Directly in Events Manager [Recommended]

### Prerequisites
- You must be an Admin or Operator for the ad account to access the Events Manager token generation UI. 
- If you don't have access, ask your business admin to generate the token or grant you the necessary permission to access the Events Manager.

### Steps
1. In the Events Manager, navigate to the Pixel and click the **Settings** tab.

2. Click **Generate Access Token**. The token will be generated immediately. You can then copy the access token and start to make API requests.

Note that the token generated in Events Manager can only  authenticate sending events with Pixel Code/TikTok App ID/Event Set ID created under the same TikTok ad account. 

We recommend starting with using an Events Manager generated token. You can always switch to a developer app generated token later on if your business requires using the same token across multiple ad accounts.

## Option 2: With your own developer app

If you or your advertisers already have a developer app with the **"Measurement > Report Conversion Event"** scope of permission, refer to [Authorization](https://ads.tiktok.com/marketing_api/docs?id=1738373141733378) and [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162) to generate an access token by using a developer app.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/82c8afdd173e06d3b0b8d5d79f601faf" alt="Settings" width="60%" style="color:#808080"/>
