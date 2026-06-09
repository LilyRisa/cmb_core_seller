---
title: "Create a Payment Portfolio"
doc_id: 1839151986039809
path: "API Reference / BC Payment Portfolios / Create a Payment Portfolio"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Payment Portfolio

Use this endpoint to create a Payment Portfolio.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/payment_portfolio/create/

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
|payment_portfolio_name{Required}|string|The name of the Payment Portfolio.|
|advertiser_ids{Required}|string[]|The list of ad accounts that you want to link to the Payment Portfolio.<br>You can only link ad accounts that belong to the same client as the Payment Portfolio.<br><br>Max size: 20.<br><br><ul><li>If the ad account belongs to a Business Center, you need to have Finance Manager or Admin access to the Business Center. To confirm your access to a Business Center, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and check the returned `user_role` and `finance_role`.</li><li>If the ad account does not belong to a Business Center, you need to have Admin or Operator access to the ad account.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If `advertiser_ids` contains any invalid IDs, the whole call will fail.|
|payment_portfolio_type|string|The type of the Payment Portfolio.<br><br>Enum values:<ul><li>`SHARED`: Advanced Payment Portfolio. For Advanced Payment Portfolios, funds are centrally managed and jointly used by all linked ad accounts.</li></ul><br>Default value: `SHARED`.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/payment_portfolio/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "payment_portfolio_name": "{{payment_portfolio_name}}",
    "advertiser_ids": ["{{advertiser_id}}", "{{advertiser_id}}", "{{advertiser_id}}"]
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
#|payment_portfolio_id|string|The ID of the Payment Portfolio.|
#|payment_portfolio_name|string|The name of the Payment Portfolio.|
#|payment_portfolio_type|string|The type of the Payment Portfolio.<br><br>Enum values:<ul><li>`SHARED`: Advanced Payment Portfolio. For Advanced Payment Portfolios, funds are centrally managed and jointly used by all linked ad accounts.</li></ul>|
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
        "payment_portfolio_id": "{{payment_portfolio_id}}",
        "payment_portfolio_name": "{{payment_portfolio_name}}",
		"payment_portfolio_type": "SHARED"
    }
}
(/code)
```




