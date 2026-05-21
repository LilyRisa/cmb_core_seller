# GET/POSTCreateCustomerAccountRelationshipByOTP

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fcustomers%2Fexternal_relationships_bundle
> API path: /logistics/epis/customers/external_relationships_bundle
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:46:26.220Z

---

Latest update2022-12-14 08:50:40

5032

CreateCustomerAccountRelationshipByOTP

GET/POST

/logistics/epis/customers/external\_relationships\_bundle

No Authorization Required

Description:Create customer account relationship for external by OTP

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
| platformName | String | Yes | Platform name |
| otp | String | Yes | Bundle code generated in Lazada Logistics Website |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Request success or not |
| retryable | Boolean | Is fail request retryable |
| traceId | String | Trace ID for debugging |
| errorMessage | String | Error code |
| errorCode | String | Error message |
| errors | Object\[\] | Error detail |
| field | String | When validation failed on field, the error field path will be included, begin with "$." as root object |
| errorMessage | String | Detail error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/customers/external_relationships_bundle)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/epis/customers/external\_relationships\_bundle

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/customers/external_relationships_bundle");
request.addApiParameter("externalSellerId", "L001231321");
request.addApiParameter("platformName", "OneLink");
request.addApiParameter("otp", "L0000001");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "retryable": "false",
  "traceId": "0ba2887315172940728551014",
  "code": "0",
  "success": "true",
  "errorMessage": "Bad request",
  "errorCode": "BAD_REQUEST",
  "request_id": "0ba2887315178178017221014",
  "errors": [
    {
      "field": "$.items.name",
      "errorMessage": "name must not be blank"
    }
  ]
}
```
