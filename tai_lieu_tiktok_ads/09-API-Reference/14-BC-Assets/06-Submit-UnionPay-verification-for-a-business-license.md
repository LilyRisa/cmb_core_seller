---
title: "Submit UnionPay verification for a business license"
doc_id: 1813772274039809
path: "API Reference / BC Assets / Submit UnionPay verification for a business license"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Submit UnionPay verification for a business license

Use this endpoint to submit UnionPay verification for a business license.

Once you submit UnionPay verification, wait for around 20 seconds and use [/advertiser/info/](https://business-api.tiktok.com/portal/docs?id=1739593083610113) to confirm the status of the ad account. If the ad account is rejected for UnionPay verification failure, resubmit your license information (`license_no` and `license_image_id`) using [/advertiser/update/](https://business-api.tiktok.com/portal/docs?id=1739939050770434) and then resubmit UnionPay verification using [/bc/advertiser/unionpay_info/submit/](https://business-api.tiktok.com/portal/docs?id=1813772274039809).

To learn more about how to create ad accounts in a Business Center, see [Create an ad account](https://business-api.tiktok.com/portal/docs?id=1754705407307777#item-link-Create%20an%20ad%20account).

> <span style="color:DodgerBlue">**Note**</span><br>
> If you submit UnionPay verification multiple times while the ad account is under review, only the initial submission will be accepted. Subsequent submissions will be ignored.

## Before you start
- Confirm that your business license requires UnionPay verification using [/bc/advertiser/unionpay_info/check/](https://business-api.tiktok.com/portal/docs?id=1813772238056449). The `unionpay_verification_required` returned for the `license_no` should be `true`.
- Create an ad account with the same `license_no` using [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721). This will associate the ad account with the business license requiring UnionPay verification.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/unionpay_info/submit/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| bc_id {Required} | string | Business Center ID. <br>You need to have Admin permission for the Business Center. <br><br>To obtain the list of Business Centers where you have Admin permission, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and ensure the returned `user_role` is `ADMIN`. |
| advertiser_id {Required} | string | Ad account ID. <br><br>You need to specify the ID of the ad account associated with the business license that requires UnionPay verification and have Admin permission for the ad account. |
| representative_name {Required} | string | The Chinese name of the legal representative as it appears on the business license. |
| representative_document_type | string | The type of the representative document. <br><br>Enum values:<ul><li>`ID_CARD`: ID card.</li><li>`PASSPORT`: Passport.</li><li>`HK_MACAO_EXIT_ENTRY_PERMIT`: Exit-Entry Permit for Travelling to and from Hong Kong and Macao.</li><li>`TAIWAN_MAINLAND_TRAVEL_PERMIT`: Mainland Travel Permit for Taiwan Residents.</li><li>`HK_MACAO_MAINLAND_TRAVEL_PERMIT`: Mainland Travel Permit for Hong Kong and Macao Residents.</li></ul>Default value: `ID_CARD`. |
| representative_id {Required} | string | The ID of the representative document.<br><br><ul><li>When `representative_document_type` is `ID_CARD` or not passed, specify the ID card number of the legal representative in 18 digits. <ul><li>Example: `234123190101012345`.</li></ul></li><li>When `representative_document_type` is `PASSPORT`, specify the alphanumeric ID of the passport.<ul><li>Example: `EF1234567`.</li></ul></li><li>When `representative_document_type` is `HK_MACAO_EXIT_ENTRY_PERMIT`, specify the alphanumeric ID card number of the Exit-Entry Permit for Travelling to and from Hong Kong and Macao. <ul><li>Example: `CA1234567`. </li></ul></li><li>When `representative_document_type` is `TAIWAN_MAINLAND_TRAVEL_PERMIT`, specify the numeric ID of the Mainland Travel Permit for Taiwan Residents. <ul><li>Example: `12345678`.</li></ul></li><li>When `representative_document_type` is `HK_MACAO_MAINLAND_TRAVEL_PERMIT`, specify the alphanumeric ID Mainland Travel Permit for Hong Kong and Macao Residents. <ul><li>Example: `H12345678`.</li></ul></li></ul>|
| unionpay_account {Required} | string | The UnionPay account number in 12 to 19 digits. |
| representative_phone_number {Required} | string | The phone number of the legal representative in 11 digits.<br><br>Example: `13812345678`. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/unionpay_info/submit/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "advertiser_id": "{{advertiser_id}}",
    "representative_name": "{{representative_name}}",
    "representative_id": "{{representative_id}}",
    "unionpay_account": "{{unionpay_account}}",
    "representative_phone_number": "{{representative_phone_number}}"
}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
