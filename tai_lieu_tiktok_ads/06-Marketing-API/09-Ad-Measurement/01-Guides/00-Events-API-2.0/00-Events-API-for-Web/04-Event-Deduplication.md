---
title: "Event Deduplication"
doc_id: 1771100965992450
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for Web / Event Deduplication"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Event Deduplication

**For the best ad performance, we recommend advertisers set up both Tiktok Pixel SDK and Events API to ensure maximum data coverage.**

Deduplication is needed for advertisers who are using both TikTok Pixel SDK and Events API to send the same event. For example, if you are sending the same event from both TikTok Pixel SDK and Events API, then you need to set up deduplication with the `event_id` or first-party cookie (`_ttp`). Doing so allows TikTok to deduplicate multiple copies of the same event, and would allow TikTok to better report/optimize your campaign performance. For details of Event deduplication, see [here](https://ads.tiktok.com/help/article/event-deduplication?redirected=2).

# `event_id`(Recommended)
TikTok deduplicates events based on their `event_source_id`, `event` (for instance, `Purchase`, or `AddToCart`) and `event_id`. Advertisers should choose a unique string as the `event_id` for each event. Order numbers or transaction IDs may be suitable for use as `event_id`. If no such IDs are available, a random number can be used, but it must be consistent across the Pixel SDK Events and Events API Events for deduplication to work.

If multiple events with the same `event_source_id`, `event` and `event_id` are received, we keep the first one and discard any later ones received within 48 hours of the first event. If a later event is received within 5 minutes of the first event, we attempt to merge its data into the first event. For example, if the first event does not include `user.email` but a later duplicate event (received within 5 minutes) contains this information, we merge the email data into the first event.

Here are some examples of how to attach an `event_id` to a TikTok Pixel SDK event and an Events API event:

## Pixel SDK code example

```xcodeblock
(code)
// Javascript code
ttq.track('AddToCart',{
    content_type: 'product',
    value: 9.2,
    currency: 'USD'
	}, 
	{
	<span style="background-color: green;">event_id:'abcde12345'</span>
	}
)
(/code)
```
## Events API example (using curl)
```xcodeblock
(code)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: {{ACCESS_TOKEN}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "event_source": "web",
    "event_source_id": "{{PIXEL_ID}}"
    "data": [
        {
            "event": "AddToCart",
            "event_time": 1687758765,
            <span style="background-color: green;">"event_id": "abcde12345",</span>
            "properties": {
                "content_type": "product",
                "value": 9.2,
                "currency": "USD"
            }
        }
    ]
}'
(/code)
```
# Cookie

If you do not send `event_id` from Events API, but send TikTok cookie (`_ttp`), we will attempt to deduplicate between browser/server events based on the `_ttp` cookie value. 

To leverage cookie based deduplication, you need to enable first-party cookie in your pixel settings. The browser pixel will then automatically store a unique identifier in a first-party cookie named `_ttp`. When reporting events via Events API, include the  `_ttp` cookie value in the `user.ttp` field of the API payload. See [Send TikTok Cookie](https://business-api.tiktok.com/portal/docs?id=1771100936446977). 

Cookie deduplication uses the combination of [`pixelCode`, `event`, `_ttp`] as deduplication key, and has a 5-minute deduplication window. This means that after we receive the 
first unique event, we will detect and drop events that arrive in the next 5 minutes with the same [`pixelCode`, `event`, `_ttp`]. 

Some limitations to this deduplication approach: 
- Cookie deduplication will only drop events sent from Events API, specifically, if there is a browser pixel event of the same  [`pixelCode`, `event`, `_ttp`] received 5 minutes or less prior to the receiving of the server event. Cookie deduplication will never drop any event sent from the browser pixel. 
- Cookie deduplication will only drop Events API event when `event_id` is not set in the Events API payload. Therefore, if you want to leverage cookie based deduplication, do not send `event_id` in the Events API payload for an event. 

# Verify your deduplication setup
To verify that your deduplication is working correctly and to see how many events have been deduplicated, navigate to your pixel's detail page.
- The "**Browser**" metric shows the distinct events received only from the Pixel SDK.
- The "**Server**" metric shows the distinct events received only from the Events API.
- The "**Server & Browser**" metric shows the number of events sent from both Events API and Pixel SDK (the same event sent from both channels will be counted as 1 event) .  If the Server & Browser numbers (total events and attributed events) are too low, check your deduplication implementation, and make sure the `event_id`  is identical between Pixel SDK and Events API.
- Select "**View details**" under **Event Health** column for any event type with Connection Method "Server & Browser", it will open the Event Health page that shows you detailed information about matching and deduplication health.

