# GETGetWarehouseListForMCL

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fwarehouses%2Fget
> API path: /fbl/warehouses/get
> Category: FBL API
> Scraped: 2026-05-20T23:41:45.117Z

---

Latest update2026-05-21 07:41:36

500

GetWarehouseListForMCL

GET

/fbl/warehouses/get

No Authorization Required

Description:Get Warehouse List By Country And Multi-Channel

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
| country\_code | String | Yes | CountryCode |
| page | Number | Yes | PageIndex |
| per\_page | Number | Yes | Maximum number of results per page |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Success flag |
| error\_code | String | Error Code |
| error\_message | String | Error Message |
| page | Number | Page Index |
| per\_page | Number | Maximum number of results per page |
| total\_count | Number | Total count |
| total\_page | Number | Total page |
| data | Object\[\] | Warehouse list |
| warehouse\_name | String | Warehosue name |
| warehouse\_code | String | Warehouse code |
| platform\_name | String | Platform name |
| country\_code | String | Country ID |
| area\_code | String | Area code |
| city\_code | String | City code |
| town\_code | String | Town code |
| division\_id | String | The default address of the warehouse, when no explicit address is given, the default is used. |
| longitude | String | longitude |
| latitude | String | latitude |
| zip\_code | String | Postcode |
| multi\_channel | Boolean | Whether multi-channel warehouse |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/warehouses/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/warehouses/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/warehouses/get");
request.setHttpMethod("GET");
request.addApiParameter("country_code", "MY");
request.addApiParameter("page", "1");
request.addApiParameter("per_page", "20");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "Param invalid",
  "per_page": "20",
  "code": "0",
  "data": [
    {
      "country_code": "MY",
      "town_code": "R80020020",
      "warehouse_name": "Subang",
      "multi_channel": "TRUE",
      "warehouse_code": "OMS-LAZADA-MY-W-11",
      "area_code": "R2932285",
      "latitude": "111.1111",
      "platform_name": "LAZADA_SG , LAZADA_MY",
      "city_code": "R80017531",
      "division_id": "R80020020",
      "zip_code": "533864",
      "longitude": "12.234"
    }
  ],
  "success": "TRUE",
  "total_count": "20",
  "total_page": "10",
  "error_code": "PARAM_INVALID",
  "page": "1",
  "request_id": "0ba2887315178178017221014"
}
```
