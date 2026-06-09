---
title: "Events API and Pixel setup"
doc_id: 1799004052020225
path: "Marketing API / Ad Measurement / Guides / Google Tag Manager (GTM) Integration / Events API and Pixel setup"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Events API and Pixel setup

To install TikTok Pixel and configure events sent to TikTok through both Pixel and Events API, you can add TikTok Pixel tags to your Google Tag Manager (GTM) containers. The interactive setup flow in TikTok Events Manager will guide you in configuring and installing all the necessary [TikTok tags, triggers, and variables](https://business-api.tiktok.com/portal/docs?id=1799004081342466), allowing you to complete the setup with just a few clicks.

The GTM and Events API integration flow will set up **both TikTok Pixel and Events API**. This dual-setup is recommended to maximize your event coverage and campaign performance. Additionally, with the dual channels setup, your campaign performance is protected against event disruption because the event signals will still be received from one channel of the other fails.

Here are the specific setup guides based on your requirements:
- For TikTok Pixel only setup, see [TikTok Pixel only setup](https://business-api.tiktok.com/portal/docs?id=1799004034003969).
- For Events API only setup, see <a href="#item-link-Q5: How can I set up Events API only integration?">Set up Events API only integration</a>.

## Prerequisites
1. A [Google Analytics account](https://analytics.google.com/analytics/web/) and familiarity with [Google Analytics 4](https://support.google.com/analytics/answer/10089681?hl=en) (GA4).
2. A web container installed on your website and a server container created in your Google Tag Manager account.
  - [Learn more about setting up Google Tag Manager](https://support.google.com/tagmanager/answer/14842164). 
  - [Learn more about setting up server container](https://developers.google.com/tag-platform/learn/sst-fundamentals/4-sst-setup-container).
3. Publish, Approve, Edit, and Read permissions of the web and server containers. [Learn more about container permissions](https://support.google.com/tagmanager/answer/6107011?hl=en#container_permissions).

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a660904d3817693baf7b57e57ba40c51" alt="Publish, Approve, Edit, and Read permissions of the web and server containers" width="60%" style="color:#808080" />

4. A Google Cloud Platform (GCP) account and a GCP billing account for provisioning your tagging server. [Learn more](https://developers.google.com/tag-platform/tag-manager/server-side/cloud-run-setup-guide?provisioning=automatic#prerequisites).
5. Familiarity with basic concepts such as [tags, triggers, and variables](https://support.google.com/tagmanager/answer/6103657) and [server-side tagging](https://developers.google.com/tag-platform/tag-manager/server-side/intro).

## Setup guide
1. In [TikTok Ads Manager](https://ads.tiktok.com/i18n/home), click **Tools** > **Events**, and then click **Connect data source** > **Web** > **Partner integrations** > **Google Tag Manager** > **Server-side and Client-side tagging**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/345b4ff82bd31996cf7db0c80f9c35e4" alt="Tools > Events" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d286d60af284d301666a7d327669eaa2" alt="Connect data source" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e962fe912750f1ba7ab0f3655b832a98" alt="Web" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2c0b0ebe4c0fefc8cd1da39ea3963d52" alt="Partner integrations" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/84664337710e111dbf45c6ed56fcb8d3" alt="Google Tag Manager" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/77c94513e7a92520581207af22ef1d76" alt="Server-side and Client-side tagging" width="60%" style="color:#808080" />

- To integrate an existing Pixel ID with Google Tag Manager, in the **Settings** tab of the pixel in Events Manager, click **Choose Partner** > **Google Tag Manager** > **Server-side and Client-side tagging**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/973a7c83d59a0369f964ce954a993cf7" alt="Settings" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e147e2fd3b56e7a45d85dff4340ce1e7" alt="Choose Partner" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/77c94513e7a92520581207af22ef1d76" alt="Server-side and Client-side tagging" width="60%" style="color:#808080" />

2. Log in to your Google account that your GTM account is linked to, and make sure the following:
  - The Google account is associated with your GTM account.
  - **Both Web container and Server container** have been created in your GTM account.
  
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/cd10516b9cd2c5ed822f21585909c728" alt="Choose an account" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/078476fd6a00c49d21caff15b7537f17" alt="Set up containers" width="60%" style="color:#808080" />

3. Follow the following instructions to set up your tagging server and custom domain for your server.
  - To set up a fully functional tagging server with **Google Cloud Run** for testing purposes, select **Automatic provisioning tagging server** (recommended). Note that this requires creating a Google Cloud Platform (GCP) billing account. The auto-provisioned server usually does not incur charges when used solely for testing. [Learn more](https://developers.google.com/tag-platform/tag-manager/server-side/cloud-run-setup-guide?provisioning=automatic#provision_a_preview_and_tagging_server).
    - You can later [upgrade the configuration](https://developers.google.com/tag-platform/tag-manager/server-side/cloud-run-setup-guide?provisioning=automatic#edit_the_service_configuration) of the auto-provisioned server for production use, or [manually create a tagging server](https://developers.google.com/tag-platform/tag-manager/server-side/manual-setup-guide) using other cloud providers (for instance, Amazon AWS or Microsoft Azure) or on your own servers. [Learn more about tagging server deployment options](https://developers.google.com/tag-platform/learn/sst-fundamentals/4-sst-setup-container?continue=https%3A%2F%2Fdevelopers.google.com%2Ftag-platform%2Flearn%2Fsst-fundamentals%23article-https%3A%2F%2Fdevelopers.google.com%2Ftag-platform%2Flearn%2Fsst-fundamentals%2F4-sst-setup-container#tagging-server-deployment-options).
  - To measure activity without relying on third-party cookies and client-side measurement, set up a custom domain. Once you complete the integration, you can configure a custom domain for your tagging server. [Learn more](https://developers.google.com/tag-platform/tag-manager/server-side/custom-domain?option=same-origin).

It is recommended to use **automatic provisioning** to quickly set up a testing tagging server and proceed with the rest of the setup flow. Once you have validated that the Events API integration is functioning correctly, you can later consider switching to other tagging server deployment options or adding a custom domain for your tagging server. See <a href="#item-link-Q2: How can I update the tagging server URL?">Update the tagging server URL</a>.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f0c66f73538e6c9966d51efb8a1b638b" alt="Set up tagging server
" width="60%" style="color:#808080" />

4. Specify a name for your pixel (if you are creating a new pixel), choose the GTM **Account**, **Web container**, **Server container** and **Workspace** in which TikTok tags should be installed.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0a07b0ae622d832e1f97ccb620d0703d" alt="Name your pixel" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d0a0237646f6b967506c8525eeb39e4d" alt="Choose Account, Web container, Server container and Workspace" width="60%" style="color:#808080" />

5. To set up the TikTok template tags, provide the following information:
  - Measurement ID: A unique identifier for a Google Analytics 4 (GA4) web data stream. You can find it in your [Google Analytics account](https://analytics.google.com/analytics/web). For more details on how to find your measurement ID, see [Find your measurement ID](https://support.google.com/analytics/answer/12270356?sjid=12416340890415772758-AP).
  - Tagging server URL: The URL of the tagging server that you have previously set up.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/08fdd236692507a5955338a844cfbd5f" alt="Set up tags" width="60%" style="color:#808080" />

6. Review, confirm, and publish the changes to your GTM account. Click "**I have confirmed the details of each tag being published**". This will automatically install the necessary tags, triggers and variables for TikTok pixel and Events API to work properly with GTM. For more details on what will be installed, see [GTM container changes](https://business-api.tiktok.com/portal/docs?id=1799004081342466).

> <span style="color:DodgerBlue">**Note**</span><br>
><ul><li>Once the changes are published, they cannot be modified.</li><li>If your GTM workspace already has tags, variables, or triggers with the same names as the changes being published, those will be skipped to preserve your existing production GTM settings.</li><li>If you want to make any modifications to the published version or reset TikTok tags, variables, or triggers, see <a href="#item-link-Q4: How can I clean up or re-install?">How to clean up or re-install</a> for instructions on reinstallation.</li></ul>

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/42ad8ec70a0bfda8006c9f2a9ca1573c" alt="Review and publish resources" width="60%" style="color:#808080" />


- We will automatically populate the Pixel ID and an Events API access token in the installed TikTok Events API tag in your server container. If you decide to switch to a different Pixel ID in the future, make sure to follow the <a href="#item-link-Q9: How can I generate a new access token for my Events API integration if I switched to a different Pixel?">FAQ</a> to generate a new token and update the Pixel ID and Access Token values in your TikTok Events API tag.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1f9dae626db74550775b4c52d32420c5" alt="Pixel ID and Access Token values in your TikTok Events API tag" width="60%" style="color:#808080" />


- If you encounter an error while publishing the changes, you can troubleshoot common causes by following <a href="#item-link-Q1: Something went wrong during the Review and publish resources step. What should I do?">instructions about what should you do when something went wrong during the Review and Publish resources step</a>. Resolve the issues and retry the publishing process.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8fb60b24da2a4589c04bc3abc6c1d59b" alt="Something went wrong" width="60%" style="color:#808080" />


7. Once changes are published, the TikTok Pixel will be installed when your website is loaded. Follow the instructions in Events Manager to complete the event configuration. Once you finish setting up the event using your previously chosen method (either with GTM data layer or manually), the TikTok Pixel will start reporting those events on your website. Additionally, your tagging server will also report those events to TikTok Events API.
  - For instructions on setting up TikTok events using GTM data layer, see [Set up events with data layer](https://business-api.tiktok.com/portal/docs?id=1799004097478658).
  - For instructions on manual setup of TikTok events without using the GTM data layer, see [Set up events manually](https://business-api.tiktok.com/portal/docs?id=1799004110681154).

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/001e7c641a252dc0065fb6b5b52c8851" alt="Set up events" width="60%" style="color:#808080" />


8. Verify Events API integration. You can test if events are successfully received by Events API using the **Test Events** tool in Events Manager. Additionally, you can use the GTM Preview mode to validate whether TikTok tags are being triggered. For more detailed instructions, see [Verify GTM setup](https://business-api.tiktok.com/portal/docs?id=1799004129683458).

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/24eb4c9ea2071a742c22fe95112f7bc1" alt="Testing" width="60%" style="color:#808080" />


## FAQs

### Q1: Something went wrong during the Review and publish resources step. What should I do?

**A**: If you encounter an issue while reviewing and publishing resources, here are some troubleshooting steps that you can follow:

1. Ensure you have the necessary container permissions (Publish, Approve, Edit, and Read) for publishing changes. 

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4884fb8195cc32f9a0fba525faab8184" alt="container permissions" width="60%" style="color:#808080" />

2. Check if your container has any pre-existing errors such as undefined variables and triggers. To do so, use the **Preview** mode for your container to detect any issues in your workspace.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6651bc15d12df129b9c2281d9fb1aba9" alt="Preview mode" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b279ffc597a812a9b5050fc77282f6ee" alt="Validate container" width="60%" style="color:#808080" />

3. Fix any problems you find, then retry publishing the changes from the Events Manager setup flow.

4. If the issue persists, try cleaning up your workspace. For instructions on cleaning up or re-installing, see <a href="#item-link-Q4: How can I clean up or re-install?">How to clean up or re-install</a>.

### Q2: How can I update the tagging server URL?

**A**: If your tagging server URL has changed (for example, if you switch from GCP to another server deployment option or configure a custom domain for the tagging server), you need to update the GA4 Config tag. This ensures that events are correctly routed to the new URL. Here's how you can update the tagging server URL:
- Locate the TikTok GA4 Config tag within the web container.
- Update the value of `server_container_url` to the updated tagging server URL and save the change.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/db08f85ea1c6de5995641ce24e1749f3" alt="TikTok GA4 Config tag" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3c661d0dc47a64e575309a8a97969d9d" alt="Update the value of server_container_url" width="60%" style="color:#808080" />


### Q3: Can I reuse my existing tagging server setup for Events API integration?

**A**: Yes, you can reuse your existing tagging server for integrating with the Events API. If your GTM Web container already has a Google Tag with `server_container_url` pointing to the tagging server, you can continue using it. Simply delete the highlighted Google Tag installed by the interactive setup flow, which is named `TT-{PixelID}-XXXXX-Web-Tag-GA4_Config`.

However, make sure to retain the TikTok GA4 Event tag, named `TT-{PixelID}-XXXXX-Web-Tag-GA4_Event`, and all other installed variables, as they are necessary for formatting the event correctly for the TikTok Events API tag in your server container to parse.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/245ab0df6a3aa7e89110b0fe2f24b860" alt="Google Tag" width="60%" style="color:#808080" />


### Q4: How can I clean up or re-install?
**A**: In some scenarios, you might need to delete the existing integration and perform a re-installation. This includes the following scenarios:
- Upgrading from a TikTok Pixel only integration to TikTok Pixel & Events API integration.
- Performing a "factory reset" for other reasons, such as fixing setup issues.

To clean up and re-install in these cases, follow these steps:
1. Create a backup workspace in the same container to save existing configurations.
2. In your default workspace, delete previously installed TikTok tags, variables and triggers. See [GTM container changes](https://business-api.tiktok.com/portal/docs?id=1799004081342466).
3. After cleanup, go through the interactive setup flow again to re-install the integration. 
4. Make sure to copy back any important customizations from the backup workspace to the default workspace. 


### Q5: How can I set up Events API only integration?
**A**: By default, this interactive setup flow in Events Manager integrates both the Pixel and Events API with GTM. However, if you prefer to use Events API only and not the Pixel, you can manually uninstall the Pixel setup. Note that the recommended setup is to use both Pixel and Events API to maximize event coverage and campaign performance.

To set up Events API integration, delete the "TikTok Pixel" and "Custom HTML" tags from the Web container used for integration.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/227250fc8358ad6d6ae4d9ede9b3ee65" alt="TikTok Pixel and Custom HTML tags" width="60%" style="color:#808080" />


### Q6:  How can I pick the right API to use?
**A**: Google Tag and Tag Manager are different measurement frameworks provided by Google, offering similar functionalities and APIs that can operate the [data layer](https://support.google.com/tagmanager/answer/6164391?hl=en&ref_topic=3441647&sjid=9251457294155490283-NA) to pass information from your website to your Tag Manager container. 

For Google Tag, the API is [gtag()](https://developers.google.com/tag-platform/gtagjs/reference). For Tag Manager, the API is [dataLayer.push()](https://developers.google.com/tag-platform/tag-manager/datalayer). The Events API integration supports both APIs but the set of tags and variables installed by the Events Manager interactive flow are slightly different in order to accommodate specific API behavior. 
- If your website already uses one of the frameworks (for example, for measuring websites with Google Analytics), you need to continue using the same framework and the corresponding API to set up events for TikTok. See [Set up events with data layer](https://business-api.tiktok.com/portal/docs?id=1799004097478658).
- If your website has not used these APIs but you want to implement one for your Events API integration, refer to [Google Help article](https://support.google.com/tagmanager/answer/7582054?hl=en) to pick the API that best fits your business needs.

### Q7: Do I still need to set up `event_id` to dedup events from Events API and Pixel?
**A**: No. If you use the interactive flow in Events Manager to set up Events API and Pixel integration, an `event_id` variable will be automatically configured for you. You don't need to configure anything extra for deduplication to work. [Learn more about event deduplication](https://ads.tiktok.com/help/article/event-deduplication).

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3e83f1e7ddf908c17edacb09510bc9d2" alt="event_id variable" width="60%" style="color:#808080" />

### Q8: I manually installed the [TikTok Events API (Official) template](https://tagmanager.google.com/gallery/#/owners/tiktok/templates/gtm-template-eapi) tag instead of using the Events Manager interactive flow. Can I continue using it?
**A**: Yes, you can. If you manually installed the template tag from the GTM template gallery, you can continue using it without any issues and retain any existing customizations you've made to the template. However, it is recommended to upgrade your installation by going through the Events Manager interactive flow. This will make future modifications much easier. For more information, see <a href="#item-link-Q4: How can I clean up or re-install?">How to clean up or re-install</a>.

### Q9: How can I generate a new access token for my Events API integration if I switched to a different Pixel?
**A**: The interactive setup flow will automatically create a valid Events API access token and set it in the TikTok Events API tag. However, if you want to switch to a different token (for example, if you switch to a different Pixel ID later), follow the Events API developer doc on [Authentication](https://business-api.tiktok.com/portal/docs?id=1771101130925058) to generate a new token for that Pixel ID.

## Support
- If you have any questions, please contact [TikTok Ads Customer Support](https://ads.tiktok.com/help/article/contact-tiktok-business-support) or reach out to your TikTok Sales Representative.
- If you have any feature requests or encounter any issues, file an issue on GitHub [here](https://github.com/tiktok/gtm-template-eapi).
