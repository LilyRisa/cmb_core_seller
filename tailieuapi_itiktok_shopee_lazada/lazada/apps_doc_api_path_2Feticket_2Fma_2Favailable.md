# GET/POSTGlobalEticketMerchantMaAvailable

> Source: https://open.lazada.com/apps/doc/api?path=%2Feticket%2Fma%2Favailable
> API path: /eticket/ma/available
> Category: E-Tickets API
> Scraped: 2026-05-20T23:52:25.024Z

---

Latest update2022-07-26 00:18:41

2438

GlobalEticketMerchantMaAvailable

GET/POST

/eticket/ma/available

Authorization Required

Description:the callback interface before consume code

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
| code | String | Yes | waiting consume code |
| serial\_num | String | Yes | consume serialVersionUID |
| pos\_id | String | No | consume tools no |
| outer\_id | String | Yes | order id |
| consume\_num | Number | Yes | consume num |
| consume\_store\_id | String | Yes | consume store id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| resp\_body | Object | response |
| attribute\_map | Object | attribute\_map |
| ret\_code | String | sub code |
| ret\_msg | String | sub info |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/eticket/ma/available)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/eticket/ma/available

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/eticket/ma/available");
request.addApiParameter("biz_type", "3001");
request.addApiParameter("code", "1c31ce6d1dc1691fc211");
request.addApiParameter("serial_num", "sn1238475");
request.addApiParameter("pos_id", "2132312");
request.addApiParameter("outer_id", "35900200436004");
request.addApiParameter("consume_num", "1");
request.addApiParameter("consume_store_id", "143003");
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
    "attribute_map": {}
  },
  "ret_msg": "success",
  "ret_code": "isv.success-all",
  "request_id": "0ba2887315178178017221014"
}
```
