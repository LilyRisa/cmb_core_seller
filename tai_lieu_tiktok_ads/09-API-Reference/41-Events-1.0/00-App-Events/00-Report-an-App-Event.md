---
title: "Report an App Event"
doc_id: 1740859196043266
path: "API Reference / Events 1.0 / App Events / Report an App Event"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Report an App Event

Use this endpoint to report a mobile (i.e. Android/iOS) app event (e.g. Launch APP, Purchase) to TikTok.
> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/app/track/| /v1.3/app/track/|
|Request parameter data type|`tiktok_app_id`: number <br>`campaign_id` (in Context object): number <br>`ad_id` (in Context object): number <br>`creative_id` (in Context object): number |`tiktok_app_id`: string <br>`campaign_id` (in Context object): string<br>`ad_id` (in Context object): string<br>`creative_id` (in Context object): string |
```


## Request

**Endpoint** 
https://business-api.tiktok.com/open_api/v1.3/app/track/

**Method** POST

**Header**

```xtable
|Field|Data Type|Desciption|
|---|---|---|
|Access-Token {Required}|string|Authorized Access Token. It can be obtained through the <br>["Get Access token"](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162) interface. |
|Content-Type {Required}|string|Request message type.<br>Allowed format: `"application/json"`.|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|tiktok_app_id {Required}|string|TikTok App ID. <br />A TikTok App ID is a unique code that is generated in TikTok Ads Manager. An advertiser can bind multiple TikTok App IDs to the same mobile app. To learn about how to generate a TikTok App ID, see [TikTok App ID](https://ads.tiktok.com/help/article?aid=10001060).|
|event |string|App event name. Examples: `INSTALLAPP`, `PURCHASE`. For supported app events, see the <strong>Event name for event data connection</strong> column in the tables on the [Supported event types](https://ads.tiktok.com/marketing_api/docs?id=1739585574120449).|
|timestamp |string|Timestamp with ISO 8601 format. See [wiki](https://en.wikipedia.org/wiki/ISO_8601).|
|context {Required}|object|Information about ad data connection in JSON format. For data fields in this object, see the <strong>Context object</strong> section below.|
|properties |object|Properties associated with the event in JSON format. For data fields in this object, see the <strong>Properties object</strong> section.|
```
### Context object

```xtable
|Field{35%}|Data Type{10%}|Description{50%}|
|---|---|---|
|context {Required}|object|Information about ad data connection in JSON format.|
#|app {Required}|object|Details about the app to report the event.|
##|id {Required}|string|Mobile App ID. <br />Examples (bold section) are, App Store: https://apps.apple.com/us/app/angry-birds/id343200656; <br />Google Play：https://play.google.com/store/apps/details?id=com.rovio.angrybirds. <br />If it is an Android app not in Google Play store, please use the package name.|
##|name |string|Application name.|
##|namespace |string|Android package name or iOS Bundle ID. Example: com.rovio.baba.|
##|version |string|App version number. For example, 3.0.2.|
##|build |string|App build number. For example, 122.|
#|device |object|Data used to report device-level data to match users. idfa/idfv/advertising_id are required so long as they are obtainable. iOS：idfa/idfv; Android：advertising_id.|
##|platform |string|Operating system of user’s device; currently only accepting iOS/Android.|
##|idfa |string|iOS idfa. For iOS14+, please ensure iDFA data collection follows [Apple’s policies](https://developer.apple.com/app-store/user-privacy-and-data-use/).<br>Both raw and SHA-256 IDFA are accepted, but please note that:<ul><li> For raw IDFA, all characters must be UPPERCASE. For SHA-256 IDFA, all characters must be lowercase. Examples: <ul><li> Raw IDFA: "EA7583CD-A667-48BC-B806-42ECB2B48606"</li><li> SHA-256 IDFA: "70574fa9c8f498a7b2e5c8712b1126de7b1406fd02fdc591821c5bd33092fd1c"</li></ul></li><li> Leading/trailing spaces need to be trimmed before hashing.</li></ul>|
##|idfv |string|iOS idfv.|
##|gaid |string|Google Advertising ID.<br>Both raw and SHA-256 GAID are accepted, but please note that:<ul><li>   For both raw and SHA-256 GAID, all characters must be lowercase. Examples: <ul><li>     Raw GAID: "aaaaaa-2222-1111-8787-809e92233ba0"</li><li>     SHA-256 GAID: "0ebad966881e1eebffe69b3abf624631cf83ec9bf8cb3ce134c7c75c6d8ee7fc"</li></ul></li><li> Leading/trailing spaces need to be trimmed before hashing.</li></ul>|
##|att_status |string|Whether the user has authorized your mobile app to access app-related data for measuring the user or the device. This is an iOS only field. Values: <ul><li>`AUTHORIZED`: The user has authorized access to app-related data that can be used for measuring the user or the device.</li><li>`DENIED`: The user didn't agree to authorize your mobile app to access app-related data for measuring the user or the device via [Apple's AppTrackingTransparency framework](https://developer.apple.com/documentation/apptrackingtransparency). If set to this value, TikTok will not use the PII data (e.g. User email/phone number) in this app event for user-level matching.</li><li>`NOT_DETERMINED`: The user has not yet received an authorization request to authorize access to app-related data that can be used for measuring the user or the device.</li> <li>`RESTRICTED`: The authorization to access app-related data is restricted.</li><li>`NOT_APPLICABLE`: The iOS version is below 14 or the device is running Android.</li>|
#|locale |string|Examples：zh-CN、en-US. For reference, see [the IETF BCP 47 standardized code](https://tools.ietf.org/rfc/bcp/bcp47.txt).|
#|ip |string|User IP.|
#|user_agent |string|User agent from the user’s device. Example: Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1.|
#|user |object|The personal data of the user.|
##|external_id |string|The external user ID.<br />It must be hashed with SHA256 on the client side. Leading/Trailing spaces need to be trimmed before hashing.|
##|phone_number |string| User's phone number. <br />It must be hashed with SHA256 on the client side. <br/>Please include country code and format it based on the following rules:<br/>If the country code is 86, then do not include country code (example: 13800000000). Otherwise, include country code with + and remove any other characters (spaces, '-') between numbers (example for US: +12133734253)<br />Hash the code with SHA256. Reference documents: https://github.com/google/libphonenumber.|
##|email |string|User's email.<br />It must be hashed with SHA256 on the client side. You should first lowcase all the characters and then do hash.|
#|library |object|SDK version info.|
##|name |string|Version number in string. Example: bytedance/tiktok-business-android-sdk.|
##|version |string|Version numer. Example: 1.2.0.|
#|ori_attribution_partner|string|If the event is attributed by Mobile Measurement Partner to another platform, use this field to specify the platform name. Example: Facebook, Google.|
#|ad|object|Ad information|
##|callback|string|Callback information to help attribute events.|
##|campaign_id|string|Campaign ID.|
##|ad_id|string| Ad group ID.|
##|creative_id|string|Ad ID.|
##|isRetargeting|boolean|Whether the user is a retargeting user.|
##|attributed|boolean|Whether the event is attributed.|
##|attributionType|string|Attribution type.|
##|attribution_provider|string|Attributed provider.|
#|location|object|User's location information.|
##|country|string|Country or region.|
##|city|string|City.|
```

### Properties object

Properties associated with the event in JSON format. Some properties may only be associated with certain app events. See schema below.

```xtable
|Field|Data Type|Description|
|---|---|---|
|properties |object|Properties associated with the event in JSON format.|
#|contents |object[]|Related items in an app event (e.g. items purchased in a Purchase event).|
##|price |double|Price of item.|
##|quantity |number|Number of item.|
##|content_type |string|Type of the product item. This can be a description of the product item (e.g. being purchased).|
##|content_id |string|ID of the product item in your system.|
##|brand|string| Brand name of the product item.|
#|currency |string|ISO 4217 code. Examples: "EUR", "USD", "JPY".|
#|value |double|Total value of the order or items sold.|
#|description |string|A string description of the app event.|
#|query |string|The text string that was searched for.|
```
### Example

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

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The return message. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|request_id |string|The unique ID of the request. |
|data|object|Returned data.|
```

### Example

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
