---
title: "Google Tag Manager (GTM) Integration"
doc_id: 1799004006287425
path: "Marketing API / Ad Measurement / Guides / Google Tag Manager (GTM) Integration"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Google Tag Manager (GTM) Integration

Google Tag Manager (GTM) is a tag management system that enables you to quickly and easily add measurement codes and related code fragments, collectively known as [tags](https://support.google.com/tagmanager/answer/3281060), to your website or mobile app.

With GTM, you can set up TikTok Pixel to send events on your website to TikTok. Additionally, you can use GTM to configure [server-side tagging](https://developers.google.com/tag-platform/tag-manager/server-side/intro) and send events to TikTok Events API through a server-to-server connection.

To implement TikTok Pixel and Events API, we recommend using the interactive setup flow in TikTok Events Manager, which offers two options:
- Client-side tagging: This setup option establishes a connection specifically for TikTok Pixel. To learn about the steps, see [TikTok Pixel only setup](https://business-api.tiktok.com/portal/docs?id=1799004034003969).
- Server-side and client-side tagging (recommended): This setup option allows you to establish connections for both TikTok Pixel and TikTok Events API through GTM server-side tagging. It is the recommended option for maximizing event coverage, optimizing campaign performance, and benefiting from auto-deduplication by TikTok. To learn about the steps, see [Events API and Pixel setup](https://business-api.tiktok.com/portal/docs?id=1799004052020225).
  - [Learn more about server-side tagging and its benefits](https://support.google.com/tagmanager/answer/13387731).
  
  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/84664337710e111dbf45c6ed56fcb8d3" alt="Google Tag Manager" width="60%" style="color:#808080" />

## Prerequisites
**To set up TikTok Pixel only integration with Google Tag Manager, you will need:**

1. A Google Tag Manager account and a web container installed on your website. [Learn more about setting up Google Tag Manager](https://support.google.com/tagmanager/answer/14842164). 
2. Publish, Approve, Edit, and Read permissions of the web container. [Learn more about GTM container permissions](https://support.google.com/tagmanager/answer/6107011?hl=en#container_permissions).
3. Familiarity with Google Tag Manager, and basic concepts such as clients, tags, triggers, and variables. [Learn more about the basic concepts](https://support.google.com/tagmanager/answer/6103657).

**To set up TikTok Pixel and Events API integration with Google Tag Manager, you will additionally need:**
1. A [Google Analytics account](https://analytics.google.com/analytics/web/) and familiarity with [Google Analytics 4](https://support.google.com/analytics/answer/10089681?hl=en) (GA4).
2. A [server container](https://developers.google.com/tag-platform/learn/sst-fundamentals/4-sst-setup-container) created in your Google Tag Manager account and Publish, Approve, Edit, and Read permissions of the server container. [Learn more about container permissions](https://support.google.com/tagmanager/answer/6107011?hl=en#container_permissions).
3. A Google Cloud Platform (GCP) account and a GCP billing account for provisioning your tagging server. [Learn more](https://developers.google.com/tag-platform/tag-manager/server-side/cloud-run-setup-guide?provisioning=automatic#prerequisites).
4. Familiarity with the basic concept of [server-side tagging](https://developers.google.com/tag-platform/tag-manager/server-side/intro).

## Get started
- [TikTok Pixel only setup](https://business-api.tiktok.com/portal/docs?id=1799004034003969)
- [Events API and Pixel setup](https://business-api.tiktok.com/portal/docs?id=1799004052020225)
