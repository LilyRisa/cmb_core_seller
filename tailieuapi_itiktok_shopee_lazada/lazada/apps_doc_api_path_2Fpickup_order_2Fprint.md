# GET/POSTPrintPickuoOrder

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpickup_order%2Fprint
> API path: /pickup_order/print
> Category: Choice Customized API
> Scraped: 2026-05-21T00:10:48.429Z

---

Latest update2024-01-25 11:06:23

1557

PrintPickuoOrder

GET/POST

/pickup\_order/print

Authorization Required

Description:Print Pickuo Order.

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
| pickup\_order\_no | String | Yes | 揽收单号 |
| pdf\_size | String | Yes | pdf格式枚举类型。A4纸大小样式、100\*100大小样式。{PICKUP\_A4/PICKUP\_1010} |
| box\_number | String | Yes | 装箱数量。（最大值 100） |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object | data |
| file | String | pdf文件下载路径。{文件下载url有过期时间，过期后需要重新调用生成文件url} |
| success | Boolean | true |
| error\_message | String | error msg |
| error\_code | String | error code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/pickup_order/print)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/pickup\_order/print

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/pickup_order/print");
request.addApiParameter("pickup_order_no", "pickup_order_no");
request.addApiParameter("pdf_size", "PICKUP_A4");
request.addApiParameter("box_number", "1");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "error_message": "null",
    "data": {
      "file": "http://url"
    },
    "success": "true",
    "error_code": "null"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
