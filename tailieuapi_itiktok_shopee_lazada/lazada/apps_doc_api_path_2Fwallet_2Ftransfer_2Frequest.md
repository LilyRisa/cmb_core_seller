# GET/POSTDirectTransferRequest

> Source: https://open.lazada.com/apps/doc/api?path=%2Fwallet%2Ftransfer%2Frequest
> API path: /wallet/transfer/request
> Category: Lazada Wallet Corporate Top-up API
> Scraped: 2026-05-20T23:58:35.294Z

---

Latest update2022-07-26 00:18:22

3441

DirectTransferRequest

GET/POST

/wallet/transfer/request

No Authorization Required

Description:Direct Transfer - Request to transfer

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
| amount | String | Yes | Transfer amount，precise to two decimal places. |
| transfer\_order\_id | String | Yes | ISV transfer order id，length <= 32 |
| account\_number | String | Yes | Phone number or email address，accepted phone number starts with (PH : +639, +638, 08, 09, 638, 639) |
| withdrawable | Boolean | No | The funds type for transfers. Set true for funds that can be withdrawn and false for funds that cannot be withdrawn. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| account\_number | String | The email or phone number of user to be transferred to |
| transfer\_order\_id | String | ISV input transfer order id |
| transfer\_request\_id | String | Lazada transfer order id |
| amount | String | The amount to transfer |
| deposit | String | The available balance of ISV |
| withdrawable | Boolean | The funds type for transfers. Set true for funds that can be withdrawn and false for funds that cannot be withdrawn. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| OPEN\_DIRECT\_TRANSFER\_LOCK\_CONFLICT | Direct transfer request is already being processed，please wait for a moment and check status | Direct transfer request is already being processed，please wait for a moment and check status |
| TRANSFER\_ERROR\_MSG\_RESPONSED\_FAILED | Error happens when transferring，please contact lazada team | Error happens when transferring, please contact lazada team |
| TRANSFER\_VALUE\_UNMATCHED | Transfer amount does not match | Transfer amount does not match, please enter same amount |
| TRANSFER\_USER\_UNMATCHED | User to be transferred not match | User to be transferred not match, please use same account |
| TRANSFER\_ERROR\_ACCOUNT\_NUMBER\_INVALID | Account number is invalid | Please check and re-enter your account number |
| OPEN\_DIRECT\_TRANSFER\_INTERNAL\_FAIL | Direct transfer internal error, please retry or contact lazada tech team. | Direct transfer internal error, please retry or contact lazada tech team. |
| TRANSFER\_ERROR\_TRANSFER\_ORDER\_ID\_INVALID | Transfer order ID is invalid | Please check and re-enter your transfer order ID |
| TRANSFER\_ERROR\_MSG\_AMOUNT\_INVALID | Amount is invalid | Please check and re-enter your amount |
| APP\_KEY\_INVALID | App key is invalid, please contact lazada tech team. | App key is invalid, please contact lazada tech team. |
| USER\_IS\_NOT\_LOGGED\_IN | The user is not logged in | Please log in your account |
| PROCEED\_TRANSFER\_EXCEPTION | Internal error, please retry or contact lazada tech team. | Internal error, please retry or contact lazada tech team. |
| OPEN\_API\_CALL\_EXCEED\_LIMIT | Open Api call times exceeds: apiName\_limitType | Open Api call times exceeds, please contact lazada tech team or retry later |
| BIZ\_DEGRADATION\_ERROR | The service is not available now | The service is not available now, please retry or contact lazada tech team |
| TRANSFER\_ERROR\_MSG\_WALLET\_INACTIVATED | The transfer account has not activated the wallet | The transfer account has not activated the wallet, please activate your wallet |
| TRANSFER\_ERROR\_MSG\_USER\_NOT\_FOUND | User to be transferred not found. | User to be transferred not found, please check your account or contact the lazada tech team |
| USER\_BALANCE\_NOT\_ENOUGH | The available deposit is not enough for the transfer. | The available deposit is not enough for the transfer, please top up or reduce the transfer amount |
| TRANSFER\_AMOUNT\_EXCEED\_LIMIT | The transfer amount has exceeded the limit. | The transfer amount has exceeded the limit, please reduce the transfer amount |
| TRANSFER\_IS\_CORPORATE\_USER\_ERROR | The recipient account is corporate user. | The recipient account is corporate user, please change the recipient account |
| TRANSFER\_ERROR\_NATION\_NOT\_IN\_LIST | The current user's region does not have permission to access, please contact the lazada tech team. | The current user's region does not have permission to access, please contact the lazada tech team |
| RISK\_REJECT | The transfer recipient's account status is abnormal, please check | The transfer recipient's account status is abnormal, please check |
| TRANSFER\_WITHDRAWABLE\_UNMATCHED | Transfer withdrawable does not match. | Transfer withdrawable does not match. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/wallet/transfer/request)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/wallet/transfer/request

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/wallet/transfer/request");
request.addApiParameter("amount", "0.01");
request.addApiParameter("transfer_order_id", "test001");
request.addApiParameter("account_number", "09160000001");
request.addApiParameter("withdrawable", "true");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "account_number": "lzd_test_001@tom.com",
  "amount": "0.01",
  "code": "0",
  "withdrawable": "true",
  "transfer_order_id": "test001",
  "transfer_request_id": "open_100100_test001_id",
  "deposit": "99.99",
  "request_id": "0ba2887315178178017221014"
}
```
