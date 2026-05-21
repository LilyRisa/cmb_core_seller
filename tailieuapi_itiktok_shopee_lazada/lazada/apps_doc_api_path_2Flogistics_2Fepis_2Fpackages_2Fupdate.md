# POSTEpisPackageInfoUpdate

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fpackages%2Fupdate
> API path: /logistics/epis/packages/update
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:49:06.468Z

---

Latest update2024-01-11 17:53:01

2497

EpisPackageInfoUpdate

POST

/logistics/epis/packages/update

No Authorization Required

Description:External partner call EPIS to update package info after RTS

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
| packageCode | String | Yes | Package code |
| receiverName | String | Yes | Receiver name |
| receiverPhone | String | Yes | Receiver phone number |
| totalAmount | String | No | Payment total amount |
| insuranceAmount | String | No | Payment insurance amount |
| deliveryNote | String | No | Delivery note |
| receiverAddress | Object | No | Receiver address |
| id | String | No | Receiver address id Lazada Last level (level 4 / 5) RCode |
| details | String | No | Receiver address detail |
| type | String | No | Address type |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| retryable | Boolean | Is failed request retryable? |
| traceId | String | trace id for debug |
| success | Boolean | Is success? |
| errorMessage | String | Error message |
| errorCode | String | Error code |
| errors | Object\[\] | Error detail |
| field | String | Error field name |
| errorMessage | String | Errror message |
| errorCode | String | Error code |
| data | Object | update result |
| convertedAddress | Object | Converted address |
| id | String | Converted rcode |
| details | String | Address detail |
| type | String | OLD or NEW address |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/packages/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/epis/packages/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/packages/update");
request.addApiParameter("packageCode", "FU2520016900000000000005515757120");
request.addApiParameter("receiverName", "John");
request.addApiParameter("receiverPhone", "0972018000");
request.addApiParameter("totalAmount", "4.5");
request.addApiParameter("insuranceAmount", "4.5");
request.addApiParameter("deliveryNote", "Delivery note");
request.addApiParameter("receiverAddress", "{\"details\":\"25A Nguy\u1EC5n \u0110\u00ECnh Chi\u1EC3u\",\"id\":\"R6846129\",\"type\":\"Home\"}");
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
  "data": {
    "convertedAddress": {
      "details": "25A Nguyễn Đình Chiểu",
      "id": "R6846129",
      "type": "OLD"
    }
  },
  "success": "false",
  "errorMessage": "Bad request",
  "errorCode": "BAD_REQUEST",
  "request_id": "0ba2887315178178017221014",
  "errors": [
    {
      "field": "receiverAddress",
      "errorMessage": "Cannot found LM hub",
      "errorCode": "CANNOT_FOUND_LM_HUB"
    }
  ]
}
```
