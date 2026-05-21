# GET/POSTGetOVOOrders

> Source: https://open.lazada.com/apps/doc/api?path=%2Forders%2Fovo%2Fget
> API path: /orders/ovo/get
> Category: Order API
> Scraped: 2026-05-20T23:23:34.673Z

---

Latest update2022-07-19 18:38:45

13240

GetOVOOrders

GET/POST

/orders/ovo/get

Authorization Required

Description:This interface is only applicable to the merchant side of the business and is used to set the maximum number of SKUs that certain merchants can sell per day

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
| access\_token | String | Yes | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| tradeOrderIds | String | Yes | id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| success | String | success or not |
| tradeOrders | Object\[\] | trader orders |
| tradeOrderId | Number | trade order id |
| paymentMethod | String | payment method |
| paidTime | String | paid time |
| tradeOrderLines | Object\[\] | trade OrderLines |
| tradeOrderLineId | Number | trade orderLine id |
| deliveryStatus | String | delivery status |
| reverseStatus | String | reverse status |
| deliveredTime | String | delivered time |
| errorCode | String | error code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/orders/ovo/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/orders/ovo/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/orders/ovo/get");
request.addApiParameter("tradeOrderIds", "31938200743006");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "success": "true",
    "errorCode": "SYSREM_ERROR",
    "tradeOrders": [
      {
        "tradeOrderId": "31938200743006",
        "paymentMethod": "OVO",
        "paidTime": "2020-05-20T19:46:22.754+08:00[GMT+08:00]",
        "tradeOrderLines": [
          {
            "deliveredTime": "2020-05-20T19:46:22.754+08:00[GMT+08:00]",
            "tradeOrderLineId": "31938200743006",
            "deliveryStatus": "DELIVERED",
            "reverseStatus": "NO_ISSUE"
          }
        ]
      }
    ]
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
