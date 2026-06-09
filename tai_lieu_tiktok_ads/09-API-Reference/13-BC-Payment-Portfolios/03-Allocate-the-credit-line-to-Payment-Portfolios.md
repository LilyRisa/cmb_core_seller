---
title: "Allocate the credit line to Payment Portfolios"
doc_id: 1839151998450754
path: "API Reference / BC Payment Portfolios / Allocate the credit line to Payment Portfolios"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Allocate the credit line to Payment Portfolios

Use this endpoint to allocate the credit line of a client to one or more Payment Portfolios.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/payment_portfolio/credit_line/update/

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
|allocation_details{Required}|object[]|The details of credit line allocation.<br><br>Max size: 10.|
#|payment_portfolio_id{Required}|string|The ID of the Advanced Payment Portfolio.<br><br>To obtain a list of available Payment Portfolio IDs, use [/payment_portfolio/get/](https://business-api.tiktok.com/portal/docs?id=1839151931301954) and filter for Payment Portfolios where the `payment_portfolio_type` is `SHARED`.|
#|allocation_percentage{Required}|number|The percentage of credit line that you want to allocate to the Payment Portfolio, kept to four decimal places.<br>Value range: 0-1.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The percentage you allocate to each Payment Portfolio must be greater than its used percentage. To confirm the used credit line percentage for a Payment Portfolio, check the `min_allocation_percentage` returned from [/payment_portfolio/get/](https://business-api.tiktok.com/portal/docs?id=18391519313019545).</li><li>If your total credit line changes later, the credit line for each Portfolio will change based on the set percentage.</li><li>Each Portfolio's available credit line is automatically restored when outstanding bills are settled. You won't need to manually reallocate the amount.</li></ul>|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/payment_portfolio/credit_line/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "allocation_details": [
        {
            "payment_portfolio_id": "{{payment_portfolio_id}}",
            "allocation_percentage": {{allocation_percentage}}
        },
        {
            "payment_portfolio_id": "{{payment_portfolio_id}}",
            "allocation_percentage": {{allocation_percentage}}
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




