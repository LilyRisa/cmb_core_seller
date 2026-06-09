---
title: "GTM container changes"
doc_id: 1799004081342466
path: "Marketing API / Ad Measurement / Guides / Google Tag Manager (GTM) Integration / GTM container changes"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# GTM container changes

The following table outlines the tags, variables, and triggers created in your GTM (Google Tag Manager) account by the Events Manager interactive setup flow. Note that the specific installation options you choose may result in different elements being installed.

> <span style="color:DodgerBlue">**Note**</span><br>
> These changes will automatically apply to your GTM container once you complete the server or client setup flow.

## TikTok Pixel integration

### Event Builder as event setup method
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/setup_method" alt="setup_method" width="60%" style="color:#808080" />

```xtable
| Container {10%}| Item  {10%}| Type {15%} | Name  {30%}| Purpose {35%} |
|---|---|-|---|---|
| Web | Tag | Custom HTML | TT-&#123;PixelCode&#125;-Web-Tag-Pixel_Setup | This tag installs the TikTok pixel base code on your website. |
```
### GTM data layer APIs as event setup method
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/setup_method2" alt="setup_method2" width="60%" style="color:#808080" />

<table>
<thead>
  <tr>
    <th style="width:10%">Container</th>
    <th style="width:10%">Item</th>
    <th style="width:15%">Type</th>
    <th style="width:30%">Name</th>
    <th style="width:35%">Purpose</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="4">Web</td>
    <td rowspan="2">Tag</td>
    <td>Custom HTML</td>
    <td>TT-{PixelCode}-Web-Tag-Pixel_Setup</td>
    <td>This tag installs the TikTok pixel base code on your website.</td>
  </tr>
  <tr>
    <td>TikTok Pixel</td>
    <td>TT-{PixelCode}-Web-Tag-Pixel_Event</td>
    <td>This tag sends events on your website to TikTok.</td>
  </tr>
  <tr>
    <td>Trigger</td>
    <td>Custom Event</td>
    <td>TT-{PixelCode}-Web-Trigger-Custom_Event</td>
    <td>This trigger selects the events that will be sent to TikTok.</td>
  </tr>
  <tr>
    <td>Template</td>
    <td>TikTok Pixel</td>
    <td>TikTok Pixel</td>
    <td>This template helps process and send events.</td>
  </tr>
</tbody>
</table>

## TikTok Pixel & Events API integration

<table>
<thead>
  <tr>
    <th style="width:10%">Container</th>
    <th style="width:10%">Item</th>
    <th style="width:15%">Type</th>
    <th style="width:30%">Name</th>
    <th style="width:35%">Purpose</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="12">Web</td>
    <td rowspan="4">Tag</td>
    <td>Google Tag</td>
    <td>TT-{PixelCode}-Web-Tag-GA4_Config</td>
    <td>This tag configures the tagging server URL required for Events API integration.</td>
  </tr>
  <tr>
    <td>Google Analytics: GA4 Event</td>
    <td>TT-{PixelCode}-Web-Tag-GA4_Event</td>
    <td>This tag configures the data sent to the tagging server.</td>
  </tr>
  <tr>
    <td>Custom HTML</td>
    <td>TT-{PixelCode}-Web-Tag-Pixel_Setup</td>
    <td>This tag installs the TikTok pixel base code on your website.</td>
  </tr>
  <tr>
    <td>TikTok Pixel</td>
    <td>TT-{PixelCode}-Web-Tag-Pixel_Event</td>
    <td>This tag sends events on your website to TikTok.</td>
  </tr>
  <tr>
    <td>Trigger</td>
    <td>Custom Event</td>
    <td>TT-{PixelCode}-Web-Trigger-Custom_Event</td>
    <td>This trigger selects the events that will be sent to TikTok.</td>
  </tr>
  <tr>
    <td rowspan="6">Variable</td>
    <td>Custom JavaScript</td>
    <td>TT-{PixelCode}-Web-Variable-User_Data</td>
    <td>This variable fetches user data for server side events.</td>
  </tr>
  <tr>
    <td>Custom JavaScript</td>
    <td>TT-{PixelCode}-Web-Variable-Items</td>
    <td>This variable fetches items for server side events.</td>
  </tr>
  <tr>
    <td>Custom JavaScript</td>
    <td>TT-{PixelCode}-Web-Variable-Currency</td>
    <td>This variable fetches currency for server side events.</td>
  </tr>
  <tr>
    <td>Custom JavaScript</td>
    <td>TT-{PixelCode}-Web-Variable-Value</td>
    <td>This variable fetches value for server side events.</td>
  </tr>
  <tr>
    <td>Custom JavaScript</td>
    <td>TT-{PixelCode}-Web-Variable-External_ID</td>
    <td>This variable fetches External ID for server side events.</td>
  </tr>
  <tr>
    <td>Custom JavaScript</td>
    <td>TT-{PixelCode}-Web-Variable-Event_ID</td>
    <td>This variable fetches event ID for server side events.</td>
  </tr>
  <tr>
    <td>Template</td>
    <td>TikTok Pixel</td>
    <td>TikTok Pixel</td>
    <td>This template helps process and send events to TikTok.</td>
  </tr>
  <tr>
    <td rowspan="3">Server</td>
    <td>Tag</td>
    <td>TikTok Events API (Official)</td>
    <td>TT-{PixelCode}-Server-Tag-EAPI_Event</td>
    <td>This tag sends server side events to TikTok Events API.</td>
  </tr>
  <tr>
    <td>Trigger</td>
    <td>Custom</td>
    <td>TT-{PixelCode}-Server-Trigger-All_Event</td>
    <td>This trigger selects the server side events that will be sent to TikTok Events API.</td>
  </tr>
  <tr>
    <td>Template</td>
    <td>TikTok Events API (Official)</td>
    <td>TikTok Events API (Official)</td>
    <td>This template helps process and send sever side events to TikTok Events API.</td>
  </tr>
</tbody>
</table>


