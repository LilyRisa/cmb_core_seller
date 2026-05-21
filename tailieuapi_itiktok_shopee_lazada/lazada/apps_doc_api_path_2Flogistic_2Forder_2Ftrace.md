# GET/POSTGetOrderTrace

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistic%2Forder%2Ftrace
> API path: /logistic/order/trace
> Category: Logistics API
> Scraped: 2026-05-20T23:28:42.964Z

---

Latest update2022-07-29 11:58:45

16437

GetOrderTrace

GET/POST

/logistic/order/trace

Authorization Required

Description:Query logistic detail for seller erp with seller id, order id and locale info. This api is only available in the state after ready to ship.

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
| order\_id | String | Yes | order id |
| locale | String | No | local |
| ofcPackageIdList | String\[\] | No | package id list |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | Result |
| error\_code | Object | Error code |
| displayMessage | String | displayMessage |
| repeated | Boolean | Repeated |
| retry | Boolean | Retry |
| not\_success | Boolean | Not success |
| success | Boolean | Success |
| module | Object\[\] | Module |
| warehouse\_detail\_info | String | Warehouse detail info |
| ofc\_order\_id | String | ofc order id |
| package\_detail\_info\_list | Object\[\] | Package detail info list |
| order\_line\_info\_list | String | Order line info list |
| tracking\_number | String | Tracking number |
| ofc\_package\_id | String | ofc package id |
| logistic\_detail\_info\_list | Object\[\] | Logistic detail info list |
| package\_location\_name | String | Package location name |
| event\_date | String | Event date |
| detail\_type | String | Detail stauts type |
| proof\_images | Object\[\] | Proof images |
| receive\_time | Number | Receive time |
| status\_code | String | Status code |
| icon | String | icon |
| event\_time | Number | Event time |
| description | String | Description of status |
| title | String | title of status |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| INPUT\_PARAM\_VALID | query trade failed | order id or ofcPackageIdList invalid |
| LD\_INVOKE\_DOWNSTREAM\_RESPONSE\_BLANK | LD\_INVOKE\_DOWNSTREAM\_RESPONSE\_BLANK | This order does not exist in the current country or store, please call the GetOrders API to check if you have entered the correct order ID |
| Dropshipping invalid | input orderId: Own Warehouse invalid | The input parameters are incorrect, please check that the package id you entered in the ofcPackageIdList field is correct. |
| LD\_INPUT\_PARAM\_VALID | orderId is wrong | The order number does not exist in the current store or is incorrect, please check if the order number input format in your request is correct first, and then call the GetOrders API or check in the Seller Center if the order exists in the current requesting store. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistic/order/trace)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistic/order/trace

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistic/order/trace");
request.addApiParameter("order_id", "56150613585762");
request.addApiParameter("locale", "en");
request.addApiParameter("ofcPackageIdList", "[]");
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
    "module": [
      {
        "warehouse_detail_info": "null",
        "ofc_order_id": "null",
        "package_detail_info_list": [
          {
            "order_line_info_list": "List\u003cT\u003e",
            "ofc_package_id": "FP032211046428116",
            "tracking_number": "NLXSG20300914",
            "logistic_detail_info_list": [
              {
                "package_location_name": "null",
                "status_code": "1200",
                "proof_images": [],
                "detail_type": "ready_to",
                "event_date": "null",
                "receive_time": "0",
                "icon": "null",
                "description": "Your parcel has been packed and ready to be handed over to our shipping provider.",
                "title": "Packed by seller / warehouse",
                "event_time": "1625987646597"
              }
            ]
          }
        ]
      }
    ],
    "error_code": {
      "displayMessage": "null"
    },
    "repeated": "false",
    "retry": "false"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
