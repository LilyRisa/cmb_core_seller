# GETGetCategoryTree

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcategory%2Ftree%2Fget
> API path: /category/tree/get
> Category: Product API
> Scraped: 2026-05-20T23:08:02.685Z

---

Latest update2022-07-28 17:07:38

19416

GetCategoryTree

GET

/category/tree/get

No Authorization Required

Description:Use this API to retrieve the list of all product categories in the system.

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
| language\_code | String | No | Language code indicates the type of language you would like to translate. Please note not all languages are available in every region. For example, in Indonesia, only English and Indonesia are available. If you are passing a language code which does not belong to your area, null value might receive. Please do make sure your language code is correct. Supported language codes are listed as below: English:"en\_US" - available in every area Singapore:"en\_SG" - available in Singapore Thailand"th\_TH" - available in Thailand Indonesia:"id\_ID" - available in Indonesia Vietnam:"vi\_VN" - available in Vietnam Philippines: "fil\_PH" - available in Philippines Malaysia : "ms\_MY" - available in Malaysia Default(if null is passed): "en\_US" |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object\[\] | Response body |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/category/tree/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/category/tree/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/category/tree/get");
request.setHttpMethod("GET");
request.addApiParameter("language_code", "en_US");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": [
    {
      "category_id": 6588,
      "children": [
        {
          "category_id": 7436,
          "var": true,
          "name": "Socks",
          "leaf": true
        },
        {
          "category_id": 7435,
          "var": true,
          "name": "Underwear",
          "leaf": true
        }
      ],
      "var": true,
      "name": "Socks \u0026 Tights",
      "leaf": false
    }
  ],
  "request_id": "0ba2887315178178017221014"
}
```
