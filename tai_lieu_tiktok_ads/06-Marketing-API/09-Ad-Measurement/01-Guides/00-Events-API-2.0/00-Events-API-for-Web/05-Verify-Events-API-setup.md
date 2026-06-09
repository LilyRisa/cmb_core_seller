---
title: "Verify Events API setup"
doc_id: 1771100984456193
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for Web / Verify Events API setup"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Verify Events API setup

This article introduces how to verify web data connection setup. The processes include:
- Verify that events are received correctly.
- Verify that events are configured and triggered correctly.
- Verify that events are deduplicated correctly (if you're sending events from both Pixel SDK and Events API).

# Verify that events are received correctly
You can check if we have received your events under the pixel detail page. Click on your Pixel's detail page and under the **Overview** tab:
1. If we have received your events sent from Events API, you will see "**Server**" under **Connection Method**. Please refresh your page to see the latest status.
2. If you are sending events from both Events API and Pixel SDK, you will see "**Server & Browser**".

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7b9cf0402b4dceef388a4fdf28f0fcaa" width="60%" style="color:#808080"/>

# Verify that events are configured and triggered correctly through Test Events
After you set up the pixel and are ready for reporting Pixel events, you can send a test event to verify if everything works correctly. Make sure to remove `test_event_code` before switching to 'Production' mode.

## Steps
1. In Events Manager, click the pixel to view its configuration details.
2. In the **Test Events** tab, click the test code button in **Step 2** of the **Test Server Events** section to copy the code.

<br><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/36b1ea9210768558e1d4a96f07ee2a33" width="60%" style="color:#808080"/>

3. In the body of your API request for reporting events, add a new field `test_event_code` and paste the code you get from Step 2 as the value of the field. Send the API request. Below is a test request example. You need to replace values for `Access-Token`, `event_source_id`, and `test_event_code` with your own.

```xcodeblock
(code curl)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "event_source": "web",
  "event_source_id": "{{Pixel-Code}}",
  "test_event_code": "{{YOUR_TEST_CODE}}",
  "data": [
    {
      "event": "Purchase",
      "event_time": 1684574395,
      "event_id":"1616318632825_357"
      "user": {
        "email": ["848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f",
        "dd6ff77f54e2106661089bae4d40cdb600979bf7edc9eb65c0942ba55c7c2d7f"],
        "phone": ["2f9d2b4df907e5c9a7b3434351b55700167b998a83dc479b825096486ffcf4ea"],
        "ttp":"94e2a4j9-h3j5-k2h5-98cc-c84a745mk098",
        "ttclid": "E.C.P.v3fQ2RHacdksKfofPmlyuStIIHJ4Af1tKYxF9zz2c2PLx1Oaw15oHpcfl5AH",
        "ip": "139.177.225.245",
        "user_agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.1 Safari/605.1.15"
      },
      "page": {
        "url": "http://www.example.com/purchase?q=hello",
        "referrer": "http://demo.mywebsite.com"
      },
      "properties": {
        "contents": [
          {
            "price": 8,
            "quantity": 2,
            "content_id": "1077218",
            "content_name": "running shoes",
            "content_category": "Shoes > Sneakers > running shoes",
            "brand": "your_brand_name"
          },
          {
            "price": 30,
            "quantity": 1,
            "content_id": "1197218",
            "content_name": "running shoes",
            "content_category": "Shoes > Sneakers > running shoes",
            "brand": "your_brand_name"
          }
        ],
        "content_type": "product",
        "currency": "USD",
        "value": 46.00
      }
    }
  ]
}'
(/code)
```
4. Go to the **Test Events** tab of the pixel in **Events Manager**, and look for the test event in the **Event Activity** section. Any issues with the payload will show up to help you resolve the issues.

# Verify that events are deduplicated correctly
For the best ad performance, we recommend advertisers set up both TikTok Pixel SDK and Events API to ensure maximum data coverage. In this case, advertisers need to set up deduplication to remove duplicate events to ensure that no double counting of such events will occur for the purposes of measurement and reporting. For details of deduplication, see [Event Deduplication](https://ads.tiktok.com/marketing_api/docs?id=1771100965992450).

