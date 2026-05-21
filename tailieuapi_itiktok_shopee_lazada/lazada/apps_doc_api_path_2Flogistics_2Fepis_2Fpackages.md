# POSTEpisPackageCreation

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fpackages
> API path: /logistics/epis/packages
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:48:35.765Z

---

Latest update2022-07-28 17:09:50

11735

EpisPackageCreation

POST

/logistics/epis/packages

No Authorization Required

Description:External partner call EPIS to create package

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
| dangerousGood | Boolean | Yes | Is dangerous good. Boolean true/false |
| shipper | Object | Yes | Shipper information |
| externalSellerId | String | Yes | External seller ID |
| platformName | String | No | Platform where seller order comes from |
| externalWarehouseCode | String | No | External warehouse code |
| warehouseName | String | No | Warehouse name |
| dimWeight | Object | Yes | Package level dimweight |
| length | String | Yes | length Unit: centimeter |
| width | String | Yes | width Unit: centimeter |
| weight | String | Yes | weight Unit: gram |
| height | String | Yes | height Unit: centimeter |
| origin | Object | Yes | Origin info |
| address | Object | Yes | Origin address |
| details | String | Yes | Origin address detail |
| id | String | No | Origin address id Lazada Last level (level 4 / 5) RCode. |
| type | String | No | Origin address type. Enum \[work, home\] |
| city | String | No | city name |
| postcode | String | No | postcode |
| phone | String | Yes | Contact phone number |
| geoLocation | Object | No | Geo location |
| latitude | String | Yes | Latitude |
| longitude | String | Yes | Longitude |
| name | String | Yes | Contact name |
| email | String | No | Contact address |
| destination | Object | Yes | Destination info |
| address | Object | Yes | Destination address |
| details | String | Yes | Destination address detail |
| id | String | No | Destination address id Lazada Last level (level 4 / 5) RCode. |
| type | String | No | Origin address type. Enum \[work, home\] |
| city | String | No | city name |
| postcode | String | No | postcode |
| phone | String | Yes | Contact phone number |
| geoLocation | Object | No | Geo location |
| latitude | String | Yes | Latitude |
| longitude | String | Yes | Longitude |
| name | String | Yes | Contact name |
| email | String | No | Contact address |
| payment | Object | Yes | Payment info |
| totalAmount | String | Yes | Payment total amount |
| insuranceAmount | String | No | Payment insurance amount |
| currency | String | Yes | Payment currency. Example: VND,SGD,USD,... |
| paymentType | String | Yes | Payment type. Enum \[COD, NON-COD\] |
| paidEstimatedShippingFeeAmount | String | No | Paid estimated shipping fee amount |
| externalOrderId | String | Yes | External order id (uniquely identify partner's order). If Lazada receives mulitiple requests to create multiple orders with same externalOrderId then only the first arrived order information is recorded. All subsequent requests are treated as duplicated regardless the order information is changed or not. Therefore you can repush order, but cannot modify order information once it is already processed by Lazada |
| platformOrderCreationTime | Number | No | Unix timestamp in milliseconds. Default: Current timestamp when EPIS receives the request |
| packageType | String | No | Package type. Enum: \[Sales\_order, Customer\_return\] |
| deliveryOption | String | No | Delivery service type. Enum \[standard, economy, point\_to\_point\] |
| items | Object\[\] | Yes | Item list |
| unitPrice | String | Yes | Item price (exclude all vouchers, discount), affecting claiming if lost or damaged |
| quantity | Number | Yes | Item quantity |
| dimWeight | Object | No | Item level dimweight |
| length | String | Yes | length Unit: centimeter |
| width | String | Yes | width Unit: centimeter |
| weight | String | Yes | weight Unit: gram |
| height | String | Yes | height Unit: centimeter |
| name | String | Yes | Item name |
| id | String | No | External item ID |
| sku | String | No | Item SKU |
| category | String | No | Item category |
| paidPrice | String | Yes | Item paid price (include discount, voucher, etc...) |
| options | Object | No | Package options |
| directReturnToMerchant | Boolean | No | Set DRTM flag if package type is Customer\_return |
| forwardPackageCode | String | No | Forward package code |
| openBox | Boolean | No | Is mutual check allowed for this package |
| deliveryNote | String | No | delivery note |
| vasPartialDeliveryOption | Boolean | No | Partial delivery (optional) |
| vasFdStorageOption | Boolean | No | VAS DS Storage flag |
| orderSource | String | No | Order source |
| vasFdCallOption | Boolean | No | Vas FD call |
| vasExchangeOrderOption | Boolean | No | Vas exchange order |
| vasFdCollectShippingFeeOption | Boolean | No | Vas fd collect shipping fee |
| parcelCategories | String\[\] | No | Parcel categories |
| parcelDescription | String | No | Parcel description |
| scheduledPickupTime | Number | No | Unix timestamp in milliseconds. |
| exchangeOrder | Object | No | Exchange order |
| insuranceAmount | String | No | insurance amount |
| items | Object\[\] | No | exchange items |
| unitPrice | String | Yes | Item price (exclude all vouchers, discount), affecting claiming if lost or damaged) |
| quantity | String | Yes | Item quantity |
| dimWeight | Object | No | Item level dimweight |
| length | String | Yes | length Unit: centimeter |
| width | String | Yes | width Unit: centimeter |
| height | String | Yes | height Unit: centimeter |
| weight | String | Yes | weight Unit: gram |
| name | String | Yes | Item name |
| id | String | No | External seller ID |
| sku | String | No | Item SKU |
| category | String | No | Item category |
| paidPrice | String | Yes | Item paid price (include discount, voucher, etc...) |
| planInfo | Object | No | Partner can send tracking number to LEX |
| trackingNumber | String | No | LEX will use this tracking number for AWB |
| multiParcel | Object | No | Multi-parcel information |
| uniqueKey | String | Yes | Unique key for multi-parcels. All parcels which need to be delivered at the same time must share the same value. |
| sequenceNumber | String | Yes | Sequence number of this parcel |
| totalParcel | Number | Yes | Total number of parcels which have same Unique Key |
| sameTimeDelivery | String | No | Whether this parcel is required to be delivered at the same time. Default: true |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| retryable | Boolean | Is failed request retryable? |
| traceId | String | Trace id for debugging |
| success | Boolean | Is success? |
| errorMessage | String | Error message |
| errorCode | String | Error code |
| errors | Object\[\] | Detail errors |
| field | String | When validation failed on field, the error field path will be included, begin with "$." as root object |
| errorMessage | String | Detail error message |
| data | Object | Response data |
| packageCode | String | Unique package identifier generated by EPIS. Used to communicate between EPIS and external systems |
| trackingNumber | String | Tracking number generated by EPIS |
| portCode | String | Port code |
| routeCode | String | Routing code |
| firstMileShippingProvider | Object | First-mile shipping provider |
| tplCode | String | Shipping provider code |
| tplSlug | String | Shipping provider slug |
| tplName | String | Shipping provider name |
| lastMileShippingProvider | Object | Last-mile shipping provider |
| tplCode | String | Shipping provider code |
| tplSlug | String | Shipping provider slug |
| tplName | String | Shipping provider name |
| minEta | Number | Min promise time |
| maxEta | Number | Max promise time |
| options | Object | Response options |
| vasPartialDeliveryOptionNotAvailable | Boolean | If out of lex coverage , return "true" for partial delivery order |
| promotionCode | String | Promotion code |
| appliedVas | Object | Check this data to see which VAS is successfully applied to this order |
| vasFdStorageOption | Boolean | Is FD Storage VAS applied |
| vasFdCallOption | Boolean | Is FD Call VAS applied |
| vasFdCollectShippingFeeOption | Boolean | Is FD Collect Shipping Fee VAS applied |
| openBox | Boolean | Is Open Box VAS applied |
| vasPartialDeliveryOption | Boolean | Is Partial Delivery VAS applied |
| vasExchangeOrderOption | Boolean | Is Exchange Order VAS applied |
| aoiName | String | AOI Name |
| origin | Object | Converted origin address (VN address tree update) |
| id | String | LEX address ID |
| details | String | Address detail |
| destination | Object | Converted destination address (VN address tree update) |
| id | String | LEX address ID |
| details | String | Address detail |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| NO\_ROUTE\_ERROR | No suitable route found | origin-destination route is out of coverage |
| PARTIAL\_DELIVERY\_NOT\_AVAILABLE | Partial Delivery is not available because out of Lex coverage | Partial Delivery is not available because out of Lex coverage |
| INVALID\_ADDRESS\_ID | Provided address ID is not valid | Provided address ID is not valid |
| BAD\_REQUEST | Invalid request | Check error message for details |
| PARTNER\_NOT\_FOUND | No partner matches with provided information | Need to register partner information with Lazada before calling API |
| INTERNAL\_SYSTEM\_ERROR | Internal system error. Please try again | Internal system error. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/packages)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/epis/packages

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/packages");
request.addApiParameter("dangerousGood", "false");
request.addApiParameter("shipper", "{\"externalSellerId\":\"001231321\",\"platformName\":\"Platform_XXX\",\"warehouseName\":\"Kho Qu\u1EADn 1\",\"externalWarehouseCode\":\"VN-0000001\"}");
request.addApiParameter("dimWeight", "{\"length\":\"12.3\",\"width\":\"11.2\",\"weight\":\"334\",\"height\":\"4.4\"}");
request.addApiParameter("origin", "{\"address\":{\"city\":\"city name\",\"postcode\":\"postcode\",\"details\":\"199 \u0110i\u1EC7n Bi\u00EAn Ph\u1EE7 Qu\u1EADn B\u00ECnh Th\u1EA1nh TP.H\u1ED3 Ch\u00ED Minh\",\"id\":\"R123765\",\"type\":\"home\"},\"phone\":\"0972018000\",\"geoLocation\":{\"latitude\":\"10.7776331\",\"longitude\":\"106.7116815\"},\"name\":\"John\",\"email\":\"user@domain.com\"}");
request.addApiParameter("destination", "{\"address\":{\"city\":\"city name\",\"postcode\":\"postcode\",\"details\":\"199 \u0110i\u1EC7n Bi\u00EAn Ph\u1EE7 Qu\u1EADn B\u00ECnh Th\u1EA1nh TP.H\u1ED3 Ch\u00ED Minh\",\"id\":\"R123765\",\"type\":\"home\"},\"phone\":\"0972018000\",\"geoLocation\":{\"latitude\":\"10.7776331\",\"longitude\":\"106.7116815\"},\"name\":\"John\",\"email\":\"user@domain.com\"}");
request.addApiParameter("payment", "{\"totalAmount\":\"4.5\",\"insuranceAmount\":\"4.5\",\"paidEstimatedShippingFeeAmount\":\"4.5\",\"currency\":\"SGD\",\"paymentType\":\"COD\"}");
request.addApiParameter("externalOrderId", "FO073511135542999");
request.addApiParameter("platformOrderCreationTime", "1647857656396");
request.addApiParameter("packageType", "Sales_order");
request.addApiParameter("deliveryOption", "standard");
request.addApiParameter("items", "[{\"unitPrice\":\"6688\",\"quantity\":\"1\",\"dimWeight\":{\"length\":\"12.3\",\"width\":\"11.2\",\"weight\":\"334\",\"height\":\"4.4\"},\"name\":\"NYX Professional Makeup Can\\u0027t Stop Won\\u0027t Stop Liquid Matte Foundation\",\"id\":\"123123123\",\"sku\":\"1153678330_ID-181585888\",\"category\":\"Electronic\",\"paidPrice\":\"0\"}]");
request.addApiParameter("options", "{\"parcelDescription\":\"Parcel description \",\"orderSource\":\"MOBILE\",\"vasExchangeOrderOption\":\"true\",\"scheduledPickupTime\":\"1647857656396\",\"deliveryNote\":\"Delivery note\",\"parcelCategories\":[\"[\\\"Fashion\\\", \\\"Accessories\\\"]\",\"[\\\"Fashion\\\", \\\"Accessories\\\"]\"],\"vasFdStorageOption\":\"true\",\"vasPartialDeliveryOption\":\"true\",\"directReturnToMerchant\":\"true\",\"openBox\":\"true\",\"vasFdCollectShippingFeeOption\":\"true\",\"forwardPackageCode\":\"FU242008370000001917013936\",\"vasFdCallOption\":\"true\"}");
request.addApiParameter("exchangeOrder", "{\"insuranceAmount\":\"10.99\",\"items\":[{\"unitPrice\":\"66.88\",\"quantity\":\"1\",\"dimWeight\":{\"length\":\"12.3\",\"width\":\"11.2\",\"weight\":\"344\",\"height\":\"10.1\"},\"name\":\"NYX Professional Makeup Can\\u0027t Stop Won\\u0027t Stop Liquid Matte Foundation\",\"id\":\"123123123\",\"sku\":\"1153678330_ID-181585888\",\"category\":\"Electronic\",\"paidPrice\":\"0\"},{\"unitPrice\":\"66.88\",\"quantity\":\"1\",\"dimWeight\":{\"length\":\"12.3\",\"width\":\"11.2\",\"weight\":\"344\",\"height\":\"10.1\"},\"name\":\"NYX Professional Makeup Can\\u0027t Stop Won\\u0027t Stop Liquid Matte Foundation\",\"id\":\"123123123\",\"sku\":\"1153678330_ID-181585888\",\"category\":\"Electronic\",\"paidPrice\":\"0\"}]}");
request.addApiParameter("planInfo", "{\"trackingNumber\":\"MYX0000000001001\"}");
request.addApiParameter("multiParcel", "{\"sequenceNumber\":\"1\",\"sameTimeDelivery\":\"true\",\"totalParcel\":\"7\",\"uniqueKey\":\"UK1647857656396\"}");
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
    "routeCode": "X-ROUT-123",
    "maxEta": "1701104399000",
    "lastMileShippingProvider": {
      "tplSlug": "lex",
      "tplName": "lex",
      "tplCode": "lex"
    },
    "origin": {
      "details": "199 Điện Biên Phủ Quận Bình Thạnh TP.Hồ Chí Minh",
      "id": "R123765"
    },
    "destination": {
      "details": "199 Điện Biên Phủ Quận Bình Thạnh TP.Hồ Chí Minh",
      "id": "R123765"
    },
    "firstMileShippingProvider": {
      "tplSlug": "lex",
      "tplName": "lex",
      "tplCode": "lex"
    },
    "portCode": "X-PORT-123",
    "aoiName": "X-ROUT-123",
    "packageCode": "FU242008370000001917013936",
    "options": {
      "promotionCode": "PROMOTION_CODE",
      "vasPartialDeliveryOptionNotAvailable": "false"
    },
    "appliedVas": {
      "vasExchangeOrderOption": "true",
      "openBox": "true",
      "vasFdCollectShippingFeeOption": "true",
      "vasFdStorageOption": "true",
      "vasFdCallOption": "true",
      "vasPartialDeliveryOption": "true"
    },
    "minEta": "1701104399000",
    "trackingNumber": "GHN00003984888VNA"
  },
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
