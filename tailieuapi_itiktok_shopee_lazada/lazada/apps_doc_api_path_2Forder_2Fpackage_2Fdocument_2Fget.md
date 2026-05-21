# GET/POSTPrintAWB

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Fpackage%2Fdocument%2Fget
> API path: /order/package/document/get
> Category: Fulfillment API
> Scraped: 2026-05-20T23:27:28.809Z

---

Latest update2022-08-09 14:20:38

21375

PrintAWB

GET/POST

/order/package/document/get

Authorization Required

Description:Use this API to retrieve order-related documents, only for shipping labels.

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
| getDocumentReq | Object | Yes | request body |
| doc\_type | String | Yes | HTML/PDF |
| packages | Object\[\] | Yes | Batch size is limited to 20 |
| package\_id | String | Yes | package |
| print\_item\_list | Boolean | No | if is true, print package AWB with package item info, else no print package item info |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | resp body |
| data | Object | resp body |
| file | String | pdf /html content |
| doc\_type | String | HTML/PDF |
| pdf\_url | String | pdf file url , only exist when doc\_type is PDF |
| success | Boolean | process result |
| error\_code | String | exists when success is false |
| error\_msg | String | exists when success is false |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/package/document/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/order/package/document/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/package/document/get");
request.addApiParameter("getDocumentReq", "{\"doc_type\":\"PDF\",\"print_item_list\":\"false\",\"packages\":[{\"package_id\":\"FP234234\"},{\"package_id\":\"FP234234\"}]}");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "error_msg": "package not found",
    "data": {
      "file": "PGlmcmFtZSBzcm",
      "pdf_url": "http://www.test.com/xxx.pdf",
      "doc_type": "PDF"
    },
    "success": "true",
    "error_code": "123"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
