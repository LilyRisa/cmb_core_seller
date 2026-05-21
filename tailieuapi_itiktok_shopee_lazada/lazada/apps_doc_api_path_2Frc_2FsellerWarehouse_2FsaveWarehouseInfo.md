# GET/POSTsaveSellerWarehouseInfo

> Source: https://open.lazada.com/apps/doc/api?path=%2Frc%2FsellerWarehouse%2FsaveWarehouseInfo
> API path: /rc/sellerWarehouse/saveWarehouseInfo
> Category: Seller API
> Scraped: 2026-05-20T23:05:57.440Z

---

Latest update2024-03-21 19:51:15

2728

saveSellerWarehouseInfo

GET/POST

/rc/sellerWarehouse/saveWarehouseInfo

Authorization Required

Description:Api to create or edit the seller warehouse info except the "default" dropshipping warehouse and the return warehouse.

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
| ownerType | Number | Yes | the fixed value is 0 |
| sellerId | Number | Yes | seller id |
| warehouseOwnerType | String | Yes | the fixed value is SELLER |
| warehouseContactDTO | Object | Yes | address info |
| phoneNumber | String | Yes | phone |
| email | String | Yes | email |
| siteId | String | Yes | site id |
| warehouseAddressInfoDTO | Object | Yes | address info |
| locationLevel2Label | String | Yes | province |
| address | String | Yes | address detail |
| locationLevel4Label | String | Yes | district |
| locationLevel3Label | String | Yes | city |
| postalCode | String | Yes | postal code |
| latitude | Number | No | latitude |
| countryIosCode | String | Yes | currencyCode |
| defaultAddress | Number | Yes | the fixed value is 0 |
| longitude | Number | No | longitude |
| warehouseType | Number | Yes | the fixed value is 200 |
| ownerId | Number | Yes | seller id |
| warehouseName | String | Yes | warehouse name |
| currencyCode | String | Yes | currency code |
| resourceType | Number | Yes | resourceType - the fixed value is 1. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| not\_success | Boolean | not success |
| success | Boolean | success |
| module | Boolean | true of false for the create or update result |
| repeated | Boolean | repeated |
| retry | Boolean | retry |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/rc/sellerWarehouse/saveWarehouseInfo)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/rc/sellerWarehouse/saveWarehouseInfo

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/rc/sellerWarehouse/saveWarehouseInfo");
request.addApiParameter("ownerType", "0");
request.addApiParameter("sellerId", "123456");
request.addApiParameter("warehouseOwnerType", "SELLER");
request.addApiParameter("warehouseContactDTO", "{\"phoneNumber\":\"0918071972\",\"email\":\"325792375@qq.com\"}");
request.addApiParameter("siteId", "VN");
request.addApiParameter("warehouseAddressInfoDTO", "{\"locationLevel2Label\":\"H\u1ED3 Ch\u00ED Minh\",\"address\":\"275B \u0110\u01B0\u1EDDng Ph\u1EA1m Ng\u0169 L\u00E3o, Ph\u01B0\u1EDDng Ph\u1EA1m Ng\u0169 L\u00E3o, Qu\u1EADn 1, H\u1ED3 Ch\u00ED Minh, Vi\u1EC7t Nam\",\"locationLevel4Label\":\"Ph\u01B0\u1EDDng Ph\u1EA1m Ng\u0169 L\u00E3o, Qu\u1EADn 1\",\"locationLevel3Label\":\"H\u1ED3 Ch\u00ED Minh\",\"postalCode\":\"453636\",\"latitude\":\"3.456\",\"countryIosCode\":\"VN\",\"defaultAddress\":\"0\",\"longitude\":\"3.456\"}");
request.addApiParameter("warehouseType", "200");
request.addApiParameter("ownerId", "32525");
request.addApiParameter("warehouseName", "STORE1");
request.addApiParameter("currencyCode", "VN");
request.addApiParameter("resourceType", "1");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "not_success": "false",
    "success": "true",
    "module": "true",
    "repeated": "false",
    "retry": "false"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
