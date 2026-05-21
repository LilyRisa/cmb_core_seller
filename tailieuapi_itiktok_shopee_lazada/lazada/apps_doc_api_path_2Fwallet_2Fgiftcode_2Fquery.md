# GET/POSTGiftCodeQuery

> Source: https://open.lazada.com/apps/doc/api?path=%2Fwallet%2Fgiftcode%2Fquery
> API path: /wallet/giftcode/query
> Category: Lazada Wallet Corporate Top-up API
> Scraped: 2026-05-20T23:58:50.253Z

---

Latest update2022-07-26 00:18:26

2655

GiftCodeQuery

GET/POST

/wallet/giftcode/query

No Authorization Required

Description:Gift Code - Query

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
| page | Number | Yes | The page to query, page should > 0 and < the total pages, default value is 1 if this parameter is null. |
| transfer\_order\_id | String | Yes | Transfer order Id on the ISV side, length <= 32 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| records | String\[\] | The list of gift codes, need to finish unmask verification firstly. |
| total\_page | Number | The total page number of the code list |
| current\_page | Number | The current queried page of the code list |
| page\_size | Number | The default max number of codes contained in one page. |
| transfer\_order\_id | String | Transfer order Id on the ISV side, length <= 32 |
| total\_number | String | The amount of created gift code, precise to two decimal places |
| create\_status | String | The create status of the gift code |
| deposit | String | The available balance of ISV |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| GIFT\_CODE\_LOCK\_CONFLICT | Gift code is already being created，please wait for a moment and check the batch list | Gift code is already being created，please wait for a moment and check the batch list |
| OPEN\_API\_CALL\_EXCEED\_LIMIT | Open Api call times exceeds: apiName\_limitType | Open Api call times exceeds: apiName\_limitType |
| PROCEED\_TRANSFER\_EXCEPTION | Internal error, please retry or contact lazada tech team. | Internal error, please retry or contact lazada tech team. |
| USER\_IS\_NOT\_LOGGED\_IN | The user is not logged in | The user is not logged in |
| APP\_KEY\_INVALID | App key is invalid, please contact lazada tech team. | App key is invalid, please contact lazada tech team. |
| TRANSFER\_ERROR\_TRANSFER\_ORDER\_ID\_INVALID | Transfer order ID is invalid | Transfer order ID is invalid |
| GIFT\_CODE\_QUERY\_EMPTY | There are no such gift code | There are no such gift code |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/wallet/giftcode/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/wallet/giftcode/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/wallet/giftcode/query");
request.addApiParameter("page", "1");
request.addApiParameter("transfer_order_id", "test001");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "total_number": "0.01",
  "code": "0",
  "records": [],
  "transfer_order_id": "test001",
  "total_page": "5",
  "create_status": "SUCCESS",
  "deposit": "99.99",
  "request_id": "0ba2887315178178017221014",
  "current_page": "1",
  "page_size": "100"
}
```
