---
title: "TikTok Pixel only setup"
doc_id: 1799004034003969
path: "Marketing API / Ad Measurement / Guides / Google Tag Manager (GTM) Integration / TikTok Pixel only setup"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# TikTok Pixel only setup

To install TikTok Pixel and configure events sent to TikTok through TikTok Pixel, you can add TikTok Pixel tags to your Google Tag Manager (GTM) web container. The interactive setup flow in TikTok Events Manager will guide you in configuring and installing all the necessary [TikTok tags, triggers, and variables](https://business-api.tiktok.com/portal/docs?id=1799004081342466), allowing you to complete the setup with just a few clicks.

> <span style="color:DodgerBlue">**Note**</span><br>
> This article outlines the steps for setting up TikTok Pixel with Google Tag Manager. You can also use Google Tag Manager to set up both TikTok Pixel and Events API (server-side), and take advantage of the benefits of both server-side and client-side setups to maximize performance.<ul><li>[Learn more about server-side tagging and its benefits](https://support.google.com/tagmanager/answer/13387731). </li><li>To get started, see [Events API and Pixel setup](https://business-api.tiktok.com/portal/docs?id=1799004052020225).</li></ul>


## Prerequisites
1. A Google Tag Manager account and a web container installed on your website. [Learn more about setting up Google Tag Manager](https://support.google.com/tagmanager/answer/14842164). 
2. Publish, Approve, Edit, and Read permissions of the web container. [Learn more about GTM container permissions](https://support.google.com/tagmanager/answer/6107011?hl=en#container_permissions).
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a660904d3817693baf7b57e57ba40c51" alt="Publish, Approve, Edit, and Read permissions of the web and server containers" width="60%" style="color:#808080" />
3. Familiarity with Google Tag Manager, and basic concepts such as clients, tags, triggers, and variables. [Learn more about the basic concepts](https://support.google.com/tagmanager/answer/6103657).


## Setup guide
1. In [TikTok Ads Manager](https://ads.tiktok.com/i18n/home), click **Tools** > **Events**, and then click **Connect data source** > **Web** > **Partner integrations** > **Google Tag Manager** > **Client-side tagging**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/345b4ff82bd31996cf7db0c80f9c35e4" alt="Tools > Events" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d286d60af284d301666a7d327669eaa2" alt="Connect data source" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e962fe912750f1ba7ab0f3655b832a98" alt="Web" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2c0b0ebe4c0fefc8cd1da39ea3963d52" alt="Partner integrations" width="60%" style="color:#808080" />
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/pixel_client_gtm" alt="Client-side tagging" width="60%" style="color:#808080" />

- To integrate an existing Pixel ID with Google Tag Manager, in the **Settings** tab of the pixel in Events Manager, click **Choose Partner** > **Google Tag Manager** > **Client-side tagging**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/973a7c83d59a0369f964ce954a993cf7" alt="Settings" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e147e2fd3b56e7a45d85dff4340ce1e7" alt="Choose Partner" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/pixel_client" alt="Client-side tagging" width="60%" style="color:#808080" />

2. Log in to the Google account that your GTM account is linked to, and then follow the instructions to complete the account connection.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/cd10516b9cd2c5ed822f21585909c728" alt="Choose an account" width="60%" style="color:#808080" />

3. Specify a name for your pixel (if you are creating a new pixel), choose the GTM **Account**, **Container** and **Workspace** in which TikTok tags should be installed.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0a07b0ae622d832e1f97ccb620d0703d" alt="Name your pixel" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/805fbb6167d1a81276b98082317b9f54" alt="choose Account, Container and Workspace" width="60%" style="color:#808080" />

4. Choose a method to set up events (when or what event and parameters should be sent to TikTok). 
  - **Set up with Event Builder (Recommended)**. Event Builder is an easy, codeless way to set up events directly from your website. See [TikTok Event Builder](https://ads.tiktok.com/help/article/create-events?lang=en) for more details.
  - **Use Google Tag Manager data layer APIs**. For websites already integrated with [GTM data layer](https://support.google.com/tagmanager/answer/6164391?hl=en) to send custom event data, that is, your website is already using `gtag()` (Google tag API), or `dataLayer.push()` (Tag Manager API), we will install TikTok tags and variables to automatically convert data layer events to TikTok pixel events and send the pixel events to TikTok. See [Set up events with data layer](https://business-api.tiktok.com/portal/docs?id=1799004097478658).
  
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/choose_method" alt="Choose method" width="60%" style="color:#808080" />

5. Review, confirm, and publish the changes to your GTM account. Click "**I have confirmed the details of each tag being published**". This will automatically install the necessary tags, triggers and variables for TikTok pixel to work properly with GTM. For more details on what will be installed, see [GTM container changes](https://business-api.tiktok.com/portal/docs?id=1799004081342466).

> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li> Once the changes are published, they cannot be modified.</li><li>If your GTM workspace already has tags, variables, or triggers with the same names as the changes being published, those will be skipped to preserve your existing production GTM settings.</li><li>If you want to make any modifications to the published version or reset TikTok tags, variables, or triggers, see <a href="#item-link-Q3: How can I clean up or re-install?">How to clean up or re-install</a> for instructions on reinstallation.</li></ul>

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/42ad8ec70a0bfda8006c9f2a9ca1573c" alt="Review and publish resources" width="60%" style="color:#808080" />

6. Once the changes are published, the TikTok Pixel will be installed when your website is loaded. Once you have completed the event setup either through the Event Builder or GTM data layer, the TikTok Pixel will begin reporting those events on your website.
  - If you previously selected Event Builder as the event setup method, follow the [Event Builder Guide](https://ads.tiktok.com/help/article/create-events?lang=en).
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/setup_event" alt="Set up events" width="60%" style="color:#808080" />
  - If you previously selected GTM data layer as the event setup method, follow [Set up events with data layer](https://business-api.tiktok.com/portal/docs?id=1799004097478658).
  
7. Verify TikTok pixel setup. You can verify the pixel setup by using the [test event tool](https://ads.tiktok.com/help/article/test-tiktok-pixel-events-video-walkthrough?lang=en) or [Pixel Helper plugin](https://ads.tiktok.com/help/article/tiktok-pixel-helper-2.0) within Events Manager. Additionally, you can use the GTM Preview mode to validate whether TikTok tags are being triggered. For more detailed instructions, see [Verify GTM setup](https://business-api.tiktok.com/portal/docs?id=1799004129683458).

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/event_test" alt="Set up events" width="60%" style="color:#808080" />

## FAQs
### Q1: Something went wrong during the Review and publish resources step. What should I do?

**A**: If you encounter an issue while reviewing and publishing resources, here are some troubleshooting steps that you can follow:
1. Ensure you have the necessary container permissions (Publish, Approve, Edit, and Read) for publishing changes. 
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4884fb8195cc32f9a0fba525faab8184" alt="container permissions" width="60%" style="color:#808080" />

2. Check if your container has any pre-existing errors such as undefined variables and triggers. To do so, use the **Preview** mode for your container to detect any issues in your workspace.
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6651bc15d12df129b9c2281d9fb1aba9" alt="Preview mode" width="60%" style="color:#808080" />
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b279ffc597a812a9b5050fc77282f6ee" alt="Validate container" width="60%" style="color:#808080" />

3. Fix any problems you find, then retry publishing the changes from the Events Manager setup flow.

4. If the issue persists, try cleaning up your workspace. For instructions on cleaning up or re-installing, see <a href="#item-link-Q3: How can I clean up or re-install?">How to clean up or re-install</a>.

### Q2: I initially chose to set up events using Event Builder, but now I want to switch to using GTM data layer (specifically, the `gtag()` or `dataLayer.push()` API). What should I do?

**A**: To switch to GTM data layer APIs, follow these steps:
1. Go through the Events Manager flow again.
2. During the setup, select **Use Google Tag Manager data layer APIs**. This will trigger the incremental installation of additional tags and variables to support data layer integration. To learn about what each method installs, see [GTM container changes](https://business-api.tiktok.com/portal/docs?id=1799004081342466).

If you have previously set up events using data layer APIs and want to continue using Event Builder, you don't need to make any updates. The data layer API installation already supports Event Builder setup. 

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/event_q2" alt="event_q2" width="60%" style="color:#808080" />

### Q3: How can I clean up or re-install?

**A**: In some scenarios, you might need to delete the existing integration and perform a re-installation. This includes the following scenarios:
- Upgrading from a TikTok Pixel only integration to TikTok Pixel & Events API integration.
- Performing a "factory reset" for other reasons, such as fixing setup issues.

To clean up and re-install in these scenarios, follow these steps:
1. Create a backup workspace in the same container to save existing configurations.
2. In your default workspace, delete previously installed TikTok tags, variables and triggers. See [GTM container changes](https://business-api.tiktok.com/portal/docs?id=1799004081342466).
3. After cleanup, go through the interactive setup flow again to re-install the integration. 
4. Make sure to copy back any important customizations from the backup workspace to the default workspace. 

### Q4: I manually installed the [TikTok Pixel Template](https://tagmanager.google.com/gallery/#/owners/tiktok/templates/gtm-template-pixel) tag instead of using the Events Manager interactive flow. Can I continue using it?

**A**: Yes, you can. If you manually installed the TikTok Pixel Template tag from the GTM template gallery, you can continue using it without any issues and retain any existing customizations you've made to the template.

## Support
- If you have any questions, please contact [TikTok Ads Customer Support](https://ads.tiktok.com/help/article/contact-tiktok-business-support) or reach out to your TikTok Sales Representative.
- If you have any feature requests or encounter any issues, file an issue on GitHub [here](https://github.com/tiktok/gtm-template-pixel/issues).
