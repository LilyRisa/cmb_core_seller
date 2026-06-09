---
title: "Payload Converter"
doc_id: 1805266308200514
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Payload Converter"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Payload Converter

This guide takes you through the process of using the Payload Converter for the Events API. 

The Payload Converter provides a solution that automatically identifies the channel of an external payload, such as Meta, at TikTok's server side. It then converts it into a format equivalent for TikTok. As a result, advertisers can directly send pre-constructed events to TikTok via Events API.

## Before you begin
Please follow these [steps](https://business-api.tiktok.com/portal/docs?id=1771100865818625) to get started with web data connection with TikTok to start sending events to TikTok using the Payload Converter.

## Send Requests
To send new events, make a `POST` request to Events API's endpoint: [https://business-api.tiktok.com/open_api/v1.3/event/track/](https://business-api.tiktok.com/portal/docs?id=1771101303285761).

```xtable
| Header Field{30%} | Data Type {20%}| Description{50%} |
|---|---|---|
| Access-Token {Required}| string | Authorized access token.<br><br>To obtain an access token, refer to [Events API 2.0 - Authentication](https://ads.tiktok.com/marketing_api/docs?id=1771101130925058). |
| X-Event-Source-ID {Required}| string | Pixel ID. |
| Content-Type{Required} | string | Request message type.<br><br> Allowed format: `application/json`. |
| X-Ttp-Cookie | string | For setting up deduplication between TikTok Pixel and Events API.<br><br> See <a href="#item-link-Deduplication with Payload Converter">Deduplication with Payload Converter</a> for more details |
```

The following **Demos of the Payload Converter** demonstrate how to send Meta Events via the TikTok Events API. Once the events arrive at TikTok, the payload converter will be triggered to convert the Meta Event into a TikTok Event.

### cURL
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: <TIKTOK_PIXEL_ACCESS_TOKEN>' \
--header 'X-Event-Source-ID: <TIKTOK_PIXEL_ID>' \
--header 'Content-Type: application/json' \
--data-raw '
{
  "data": [
    {
      "event_name": "ViewContent",
      "event_time": 1705657064,
      "user_data": {
        "em": [
          "8830eedd6c6b5ea97d181563a349476ca1bb25ace1f94b5c5e48d9cad727941b"
        ],
        "ph": [
          "254aa248acb47dd654ca3ea53f48c2c26d641d23d7e2e93a1ec56258df7674c4"
        ],
        "client_ip_address": "123.123.123.123"
      },
      "custom_data": {
        "currency": "USD",
        "value": 123.45,
        "contents": [
          {
            "id": "product123",
            "quantity": 1
          }
        ]
      },
      "event_source_url": "http://tiktok-market.com/product/123",
      "action_source": "website"
    }
  ]
}
'
(/code)
```

### Java SDK

If advertisers are already using the Meta Conversion API SDK, you can reuse the existing Meta code. By merely adding the "Access-Token" and "X-Event-Source-ID", you can effortlessly send Meta events to TikTok (for specific operations, refer to the following Java SDK example).

```xcodeblock
(code curl http)
import com.facebook.ads.sdk.APIContext;
import com.facebook.ads.sdk.APIException;
import com.facebook.ads.sdk.serverside.*;
import com.google.gson.Gson;
import com.google.gson.JsonElement;
import com.google.gson.JsonObject;
import okhttp3.*;

import java.io.IOException;
import java.util.Arrays;

public class Main {

    public static final String ACCESS_TOKEN = "<META_CAPI_ACCESS_TOKEN>";
    public static final String PIXEL_ID = "<META_PIXEL_ID>";

    public static void main(String[] args) {

        //Example of Sending events using Meta Conversion API SDK.
        APIContext context = new APIContext(ACCESS_TOKEN).enableDebug(true);
        context.setLogger(System.out);

        UserData userData = new UserData()
            .emails(Arrays.asList("joe@eg.com"))
            .phones(Arrays.asList("12345678901"))
            .clientIpAddress("123.123.123.123");

        Content content = new Content()
            .productId("product123")
            .quantity(1L);

        CustomData customData = new CustomData()
            .addContent(content)
            .currency("USD")
            .value(123.45F);

        Event event = new Event();
        event.eventName("ViewContent")
            .eventTime(System.currentTimeMillis() / 1000L)
            .userData(userData)
            .customData(customData)
            .eventSourceUrl("http://tiktok-market.com/product/123")
            .actionSource(ActionSource.website);

        EventRequest eventRequest = new EventRequest(PIXEL_ID, context);
        eventRequest.addDataItem(event);

        try {
            EventResponse response = eventRequest.execute();
            System.out.println(String.format("Standard API response : %s ", response));
        } catch (APIException e) {
            e.printStackTrace();
        }


        //Example of Payload Converter: Sending Meta Events via the TikTok Events API.
        JsonObject metaPayload = new JsonObject();
        metaPayload.add("data", new Gson().fromJson(eventRequest.getSerializedPayload(), JsonElement.class));

        OkHttpClient client = new OkHttpClient();
        Request request = new Request.Builder()
            .url("https://business-api.tiktok.com/open_api/v1.3/event/track/")
            .addHeader("Access-Token", "<TIKTOK_PIXEL_ACCESS_TOKEN>")
            .addHeader("X-Event-Source-ID", "<TIKTOK_PIXEL_ID>")
            .addHeader("Content-Type", "application/json")
            .post(RequestBody.create(MediaType.parse("application/json; charset=utf-8"), metaPayload.toString()))
            .build();

        try {
            Response response = client.newCall(request).execute();
            System.out.println(String.format("Tiktok Standard API response : %s ", response));

        } catch (IOException e) {
            e.printStackTrace();
        }
    }
}
(/code)
```

### Java Non-SDK Sample
```xcodeblock
(code curl http)
import com.google.gson.JsonArray;
import com.google.gson.JsonObject;
import okhttp3.*;

public class Main {

    public static final String ACCESS_TOKEN = "<META_CAPI_ACCESS_TOKEN>";
    public static final String PIXEL_ID = "<META_PIXEL_ID>";

    public static void main(String[] args) {

        //Example of Sending Meta events using http post
        JsonObject userData = new JsonObject();
        userData.addProperty("em", "8830eedd6c6b5ea97d181563a349476ca1bb25ace1f94b5c5e48d9cad727941b");
        userData.addProperty("ph", "254aa248acb47dd654ca3ea53f48c2c26d641d23d7e2e93a1ec56258df7674c4");
        
        JsonObject contentsData = new JsonObject();
        customData.addProperty("product_id", "product123");
        customData.addProperty("quantity", 1);
        
        List<JsonObject> contentsArr = new ArrayList<>();
        contentsArr.add(contentsData);
        
        JsonObject customData = new JsonObject();
        customData.addProperty("contents", contentsArr);
        customData.addProperty("value", 123.45);
        customData.addProperty("currency", "USD");

        JsonObject event = new JsonObject();
        event.addProperty("event_name", "ViewContent");
        event.addProperty("event_time", System.currentTimeMillis() / 1000L);
        event.add("user_data", userData);
        event.add("custom_data", customData);
        event.addProperty("action_source", "website");
        event.addProperty("event_source_url", "http://tiktok-market.com/product/123");

        JsonObject metaPayload = new JsonObject();
        JsonArray data = new JsonArray();
        data.add(event);
        metaPayload.add("data", data);

        OkHttpClient client = new OkHttpClient();
        Request request = new Request.Builder()
                .url("https://graph.facebook.com/v20.0/" + PIXEL_ID + "/events?access_token=" + ACCESS_TOKEN)
                .addHeader("Content-Type", "application/json")
                .post(RequestBody.create(MediaType.parse("application/json; charset=utf-8"), metaPayload.toString()))
                .build();

        try {
            Response response = client.newCall(request).execute();
            System.out.println(String.format("Meta Standard API response : %s ", response));

        } catch (Throwable t) {
            System.out.println(t.getMessage());
        }

        //Example of Payload Converter: Sending Meta Events via the TikTok Events API.
        OkHttpClient tiktokClient = new OkHttpClient();
        Request tiktokRequest = new Request.Builder()
                .url("https://business-api.tiktok.com/open_api/v1.3/event/track/")
                .addHeader("Access-Token", "<TIKTOK_PIXEL_ACCESS_TOKEN")
                .addHeader("X-Event-Source-ID", "<TIKTOK_PIXEL_ID>")
                .addHeader("Content-Type", "application/json")
                .post(RequestBody.create(MediaType.parse("application/json; charset=utf-8"), metaPayload.toString()))
                .build();

        try {
            Response response = tiktokClient.newCall(tiktokRequest).execute();
            System.out.println(String.format("Tiktok Standard API response : %s ", response));

        } catch (Throwable t) {
            System.out.println(t.getMessage());
        }
    }
}
(/code)
```

### Python SDK
```xcodeblock
(code curl http)
import json
import time

import requests
from facebook_business.adobjects.serverside.action_source import ActionSource
from facebook_business.adobjects.serverside.content import Content
from facebook_business.adobjects.serverside.custom_data import CustomData
from facebook_business.adobjects.serverside.event import Event
from facebook_business.adobjects.serverside.event_request import EventRequest
from facebook_business.adobjects.serverside.user_data import UserData
from facebook_business.api import FacebookAdsApi

# Example of Sending events using Meta Conversion API SDK.
access_token = '<META_CAPI_ACCESS_TOKEN>'
pixel_id = '<META_PIXEL_ID>'

FacebookAdsApi.init(access_token=access_token)

user_data = UserData(
    emails=['joe@eg.com'],
    phones=['12345678901'],
    client_ip_address='123.123.123.123',
)

content = Content(
    product_id='product123',
    quantity=1,
)

custom_data = CustomData(
    contents=[content],
    currency='USD',
    value='123.45',
)

event = Event(
    event_name='ViewContent',
    event_time=int(time.time()),
    user_data=user_data,
    custom_data=custom_data,
    event_source_url='http://tiktok-market.com/product/123',
    action_source=ActionSource.WEBSITE,
)

events = [event]

event_request = EventRequest(
    events=events,
    pixel_id=pixel_id,
)

event_response = event_request.execute()
print(event_response)

# Example of Payload Converter: Sending Meta Events via the TikTok Events API.
meta_payload = {
    'data': [event.normalize() for event in events]
}

headers = {
    'Access-Token': '<TIKTOK_PIXEL_ACCESS_TOKEN>',
    'X-Event-Source-ID': '<TIKTOK_PIXEL_ID>',
    'Content-Type': 'application/json',
}

response = requests.post('https://business-api.tiktok.com/open_api/v1.3/event/track/', headers=headers,
                         data=json.dumps(meta_payload))
print(response.text)
(/code)
```

### Python Non-SDK Sample
```xcodeblock
(code curl http)
import json
import time

import requests

META_ACCESS_TOKEN = '<META_CAPI_ACCESS_TOKEN>'
META_PIXEL_ID = '<META_PIXEL_ID>'


def convert():
    # example Meta CAPI event
    event = {
        "event_name": "ViewContent",
        "event_time": int(time.time()),
        "action_source": "website",
        "event_source_url": "http://tiktok-market.com/product/123",
        "user_data": {
            "em": [
                # hash for joe@eg.com 
                "8830eedd6c6b5ea97d181563a349476ca1bb25ace1f94b5c5e48d9cad727941b"
            ],
            "ph": [
                # hash for 12345678901
                "254aa248acb47dd654ca3ea53f48c2c26d641d23d7e2e93a1ec56258df7674c4"
            ]
        },
        "custom_data": {
            "contents": [
                {
                    "product_id": "product123",
                    "quantity": 1
                }
            ],
            "currency": "USD",
            "value": 123.45
        }
    }

    meta_payload = {
        'data': [event]
    }
    # post event to Meta CAPI
    meta_url = "https://graph.facebook.com/v20.0/" + META_PIXEL_ID + "/events?access_token=" + META_ACCESS_TOKEN
    meta_headers = {'Content-Type': 'application/json'}
    meta_response = requests.post(url=meta_url, headers=meta_headers, data=json.dumps(meta_payload))
    print(meta_response.text)

    tiktok_headers = {
        'Access-Token': '<TIKTOK_PIXEL_ACCESS_TOKEN>',  # tiktok eAPI access token
        'X-Event-Source-ID': '<TIKTOK_PIXEL_ID>',  # tiktok pixel ID
        'Content-Type': 'application/json',
    }
    # post same meta payload to TikTok Events API
    tiktok_response = requests.post(url='https://business-api.tiktok.com/open_api/v1.3/event/track/',
                                    headers=tiktok_headers, data=json.dumps(meta_payload))
    # example success response {"code": 0, "message": "OK", "request_id": "xxxxxxx", "data": {}}
    print(tiktok_response.text)

(/code)
```

## Verify Events Setup
After setting up the Payload Converter integration and before sending live events to TikTok, you can send test events to verify that the data connection is working properly. Please follow [these steps](https://business-api.tiktok.com/portal/docs?id=1771100984456193) to generate the test event code to include in the payload to send to TikTok. Once you have the test event code, please refer to the examples below on how to include the test event code to the payload.


### cURL

```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: <TIKTOK_PIXEL_ACCESS_TOKEN>' \
--header 'X-Event-Source-ID: <TIKTOK_PIXEL_ID>' \
--header 'Content-Type: application/json' \
--data-raw '
{
  "data": [
    {
      "event_name": "ViewContent",
      "event_time": 1705657064,
      "user_data": {
        "em": [
          "8830eedd6c6b5ea97d181563a349476ca1bb25ace1f94b5c5e48d9cad727941b"
        ],
        "ph": [
          "254aa248acb47dd654ca3ea53f48c2c26d641d23d7e2e93a1ec56258df7674c4"
        ],
        "client_ip_address": "123.123.123.123"
      },
      "custom_data": {
        "currency": "USD",
        "value": 123.45,
        "contents": [
          {
            "id": "product123",
            "quantity": 1
          }
        ]
      },
      "event_source_url": "http://tiktok-market.com/product/123",
      "action_source": "website"
    }
  ],
  <span style="background-color: green;">"test_event_code": "&#60;TIKTOK_TEST_EVENT_CODE&#62;"</span>
}
'
(/code)
```

### Java SDK

```xcodeblock
(code http)
import com.facebook.ads.sdk.APIContext;
import com.facebook.ads.sdk.APIException;
import com.facebook.ads.sdk.serverside.*;
import com.google.gson.Gson;
import com.google.gson.JsonElement;
import com.google.gson.JsonObject;
import okhttp3.*;

import java.io.IOException;
import java.util.Arrays;

public class Main {

    public static final String ACCESS_TOKEN = "<META_CAPI_ACCESS_TOKEN>";
    public static final String PIXEL_ID = "<META_PIXEL_ID>";

    public static void main(String[] args) {

        //Example of Sending events using Meta Conversion API SDK.
        APIContext context = new APIContext(ACCESS_TOKEN).enableDebug(true);
        context.setLogger(System.out);

        UserData userData = new UserData()
            .emails(Arrays.asList("joe@eg.com"))
            .phones(Arrays.asList("12345678901"))
            .clientIpAddress("123.123.123.123");

        Content content = new Content()
            .productId("product123")
            .quantity(1L);

        CustomData customData = new CustomData()
            .addContent(content)
            .currency("USD")
            .value(123.45F);

        Event event = new Event();
        event.eventName("ViewContent")
            .eventTime(System.currentTimeMillis() / 1000L)
            .userData(userData)
            .customData(customData)
            .eventSourceUrl("http://tiktok-market.com/product/123")
            .actionSource(ActionSource.website);

        EventRequest eventRequest = new EventRequest(PIXEL_ID, context);
        eventRequest.addDataItem(event);
        <span style="background-color: green;">eventRequest.setTestEventCode("&#60;TIKTOK_TEST_EVENT_CODE&#62;");</span>

        try {
            EventResponse response = eventRequest.execute();
            System.out.println(String.format("Standard API response : %s ", response));
        } catch (APIException e) {
            e.printStackTrace();
        }


        //Example of Payload Converter: Sending Meta Events via the TikTok Events API.
        JsonObject metaPayload = new JsonObject();
        metaPayload.add("data", new Gson().fromJson(eventRequest.getSerializedPayload(), JsonElement.class));

        OkHttpClient client = new OkHttpClient();
        Request request = new Request.Builder()
            .url("https://business-api.tiktok.com/open_api/v1.3/event/track/")
            .addHeader("Access-Token", "<TIKTOK_PIXEL_ACCESS_TOKEN>")
            .addHeader("X-Event-Source-ID", "<TIKTOK_PIXEL_ID>")
            .addHeader("Content-Type", "application/json")
            .post(RequestBody.create(MediaType.parse("application/json; charset=utf-8"), metaPayload.toString()))
            .build();

        try {
            Response response = client.newCall(request).execute();
            System.out.println(String.format("Tiktok Standard API response : %s ", response));

        } catch (IOException e) {
            e.printStackTrace();
        }
    }
}
(/code)
```

### Java Non-SDK Sample

```xcodeblock
(code http)
import com.google.gson.JsonArray;
import com.google.gson.JsonObject;
import okhttp3.*;

public class Main {

    public static final String ACCESS_TOKEN = "<META_CAPI_ACCESS_TOKEN>";
    public static final String PIXEL_ID = "<META_PIXEL_ID>";

    public static void main(String[] args) {

        //Example of Sending Meta events using http post
        JsonObject userData = new JsonObject();
        userData.addProperty("em", "8830eedd6c6b5ea97d181563a349476ca1bb25ace1f94b5c5e48d9cad727941b");
        userData.addProperty("ph", "254aa248acb47dd654ca3ea53f48c2c26d641d23d7e2e93a1ec56258df7674c4");
        
        JsonObject contentsData = new JsonObject();
        customData.addProperty("product_id", "product123");
        customData.addProperty("quantity", 1);
        
        List<JsonObject> contentsArr = new ArrayList<>();
        contentsArr.add(contentsData);
        
        JsonObject customData = new JsonObject();
        customData.addProperty("contents", contentsArr);
        customData.addProperty("value", 123.45);
        customData.addProperty("currency", "USD");

        JsonObject event = new JsonObject();
        event.addProperty("event_name", "ViewContent");
        event.addProperty("event_time", System.currentTimeMillis() / 1000L);
        event.add("user_data", userData);
        event.add("custom_data", customData);
        event.addProperty("action_source", "website");
        event.addProperty("event_source_url", "http://tiktok-market.com/product/123");

        JsonObject metaPayload = new JsonObject();
        JsonArray data = new JsonArray();
        data.add(event);
        metaPayload.add("data", data);
        <span style="background-color: green;">metaPayload.add("test_event_code", "&#60;TIKTOK_TEST_EVENT_CODE&#62;")</span>

        OkHttpClient client = new OkHttpClient();
        Request request = new Request.Builder()
                .url("https://graph.facebook.com/v20.0/" + PIXEL_ID + "/events?access_token=" + ACCESS_TOKEN)
                .addHeader("Content-Type", "application/json")
                .post(RequestBody.create(MediaType.parse("application/json; charset=utf-8"), metaPayload.toString()))
                .build();

        try {
            Response response = client.newCall(request).execute();
            System.out.println(String.format("Meta Standard API response : %s ", response));

        } catch (Throwable t) {
            System.out.println(t.getMessage());
        }

        //Example of Payload Converter: Sending Meta Events via the TikTok Events API.
        OkHttpClient tiktokClient = new OkHttpClient();
        Request tiktokRequest = new Request.Builder()
                .url("https://business-api.tiktok.com/open_api/v1.3/event/track/")
                .addHeader("Access-Token", "<TIKTOK_PIXEL_ACCESS_TOKEN")
                .addHeader("X-Event-Source-ID", "<TIKTOK_PIXEL_ID>")
                .addHeader("Content-Type", "application/json")
                .post(RequestBody.create(MediaType.parse("application/json; charset=utf-8"), metaPayload.toString()))
                .build();

        try {
            Response response = tiktokClient.newCall(tiktokRequest).execute();
            System.out.println(String.format("Tiktok Standard API response : %s ", response));

        } catch (Throwable t) {
            System.out.println(t.getMessage());
        }
    }
}
(/code)
```

### Python SDK

```xcodeblock
(code http)
import json
import time

import requests
from facebook_business.adobjects.serverside.action_source import ActionSource
from facebook_business.adobjects.serverside.content import Content
from facebook_business.adobjects.serverside.custom_data import CustomData
from facebook_business.adobjects.serverside.event import Event
from facebook_business.adobjects.serverside.event_request import EventRequest
from facebook_business.adobjects.serverside.user_data import UserData
from facebook_business.api import FacebookAdsApi

# Example of Sending events using Meta Conversion API SDK.
access_token = '<META_CAPI_ACCESS_TOKEN>'
pixel_id = '<META_PIXEL_ID>'

FacebookAdsApi.init(access_token=access_token)

user_data = UserData(
    emails=['joe@eg.com'],
    phones=['12345678901'],
    client_ip_address='123.123.123.123',
)

content = Content(
    product_id='product123',
    quantity=1,
)

custom_data = CustomData(
    contents=[content],
    currency='USD',
    value='123.45',
)

event = Event(
    event_name='ViewContent',
    event_time=int(time.time()),
    user_data=user_data,
    custom_data=custom_data,
    event_source_url='http://tiktok-market.com/product/123',
    action_source=ActionSource.WEBSITE,
)

events = [event]

event_request = EventRequest(
    events=events,
    <span style="background-color: green;">test_event_code='&#60;TIKTOK_TEST_EVENT_CODE&#62;',</span>
    pixel_id=pixel_id,
)

event_response = event_request.execute()
print(event_response)

# Example of Payload Converter: Sending Meta Events via the TikTok Events API.
meta_payload = {
    'data': [event.normalize() for event in events]
}

headers = {
    'Access-Token': '<TIKTOK_PIXEL_ACCESS_TOKEN>',
    'X-Event-Source-ID': '<TIKTOK_PIXEL_ID>',
    'Content-Type': 'application/json',
}

response = requests.post('https://business-api.tiktok.com/open_api/v1.3/event/track/', headers=headers,
                         data=json.dumps(meta_payload))
print(response.text)
(/code)
```

### Python Non-SDK Sample

```xcodeblock
(code http)
import json
import time

import requests

META_ACCESS_TOKEN = '<META_CAPI_ACCESS_TOKEN>'
META_PIXEL_ID = '<META_PIXEL_ID>'


def convert():
    # example Meta CAPI event
    event = {
        "event_name": "ViewContent",
        "event_time": int(time.time()),
        "action_source": "website",
        "event_source_url": "http://tiktok-market.com/product/123",
        "user_data": {
            "em": [
                # hash for joe@eg.com 
                "8830eedd6c6b5ea97d181563a349476ca1bb25ace1f94b5c5e48d9cad727941b"
            ],
            "ph": [
                # hash for 12345678901
                "254aa248acb47dd654ca3ea53f48c2c26d641d23d7e2e93a1ec56258df7674c4"
            ]
        },
        "custom_data": {
            "contents": [
                {
                    "product_id": "product123",
                    "quantity": 1
                }
            ],
            "currency": "USD",
            "value": 123.45
        }
    }

    meta_payload = {
        'data': [event],
        <span style="background-color: green;">'test_event_code': '&#60;TIKTOK_TEST_EVENT_CODE&#62;'</span>
    }
    # post event to Meta CAPI
    meta_url = "https://graph.facebook.com/v20.0/" + META_PIXEL_ID + "/events?access_token=" + META_ACCESS_TOKEN
    meta_headers = {'Content-Type': 'application/json'}
    meta_response = requests.post(url=meta_url, headers=meta_headers, data=json.dumps(meta_payload))
    print(meta_response.text)

    tiktok_headers = {
        'Access-Token': '<TIKTOK_PIXEL_ACCESS_TOKEN>',  # tiktok eAPI access token
        'X-Event-Source-ID': '<TIKTOK_PIXEL_ID>',  # tiktok pixel ID
        'Content-Type': 'application/json',
    }
    # post same meta payload to TikTok Events API
    tiktok_response = requests.post(url='https://business-api.tiktok.com/open_api/v1.3/event/track/',
                                    headers=tiktok_headers, data=json.dumps(meta_payload))
    # example success response {"code": 0, "message": "OK", "request_id": "xxxxxxx", "data": {}}
    print(tiktok_response.text)

(/code)
```

Go to the **Test Events** tab of the Pixel in Events Manager, and look for the test event in the **Event Activity** section. Any issues with the payload will show up to help you resolve the issues.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7f1ab488edc53374661b2b1e9adc72e9" alt="Event Activity" width="60%" style="color:#808080" />

Make sure to remove `test_event_code` before switching to 'Production' mode.

## Deduplication with Payload Converter
Deduplication is needed for advertisers who are using both TikTok Pixel SDK and Events API to send the same event, allowing TikTok to better report/optimize your campaign performance. Learn more about [Event Deduplication](https://business-api.tiktok.com/portal/docs?id=1771100965992450).

When sending other platform's payload to Events API (i.e. leveraging the Payload Converter feature), you can set up [Cookie Deduplication](https://business-api.tiktok.com/portal/docs?id=1771100965992450#item-link-Cookie) to deduplicate events between TikTok Pixel and Events API.

You will need to [enable first-party cookies](https://business-api.tiktok.com/portal/docs?id=1771100936446977#item-link-_ttp%20cookie) in your TikTok Pixel settings, and also send an extra `X-Ttp-Cookie` http request header with your Events API request. The `X-Ttp-Cookie` header's value should be the cookie value of TikTok first-party cookie (`_ttp`).  An example curl request:

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: <TIKTOK_PIXEL_ACCESS_TOKEN>' \
--header 'X-Event-Source-ID: <TIKTOK_PIXEL_ID>' \
--header 'X-Ttp-Cookie: <TIKTOK_TTP_COOKIE_VALUE>' \
--header 'Content-Type: application/json' \
--data-raw '{
  <SOME_PAYLOAD>
}'
(/code)
```

## Responses & Errors
For API error response handling, please refer to [this doc](https://business-api.tiktok.com/portal/docs?id=1771101162079234).

