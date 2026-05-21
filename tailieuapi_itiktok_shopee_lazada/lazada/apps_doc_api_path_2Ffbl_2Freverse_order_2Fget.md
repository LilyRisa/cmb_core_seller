# GETQueryReverseOrderForMCL

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Freverse_order%2Fget
> API path: /fbl/reverse_order/get
> Category: FBL API
> Scraped: 2026-05-20T23:43:18.013Z

---

Latest update2022-07-29 12:50:57

2256

QueryReverseOrderForMCL

GET

/fbl/reverse\_order/get

Authorization Required

Description:Query Reverse Order for MCL

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
| sales\_order\_number | String | Yes | Sales order number from platform |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Whether Success |
| error\_message | String | Error Message |
| data | Object\[\] | Result Data |
| sales\_order\_number | String | Sales order number from platform |
| create\_time | String | Reverse order create time in ISO8601 format |
| type | String | Reverse order type |
| status | String | Reverse order status |
| items | Object\[\] | Items in reverse order |
| fulfillment\_sku\_id | Number | Fulfillment sku id |
| fulfillment\_sku\_code | String | Fulfillment sku code |
| quantity | Number | Item number |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/reverse_order/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/reverse\_order/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/reverse_order/get");
request.setHttpMethod("GET");
request.addApiParameter("sales_order_number", "123456");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "Internal system error",
  "code": "0",
  "data": [
    {
      "sales_order_number": "123456",
      "create_time": "2021-10-27T03:07:00.000Z",
      "type": "failed_delivery, customer_return",
      "items": [
        {
          "quantity": "1",
          "fulfillment_sku_id": "12345678",
          "fulfillment_sku_code": "12345678_PH-12345678"
        }
      ],
      "status": "request_created, request_accepted, completed_reinbounded, request_cancelled"
    }
  ],
  "success": "true",
  "request_id": "0ba2887315178178017221014"
}
```
