---
title: "Pixel integration with Gateway"
doc_id: 1835087630325761
path: "Marketing API / Ad Measurement / Guides / Events API Gateway / Pixel integration with Gateway"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Pixel integration with Gateway

**Required permissions(s) :** TikTok Events Manager access and Pixel owner
## Establish Gateway Connection via Events manager
Connecting Pixels with an existing hub instance.
- Navigate to Pixel Settings.
  - Visit TikTok Ads Manager and go to **Tools > Events > Data Sources**, select a Pixel and go to "**Settings**" tab.
  - Locate the **Events API Gateway** section and click **Connect**.
  
  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7121ddeff88e6bb53433be1ad4ea0b83" alt="eAPI Gateway" width="60%" style="color:#808080" />
  
  - Click **Connect to an existing Events API Gateway**, then click **Next**.

- Input Host Domain.
  - In the provided input field, enter your host domain. This domain can be created via cloud services or provided by any domain provider.
- Connect to Events API Gatewaye Hub.
  - Click the **Connect** button.
  - Upon successful connection, the Events API Gateway Hub interface will be displayed.
- Launch hub from Pixel settings page.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3d38a8fde7bb7b240d99ed44ad47e331" alt="Launch Hub" width="60%" style="color:#808080" />

## Establish Gateway connection via Gateway Hub
- Navigate to your tenant and choose the data sources tab. Here it lists your current connecting/connected Pixels.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/da68a65bd99198ff974e87cc4af72436" alt="Choose Data Sources Tab" width="60%" style="color:#808080" />

- Click **connect data source**, then you will be directed to TT4B and the tenant should grant Events API Gateway the permissions displayed on the page, including Ad Account Management/Pixel Management/Measurement.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/558d09c6bcc3b1ab8dbc216387bb2bfc" alt="Click Data Sources" width="60%" style="color:#808080" />

- After permission is granted, you will be directed to Events API Gateway and there are two selectors:

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7d5ea55297a1d339ae9c64d04d2c8a3b" alt="eAPIG" width="40%" style="color:#808080" />

- After selecting advertiser account and Pixel (here it only shows Pixels you have edit permission), you should see the Pixel connected.
