---
title: "Create a pixel"
doc_id: 1740858779758593
path: "API Reference / Events 1.0 / Pixel Events / Create a pixel"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a pixel

Use this endpoint to create a Pixel and install it on your website. Please refer to [Installing Pixel](https://ads.tiktok.com/help/article?aid=717665906670358459) for help.
> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

Preconditions：
- You must own a business website.
- You must have permission to edit the website's code.

Note：

* It is recommended that the pixel name corresponds with your website or domain name. The length cannot exceed 40 characters and should not be duplicated.
* We recommended using one pixel per website.


## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/pixel/create/| /v1.3/pixel/create/|
|Request parameter data type|`advertiser_id`: number |`advertiser_id`: string |
|Response parameter data type|`pixel_id`: number |`pixel_id`: string |
|New response parameters|/ | `pixel_category`<br>`automatic_advanced_matching_fields`<br>`enable_first_party_cookies`<br>`enable_expanded_data_sharing`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/pixel/create/

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type. <br> Allowed values: `"application/json"`  |
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|pixel_name {Required}|string| Pixel name. <br><br>Length limit: 40 characters and cannot contain emojis.<br><br><p><span style="color:darkred"><b>Note</b></span>: Duplicate pixel names are not supported.</p>|
|pixel_category {-to be deprecated}|string| Pixel data connection event category.<br><br><p><span style="color:darkred"><b>Note</b></span>: This field no longer determines the pixel events that the Pixel can measure, even if it is passed. We recommend not including this field for new Pixels. Note that this field will be deprecated in the next API version.|
```

### Example
```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "pixel_name": "PIXEL_NAME"
}' \
https://business-api.tiktok.com/open_api/v1.3/pixel/create/
(/code)
```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string| Unique ID of the request|
|data |object|Return data   |
#|pixel_code |string| Pixel Code, the ID you see on the event manager's platform.|
#|pixel_id |string| Pixel ID. |
#|pixel_name|string| Pixel name. |
#| pixel_category{-to be deprecated} | string | The Pixel data connection event category for the Pixel. <br><br> Enum values: <ul><li>`ONLINE_STORE`: E-commerce events. </li><li> `FILLING_FORM`: Form. </li><li>`CONTACTS`: Consultation events. </li><li>`LANDING_PAGE`: APK download events.</li><li>`CUSTOMIZE_EVENTS`: Custom events.</li></ul><br>If this field is not passed in the request, this field will default to `CUSTOMIZE_EVENTS`.<br><br><p><span style="color:darkred"><b>Note</b></span>: This field no longer determines the pixel events that the Pixel can measure and will be deprecated in the next API version.|
#|partner_name|string| Name of the merchant partner.|
#|advanced_matching_fields|object|Manual Advanced Matching settings for the pixel.|
##|phone_number|boolean|Whether phone number is enabled for Manual Advanced Matching. <br><br>Default value: `true`.|
##|email|boolean|Whether email is enabled for Manual Advanced Matching. <br><br>Default value: `true`.|
#|automatic_advanced_matching_fields|object|Automatic Advanced Matching settings for the pixel.<br><br>Automatic Advanced Matching automatically uses customer information collected through your TikTok Pixel, like email, name, and phone number, to improve match rates, conversion tracking, and retargeting.|
##|phone_number|boolean|Whether phone number is enabled for Automatic Advanced Matching. <br><br>Default value: `false`.|
##|email|boolean|Whether email is enabled for Automatic Advanced Matching. <br><br>Default value: `false`.|
##|name|boolean|Whether name is enabled for Automatic Advanced Matching. <br><br>Default value: `false`.|
##|address|boolean|Whether address is enabled for Automatic Advanced Matching. <br><br>Default value: `false`.|
##|external_id|boolean|Whether external ID is enabled for Automatic Advanced Matching. <br><br>Default value: `false`.|
#|enable_first_party_cookies|boolean|Whether to allow first-party cookies.<br><br>Allowing first-party cookies will allow the system to share data from your website's first-party cookies with TikTok to improve measurement and help reach more users. [Learn more about using cookies with TikTok Pixel](https://ads.tiktok.com/help/article/using-cookies-with-tiktok-pixel).<br><br>Default value: `true`.|
#|enable_expanded_data_sharing|boolean|Whether to enable expanded data sharing.<br><br>Enabling expanded data sharing allows this pixel to collect more information from your website, including page content (such as meta tags, and product details), visitor actions (such as clicks, button interactions, and time spent), and website performance (such as page load speed and responsiveness). This data may be used to improve ad delivery and campaign performance on TikTok. You can view all collected data in TikTok Pixel Helper. [Learn more about Expanded Data Sharing with the TikTok Pixel](https://ads.tiktok.com/help/article/enhance-data-postback-with-the-tiktok-pixel).<br><br>Default value: `true`.|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "advanced_matching_fields": {
            "email": true,
            "phone_number": true
        },
        "automatic_advanced_matching_fields": {
            "address": false,
            "email": false,
            "external_id": false,
            "name": false,
            "phone_number": false
        },
        "enable_expanded_data_sharing": true,
        "enable_first_party_cookies": true,
        "pixel_category": "CUSTOMIZE_EVENTS",
        "pixel_code": "{{pixel_code}}",
        "pixel_id": "{{pixel_id}}",
        "pixel_name": "{{pixel_name}}"
    }
}
(/code);
```
