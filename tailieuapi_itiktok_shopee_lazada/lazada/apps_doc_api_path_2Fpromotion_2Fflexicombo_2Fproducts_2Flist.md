# GETListFlexiComboProducts

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpromotion%2Fflexicombo%2Fproducts%2Flist
> API path: /promotion/flexicombo/products/list
> Category: Flexicombo API
> Scraped: 2026-05-20T23:17:19.196Z

---

Latest update2022-07-29 14:20:58

3176

ListFlexiComboProducts

GET

/promotion/flexicombo/products/list

Authorization Required

Description:list flexi combo products

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
| cur\_page | Number | Yes | current page |
| page\_size | Number | Yes | page size;Maximum value: 100; Minimum value: 10 |
| id | Number | Yes | flexi combo id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | data |
| total | Number | total |
| current | Number | current |
| data\_list | Number\[\] | data\_list |
| page\_size | Number | page\_size |
| success | Boolean | true|false |
| error\_code | String | error\_code |
| error\_msg | String | error\_msg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 21 | E021: Internal System Error | Internal System Error |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/promotion/flexicombo/products/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/promotion/flexicombo/products/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/promotion/flexicombo/products/list");
request.setHttpMethod("GET");
request.addApiParameter("cur_page", "1");
request.addApiParameter("page_size", "10");
request.addApiParameter("id", "9616200353530");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "null",
  "code": "0",
  "data": {
    "data_list": [],
    "total": "6",
    "current": "1",
    "page_size": "10"
  },
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
