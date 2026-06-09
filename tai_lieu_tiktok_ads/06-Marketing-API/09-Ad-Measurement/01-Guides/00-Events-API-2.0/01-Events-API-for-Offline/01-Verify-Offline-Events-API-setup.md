---
title: "Verify Offline Events API setup"
doc_id: 1771101049613313
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for Offline / Verify Offline Events API setup"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Verify Offline Events API setup

## Verify that events are received correctly 
Navigate to **Tools** > **Events** > **Data sources** button. This will take you to the detail page of your offline event set.

Under the **Overview** tab, you can check if we have received your events. To view the latest status, refresh the page.
- If we have received your events sent from Events API, you will see "**Server**" under **Connection Method**. 
- If we have received your events sent directly from your browser via file upload, you will see "**Browser**" under **Connection Method**.

## Test Events
After you set up the Offline Event Set and are ready to start reporting offline events, you can send a test event to ensure that everything works correctly. **Before switching to 'Production' mode, make sure to remove `test_event_code` .**

### Steps
Follow these steps to send a test event:
1. In Events Manager, click the Offline Event Set to view its configuration details.
2. In the **Test Events** tab (see screenshot below), click the test code button in **Step 2** of the **Test Server Events** section. This will copy the code to your clipboard. 

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/36b1ea9210768558e1d4a96f07ee2a33" alt="test code" width="60%" style="color:#808080"/>

3. In the body of your API request for reporting events, add a new field `test_event_code` and paste the code you get from Step 2 as the value of the field. Then send the API request. Below is an example of a test request. You need to replace the values for `Access-Token`, `event_source_id`, and `test_event_code` with your own values.

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "event_source": "offline",
  "event_source_id": "{{Offline-Event-Set-ID}}",
  "test_event_code": "{{YOUR_TEST_CODE}}",
  "data": [
    {
      "event_id":"1616318632825_357",
      "event": "Purchase",
      "event_time": 1684574395,
      "user": {
        "email": ["848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f",
        "dd6ff77f54e2106661089bae4d40cdb600979bf7edc9eb65c0942ba55c7c2d7f"],
        "phone": ["2f9d2b4df907e5c9a7b3434351b55700167b998a83dc479b825096486ffcf4ea"]
       },
      "properties": {
        "order_id": "abc_xyz",
        "shop_id": "123abc",
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

4. Go to the **Test Events** tab, and look for the test event in the **Event Activity** section. Any issues with the payload will show up to help you resolve the issues.

