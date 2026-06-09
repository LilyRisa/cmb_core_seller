---
title: "Report Offline Events in bulk"
doc_id: 1758428053652482
path: "API Reference / Events 1.0 / Offline Events / Report Offline Events in bulk"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Report Offline Events in bulk

Use this endpoint to report offline events in bulk to reduce traffic to the TikTok API for Business. We recommend that you report no more than 100 events in one request, with the total size less than 10 MBs.

> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/offline/batch/

**Method** POST

**Header**

```xtable
|Field|Data Type|Desciption|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {Required}|string|Request message type.<br>Allowed format: `"application/json"`.|
```

**Parameters**
```xtable
|Field{30%}|Data Type{10%}|Description{40%}|Examples{20%}|
|---|---|---|
|event_set_id{Required}|string |Event Set ID. |7057103914977558530|
|batch |object[]|A list of offline events. |
#|event {Required}|string |Event type. <br>Refer to the [Report Offline Events and Parameters-Supported Offline Events](https://ads.tiktok.com/marketing_api/docs?id=1758053486938113) section for supported values. |Purchase|
#|event_id|string|A unique value for each event. This ID can be used to match data between partner and TikTok. We suggest it is a String of 32 characters, including numeric digits (0-9), uppercase letters (A-Z), and lowercase letters (a-z).|1616318632825_357|
#|context{Required} |object  | Dictionary of extra information that provides useful context about a message. For the data fields in this object, see the "User context object parameters" section below.|See "User context object parameters" section|
#|properties{Required}|object  |Free-form dictionary of properties of the event, e.g., revenue. For the data fields in this object, see the "Properties object parameters" section below.|See "Properties object parameters" section|
#|timestamp{Required}|datetime|Timestamp that the event took place. Timestamp with [ISO-8601](https://en.wikipedia.org/wiki/ISO_8601) format. |2019-06-12T19:11:01.152Z|
```

### User context object parameters
The table below introduces the supported fields in `context` object. **You must pass in either `phone_numbers` or `emails` and provide at least one value for the specified field.**

```xtable
|Field{20%}|Data Type{10%}|Description{25%}|Formatting{30%}|Examples{15%}|
|---|---|---|---|---|
|user{Required}| object|The personal data of a user.| | 
#|phone_numbers|string[]|User's phone number. <br>It must be hashed with SHA-256 on the client side. You must pass in either this field or `emails`. |Prior to hashing, format the phone number:<ul><li>Normalize your phone numbers to E.164 format, a combination of + ［country code]［phone number]. An example of a US number in the E.164 format: +12133734253</li><li>Recommend using [https://github.com/catamphetamine/libphonenumber-js](https://github.com/catamphetamine/libphonenumber-js) for E.164 parsing.</li><li>Only SHA-256 is accepted. Here is the recommended package: [GitHub - emn178/js-sha256: A simple SHA-256 / SHA-224 hash function for JavaScript supports UTF-8 encoding.](https://github.com/emn178/js-sha256)|["sha256phone1","sha256phone2"]|
#|emails|string[]|User's email. <br>It must be hashed with SHA-256 on the client side. You must pass in either this field or `phone_numbers`. |Prior to hashing, format the email:<ul><li>You should lowercase all characters before hashing.</li><li>Do not perform any other types of normalization on emails prior to hashing.</li><li>Only SHA-256 is accepted. Here is the recommended package: [GitHub - emn178/js-sha256: A simple SHA-256 / SHA-224 hash function for JavaScript supports UTF-8 encoding.](https://github.com/emn178/js-sha256)| ["sha256email1", "sha256email2"]|
```


### Properties object parameters
The table below introduces the supported fields in `properties` object.

```xtable
|Field{20%}|Data Type{10%}|Description{35%}|Examples{35%} |
|---|---|---|---|
|order_id|string| Order ID.|abc_xyz |
|shop_id|string|Shop ID. | 
|contents|object[]|Product or Item array. | 
#|content_name|string|Name of the product. |socks |
#|content_type|string|Product or content type.|product |
#|content_category|string|Product or content category.|Men's cloth |
#|content_id|string|Product or content identifier.|230222800 |
#|quantity|number|Number of item.|1 |
#|price|number|Price of the product or content.|125.05 |
#|brand|string|Brand name of the product item.| |
|currency{+Conditional}|string|ISO 4217 code. Required for revenue reporting. Example: "USD".<br><strong>List of currencies currently supported:</strong> AED, ARS, AUD, BDT, BHD, BIF, BOB, BRL, CAD, CHF, CLP, CNY, COP, CRC, CZK, DKK, DZD, EGP, EUR, GBP, GTQ, HKD, HNL, HUF, IDR, ILS, INR, ISK, JPY, KES, KHR, KRW, KWD, KZT, MAD, MOP, MXN, MYR, NGN, NIO, NOK, NZD, OMR, PEN, PHP, PHP, PKR, PLN, PYG, QAR, RON, RUB, SAR, SEK, SGD, THB, TRY, TWD, UAH, USD, VES, VND, ZAR.|USD |
|value{+Conditional}|number|Revenue of total contents. Required for revenue reporting.|125.05 |
|event_channel|string|Event channel. Enum values:<ul><li>email<li>website<li>phone_call<li> in_store<li>crm<li>other|in_store |
```



### Example
> <span style="color: dodgerblue">**Note**</span><br>You need to replace the dummy data in the example with your own data.

```xcodeblock
(code cURL HTTP)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/offline/batch/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "event_set_id": "{{event_set_id}}",
    "batch": [{
            "event": "Purchase",
            "event_id": "{{event_id}}",
            "timestamp": "2020-09-17T19:49:27Z",
            "context": {
                "user": {
                    "phone_numbers": ["2f9d2b4df907e5c9a7b3434351b55700167b998a83dc479b825096486ffcf4ea",
                        "1e7c3b4ff108e5c9a7b3434351b55700167b998a83dc479b825096486cvvf6gt"
                    ],
                    "emails": ["dd6ff77f54e2106661089bae4d40cdb600979bf7edc9eb65c0942ba55c7c2d7f"]
                }
            },
            "properties": {
                "order_id": "abc_xyz",
                "shop_id": "123abc",
                "contents": [{
                        "price": 8,
                        "quantity": 2,
                        "content_type": "product",
                        "content_id": "1077218",
                        "content_name": "socks",
                        "content_category": "Men's cloth"
                    },
                    {
                        "price": 30,
                        "quantity": 1,
                        "content_type": "product",
                        "content_id": "1197218",
                        "content_name": "Nike Air Jordan 1",
                        "content_category": "Men's shoes"
                    }
                ],
                "event_channel": "in_store",
                "currency": "USD",
                "value": 46.00
            }
        },
        {
            "event": "Purchase",
            "event_id": "{{event_id}}",
            "timestamp": "2020-09-18T20:50:27Z",
            "context": {
                "user": {
                    "phone_numbers": ["2f9d2b4df907e5c9a7b3434351b55700167b998a83dc479b825096486ffcf4ea",
                        "1e7c3b4ff108e5c9a7b3434351b55700167b998a83dc479b825096486cvvf6gt"
                    ],
                    "emails": ["dd6ff77f54e2106661089bae4d40cdb600979bf7edc9eb65c0942ba55c7c2d7f"]
                }
            },
            "properties": {
                "order_id": "abc_xyz_2",
                "shop_id": "123abc",
                "contents": [{
                        "price": 20,
                        "quantity": 1,
                        "content_type": "product",
                        "content_id": "1077220",
                        "content_name": "Jacket",
                        "content_category": "Men's cloth"
                    },
                    {
                        "price": 100,
                        "quantity": 1,
                        "content_type": "product",
                        "content_id": "1197221",
                        "content_name": "Jeans",
                        "content_category": "Men's cloth"
                    }
                ],
                "event_channel": "in_store",
                "currency": "USD",
                "value": 120.00
            }
        }
    ]
}
'
(/code);
```
## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log id of a request, which uniquely identifies the request. |
|data|object| Error details. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "request_id": "2019091810032301011023224213558",
    "data":{}
}

(/code);
```



