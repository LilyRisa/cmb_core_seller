# GET/POSTOpenServiceWithdrawQuery

> Source: https://open.lazada.com/apps/doc/api?path=%2Fwallet%2Fopen%2Fservice%2Fwithdraw%2Fquery
> API path: /wallet/open/service/withdraw/query
> Category: LazPay API
> Scraped: 2026-05-20T23:56:59.562Z

---

Latest update2023-02-07 11:13:51

2325

OpenServiceWithdrawQuery

GET/POST

/wallet/open/service/withdraw/query

Authorization Required

Description:Open Service Withdraw Query

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
| withdraw\_request\_id | String | Yes | ISV withdraw request id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| withdraw\_request\_id | String | ISV withdraw request id |
| withdraw\_id | String | Lazada withdraw id |
| withdraw\_amount | String | withdraw amount，precise to two decimal places. |
| withdrawable | String | withdrawable feature |
| currency | String | currency |
| partner\_deposit | String | The available balance of ISV |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/wallet/open/service/withdraw/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/wallet/open/service/withdraw/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/wallet/open/service/withdraw/query");
request.addApiParameter("withdraw_request_id", "test001");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "withdraw_id": "openDT_WD_123456_test001_id",
  "withdraw_amount": "0.01",
  "code": "0",
  "withdrawable": "true",
  "partner_deposit": "1888.88",
  "currency": "PHP",
  "withdraw_request_id": "test001",
  "request_id": "0ba2887315178178017221014"
}
```
