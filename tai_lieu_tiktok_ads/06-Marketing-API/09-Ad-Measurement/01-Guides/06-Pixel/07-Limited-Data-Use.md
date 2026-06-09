---
title: "Limited Data Use"
doc_id: 1770092377990145
path: "Marketing API / Ad Measurement / Guides / Pixel / Limited Data Use"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Limited Data Use

**"Limited Data Use" (LDU)** for the **Events API** and **Pixel** is a feature to give businesses more control over how their event data is used in TikTok's systems.
<br>In order to help facilitate advertiser's compliance with the right to opt-out of sale and sharing of personal data under certain U.S. state privacy laws, TikTok offers a Limited Data Use ("LDU") feature. LDU for the Pixel and Events API helps give businesses more control over how their event data is used in TikTok's systems. The feature will be available in the following U.S. states: California, Colorado, Connecticut, Utah, Virginia, Iowa, Minnesota, Tennessee, Maryland, Delaware, Florida, Montana, Nebraska, New Hampshire, New Jersey, Oregon, Texas, Indiana, Kentucky.
<br>Businesses should consult with their legal advisors on how to use Tiktok's products in compliance with applicable laws. For details on new state data protection laws, please see our updated [jurisdictional terms](https://ads.tiktok.com/i18n/official/article?aid=10015015).

# About Limited Data Use
A business may give users choices on their website the option to opt out of the sale or sharing of their data.  
- If the business is using tools such as the Events API or pixel to share events with TikTok, businesses can choose to send TikTok a parameter to indicate that the advertiser wants limited processing of the events data shared.
- TikTok will recognize this parameter as a request for limited data processing, and will limit its processing activities accordingly if the event shared occurred in an eligible location. 

Businesses who choose to use Limited Data Use may notice an impact on audience sizes in the following U.S. states: California, Colorado, Connecticut, Utah, Virginia, Iowa, Minnesota, Tennessee, Maryland, Delaware, Florida, Montana, Nebraska, New Hampshire, New Jersey, Oregon, Texas, Indiana, Kentucky and on their campaign effectiveness (as LDU data is not used for optimization purposes). 

## Implementing Limited Data Use (Pixel)
There are two ways to enable LDU: enabling all events under the pixel, or enable by event (custom coding)

**1) Enable all Events under the Pixel**
 - download the pixel base code for a new pixel / updating an old pixel
 - open the pixel base code with a text editor, and find the code segment with ttq.load
 - add ',{limited_data_use:true}' right after the pixel code, don't forgot the ','
 - paste the pixel code in the header section of your website, and continue the flow as normal

Here is an image of how the pixel code will look:
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7ed69ad576dfdf42f65b306e19cbe1f3" width="100%" style="color:#808080"/>

**2) Enable by Event (custom coding)**
 - add an additional limited data sharing flag into the custom coding call 
 - for example: ttq.track('Purchase')  will become ttq.track('Purchase',{"limited_data_use":true})

