---
title: "Event deduplication"
doc_id: 1739584864945154
path: "Marketing API / Ad Measurement / Guides / Events API 1.0 / Events API : Web / Event deduplication"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Event deduplication


**For the best ad performance, we recommend advertisers set up both TikTok Pixel SDK and Events API to ensure maximum data coverage.**

For details of Event deduplication, see [here](https://ads.tiktok.com/help/article?aid=10012410&redirected=1). 

Deduplication is needed for advertisers who are using both TikTok Pixel SDK and Events API to send the same event. For example, if you are sending `Purchase` event from both TikTok Pixel SDK and Events API, then you need to set up deduplication correctly. If you are sending non-overlapping events from TikTok Pixel SDK and Events API, then you do not need deduplication. For example, if you are sending `ViewContent` event from TikTok Pixel SDK and `Purchase` event from Events API, you do not need to implement deduplication. 

Deduplication will be based on the presence of a common `event_id`, `event`, and `pixel_code` parameter with events shared via both Events API and Pixel SDK. The event name (i.e. `Purchase` or `AddToCart`) and `event_id` must be exactly the same for deduplication to work correctly.

The `event_id` is a unique string chosen by the advertiser. Advertisers may already have order numbers or transaction IDs that are suited well for use in deduplication. For advertisers without ready-made IDs, a random number can be used. However this number must be consistent across the Pixel SDK Events and Events API Events for deduplication to work.

Duplicated events arriving within 5 minutes of each other will be merged - the latter event will be merged into the first event if the data field was not in the first event.  Duplicated events within 48 hours, the latter event will be dropped.

## Pixel SDK code example
```xcodeblock
(code Example JavaScript)
ttq.track('AddToCart', {
    contents: [
    {
      content_id: '301',
      content_name: 'running shoes',
      content_category: 'Shoes > Sneakers > running shoes',
      brand: 'your brand name',
      quantity: 1,
      price: 8,
    }],
    content_type: 'product',
    value: 9.2,
    currency: 'USD',  
},
{event_id:'1239485'});
(/code);
```
## Events API example
```xcodeblock
(code Example http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/pixel/track/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "pixel_code": "BU35TSQHT2A1QT375GMG",
  "event": "AddToCart",
  "event_id":"1239485",
  "timestamp": "2020-09-17T19:49:27Z",
  "context": {
    "ad": {
      "callback": "123ATXSfe"
    },
    "user": {
      "external_id": "f0e388f53921a51f0bb0fc8a2944109ec188b59172935d8f23020b1614cc44bc",
      "phone_number": "2f9d2b4df907e5c9a7b3434351b55700167b998a83dc479b825096486ffcf4ea",
      "email": "dd6ff77f54e2106661089bae4d40cdb600979bf7edc9eb65c0942ba55c7c2d7f"
    },
    "user_agent": "Mozilla/5.0 (platform; rv:geckoversion) Gecko/geckotrail Firefox/firefoxversion",
    "ip": "13.57.97.131"
  },
  "properties": {
    "contents": [
     {
        "price": 8,
        "quantity": 2,
        "content_id": "1077218",
        "content_name": "running shoes",
        "content_category": "Shoes > Sneakers > running shoes",
        "brand": "your brand name"
      }
    ],
    "content_type": "product",
    "currency": "USD",
    "value": 46.00
  }
}'
(/code);
```



# Verify your deduplication set up

To see if your deduplication worked and how many events have been deduplicated, please navigate to your pixel's detail page.

1. The "Browser" metric is the distinct events received only from Pixel SDK. 
2. The "Server" metric is the distinct events received only from Events API.
3. The "Server & Browser" metric shows the number of events sent from both Events API and Pixel SDK (the same event sent from both channels will be counted as 1 event). If the Server & Browser numbers are too low, please check your deduplication implementation, and make sure the `event_id` is identical between Pixel SDK and Events API. 

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7b9cf0402b4dceef388a4fdf28f0fcaa" width="60%" style="color:#808080"/>



