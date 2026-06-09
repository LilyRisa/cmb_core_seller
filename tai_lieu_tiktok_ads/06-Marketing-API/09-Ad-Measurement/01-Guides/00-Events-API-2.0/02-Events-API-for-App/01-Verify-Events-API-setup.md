---
title: "Verify Events API setup"
doc_id: 1819583269559297
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for App / Verify Events API setup"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Verify Events API setup

This article introduces how to verify app data connection setup. The processes include:
- Verify that events are received correctly.
- Verify that events are configured and triggered correctly.


# Verify that events are received correctly
You can check if we have received your events under the app detail page. Click on your app's detail page and under the **Overview** tab, scroll down to the **Reporting** section:

1. Ensure **TikTok App Events API** is selected as **Data Source**.
2. Check the number of **Total Events**. Note that this count may be slightly delayed.

# Verify that events are configured and triggered correctly through Test Events
After you set up your app and are ready for reporting app events, you can send a test event to verify if everything works correctly. Make sure to remove `test_event_code` before switching to 'Production' mode.

## Steps
1. In Events Manager, click the app to view its configuration details.
2. In the **Test Events** tab, click the test code button in **Step 2** to copy the code.

<br><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/36b1ea9210768558e1d4a96f07ee2a33" alt="Check Test Events" width="60%" style="color:#808080"/>

3. In the body of your API request for reporting events, add a new field `test_event_code` and paste the code you get from Step 2 as the value of the field. Send the API request. Below is a test request example. You need to replace values for `Access-Token`, `event_source_id`, and `test_event_code` with your own.

```xcodeblock
(code curl)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "event_source": "app",
  "event_source_id": "{{TIKTOK_APP_ID}",
  "test_event_code": "{{YOUR_TEST_CODE}}",
  "data": [
    {
      "event": "InstallApp",
      "event_time": 1684574395,
      "user": {
        "phone": "{{HASHED_PHONE}}",
        "email": "{{HASHED_EMAIL}}",
        "idfa": "{{IDFA}}",
        "idfv": "{{IDFV}}",
        "gaid": "{{GAID}}",
        "att_status": "AUTHORIZED"
        "locale": "en-US"
      },
      "app": {
        "app_id": "com.test.flyingsneakers"
        "app_name": "Flying Sneakers"
        "app_version": "3.0.2"
      },
	  "ad": {
        "attributed": true,
        "callback": "{{CALLBACK}}",
        "campaign_id": "1700817380525057",
        "ad_id": "1700817380806706",
        "creative_id": "1700817378256945"
        "is_retargeting": false,
        "attribution_type": "click_through"
      },
      "properties": {
        "contents": [
          {
            "currency": "USD",
            "value": 200.0
			"content_type": "product",
          {
            "price": 100.0,
            "quantity": 2,
            "content_id": "12345",
            "content_name": "Fancy-AirMax2.0 Black",
            "content_category": "Shoes - running shoes",
            "brand": "Fancy Sneakers"
          }
        ]
      }
    }
  ]
}'
(/code)
```
4. Go to the **Test Events** tab of the app in **Events Manager**, and look for the test event in the **Event Activity** section. Any issues with the payload will show up to help you resolve the issues.

