# GET/POSTEstimateShippingFee

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Festimate_shipping_fee
> API path: /logistics/epis/estimate_shipping_fee
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:51:11.356Z

---

Latest update2023-01-11 15:31:07

3956

EstimateShippingFee

GET/POST

/logistics/epis/estimate\_shipping\_fee

No Authorization Required

Description:Estimate shipping fee

## Service Endpoints

| Region | Endpoint |
| --- | --- |
| Vietnam | https://api.lazada.vn/rest |
| Singapore | https://api.lazada.sg/rest |
| Philippines | https://api.lazada.com.ph/rest |
| Malaysia | https://api.lazada.com.my/rest |
| Thailand | https://api.lazada.co.th/rest |
| Indonesia | https://api.lazada.co.id/rest |
## Common Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| app\_key | String | Yes | Unique app ID issued by LAZADA Open Platform console when you apply for an app category |
| timestamp | String | Yes | The time stamp of the request e.g. 1517820392000 (which translates to 5 February 2018 08:46:32) with less than 7200s difference from UTC time |
| access\_token | String | No | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| externalSellerId | String | Yes | External seller ID |
| platformName | String | Yes | Platform where seller order comes from |
| fromAddressId | String | No | Lazada last level address R-code |
| toAddressId | String | No | Lazada last level address R-code |
| chargeFactor | Object | Yes | Charge factors |
| packageType | String | No | Default package type = Sales\_order. Enum \[Sales\_order, Customer\_return\] |
| deliveryOption | String | No | Default delivery service type = standard. Enum \[standard, economy, point\_to\_point\] |
| fulfillmentMethod | String | No | Default fulfillment method = Dropshipping. Enum \[Dropshipping, MP, Marketplace\] |
| paymentType | String | Yes | Payment type. Enum \[COD, NON-COD\] |
| weight | String | Yes | Weight. Unit: gram |
| insuranceAmount | String | No | Insurance amount |
| fromLocation | Object | No | Geo location |
| latitude | String | Yes | Latitude |
| longitude | String | Yes | Longitude |
| toLocation | Object | No | Geo location |
| latitude | String | Yes | Latitude |
| longitude | String | Yes | Longitude |
| packageCode | String | No | package code |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| retryable | Boolean | Is failed request retryable? |
| traceId | String | Trace id for debugging |
| data | Object\[\] | Rating response |
| transactionId | String | Expense item ID |
| transactionType | String | Expense item type example: Delivery, International Delivery, COD, Insurance, Surcharge |
| transactionName | String | Expense item name |
| amount | String | Fee amount |
| taxAmount | String | Tax amount |
| currency | String | Currency: IDR, MYR, PHP, SGD, THB, VND |
| success | Boolean | Is success? |
| errorMessage | String | Error message |
| errorCode | String | Error code |
| errors | Object\[\] | Detail errors |
| field | String | When validation failed on field, the error field path will be included, begin with "$." as root object |
| errorMessage | String | Detail error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/estimate_shipping_fee)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/epis/estimate\_shipping\_fee

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/estimate_shipping_fee");
request.addApiParameter("externalSellerId", "001231321");
request.addApiParameter("platformName", "Platform_XXX");
request.addApiParameter("fromAddressId", "R12345");
request.addApiParameter("toAddressId", "R56789");
request.addApiParameter("chargeFactor", "{\"insuranceAmount\":\"123.1\",\"fulfillmentMethod\":\"Dropshipping\",\"weight\":\"334.0\",\"packageType\":\"Sales_order\",\"deliveryOption\":\"standard\",\"paymentType\":\"COD\"}");
request.addApiParameter("fromLocation", "{\"latitude\":\"10.7776331\",\"longitude\":\"106.7116815\"}");
request.addApiParameter("toLocation", "{\"latitude\":\"10.7776331\",\"longitude\":\"106.7116815\"}");
request.addApiParameter("packageCode", "FU3330026200000000000006872687769");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "retryable": "false",
  "traceId": "1666f8ce16709204399801013bf6cf",
  "code": "0",
  "data": [
    {
      "transactionType": "Delivery",
      "amount": "100000.0",
      "currency": "VND",
      "transactionName": "Delivery",
      "taxAmount": "11000.0",
      "transactionId": "1234"
    }
  ],
  "success": "false",
  "errorMessage": "Bad request",
  "errorCode": "BAD_REQUEST",
  "request_id": "0ba2887315178178017221014",
  "errors": [
    {
      "field": "$.platformName",
      "errorMessage": "$.platformName must not be blank"
    }
  ]
}
```
