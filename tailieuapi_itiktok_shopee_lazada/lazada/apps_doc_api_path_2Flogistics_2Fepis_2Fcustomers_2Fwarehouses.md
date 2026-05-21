# POSTCreateOrUpdateCustomerWarehouse

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fcustomers%2Fwarehouses
> API path: /logistics/epis/customers/warehouses
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:46:56.769Z

---

Latest update2022-09-19 03:08:56

5034

CreateOrUpdateCustomerWarehouse

POST

/logistics/epis/customers/warehouses

No Authorization Required

Description:External partner calls LAZADA to create or update warehouses

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
| externalSellerId | String | Yes | External seller ID |
| platformName | String | Yes | Platform name |
| warehouseCode | String | Yes | External warehouse code |
| warehouseName | String | Yes | Warehouse name |
| contactName | String | Yes | Warehouse contact name |
| phone | String | Yes | Warehouse contact phone number. If no country phone prefix, EPIS will append the country prefix of current country |
| email | String | No | Warehouse contact email |
| type | String | Yes | Enum: NORMAL / RETURN |
| address | Object | Yes | Warehouse address |
| id | String | Yes | Lazada last level (level 4 / 5) RCode. |
| details | String | Yes | Address details |
| solutionCodes | String\[\] | Yes | List of Lazada solution codes. Enum \[LAZADA\_STANDARD\_VN, LAZADA\_BULKY\_VN\] |
| configuration | Object | No | Warehouse configuration |
| deliveryNote | String | No | Warehouse default delivery note |
| services | Object\[\] | No | VAS options |
| serviceName | String | Yes | service name |
| enable | Boolean | Yes | enable service or not |
| properties | String | No | service properties |
| dropshippingInfo | Object | No | drop shipping info |
| originPartnerName | String | No | Parnert Name |
| originPlatformName | String | No | Platform Name |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| retryable | Boolean | Is fail request retryable |
| traceId | String | Trace ID for debugging |
| success | Boolean | Request success or not |
| errorMessage | String | Error message |
| errorCode | String | Error code |
| errors | Object\[\] | Error field |
| field | String | Detail error message |
| errorMessage | String | When validation failed on field, the error field path will be included, begin with "$." as root object |
| data | Object | Response |
| convertedAddress | Object | Converted warehouse address (Viet Nam address tree update) |
| id | String | Lex address ID |
| details | String | Address detail |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/customers/warehouses)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/epis/customers/warehouses

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/customers/warehouses");
request.addApiParameter("externalSellerId", "L001231321");
request.addApiParameter("platformName", "OneLink");
request.addApiParameter("warehouseCode", "L0000001");
request.addApiParameter("warehouseName", "OMS Warehouse HN");
request.addApiParameter("contactName", "John Wick");
request.addApiParameter("phone", "+84090123123");
request.addApiParameter("email", "email@gmail.com");
request.addApiParameter("type", "NORMAL");
request.addApiParameter("address", "{\"details\":\"199 \u0110i\u1EC7n Bi\u00EAn Ph\u1EE7 Qu\u1EADn B\u00ECnh Th\u1EA1nh TP.H\u1ED3 Ch\u00ED Minh\",\"id\":\"R123765\"}");
request.addApiParameter("solutionCodes", "[\"LAZADA_STANDARD_VN\"]");
request.addApiParameter("configuration", "{\"deliveryNote\":\"Warehouse default delivery note\",\"services\":[{\"enable\":\"true\",\"serviceName\":\"vas_fd_storage\",\"properties\":\"{\\\"fd_storage_days\\\": \\\"4\\\"}\"},{\"enable\":\"true\",\"serviceName\":\"vas_fd_storage\",\"properties\":\"{\\\"fd_storage_days\\\": \\\"4\\\"}\"}]}");
request.addApiParameter("dropshippingInfo", "{\"originPlatformName\":\"Pancake\",\"originPartnerName\":\"Pancake\"}");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "retryable": "false",
  "traceId": "0ba2887315172940728551014",
  "code": "0",
  "data": {
    "convertedAddress": {
      "details": "62 Nam Ky Khoi Nghia",
      "id": "R12345"
    }
  },
  "success": "true",
  "errorMessage": "Bad request",
  "errorCode": "BAD_REQUEST",
  "request_id": "0ba2887315178178017221014",
  "errors": [
    {
      "field": "$.items.name",
      "errorMessage": "name must not be blank"
    }
  ]
}
```
