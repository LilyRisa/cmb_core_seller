---
title: "Limited data use"
doc_id: 1771101204435970
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Limited data use"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Limited data use

**"Limited Data Use" (LDU)** for the **Events API** and **Pixel** is a feature to give businesses more control over how their event data is used in TikTok's systems.

In order to help facilitate advertiser's compliance with the right to opt-out of sale and sharing of personal data under certain U.S. state privacy laws, TikTok offers a Limited Data Use ("LDU") feature. LDU for the Pixel and Events API helps give businesses more control over how their event data is used in TikTok's systems. The feature will be available in the following U.S. states: California, Colorado, Connecticut, Utah, Virginia, Iowa, Minnesota, Tennessee, Maryland, Delaware, Florida, Montana, Nebraska, New Hampshire, New Jersey, Oregon, Texas, Indiana, Kentucky.

Businesses should consult with their legal advisors on how to use TikTok's products in compliance with applicable laws. For details on new state data protection laws, please see our updated [jurisdictional terms](https://ads.tiktok.com/i18n/official/article?aid=10015015).

# About Limited Data Use
A business may give users choices on their website the option to opt out of the sale or sharing of their data.  
- If the business is using tools such as the Events API or pixel to share events with TikTok, businesses can choose to send TikTok a parameter to indicate that the advertiser wants limited processing of the events data shared.
- TikTok will recognize this parameter as a request for limited data processing, and will limit its processing activities accordingly if the event shared occurred in an eligible location. 

Businesses who choose to use Limited Data Use may notice an impact on audience sizes in the following U.S. states: California, Colorado, Connecticut, Utah, Virginia, Iowa, Minnesota, Tennessee, Maryland, Delaware, Florida, Montana, Nebraska, New Hampshire, New Jersey, Oregon, Texas, Indiana, Kentucky and on their campaign effectiveness (as LDU data is not used for optimization purposes). 

# Implementing Limited Data Use (Events API 2.0)
To flag an event for limited data processing, a business will add one extra line of code to their Events API call.
> <span style="color: DodgerBlue">**Note**</span><br> It is required to include IP address in the data sent to TikTok via the Events API to enable Limited Data Use.

See the line highlighted in green below as an illustration for an "add to cart" event:
``` xcodeblock
(code)
{
    "event_source":"web",
    "event_source_id": "{{pixel_code}}"
    "data": [
        {
            "event": "AddToCart",
            "event_time": 1687758765,
            <span style="background-color: green;">"limited_data_use": true,  // new data field</span>
            "user": {
                "email": "848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f",
                "phone": "2f9d2b4df907e5c9a7b3434351b55700167b998a83dc479b825096486ffcf4ea"
            },
            "page": {
                "url": "http://www.example.com/purchase?q=hello"
            }
            // ... other fields ...
        }
    ]
}
(/code)
```

