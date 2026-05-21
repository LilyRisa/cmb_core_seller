# GET/POSTRedeemOrderItems

> Source: https://open.lazada.com/apps/doc/api?path=%2Feticket%2Fcode%2Fconsume
> API path: /eticket/code/consume
> Category: E-Tickets API
> Scraped: 2026-05-20T23:53:37.377Z

---

Latest update2026-05-21 07:53:30

500

RedeemOrderItems

GET/POST

/eticket/code/consume

Authorization Required

Description:Certificate Consume Open API

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
| biz\_type | Number | Yes | biz type |
| code | String | Yes | certificate code |
| outer\_id | String | Yes | outer id |
| serial\_num | String | Yes | consume serial number |
| consume\_num | Number | Yes | consume num |
| store\_id | String | No | store id |
| pos\_id | String | No | pos id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | response body |
| outer\_id | String | outer id |
| serial\_num | String | consume serial number |
| left\_num | Number | code left available num |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 100 | E100: Param Invalid, "%s" | Param invalid |
| 101 | E101: Redemption Operator Invalid | The certificate not belongs to the seller |
| 200 | E200: Certificate Not Exist | Certificate not exist |
| 202 | E202: Certificate Can Not Distinguish | Can't distinguish the business type of this code |
| 203 | E203: Certificate Order Not Exist | No matched certificate of the outerId |
| 301 | E301: Certificate Not Available | Certificate status available, can't redeem |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/eticket/code/consume)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/eticket/code/consume

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/eticket/code/consume");
request.addApiParameter("biz_type", "5107");
request.addApiParameter("code", "f12csfds");
request.addApiParameter("outer_id", "FO1fsdjhk123");
request.addApiParameter("serial_num", "3451c641-a7da-4264-92cb-78a1f79392c3");
request.addApiParameter("consume_num", "1");
request.addApiParameter("store_id", "123456");
request.addApiParameter("pos_id", "POS1");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "left_num": "0",
    "outer_id": "FO1fsdjhk123",
    "serial_num": "3451c641-a7da-4264-92cb-78a1f79392c3"
  },
  "request_id": "0ba2887315178178017221014"
}
```
