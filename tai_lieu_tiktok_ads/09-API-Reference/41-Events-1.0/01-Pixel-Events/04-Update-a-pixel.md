---
title: "Update a pixel"
doc_id: 1740858799524865
path: "API Reference / Events 1.0 / Pixel Events / Update a pixel"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a pixel

Use this endpoint to update the Pixel name. Pixel name should correspond with your website/domain name, with length not exceeding 40 characters. Pixel name should not be duplicated. 
> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/pixel/update/| /v1.3/pixel/update/|
|Request parameter data type|`advertiser_id`: number |`advertiser_id`: string |
|New request parameters|/|`automatic_advanced_matching_fields`<br>`enable_first_party_cookies`<br>`enable_expanded_data_sharing`|
|Response parameter data type|`pixel_id`: number |`pixel_id`: string |
```



## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/pixel/update/

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"` |
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID.  |
|pixel_name {Required}|string| Pixel name.<br><br>Length limit: 40 characters and cannot contain emojis.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you want to keep the name unchanged, set this field to an empty string (<code>""</code>).</p>|
|pixel_id {Required}|string| Pixel ID. |
|advanced_matching_fields|object| Manual Advanced Matching settings for the pixel.<br><br>This setting is only applicable to pixels generated through partner integrations, and does not apply to manually configured pixels.|
#|phone_number {+Conditional}|boolean|Required when `advanced_matching_fields` is specified.<br><br>Whether phone number is enabled for Manual Advanced Matching. <br><br>Default value: `true`.|
#|email  {+Conditional}|boolean| Required when `advanced_matching_fields` is specified.<br><br>Whether email is enabled for Manual Advanced Matching. <br><br>Default value: `true`.|
|automatic_advanced_matching_fields|object|Automatic Advanced Matching settings for the pixel.<br><br>Automatic Advanced Matching automatically uses customer information collected through your TikTok Pixel, like email, name, and phone number, to improve match rates, conversion tracking, and retargeting.<br><br><p><span style="color:darkred"><b>Note</b></span>: To help protect customer privacy, sensitive information will be hashed before it's shared with TikTok. If you operate within a more regulated or sensitive industry like financial services or health care, consider using Manual Automatic Matching (MAM) instead of Automatic Advanced Matching (AAM).</p>|
#|phone_number {+Conditional}|boolean|Required when `automatic_advanced_matching_fields` is specified.<br><br>Whether phone number is enabled for Automatic Advanced Matching. <br><br>Supported values: `true`, `false`.|
#|email {+Conditional}|boolean|Required when `automatic_advanced_matching_fields` is specified.<br><br>Whether email is enabled for Automatic Advanced Matching. <br><br>Supported values: `true`, `false`.|
#|name {+Conditional}|boolean|Required when `automatic_advanced_matching_fields` is specified.<br><br>Whether name is enabled for Automatic Advanced Matching. <br><br>Supported values: `true`, `false`.|
#|address {+Conditional}|boolean|Required when `automatic_advanced_matching_fields` is specified.<br><br>Whether address is enabled for Automatic Advanced Matching. <br><br>The address includes basic location details of a user, including city, state, zip code, and country. Street-level information is not collected. Sharing this helps TikTok match users more accurately.<br><br>Supported values: `true`, `false`.|
#|external_id {+Conditional}|boolean|Required when `automatic_advanced_matching_fields` is specified.<br><br>Whether external ID is enabled for Automatic Advanced Matching. <br><br>The external ID is the identification that represents a user on the advertiser's platform, like a customer ID, order ID or loyalty card number. Sharing it helps TikTok match users more accurately.<br><br>Supported values: `true`, `false`.|
|enable_first_party_cookies|boolean|Whether to allow first-party cookies.<br><br>Allowing first-party cookies will allow the system to share data from your website's first-party cookies with TikTok to improve measurement and help reach more users. [Learn more about using cookies with TikTok Pixel](https://ads.tiktok.com/help/article/using-cookies-with-tiktok-pixel).<br><br>Supported values: `true`, `false`.|
|enable_expanded_data_sharing|boolean|Whether to enable expanded data sharing.<br><br>Enabling expanded data sharing allows this pixel to collect more information from your website, including page content (such as meta tags, and product details), visitor actions (such as clicks, button interactions, and time spent), and website performance (such as page load speed and responsiveness). This data may be used to improve ad delivery and campaign performance on TikTok. You can view all collected data in TikTok Pixel Helper. [Learn more about Expanded Data Sharing with the TikTok Pixel](https://ads.tiktok.com/help/article/enhance-data-postback-with-the-tiktok-pixel).<br><br>Supported values: `true`, `false`.|
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "pixel_name": "PIXEL_NAME", 
    "pixel_id": "PIXEL_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/pixel/update/
(/code)

```

## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see[Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|message |string|Return message, see[Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
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
    "request_id": "202003181255150101890492232F45E5EA"
}
(/code);
```

