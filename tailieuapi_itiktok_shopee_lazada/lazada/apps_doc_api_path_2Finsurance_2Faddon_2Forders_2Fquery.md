# GET/POSTqueryAddonOrder

> Source: https://open.lazada.com/apps/doc/api?path=%2Finsurance%2Faddon%2Forders%2Fquery
> API path: /insurance/addon/orders/query
> Category: LazPay API
> Scraped: 2026-05-20T23:57:41.275Z

---

Latest update2026-05-21 07:57:28

500

queryAddonOrder

GET/POST

/insurance/addon/orders/query

No Authorization Required

Description:list user addon order detail

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
| pageNum | Number | Yes | pageNum |
| pageSize | Number | Yes | pageSize |
| userToken | String | Yes | userToken |
| orderStatus | String | No | orderStatus |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| redirectUrl | String | redirectUrl |
| resultCode | String | resultCode |
| data | Object | data |
| total | Number | total |
| totalPages | Number | totalPages |
| pageSize | Number | pageSize |
| orderList | Object\[\] | orderList |
| premium | String | premium |
| expireTime | Number | expireTime |
| effectiveTime | Number | effectiveTime |
| insuranceName | String | insuranceName |
| orderStatus | String | orderStatus |
| policyLink | String | policyLink |
| paidPremium | String | paidPremium |
| transactionId | String | transactionId |
| productName | String | productName |
| insuredName | String | insuredName |
| zoneId | String | zoneId |
| orderDetailLink | String | orderDetailLink |
| pageNum | Number | pageNum |
| traceId | String | traceId |
| success | Boolean | success |
| resultMessage | String | resultMessage |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/insurance/addon/orders/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/insurance/addon/orders/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/insurance/addon/orders/query");
request.addApiParameter("pageNum", "1");
request.addApiParameter("pageSize", "10");
request.addApiParameter("userToken", "gQk/8THS7TSQlVj42JP1lg==");
request.addApiParameter("orderStatus", "ISSUED,CANCELED,EFFECTIVE,EXPIRED,CLAIMED");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "redirectUrl": "null",
  "code": "0",
  "data": {
    "traceId": "213123qweqwe",
    "total": "50",
    "totalPages": "5",
    "pageSize": "10",
    "orderList": [
      {
        "premium": "4.37",
        "expireTime": "1784715128000",
        "orderDetailLink": "123123123",
        "effectiveTime": "1741806000000",
        "insuranceName": "Gadget_MY",
        "orderStatus": "EXPIRED",
        "zoneId": "Asiashanghai",
        "policyLink": "https://my-test.zatech.com/claim/landing?policyNo\u003dFJ3bHARDObqJKdpLRGWfC0dCGO75Ktn0jlCfrAqYqaVOSdK9hvU\u003d\u0026insurerCode\u003dAIA\u0026planName\u003dGadget%20Protection%20%28Repair%20and%20Replace%29\u0026productName\u003dfusion-my%20test",
        "insuredName": "[\"23121233\"]",
        "paidPremium": "4.37",
        "transactionId": "490265400456001",
        "productName": "fusion-my test"
      }
    ],
    "pageNum": "1"
  },
  "success": "true",
  "resultCode": "SUCCESS",
  "resultMessage": "Success",
  "request_id": "0ba2887315178178017221014"
}
```
