# POSTQueryAccountTransactions

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffinance%2Ftransaction%2FaccountTransactions%2Fquery
> API path: /finance/transaction/accountTransactions/query
> Category: Finance API
> Scraped: 2026-05-20T23:31:49.406Z

---

Latest update2022-07-28 16:52:59

17486

QueryAccountTransactions

POST

/finance/transaction/accountTransactions/query

Authorization Required

Description:Query Account Transactions

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
| transaction\_type | String | No | transaction type,Enumeration values for(Deposit,Withdrawal,Payment,null) |
| sub\_transaction\_type | String | No | sub transaction type,Enumeration values for(Settlement,Failed Payment,Returned Payment,Auto Withdrawal,Manual Withdrawal,Sponsored Solutions Top-up,null) |
| transaction\_number | String | No | transaction number |
| page\_size | Number | Yes | page size |
| start\_time | String | Yes | start time,format:yyyyMMdd |
| end\_time | String | Yes | start time,format:yyyyMMdd |
| page\_num | Number | Yes | page number |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| msg | String | error message |
| data | Object | result |
| page\_info | Object | page Info |
| page\_num | Number | pageNum |
| page\_size | Number | pageSize |
| total\_page | Number | totalPage |
| total\_count | Number | totalCount |
| transactions | Object\[\] | transactions |
| pmt\_reference | String | pmt reference |
| transaction\_number | String | trading serial number |
| transaction\_time | String | trading occurred time |
| type | String | trading type |
| sub\_type | String | trading sub type |
| payee\_account | Object | payee Account |
| account | String | payee Account |
| description | String | description |
| amount | String | amount |
| currency | String | currency |
| remarks | String | remarks |
| tracking\_list | Object\[\] | tracking list |
| name | String | The name of the state |
| status | String | Configuration of multilingual copywriting |
| update\_time | String | Update time |
| remark | String | remark |
| success | Boolean | success:true,fail:false |
| error\_code | String | error code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| IllegalAccessToken | The specified access token is invalid or expired | access token is invalid or expired |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/finance/transaction/accountTransactions/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/finance/transaction/accountTransactions/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/finance/transaction/accountTransactions/query");
request.addApiParameter("transaction_type", "Deposit");
request.addApiParameter("sub_transaction_type", " Deposit");
request.addApiParameter("transaction_number", " 1001");
request.addApiParameter("page_size", "10");
request.addApiParameter("start_time", "20220601");
request.addApiParameter("end_time", "20220602");
request.addApiParameter("page_num", "1");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "msg": "null",
  "code": "0",
  "data": {
    "page_info": {
      "total_count": "1000",
      "total_page": "100",
      "page_num": "1",
      "page_size": "10"
    },
    "transactions": [
      {
        "pmt_reference": "Bank Ref. TH1JHIY41G-20230220",
        "payee_account": {
          "description": "description",
          "account": "1001"
        },
        "amount": "±0.01",
        "sub_type": "Penarikan Dana Otomatis",
        "transaction_number": "10000001",
        "transaction_time": "2022-01-01 00:00:00",
        "currency": "IDR",
        "tracking_list": [
          {
            "update_time": "2022-01-01 00:00:00",
            "name": "WITHDRAWAL_INITIATED",
            "remark": "remark",
            "status": "Penarikan Dana Dibuat"
          }
        ],
        "type": "Penarikan Dana",
        "remarks": " remarks"
      }
    ]
  },
  "success": "true",
  "error_code": " error_code",
  "request_id": "0ba2887315178178017221014"
}
```
