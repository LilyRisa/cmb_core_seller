# GET/POSTGetShippingFee

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fget_shipping_fee
> API path: /logistics/epis/get_shipping_fee
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:51:19.466Z

---

Latest update2024-07-22 11:34:14

2227

GetShippingFee

GET/POST

/logistics/epis/get\_shipping\_fee

No Authorization Required

Description:Estimate package shipping fee (Estimated & Actual)

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
| trackingNumber | String | Yes | Lazada tracking number |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| retryable | Boolean | Is failed request retryable? |
| traceId | String | Trace id for debugging |
| data | Object | Package fee response |
| estimatedShippingFee | String | Estimated shipping fee |
| actualShippingFee | String | Actual shipping fee |
| currency | String | Currency code |
| originEstimatedShippingFee | String | Origin estimated shipping fee (Non promotion) |
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
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/get_shipping_fee)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/epis/get\_shipping\_fee

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/get_shipping_fee");
request.addApiParameter("externalSellerId", "001231321");
request.addApiParameter("platformName", "Platform_XXX");
request.addApiParameter("trackingNumber", "LXVN123123123");
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
  "data": {
    "originEstimatedShippingFee": "125000",
    "actualShippingFee": "125000",
    "estimatedShippingFee": "125000",
    "currency": "VND"
  },
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
