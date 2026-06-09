---
title: "Verify GTM setup"
doc_id: 1799004129683458
path: "Marketing API / Ad Measurement / Guides / Google Tag Manager (GTM) Integration / Verify GTM setup"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Verify GTM setup

## Verify TikTok Pixel setup

After you complete integration of TikTok Pixel with Google Tag Manger, you can verify the setup using the following methods:
- **TikTok Pixel Helper**: Install the Pixel Helper Chrome plugin in your Chrome browser and monitor the events triggered in real time. [Learn more about TikTok Pixel Helper](https://ads.tiktok.com/help/article/tiktok-pixel-helper-2.0).
- **Test Event Tool**: Use the Test Event Tool in Events Manager to scan the QR code and load a browser session in test mode. This allows you to see the events received by TikTok in real-time as they are sent from your website. [Learn more about Test Event tool](https://ads.tiktok.com/help/article/test-tiktok-pixel-events-video-walkthrough).
- **Google Tag Manager Preview mode**: Use the preview mode by clicking the **Preview** button in your web container where the TikTok Pixel tag is installed. Interact with your website to ensure that the TikTok Pixel tag triggers correctly on the events you configured through TikTok Event Builder or GTM data layer.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4da724189ba2172208b3cc76b1207a9f" width="60%" style="color:#808080" />

If you have [set up events with data layer](https://business-api.tiktok.com/portal/docs?id=1799004097478658), you can also validate the data layer values sent from your website to the web container. 

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ffe6ba87428ca5938206afb0dc33574e" width="60%" style="color:#808080" />

## Verify TikTok Events API Setup

After you complete integration of TikTok Events API with Google Tag Manger, you can verify the setup using TikTok Pixel Helper, Test Event Tool, and Google Tag Manager Preview mode.

### Using Test Event Tool and GTM Preview mode
1. In Events Manager, click the pixel to view its configuration details.
2. In the **Test Events** tab, click the test code button in **Step 2** of the **Test Server Events** section to copy the code. 

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/46dec3332d641a66b211a8ee7d3cad6f" alt="test code button" width="60%" style="color:#808080" />

3. In your GTM **Server container**, paste the test event code into the **TikTok Events API tag**. Save and preview your web and server containers.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/38d1ae405b2b66caa700cd09925b64d1" alt="test code" width="60%" style="color:#808080" />
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0d375d63b46e938fbb05cfe7f936ab00" alt="test code" width="60%" style="color:#808080" />

4. In the web container:
  - Verify that the **Google Tag** is fired when your website page loads.
  
  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ca18d6c410c4128c6f7795b3170723ec" width="60%" style="color:#808080" />
 
  - Verify that the **Google Analytics: GA4 Event** tag is fired for the event you configured on your website, and ensure that the event parameters are received by the web container.
  
  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/bb0ec476583a4a86fef7cff4f813b00b" width="60%" style="color:#808080" />  
  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c4a171ece848b9fb6af4e27735ba8a17" width="60%" style="color:#808080" />

5. In your server container:
  - Verify that the **TikTok Events API (Official)** tag is fired and that the event request is made to the [TikTok Events API endpoint](https://business-api.tiktok.com/portal/docs?id=1771101303285761).
  
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0b2f3273d54a32da2abb011a2751d204" width="60%" style="color:#808080" />  
 <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/15a80520d4e0bafed2e08b716fe4fbc7" width="60%" style="color:#808080" />
 
 - In the **Test Events** tab within Events Manager, verify that the corresponding event is received by TikTok through the server connection. For example, TikTok should receive a `Complete Payment` event if a GA4 `purchase` event is fired on your website. See [Event name mapping](https://business-api.tiktok.com/portal/docs?id=1799004097478658#item-link-Event%20name%20mapping).
  
6. Note that test events will not be used in your campaign reporting or optimization. Once you have verified your GTM setup, make sure to **remove the Test Event Code** from the TikTok Events API tag and **save and publish your changes**.
