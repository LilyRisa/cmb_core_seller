---
title: "Verify Events setup"
doc_id: 1739584863252481
path: "Marketing API / Ad Measurement / Guides / Events API 1.0 / Events API : Web / Verify Events setup"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Verify Events setup

This article introduces how to verify web data connection setup. The processes include:
- Verify that events are received correctly
- Verify that events are configured and triggered correctly
- Verify that events are deduplicated correctly (if you're sending events from both Pixel SDK and Events API)

# Verify that events are received correctly 
You can check if we have received your events under the pixel detail page. Click on your Pixel's detail page and under the **Overview** tab:
1. If we have received your events sent from Events API, you will see "Server" under **Connection Method**. Please refresh your page to see the latest status.
2. If you are sending events from both Events API and Pixel SDK, you will see "Server&Browser".

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7b9cf0402b4dceef388a4fdf28f0fcaa" width="60%" style="color:#808080"/>

# Test Events
After you set up the pixel and are ready for reporting Pixel events, you can send a test event to verify if everything works correctly. Please make sure to remove `test_event_code` before switching to 'Production' mode.

<b>Steps</b>:
1. In Events Manager, click the pixel to view its configuration details.
2. In the <b>Test Events</b> tab, click the test code button in <b>Step 2</b> of the <b>Test Server Events</b> section to copy the code.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/81b61bee1dbe8432e35c9e1f83d42301" width="60%" style="color:#808080"/>

3. In the body of your API request for reporting events ([/pixel/track/](https://ads.tiktok.com/marketing_api/docs?id=1740858531237890)), add a new field `test_event_code` and paste the code you get from Step 2 as the value of the field. Send the API request. Below is a test request example. You need to replace values for `Access-Token`, `pixel_code`, and `test_event_code` with your own. 



```xcodeblock
(code http http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/pixel/track/' \
--header 'Access-Token: YOUR_ACCESS_TOKEN' \
--header 'Content-Type: application/json' \
--data-raw '{
  "pixel_code": "YOUR_PIXEL_CODE",
  "event": "Purchase",
  "event_id": "123asdios_1234",
  "timestamp": "2020-12-14T09:49:27Z",
  "test_event_code": "YOUR_TEST_CODE",
  "context": {
    "ad": {
      "callback": "123ATXSfe"
    },
    "page": {
      "url": "http://demo.mywebsite.com/purchase",
      "referrer": "http://demo.mywebsite.com"
    },
    "user": {
      "email": "a4ef46e711c986ea534c9c3d2a1fde303505b539ef5a7ec2a63f23a536d575d1"
    },
    "user_agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.109 Safari/537.36",
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
      },
      {
        "price": 30,
        "quantity": 1,
        "content_id": "1197218",
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

4. Go to the <b>Test Events</b> tab of the pixel in <b>TikTok Ads Manager</b>, and look for the test event in the <b>Event Activity</b> section. Any issues with the payload will show up to help you resolve the issues.

# Verify that events are deduplicated correctly
For the best ad performance, we recommend advertisers set up both Tiktok Pixel SDK and Events API to ensure maximum data coverage. In this case, advertisers need to set up deduplication to remove duplicate events to ensure that no double counting of such events will occur for the purposes of measurement and reporting. For details of deduplication, see [Event deduplication](https://ads.tiktok.com/marketing_api/docs?id=1739584864945154).

To see if your deduplication worked and how many events have been deduplicated, please navigate to your pixel's detail page.
1. The "Browser" metrics are distinct events received only from Pixel SDK.
2. The "Server" metrics are distinct events received only from Events API.
3. The "Server & Browser" metrics show the number of events sent from both Events API and Pixel SDK (the same event sent from both channels will be counted as 1 event) . If this metrics is too low, If the Server & Browser numbers are too low, please check your deduplication implementation, and make sure the `event_id` is identical between Pixel SDK and Events API.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7b9cf0402b4dceef388a4fdf28f0fcaa" width="60%" style="color:#808080"/>


