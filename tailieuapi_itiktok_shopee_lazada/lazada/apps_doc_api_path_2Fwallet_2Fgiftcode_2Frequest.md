# GET/POSTGiftCodeRequest

> Source: https://open.lazada.com/apps/doc/api?path=%2Fwallet%2Fgiftcode%2Frequest
> API path: /wallet/giftcode/request
> Category: Lazada Wallet Corporate Top-up API
> Scraped: 2026-05-20T23:59:05.795Z

---

Latest update2022-07-29 14:13:04

2370

GiftCodeRequest

GET/POST

/wallet/giftcode/request

No Authorization Required

Description:Gift Code - Request

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
| amount | String | Yes | The amount of each gift code, precise to two decimal places |
| quantity | Number | Yes | The quantity of gift codes to be created |
| transfer\_order\_id | String | Yes | ISV transfer order id，length <= 32 |
| end\_timestamp | Number | Yes | End timestamp，13 bits |
| start\_timestamp | Number | Yes | Start timestamp，13 bits |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| transfer\_order\_id | String | ISV transfer order id |
| total\_number | Number | Total gift code quantity |
| create\_status | String | Create status of gift code |
| deposit | String | The available balance of ISV |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| OPEN\_API\_TIMESTAMP\_INVALID | The input timestamp is invalid | The input timestamp is invalid |
| BIZ\_DEGRADATION\_ERROR | The service is not available now | The service is not available now |
| OPEN\_API\_CALL\_EXCEED\_LIMIT | Open Api call times exceeds: apiName\_limitType | Open Api call times exceeds: apiName\_limitType |
| PROCEED\_TRANSFER\_EXCEPTION | Internal error, please contact lazada tech team | Internal error, please contact lazada tech team |
| USER\_IS\_NOT\_LOGGED\_IN | The user is not logged in | The user is not logged in |
| APP\_KEY\_INVALID | App key is invalid, please contact lazada tech team. | App key is invalid, please contact lazada tech team. |
| TRANSFER\_ERROR\_TRANSFER\_ORDER\_ID\_INVALID | Transfer order ID is invalid | Transfer order ID is invalid |
| TRANSFER\_ERROR\_MSG\_AMOUNT\_INVALID | Amount is invalid | Amount is invalid |
| TRANSFER\_ERROR\_MSG\_QUANTITY\_INVALID | The quantity of gift code is invalid | The quantity of gift code is invalid, only under test case. |
| GIFT\_CODE\_LOCK\_CONFLICT | Gift code is already being created，please wait for a moment and check the batch list. | Gift code is already being created，please wait for a moment and check the batch list. |
| BATCH\_CREATE\_ERROR | Error happens when creating gift code. Please Retry. | Error happens when creating gift code. |
| BALANCE\_ACCOUNT\_NOT\_ENOUGH | Balance account is not enough. | Balance account is not enough. |
| TRANSFER\_ERROR\_NATION\_NOT\_IN\_LIST | The current user's region does not have permission to access, please contact the lazada tech team. | The current user's region does not have permission to access, please contact the lazada tech team. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/wallet/giftcode/request)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/wallet/giftcode/request

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/wallet/giftcode/request");
request.addApiParameter("amount", "0.01");
request.addApiParameter("quantity", "1");
request.addApiParameter("transfer_order_id", "test001");
request.addApiParameter("end_timestamp", "1740260653001");
request.addApiParameter("start_timestamp", "1640260000001");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "total_number": "1",
  "code": "0",
  "transfer_order_id": "test001",
  "create_status": "SUCCESS",
  "deposit": "99.99",
  "request_id": "0ba2887315178178017221014"
}
```
