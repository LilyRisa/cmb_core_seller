# GET/POSTGlobalEticketMerchantMaQuery

> Source: https://open.lazada.com/apps/doc/api?path=%2Feticket%2Fma%2Fquery
> API path: /eticket/ma/query
> Category: E-Tickets API
> Scraped: 2026-05-20T23:52:57.500Z

---

Latest update2022-07-26 00:18:58

2280

GlobalEticketMerchantMaQuery

GET/POST

/eticket/ma/query

Authorization Required

Description:the callback interface that query lazada platform ma

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
| code | String | Yes | code |
| seller\_id | Number | Yes | sellerId |
| store\_id | Number | No | storeId |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| resp\_body | Object | response |
| certificate | Object | certificate |
| locked\_num | Number | lockedNum |
| biz\_type | Number | bizType |
| certificate\_code | String | code |
| initial\_num | Number | initialNum |
| available\_num | Number | availableNum |
| consume\_status | String | consumeStatus |
| code\_status | String | codeStatus |
| qr\_code\_url | String | qrCodeUrl |
| outer\_id | String | outerId |
| start\_time | Number | startTime |
| end\_time | Number | endTime |
| used\_num | Number | usedNum |
| attributes | Object | attributes |
| ret\_code | String | ret code |
| ret\_msg | String | ret msg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/eticket/ma/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/eticket/ma/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/eticket/ma/query");
request.addApiParameter("code", "abcabc");
request.addApiParameter("seller_id", "123123");
request.addApiParameter("store_id", "123");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "resp_body": {
    "certificate": {
      "certificate_code": "a84086489cdb4744d4df",
      "initial_num": "1",
      "biz_type": "3001",
      "end_time": "1600963199",
      "outer_id": "37009000236004",
      "qr_code_url": "null",
      "locked_num": "0",
      "start_time": "1598284800",
      "available_num": "1",
      "used_num": "0",
      "attributes": {},
      "consume_status": "1",
      "code_status": "1"
    }
  },
  "ret_msg": "操作成功",
  "ret_code": "isv.success-all",
  "request_id": "0ba2887315178178017221014"
}
```
