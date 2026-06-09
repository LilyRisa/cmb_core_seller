---
title: "Check the UnionPay verification requirement for a business license"
doc_id: 1813772238056449
path: "API Reference / BC Assets / Check the UnionPay verification requirement for a business license"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Check the UnionPay verification requirement for a business license

Use this endpoint to check whether UnionPay verification is required for your business license.

For ad accounts registered in the Chinese Mainland and belonging to one of the following types, you need to complete UnionPay verification for the associated business licenses after creating an ad account with the same business license using [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721):
- Sole Proprietorship (Individual industrial and commercial household)
- Sole Proprietorship Enterprise (Individual)
- Limited Liability Company (Solely owned by a natural person or by a natural person from Hong Kong, Macau, or Taiwan)
- Limited Liability Company (Invested or controlled by a natural person)

If you fail to complete the required UnionPay verification, the ad account will be rejected during review.

To learn more about how to create ad accounts in a Business Center, see [Create an ad account](https://business-api.tiktok.com/portal/docs?id=1754705407307777#item-link-Create%20an%20ad%20account).



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/unionpay_info/check/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| license_no {Required} | string | Business license number. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/unionpay_info/check/?license_no={{license_no}}' \
--header 'Access-Token: {{Access-Token}}' 
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
#| unionpay_verification_required | boolean | Whether UnionPay verification is required for the business license. <br><br>Supported values: <ul><li>`true`: required.</li><li>`false`: not required.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: If this field is `true`, submit UnionPay verification for your business license using [/bc/advertiser/unionpay_info/submit/](https://business-api.tiktok.com/portal/docs?id=1813772274039809). |
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
        "unionpay_verification_required": true
    }
}
(/code)
```
