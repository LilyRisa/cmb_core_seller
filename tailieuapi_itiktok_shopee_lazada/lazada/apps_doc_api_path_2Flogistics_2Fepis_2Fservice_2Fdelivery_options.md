# GETEpisGetDeliveryOptions

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fservice%2Fdelivery_options
> API path: /logistics/epis/service/delivery_options
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:47:11.761Z

---

Latest update2022-07-26 00:17:45

5002

EpisGetDeliveryOptions

GET

/logistics/epis/service/delivery\_options

No Authorization Required

Description:External partner call EPIS to get delivery options for package

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
| fromLocation | Object | No | Origin geo location |
| latitude | String | Yes | Latitude |
| longitude | String | Yes | Longitude |
| toLocation | Object | No | Destination geo location |
| latitude | String | Yes | Latitude |
| longitude | String | Yes | Longitude |
| shipper | Object | Yes | Shipper information |
| externalSellerId | String | Yes | External seller ID |
| platformName | String | No | Platform where seller order comes from |
| externalWarehouseCode | String | No | External warehouse code |
| dimWeight | Object | Yes | Package level dimweight |
| length | String | Yes | length Unit: centimeter |
| width | String | Yes | width Unit: centimeter |
| weight | String | Yes | weight Unit: gram |
| height | String | Yes | height Unit: centimeter |
| origin | Object | Yes | Origin info |
| details | String | Yes | Origin address detail |
| id | String | Yes | Origin address id Lazada Last level (level 4 / 5) RCode. |
| destination | Object | Yes | Destination info |
| details | String | Yes | Destination address detail |
| id | String | Yes | Destination address id Lazada Last level (level 4 / 5) RCode. |
| payment | Object | Yes | Payment info |
| totalAmount | String | Yes | Payment total amount |
| currency | String | Yes | Payment currency. Example: VND,SGD,USD,... |
| paymentType | String | Yes | Payment type. Enum \[COD, Non-COD\] |
| packageType | String | No | Package type. \[Sales\_order, Customer\_return\] |
| deliveryOption | String | No | Delivery service type. Enum \[standard, economy\] |
| externalOrderId | String | No | Order Id from external |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object\[\] | Response data |
| deliveryOption | String | Delivery service type. |
| firstMileDeliveryType | String | Pickupp or Drop-off |
| pickupTargetCutoffTime | String | Pickup target cutoff timestamp |
| firstMileShippingProvider | String | Firstmile shipping provider name |
| firstMileShippingProviderSlug | String | Firstmile shipping provider slug |
| lastMileShippingProvider | String | Lastmile shipping provider name |
| lastMileShippingProviderSlug | String | Lastmile shipping provider slug |
| retryable | Boolean | Is failed request retryable? |
| traceId | String | Trace id for debugging |
| success | Boolean | Is success? |
| errorMessage | String | Error message |
| errorCode | String | Error code |
| errors | Object\[\] | Detail errors |
| field | String | When validation failed on field, the error field path will be included, begin with "$." as root object |
| errorMessage | String | Detail error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/service/delivery_options)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/logistics/epis/service/delivery\_options

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/service/delivery_options");
request.setHttpMethod("GET");
request.addApiParameter("fromLocation", "{\"latitude\":\"10.7776331\",\"longitude\":\"106.7116815\"}");
request.addApiParameter("toLocation", "{\"latitude\":\"10.7776331\",\"longitude\":\"106.7116815\"}");
request.addApiParameter("shipper", "{\"externalSellerId\":\"001231321\",\"platformName\":\"Platform_XXX\",\"externalWarehouseCode\":\"VN-0000001\"}");
request.addApiParameter("dimWeight", "{\"length\":\"12.3\",\"width\":\"11.2\",\"weight\":\"334\",\"height\":\"4.4\"}");
request.addApiParameter("origin", "{\"details\":\"199 \u0110i\u1EC7n Bi\u00EAn Ph\u1EE7 Qu\u1EADn B\u00ECnh Th\u1EA1nh TP.H\u1ED3 Ch\u00ED Minh\",\"id\":\"R123765\"}");
request.addApiParameter("destination", "{\"details\":\"199 \u0110i\u1EC7n Bi\u00EAn Ph\u1EE7 Qu\u1EADn B\u00ECnh Th\u1EA1nh TP.H\u1ED3 Ch\u00ED Minh\",\"id\":\"R123765\"}");
request.addApiParameter("payment", "{\"totalAmount\":\"4.5\",\"currency\":\"SGD\",\"paymentType\":\"COD\"}");
request.addApiParameter("packageType", "Sales_order");
request.addApiParameter("deliveryOption", "standard");
request.addApiParameter("externalOrderId", "EXTERNAL_001");
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
  "data": [
    {
      "lastMileShippingProvider": "LEX VN",
      "firstMileShippingProviderSlug": "lex-vn",
      "firstMileDeliveryType": "Pickupp",
      "firstMileShippingProvider": "LEX VN",
      "deliveryOption": "standard",
      "pickupTargetCutoffTime": "1656329708068",
      "lastMileShippingProviderSlug": "lex-vn"
    }
  ],
  "success": "false",
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
