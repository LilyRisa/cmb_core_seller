# GET/POSTDirectTransferQuery

> Source: https://open.lazada.com/apps/doc/api?path=%2Fwallet%2Ftransfer%2Fquery
> API path: /wallet/transfer/query
> Category: Lazada Wallet Corporate Top-up API
> Scraped: 2026-05-20T23:58:22.772Z

---

Latest update2022-07-26 00:18:13

3854

DirectTransferQuery

GET/POST

/wallet/transfer/query

No Authorization Required

Description:Direct Transfer - Query

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
| transfer\_order\_id | String | Yes | ISV transfer order id, length <= 32 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| amount | String | Transfer amount，precise to two decimal places. |
| account\_number | String | Email or phone number, accepted phone number starts with (PH: +638, +639, 08, 09, 638, 639) |
| transfer\_order\_id | String | ISV transfer order id, length <= 32 |
| transfer\_request\_id | String | Lazada transfer order id |
| deposit | String | The available balance of ISV |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| TRANSFER\_ERROR\_MSG\_RESPONSED\_FAILED | Error happens when transferring，please contact lazada team | Error happens when transferring，please contact lazada team |
| OPEN\_DIRECT\_TRANSFER\_INTERNAL\_FAIL | Direct transfer internal error, please retry or contact lazada tech team. | Direct transfer internal error, please retry or contact lazada tech team. |
| TRANSFER\_ERROR\_MSG\_AMOUNT\_INVALID | Amount is invalid | Amount is invalid |
| APP\_KEY\_INVALID | App key is invalid, please contact lazada tech team. | App key is invalid, please contact lazada tech team. |
| USER\_IS\_NOT\_LOGGED\_IN | The user is not logged in | The user is not logged in |
| PROCEED\_TRANSFER\_EXCEPTION | Internal error, please retry or contact lazada tech team. | Internal error, please retry or contact lazada tech team. |
| OPEN\_API\_CALL\_EXCEED\_LIMIT | Open Api call times exceeds: apiName\_limitType | Open Api call times exceeds: apiName\_limitType |
| TRANSFER\_ERROR\_NATION\_NOT\_IN\_LIST | The current user's region does not have permission to access, please contact the lazada tech team. | The current user's region does not have permission to access, please contact the lazada tech team. |
| USER\_BALANCE\_NOT\_ENOUGH | The available deposit is not enough for the transfer. | The available deposit is not enough for the transfer. |
| TRANSFER\_AMOUNT\_EXCEED\_LIMIT | The transfer amount has exceeded the limit. | The transfer amount has exceeded the limit. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/wallet/transfer/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/wallet/transfer/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/wallet/transfer/query");
request.addApiParameter("transfer_order_id", "test001");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "account_number": "09160000001",
  "amount": "0.01",
  "code": "0",
  "transfer_order_id": "test001",
  "transfer_request_id": "open_105400_test001_id",
  "deposit": "99.99",
  "request_id": "0ba2887315178178017221014"
}
```
