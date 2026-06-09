---
title: "Events API : App"
doc_id: 1739584805049345
path: "Marketing API / Ad Measurement / Guides / Events API 1.0 / Events API : App"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Events API : App

> <span style="color:DodgerBlue">**Note**</span><br>Events API - App is currently in Beta. If you'd like to use this API, please contact your TikTok representative.  Your accounts need to be allowlisted before you can report events using [/v1.3/app/track/](https://ads.tiktok.com/marketing_api/docs?rid=oyn7lhbo6ar&id=1740859196043266) or [/v1.3/app/batch/](https://ads.tiktok.com/marketing_api/docs?rid=oyn7lhbo6ar&id=1740859218541569).

## Overview
TikTok’s Events API is a secure server-to-server interface that allows advertisers to share the actions customers take on their websites, apps, or offline directly with TikTok. Data that is shared via the Events API is processed similar to information shared via other TikTok data integration business tools, like the TikTok pixel and TikTok SDK. Depending on the data shared, you can leverage Events API to power other TikTok solutions like dynamic product ads, custom targeting, campaign optimization and attribution.

## Benefits

1. **Streamlined stability and security:** Integrate and iterate without client-side limitations, like network connectivity or ad blocker issues.
2. **Configurable privacy controls:** Stay compliant with rapidly evolving requirements with flexible privacy controls that let you adapt what data you share and when you share it.
3. **Maximum event measurement:** Capture more events with improved accuracy across different browsers, apps and devices to get a unified view of your customer's journey from page view to purchase.

## Notes

* For user-level data on iOS14 or higher, we expect advertisers to adhere to Apple’s policies around privacy and data use.
* Currently, we do not accept custom events or personally identifiable information through this API. If there are unlisted app events that are critical for the advertiser’s business, please contact your TikTok representative.
* A request only reports events to one mobile app ID. This restriction is mostly because authorization will be done at user/mobile app level. See the Authorization section for details.
* We will support QPS =< 1k per developer app（TikTok Marketing API app）per endpoint. If more is required, contact PSO/solutions engineer.

To report a single app event to TikTok, use the `/app/track/` endpoint. To report app events in bulk, use the `app/bulk/` endpoint.

## Process Overview

In summary, to report app events through marketing API, an advertiser would go through the following processes (assume you are already advertising on TikTok with one or more TikTok ad accounts):

1. Firstly, you need to register as a TikTok ads developer (see [Registration](https://ads.tiktok.com/marketing_api/docs?id=100520) documentation) and create a TikTok marketing API developer app (see [Create an app](https://ads.tiktok.com/marketing_api/docs?id=100521) documentation). 

2. In the ad account that promotes your mobile app, create a mobile app in TikTok Ads Manager >**Tools** > **Events** > **Data sources** > **App** and choose "**I don’t use a mobile measurement partner service**".

3. After created mobile app, you can click into it to generate TikTok App ID. See [Business Help Center](https://ads.tiktok.com/help/article?aid=10001060).

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ae87e213564faa677f2ab7c2856e51cf" alt="TikTok App ID" width="60%" style="color:#808080" />

4. Create a TikTok developer app with "Measurement > Report App Event" permission scope.

5. Once the developer app is created, use the system user from step #2 to open the authorized URL in a browser, and confirm to give the TikTok developer app the permission to report events to all the mobile apps this user has permission for (permission is at user level).

   * It is recommended to only grant the "Report App Event" permission when generating this access token, to minimize the impact if the access token is ever compromized.

6. Exchange the auth code for the access token (see [Authorization](https://ads.tiktok.com/marketing_api/docs?rid=agvbv6u2z3t&id=100511) documentation) and save the access token in a safe place.

7. Use the access token for auth in your marketing API request to send app events to TikTok.

## Report an App Event

To report a mobile (i.e. Android/iOS) app event (e.g. LaunchAPP, Purchase) to TikTok, make a `POST` request through the `/app/track/` endpoint.

### Request

**Request address** 
https://business-api.tiktok.com/open_api/v1.3/app/track/

**Request method** POST

**Request Header**

```xtable
|Field|Data Type|Desciption|
|---|---|---|
|Access-Token {Required}|string|Authorized Access Token. It can be obtained through the <br>["Get Access token"](https://ads.tiktok.com/marketing_api/docs?id=100681) interface. |
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`|
```

**Request parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|tiktok_app_id {Required}|number|TikTok App ID. <br />A TikTok App ID is a unique code that is generated in TikTok Ads Manager.  An advertiser can bind multiple TikTok App IDs to the same mobile app. To learn about how to generate a TikTok App ID, see [TikTok App ID](https://ads.tiktok.com/help/article?aid=10001060).|
|event |string|App event name. Examples: `INSTALLAPP`, `PURCHASE`. For supported app events, see the <strong>Event name for event data connection</strong> column in the tables on the [Supported event types](https://business-api.tiktok.com/portal/docs?id=1739585574120449).|
|timestamp {Required} |string|Timestamp with ISO 8601 format. See [wiki](https://en.wikipedia.org/wiki/ISO_8601).|
|context {Required}|object|Information about ad data connection in JSON format. For data fields in this object, see the <strong>Context object</strong> section below.|
|properties |object|Properties associated with the event in JSON format. For data fields in this object, see the <strong>Properties object</strong> section below.|
```
### Context object

```xtable
|Field{35%}|Data Type{10%}|Description{50%}|
|---|---|---|
|context {Required}|object|Information about ad data connection in JSON format.|
#|app {Required}|object|Details about the app to report the event.|
##|id {Required}|string|Mobile App ID. <br />Examples (bold section) are, App Store: https://apps.apple.com/us/app/angry-birds/id343200656; <br />Google Play：https://play.google.com/store/apps/details?id=com.rovio.angrybirds. <br />If it is an Android app not in Google Play store, please use the package name.|
##|name |string|Application name|
##|namespace |string|Android package name or iOS Bundle ID. Example: com.rovio.baba|
##|version |string|App version number. For example, 3.0.2.|
##|build |string|App build number. For example, 122.|
#|device |object|Data used to report device-level data to match users. idfa/idfv/advertising_id are required so long as they are obtainable. iOS：idfa/idfv; Android：advertising_id.|
##|platform |string|Operating system of user’s device; currently only accepting iOS/Android|
##|idfa |string|iOS idfa. For iOS14+, please ensure iDFA data collection follows [Apple’s policies](https://developer.apple.com/app-store/user-privacy-and-data-use/).<br>Both raw and SHA-256 IDFA are accepted, but please note that:<ul><li> For raw IDFA, all characters must be UPPERCASE. For SHA-256 IDFA, all characters must be lowercase. Examples: <ul><li> Raw IDFA: "EA7583CD-A667-48BC-B806-42ECB2B48606"</li><li> SHA-256 IDFA: "70574fa9c8f498a7b2e5c8712b1126de7b1406fd02fdc591821c5bd33092fd1c"</li></ul></li><li> Leading/trailing spaces need to be trimmed before hashing.</li></ul>|
##|idfv |string|iOS idfv|
##|gaid |string|Google Advertising ID.<br>Both raw and SHA-256 GAID are accepted, but please note that:<ul><li>   For both raw and SHA-256 GAID, all characters must be lowercase. Examples: <ul><li>     Raw GAID: "aaaaaa-2222-1111-8787-809e92233ba0"</li><li>     SHA-256 GAID: "0ebad966881e1eebffe69b3abf624631cf83ec9bf8cb3ce134c7c75c6d8ee7fc"</li></ul></li><li> Leading/trailing spaces need to be trimmed before hashing.</li></ul>|
##|att_status |string|Whether the user has authorized your mobile app to access app-related data for measuring the user or the device. This is an iOS only field. Values: <ul><li>`AUTHORIZED`: The user has authorized access to app-related data that can be used for measuring the user or the device.</li><li>`DENIED`: The user didn't agree to authorize your mobile app to access app-related data for measuring the user or the device via [Apple's AppTrackingTransparency framework](https://developer.apple.com/documentation/apptrackingtransparency). If set to this value, TikTok will not use the PII data (e.g. User email/phone number) in this app event for user-level matching.</li><li>`NOT_DETERMINED`: The user has not yet received an authorization request to authorize access to app-related data that can be used for measuring the user or the device.</li> <li>`RESTRICTED`: The authorization to access app-related data is restricted.</li><li>`NOT_APPLICABLE`: The iOS version is below 14 or the device is running Android.</li>|
#|locale |string|Examples：zh-CN、en-US. For reference, see [the IETF BCP 47 standardized code](https://tools.ietf.org/rfc/bcp/bcp47.txt).|
#|ip |string|User IP|
#|user_agent |string|User agent from the user’s device. Example: Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1|
#|user |object|The personal data of the user.|
##|external_id |string|The external user ID.<br />It must be hashed with SHA256 on the client side. Leading/Trailing spaces need to be trimmed before hashing.|
##|phone_number |string| User's phone number. <br />It must be hashed with SHA256 on the client side. <br/>Please include country code and format it based on the following rules:<br/>If the country code is 86, then do not include country code (example: 13800000000). Otherwise, include country code with + and remove any other characters (spaces, '-') between numbers (example for US: +12133734253)<br />Hash the code with SHA256. Reference documents: https://github.com/google/libphonenumber|
##|email |string|User's email.<br />It must be hashed with SHA256 on the client side. You should first lowcase all the characters and then do hash.|
#|library |object|SDK version info.|
##|name |string|Version number in string. Example: bytedance/tiktok-business-android-sdk|
##|version |string|Version numer. Example: 1.2.0|
#|ori_attribution_partner|string|If the event is attributed by Mobile Measurement Partner to another platform, use this field to specify the platform name. Example: Facebook, Google.|
#|ad|object|Ad information|
##|callback|string|Callback information to help attribute events|
##|campaign_id|number|Campaign ID|
##|ad_id|number| Ad group ID|
##|creative_id|number|Ad ID|
##|isRetargeting|boolean|Whether the user is a retargeting user|
##|attributed|boolean|Whether the event is attributed|
##|attributionType|string|Attribution type|
##|attribution_provider|string|Attributed provider|
#|location|object|User's location information|
##|country|string|Country or region|
##|city|string|City|
```

### Properties object

Properties associated with the event in JSON format. Some properties may only be associated with certain app events. See schema below.

```xtable
|Field|Data Type|Description|
|---|---|---|
|properties |object|Properties associated with the event in JSON format.|
#|contents |object[]|Related items in an app event (e.g. items purchased in a Purchase event)|
##|price |double|Price of item|
##|quantity |number|Number of item|
##|content_type |string|The type of content in the event.   <br>Enum values: `product`, `product_group`.   <ul><li> When the `content_id` in the `contents` parameter is specified as `sku_id`, set this field to `product`.  </li><li> When the `content_id` in the `contents` parameter is specified as `item_group_id`, set this field to `product_group`.  </li></ul>|
##|content_id |string|ID of the product item in your system|
##|brand|string| Brand name of the product item.|
#|currency |string|ISO 4217 code. Examples: "EUR", "USD", "JPY"|
#|value |double|Total value of the order or items sold|
#|description |string|A string description of the app event|
#|query |string|The text string that was searched for.|
```
#### Example


```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/app/track/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}' \
--data-raw \
'{
  "tiktok_app_id": "{{TIKTOK-APP-ID}}",
  "event":"AddToCart",
  "timestamp":"2023-09-17T19:49:27Z",
  "context":{
    "app":{
      "id":"1211123727",
      "name":"Angry Birds Classic",
      "namespace":"com.rovio.baba",
      "version":"3.0.2",
      "build":"122"
    },
    "device":{
      "att_status":"AUTHORIZED",
      "platform":"iOS",
      "idfa":"30255BCE-4CDA-4F62-91DC-4758FDFF8512"
    },
    "locale":"zh-CN",
    "user":{
      "email":"12346a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b",
      "phone_number":"12346a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b"
    },
    "library":{
      "name":"1.1.0-alpha.1",
      "version":"2"
    },
    "ad":{
      "callback":"E.C.P.ExampleCallback",
      "isRetargeting":false,
      "campaign_id":"1700817380525057",
      "attributed":true,
      "ad_id":"1700817380806706",
      "attributionType":"click_through",
      "creative_id":"1700817378256945",
      "attribution_provider":"mmp"
    }
  },
  "properties":{
    "currency":"USD",
    "value":10,
    "contents": [
      {
        "quantity":5,
        "price":9.9
      }
    ]
  }
}'
(/code)
```

### Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|message |string|The return message. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|request_id |string|The unique ID of the request. |
|data|object|Returned data|
```

#### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "request_id": "2019091810032301011023224213558"
}
(/code);
```

## Report App Events in Bulk

Use this endpoint to report app events in bulk to reduce traffic to the Marketing API. You can report up to 1000 events in one request. If a request contains more than 1000 events, the entire request will be rejected.

### Request

**Address** 

https://business-api.tiktok.com/open_api/v1.3/app/batch/

**Method** POST

**Header**

```xtable
|Field|Data Type|Desciption|
|---|---|---|
|Access-Token {Required}|string|Authorized Access Token. It can be obtained through the <br>["Get Access token"](https://ads.tiktok.com/marketing_api/docs?id=100681) interface. |
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|tiktok_app_id {Required}|number|TikTok App ID. <br />TikTok App is created in event manager and linked to mobile app, which also makes it linked to the user as an asset. An advertiser can bind multiple TikTok App to a same mobile app. The ID can be generated by referring to [this article](https://ads.tiktok.com/help/article?aid=10001060).|
|batch |object[]|An array of app events reported in batch. Length must be greater than 1 and smaller than 50.|
#|type |string|App event type. Currently only support "track".|
#|event |string|App event name. Examples: `INSTALLAPP`, `PURCHASE`. For supported app events, see the <strong>Event name for event data connection</strong> column in the tables on the [Supported event types](https://ads.tiktok.com/marketing_api/docs?id=1713404170195969).|
#|timestamp |string|Timestamp with ISO 8601 format. See [wiki](https://en.wikipedia.org/wiki/ISO_8601).|
#|context {Required}|object|Information about ad data connection in JSON format. For data fields in this object, see the <strong>Context object</strong> section above.|
#|properties |object|Properties associated with the event in JSON format. For data fields in this object, see the <strong>Properties object</strong> section above.|
```

#### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/app/batch/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}' \
--data-raw \
'{
  "tiktok_app_id":"{{TIKTOK-APP-ID}}",
  "batch":[
    {
      "type":"track",
      "event":"InstallApp",
      "timestamp":"2023-09-17T19:49:27Z",
      "context":{
        "app":{
          "id":"com.rovio.angrybirds"
        },
        "user":{
          "email":"12346a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b"
        },
        "device":{
          "att_status":"AUTHORIZED",
          "platform":"iOS",
          "idfa":"30255BCE-4CDA-4F62-91DC-4758FDFF8512"
        },
        "ad":{
          "callback":"E.C.P.ExampleCallback"
        }
      }
    },
    {
      "type":"track",
      "event":"Purchase",
      "timestamp":"2023-09-17T20:00:00Z",
      "context":{
        "app":{
          "id":"com.rovio.angrybirds"
        },
        "user":{
          "email":"12346a405862e402eb76a70f8a26fc732d07c32931e9fae9ab1582911d2e8a3b"
        },
        "device":{
          "att_status":"AUTHORIZED",
          "platform":"iOS",
          "idfa":"30255BCE-4CDA-4F62-91DC-4758FDFF8512"
        },
        "ad":{
          "callback":"E.C.P.ExampleCallback"
        }
      }
    }
  ]
}'
(/code)
```

### Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|message |string|The return message. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|request_id |string|The unique ID of the request. |
|data|object|Returned data|
#|partial_failure|boolean|Whether the event reporting failed partially.|
#|failed_events|object[]|List of errors in the case of partial failure|
##|order_in_batch|number|Order in batch request|
##|error|string|Error message|
```

#### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "request_id": "2019091810032301011023224213558"
}
(/code)

(code Failure http)
{
    "code": 40000,
    "message": "The value:{'aa'} is invalid for the associated parameter email.",
    "request_id": "2021012904054301023125302028613"
}
(/code)

(code Partial-Failure http)
{
    "code": 20001,
    "message": "Partial success. Correct entries are forwarded while incorrect ones are dropped.",
    "request_id": "2021012904061701023125302030787",
    "data": {
        "partial_failure": true,
        "failed_events": [
            {
                "order_in_batch": 0,
                "error": "unhashed email"
            }
        ]
    }
}
(/code)
```

