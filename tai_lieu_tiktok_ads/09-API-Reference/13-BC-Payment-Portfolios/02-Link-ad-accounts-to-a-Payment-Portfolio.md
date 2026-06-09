---
title: "Link ad accounts to a Payment Portfolio"
doc_id: 1839151992566081
path: "API Reference / BC Payment Portfolios / Link ad accounts to a Payment Portfolio"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Link ad accounts to a Payment Portfolio

Use this endpoint to link an ad account to a Payment Portfolio.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/payment_portfolio/advertiser/update/

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
|payment_portfolio_id{Required}|string|The ID of the Advanced Payment Portfolio.<br><br>To obtain a list of available Payment Portfolio IDs, use [/payment_portfolio/get/](https://business-api.tiktok.com/portal/docs?id=1839151931301954) and filter for Payment Portfolios where the `payment_portfolio_type` is `SHARED`.|
|advertiser_ids{Required}|string[]|The list of ad accounts that you want to link to the Payment Portfolio.<br>You can only link ad accounts that belong to the same client as the Payment Portfolio.<br><br>Max size: 20.<br><br><ul><li>If the ad account belongs to a Business Center, you need to have Finance Manager or Admin access to the Business Center. To confirm your access to a Business Center, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and check the returned `user_role` and `finance_role`.</li><li>If the ad account does not belong to a Business Center, you need to have Admin or Operator access to the ad account.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If `advertiser_ids` contains any invalid IDs, the whole call will fail.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/payment_portfolio/advertiser/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "payment_portfolio_id": "{{payment_portfolio_id}}",
    "advertiser_ids": ["{{advertiser_id}}"]
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


