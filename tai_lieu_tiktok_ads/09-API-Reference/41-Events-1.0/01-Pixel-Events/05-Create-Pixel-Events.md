---
title: "Create Pixel Events"
doc_id: 1740858807646209
path: "API Reference / Events 1.0 / Pixel Events / Create Pixel Events"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Pixel Events

Use this endpoint to create Pixel Events. Pixel events help you measure customer actions that happen on your website. Use **Events Manager** to add events relevant to your business and learn more about your website visitor's experience.

After creating a pixel, you can choose either an industry or custom template to define your [events](https://ads.tiktok.com/help/article?aid=573722398920296903).  An event is an action a user does on your site, like adding an item to a shopping cart. User actions can come from a TikTok ad (paid), or can come organically (unpaid). 

> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/pixel/event/create/| /v1.3/pixel/event/create/|
|Request parameter name|`name` |`event_name` |
|Request parameter data type|`advertiser_id`: number <br>`event_id`: number <br>`pixel_id`: number |`advertiser_id`: string <br>`event_id`: string <br>`pixel_id`: string |
```


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/pixel/event/create/

**Method** POST

**Header**
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`|
```

**Parameters**
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|pixel_events {Required}|object[]| Pixel Event list. |
#|currency_value |string| Event conversion value, when combined with the `currency` field represents the business value of an event. <br><br> For example, if `USD` is the specified `currency` and `currency_value` is `5.5`, it means that a conversion event generates 5.5 USD for the business.|
#|currency |string| Conversion value representing the currency.<br><br> Enum values: `INR` (Indian Rupee),` JPY` (Japanese Yen), `USD` (US Dollar). <br>Default value: `USD`. |
#|event_name |string| Event name.<br><br>The name cannot exceed 40 characters (for all languages) and cannot contain emoji. <br><br>Default value: `""`.|
#|event_type {Required}|string| Pixel event type. <br><br>For supported event types, see the "<b>Event name for ad creation (`optimization_event`)</b>" column in the "[Supported Pixel events - Pixel events](https://ads.tiktok.com/marketing_api/docs?id=1739585696931842)" section. |
#|event_code |string| Event code.|
#|event_id |string| Event ID.|
#|statistic_type|string| Event statistics type. <br><br> Enum values: `EVERY_TIME` (every time),` ONCE` (only once). <br>Default value: `EVERY_TIME`.<br><br>If a user generates two conversions through the same ad, select `EVERY_TIME` to get a statistic value of 2, and select` ONCE` to get a statistic value of 1. |
#|rules |object[]| Event data connection rules.<br><br> For the corresponding field rules and definitions, see the section "<a href="#item-link-Event rule type">Event rule type</a>".|
##|trigger {Required}|string|Event trigger type.<br><br> Enum values:  `TRIGGERTYPE_CLICK`,`TRIGGERTYPE_PAGEVIEW`. |
##|operator {Required}|string| Operator.<br><br> Enum values:  `OPERATORTYPE_CONTAINS`,`OPERATORTYPE_DOES_NOT_EQUAL`,`OPERATORTYPE_EQUALS`.|
##|value {Required}|string| Variable value |
##|variable {Required}|string| Variable type. <br><br> Enum values:  `ELEMENT`,`PAGE_HOSTNAME`,`PAGE_PATH`,`PAGE_URL`.|
|pixel_id {Required}|string| Pixel ID. |
```

### Event rule type

For each Pixel event, you can specify the event rules. Event rules are conditions that when met, can be regarded as an event. There are two types of rules: `Element click` and `Web browsing`. Each rule has different trigger conditions.

```xtable
|Trigger (rule) Type {28%}|Optional operator{26%} |Optional variable {23%}| Value{23%}|
|---|---|---|---|
|`TRIGGERTYPE_CLICK` <br> (Element click)|`OPERATORTYPE_EQUALS` (Equals)|`ELEMENT` (Element CSS selector)| Fill in the value of the CSS selector, such as `"#my-element"`|
|`TRIGGERTYPE_PAGEVIEW`<br>(Web browsing)|`OPERATORTYPE_CONTAINS`(Contains), <br>`OPERATORTYPE_EQUALS` (Equals), <br>`OPERATORTYPE_DOES_NOT_EQUAL` (Not equal to)|`PAGE_HOSTNAME` (domain name), <br>`PAGE_PATH` (path),<br> `PAGE_URL` (URL)| The value of domain name / path / URL, such as `"tiktok.com"` (domain name) or `"/a/b"` (path)|
```

For example：
1. When the parameters are set as trigger=`"TRIGGERTYPE_CLICK"`, operator=`"OPERATORTYPE_EQUALS"`, variable=`"ELEMENT"`, value=`"#my-element"`, this represents a click event of the element with the ID `my-element` on the data connection page.
2. When the parameters are set as trigger=`"TRIGGERTYPE_PAGEVIEW"`, operator=`"OPERATORTYPE_CONTAINS"`,variable=`"PAGE_PATH"`, value=`"/a/b"`, this represents a page view with an `OPERATORTYPE_CONTAINS` `/a/b` in the `PAGE_PATH`.


### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "pixel_events": [
        {
            "event_type": "EVENT_TYPE", 
            "event_id": "EVENT_ID", 
            "currency": "CURRENCY", 
            "statistic_type": "STATISTIC_TYPE", 
            "rules": [
                {
                    "operator": "OPERATOR", 
                    "variable": "VARIABLE", 
                    "trigger": "TRIGGER", 
                    "value": "VALUE"
                }
            ], 
            "event_code": "EVENT_CODE", 
            "currency_value": "CURRENCY_VALUE", 
            "name": "NAME"
        }
    ], 
    "pixel_id": "PIXEL_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/pixel/event/create/
(/code)
```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|message |string|Return message, see [Appendix-Return Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097) |
|data |object|Return data. |
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {},
    "request_id": "202003181250360101890560280F44C7EF"
}
(/code);
```

