---
title: "Create a Custom Conversion"
doc_id: 1842225226006530
path: "API Reference / Custom Conversions / Create a Custom Conversion"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Custom Conversion

Use this endpoint to create a Custom Conversion for a Pixel or an App.

> <span style="color:DodgerBlue">**Note**</span><br>
> - You can create a maximum of 1,000 Custom Conversions within an ad account. 
> - For a Pixel or an App, you can create a maximum of 100 Custom Conversions.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/custom_conversion/create/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|advertiser_id {Required}|string|Advertiser ID.|
|name {Required}|string|The name of the Custom Conversion.<br>The name must start with a letter and can include letters, numbers, spaces, underscores, or hyphens.<br><br>Length limit: 50 characters and cannot contain emoji.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Do not specify the name of a standard event.</li><li>Duplicate names for Custom Conversion within an ad account are not allowed.</li></ul> |
|description|string|The description of the Custom Conversion.<br><br>Length limit: 500 characters and cannot contain emoji.|
|event_source_type {Required}|string|Event source type.<br><br>Enum values:<ul><li>`PIXEL`: Pixel.</li><li>`APP`: App.</li></ul>|
|event_source_id {Required}|string|Event source ID.<br><br><ul><li>When `event_source_type` is `PIXEL`, specify a Pixel ID.<ul><li>To obtain IDs of Pixels within your ad account, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978) and check the returned `pixel_id`.</li></ul></li><li>When `event_source_type` is `APP`, specify the app platform ID of a Self Attribution Network (SAN) App.<ul><li>This app can only be used with the TikTok App Events SDK. The SDK creates a direct first-party connection from your app to TikTok, reducing signal loss and providing more accurate measurement. Learn [how to install the SDK](https://business-api.tiktok.com/portal/docs?id=1739584951798785).</li><li>To obtain app platform IDs of SAN Apps within your ad account, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786). Confirm that the returned `self_attribution_enabled` for the App is `true` and check the returned `app_platform_id`.</li></ul></li></ul>|
|optimization_event{+Conditional}|string|<ul><li>Required in any of the following scenarios:<ul><li>When `event_source_type` is `PIXEL`.</li><li>When `event_source_type` is `APP` and you are creating a custom conversion using a standard event.</li><li>When `event_source_type` is `APP` and you are creating a custom conversion using a Custom Event, and the app is allowlisted for mapping Custom Events to Standard Events.</li></ul></li><li>Not supported when the following conditions are all met:<ul><li>`event_source_type` is `APP`.</li><li>You are creating a custom conversion using a Custom Event.</li><li>The app is not allowlisted for mapping Custom Events to Standard Events.</li></ul></li></ul><br>The Standard Event to associate with the Custom Conversion.<br><br>For enum values, see <a href="#item-link-List of values for optimization_event">List of values for `optimization_event`</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: Mapping Custom Events to Standard Events while creating custom conversions using an app event source and a custom event is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p>|
|custom_event_type|string|Valid only when the Custom Event has been configured for the App or Pixel (`event_source_id`).<br><br>The Custom Event to associate with the Custom Conversion.<br>When you specify a Custom Event for the Custom Conversion, you need to specify the Standard Event to map the event to through `optimization_event` simultaneously.<br>Custom Events are actions that TikTok partners can define themselves beyond the predefined Standard Events list. To learn more about Custom Events, see [About Custom Events in TikTok Ads Manager](https://ads.tiktok.com/help/article/custom-events).<br><br>To obtain the Custom Events that are configured for a Pixel, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978) and check the returned `custom_event_type`.<br><br><p><span style="color:darkred"><b>Note</b></span>: When creating custom conversions using an app event source and a custom event, ensure that postbacks of the specified App Custom Event (`custom_event_type`) have been received before this custom conversion can be created. Otherwise, an error will occur.</p>|
|rules {Required}|object[]|Details of the conversion rules for the Custom Conversion.<br><br>Only actions that meet these conditions will count as Custom Conversions.<br><br>Size range: 1-10.|
#|parameter {Required}|string|Parameter.<br><br>Enum values:<ul><li>`BRAND`: `brand`.</li><li>`CONTENT_CATEGORY`: `content_category`.</li><li>`CONTENT_ID`: `content_id`.</li><li>`CONTENT_NAME`: `content_name`.</li><li>`CONTENT_TYPE`: `content_type`.</li><li>`CURRENCY`: `currency`.</li><li>`DESCRIPTION`: `description`.</li><li>`EVENT_ID`: `event_id`.</li><li>`NUM_ITEMS`: `num_items`.</li><li>`PRICE`: `price`.</li><li>`SEARCH_STRING`: `search_string`.</li><li>`STATUS`: `status`.</li><li>`URL`: `url`.</li><li>`VALUE`: `value`.</li></ul>|
#|operator {Required}|string|Operator.<br><br><ul><li>When `parameter` is `BRAND`, `CONTENT_CATEGORY`, `CONTENT_ID`, `CONTENT_NAME`, `CONTENT_TYPE`, `DESCRIPTION`, `EVENT_ID`, `SEARCH_STRING`, `STATUS`, or `URL`, the enum values are:<ul><li>`CONTAIN`: Contains.</li><li>`NOT_CONTAIN`: Does not contain.</li><li>`START_WITH`: Starts with.</li><li>`EQUAL`: Is equal to.</li><li>`NOT_EQUAL`: Is not equal to.</li></ul></li><li>When `parameter` is `NUM_ITEMS`, `PRICE`, or `VALUE`, the enum values are:<ul><li>`EQUAL`: Is equal to.</li><li>`NOT_EQUAL`: Is not equal to.</li><li>`LT`: Is less than.</li><li>`GT`: Is greater than.</li><li>`LTE`: Is less than or equal to.</li><li>`GTE`: Is greater than or equal to.</li></ul></li><li>When `parameter` is `CURRENCY`, the enum values are:<ul><li>`EQUAL`: Is equal to.</li><li>`NOT_EQUAL`: Is not equal to.</li></ul></li></ul>|
#|values {Required}|string[]|Values for the parameter.<br><br><ul><li>When `parameter` is `BRAND`, `CONTENT_CATEGORY`, `CONTENT_ID`, `CONTENT_NAME`, `CONTENT_TYPE`, `DESCRIPTION`, `EVENT_ID`, `SEARCH_STRING`, `STATUS`, `URL`, the field will be an array of strings, each with a maximum length of 100 characters and up to 100 entries.</li><li>When `parameter` is `NUM_ITEMS`, the field will be an integer between 0 and 1,000,000,000.</li><li>When `parameter` is `PRICE`, `VALUE`, the field will be a float kept to two decimal places, within the range from 0.00 to 1,000,000,000.00.</li><li>When `parameter` is `CURRENCY`, the field will be an array of strings. To learn about the enum values, see <a href="#item-link-List of currency values">List of currency values</a>.|
```

### List of values for `optimization_event`
The following table lists of the enum values for `optimization_event` for different event source types.

<table>
    <thead>
        <tr>
            <th style="width:20%"><code>event_source_type</code> value</th>
            <th style="width:30%">Available conversion event name<br></th>
            <th style="width:50%">Corresponding <code>optimization_event</code> value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="20"><code>PIXEL</code><br></td>
            <td>Add Payment Info</td>
            <td><code>ADD_BILLING</code></td>
        </tr>
        <tr>
            <td>Add to Cart</td>
            <td><code>ON_WEB_CART</code></td>
        </tr>
        <tr>
            <td>Add to Wishlist</td>
            <td><code>ON_WEB_ADD_TO_WISHLIST</code></td>
        </tr>
        <tr>
            <td>Application Approval</td>
            <td><code>APPLICATION_APPROVAL</code></td>
        </tr>
        <tr>
            <td>Click Button</td>
            <td><code>BUTTON</code></td>
        </tr>
        <tr>
            <td>Complete Registration</td>
            <td><code>ON_WEB_REGISTER</code></td>
        </tr>
        <tr>
            <td>Contact</td>
            <td><code>CONSULT</code></td>
        </tr>
        <tr>
            <td>Customize Product</td>
            <td><code>CUSTOMIZE_PRODUCT</code></td>
        </tr>
        <tr>
            <td>Download</td>
            <td><code>DOWNLOAD_START</code></td>
        </tr>
        <tr>
            <td>Find Location</td>
            <td><code>FIND_LOCATION</code></td>
        </tr>
        <tr>
            <td>Initiate Checkout</td>
            <td><code>INITIATE_ORDER</code></td>
        </tr>
        <tr>
            <td>Page View</td>
            <td><code>PAGE_VIEW</code></td>
        </tr>
        <tr>
            <td>Place an Order</td>
            <td><code>ON_WEB_ORDER</code></td>
        </tr>
        <tr>
            <td>Purchase (formerly “Complete Payment”)</td>
            <td><code>SHOPPING</code></td>
        </tr>
        <tr>
            <td>Schedule</td>
            <td><code>SCHEDULE</code></td>
        </tr>
        <tr>
            <td>Search</td>
            <td><code>ON_WEB_SEARCH</code></td>
        </tr>
        <tr>
            <td>Start Trial</td>
            <td><code>START_TRIAL</code></td>
        </tr>
        <tr>
            <td>Submit Application</td>
            <td><code>SUBMIT_APPLICATION</code></td>
        </tr>
        <tr>
            <td>Subscribe</td>
            <td><code>ON_WEB_SUBSCRIBE</code></td>
        </tr>
        <tr>
            <td>View Content</td>
            <td><code>ON_WEB_DETAIL</code></td>
        </tr>
        <tr>
            <td rowspan="29"><code>APP</code></td>
            <td>Day 1 Retention</td>
            <td><code>NEXT_DAY_OPEN</code></td>
        </tr>
        <tr>
            <td>Achieve Level</td>
            <td><code>UPDATE_LEVEL</code></td>
        </tr>
        <tr>
            <td>Add Payment Info</td>
            <td><code>ADD_PAYMENT_INFO</code></td>
        </tr>
        <tr>
            <td>Add to Cart</td>
            <td><code>IN_APP_CART</code></td>
        </tr>
        <tr>
            <td>Add to Wishlist</td>
            <td><code>ADD_TO_WISHLIST</code></td>
        </tr>
        <tr>
            <td>Checkout</td>
            <td><code>IN_APP_ORDER</code></td>
        </tr>
        <tr>
            <td>Complete Tutorial</td>
            <td><code>COMPLETE_TUTORIAL</code></td>
        </tr>
        <tr>
            <td>Create Group</td>
            <td><code>CREATE_GROUP</code></td>
        </tr>
        <tr>
            <td>Create Role</td>
            <td><code>CREATE_GAMEROLE</code></td>
        </tr>
        <tr>
            <td>Generate Lead</td>
            <td><code>SALES_LEAD</code></td>
        </tr>
        <tr>
            <td>Ad Revenue</td>
            <td><code>AD_REVENUE_VALUE</code></td>
        </tr>
        <tr>
            <td>In-App Ad Click</td>
            <td><code>IN_APP_AD_CLICK</code></td>
        </tr>
        <tr>
            <td>In-App Ad Impression</td>
            <td><code>IN_APP_AD_IMPR</code></td>
        </tr>
        <tr>
            <td>Install</td>
            <td><code>INSTALL</code></td>
        </tr>
        <tr>
            <td>Join Group</td>
            <td><code>JOIN_GROUP</code></td>
        </tr>
        <tr>
            <td>Launch App</td>
            <td><code>LAUNCH_APP</code></td>
        </tr>
        <tr>
            <td>Loan Apply</td>
            <td><code>LOAN_APPLY</code></td>
        </tr>
        <tr>
            <td>Loan Approval</td>
            <td><code>LOAN_CREDIT</code></td>
        </tr>
        <tr>
            <td>Loan Disbursal</td>
            <td><code>LOAN_COMPLETION</code></td>
        </tr>
        <tr>
            <td>Login</td>
            <td><code>LOGIN</code></td>
        </tr>
        <tr>
            <td>Purchase</td>
            <td><code>ACTIVE_PAY</code></td>
        </tr>
        <tr>
            <td>Rate</td>
            <td><code>RATE</code></td>
        </tr>
        <tr>
            <td>Registration</td>
            <td><code>ACTIVE_REGISTER</code></td>
        </tr>
        <tr>
            <td>Search</td>
            <td><code>SEARCH</code></td>
        </tr>
        <tr>
            <td>Spend Credits</td>
            <td><code>SPEND_CREDITS</code></td>
        </tr>
        <tr>
            <td>Start Trial</td>
            <td><code>START_TRIAL</code></td>
        </tr>
        <tr>
            <td>Subscribe</td>
            <td><code>SUBSCRIBE</code></td>
        </tr>
        <tr>
            <td>Unlock Achievement</td>
            <td><code>UNLOCK_ACHIEVEMENT</code></td>
        </tr>
        <tr>
            <td>View Content</td>
            <td><code>IN_APP_DETAIL_UV</code></td>
        </tr>
    </tbody>
</table>


### List of currency values
The following table lists of the enum values for `values` when `parameter` is `CURRENCY`.

```xtable
| Currency Value {30%}| Currency Name{60%} |
|---|---|
| `AED` | UAE Dirham |
| `ALL` | Lek |
| `AMD` | Armenian Dram |
| `ARS` | Argentine Peso |
| `AUD` | Australian Dollar |
| `AZN` | Azerbaijan Manat |
| `BDT` | Taka |
| `BGN` | Bulgarian Lev |
| `BHD` | Bahraini Dinar |
| `BIF` | Burundi Franc |
| `BOB` | Boliviano |
| `BRL` | Brazilian Real |
| `BYN` | Belarusian Ruble |
| `CAD` | Canadian Dollar |
| `CHF` | Swiss Franc |
| `CLP` | Chilean Peso |
| `CNY` | Yuan Renminbi |
| `COP` | Colombian Peso |
| `CRC` | Costa Rican Colon |
| `CZK` | Czech Koruna |
| `DKK` | Danish Krone |
| `DOP` | Dominican Peso |
| `DZD` | Algerian Dinar |
| `EGP` | Egyptian Pound |
| `EUR` | Euro |
| `GBP` | Pound Sterling |
| `GEL` | Lari |
| `GTQ` | Quetzal |
| `HKD` | Hong Kong Dollar |
| `HNL` | Lempira |
| `HUF` | Forint |
| `IDR` | Rupiah |
| `ILS` | New Israeli Sheqel |
| `INR` | Indian Rupee |
| `ISK` | Iceland Krona |
| `JOD` | Jordanian Dinar |
| `JPY` | Yen |
| `KES` | Kenyan Shilling |
| `KHR` | Riel |
| `KRW` | Won |
| `KWD` | Kuwaiti Dinar |
| `KZT` | Tenge |
| `MAD` | Moroccan Dirham |
| `MOP` | Pataca |
| `MXN` | Mexican Peso |
| `MYR` | Malaysian Ringgit |
| `NGN` | Naira |
| `NIO` | Cordoba Oro |
| `NOK` | Norwegian Krone |
| `NZD` | New Zealand Dollar |
| `OMR` | Rial Omani |
| `PAB` | Balboa |
| `PEN` | Sol |
| `PHP` | Philippine Peso |
| `PKR` | Pakistan Rupee |
| `PLN` | Zloty |
| `PYG` | Guarani |
| `QAR` | Qatari Rial |
| `RON` | Romanian Leu |
| `RSD` | Serbian Dinar |
| `RUB` | Russian Ruble |
| `SAR` | Saudi Riyal |
| `SEK` | Swedish Krona |
| `SGD` | Singapore Dollar |
| `THB` | Baht |
| `TND` | Tunisian Dinar |
| `TRY` | Turkish Lira |
| `TWD` | New Taiwan Dollar |
| `UAH` | Hryvnia |
| `USD` | US Dollar |
| `UZS` | Uzbekistan Sum |
| `VES` | Bolívar Soberano |
| `VND` | Dong |
| `ZAR` | Rand |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/custom_conversion/create/' \
--header 'Access-token: {{Access-token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "name": "{{name}}",
    "description": "{{description}}",
    "event_source_type": "PIXEL",
    "event_source_id": "{{event_source_id}}",
    "optimization_event": "BUTTON",
    "rules": [
        {
            "operator": "EQUAL",
            "parameter": "CURRENCY",
            "values": [
                "AED",
                "AMD",
                "ARS",
                "AZN",
                "BDT"
            ]
        },
        {
            "operator": "NOT_CONTAIN",
            "parameter": "SEARCH_STRING",
            "values": [
                "{{search_string}}",
                "{{search_string}}"
            ]
        }
    ]
}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|custom_conversion_id|string|The ID of the Custom Conversion.|
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
        "custom_conversion_id": "{{custom_conversion_id}}"
    }
}
(/code)
```
