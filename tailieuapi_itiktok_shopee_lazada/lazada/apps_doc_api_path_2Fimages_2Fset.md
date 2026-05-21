# POSTSetImages

> Source: https://open.lazada.com/apps/doc/api?path=%2Fimages%2Fset
> API path: /images/set
> Category: Product API
> Scraped: 2026-05-20T23:11:22.149Z

---

Latest update2022-07-28 17:14:55

7193

SetImages

POST

/images/set

Authorization Required

Description:Use this API to set the images for an existing product by associating one or more image URLs with it.

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
| payload | Payload | Yes | [Parameter description](https://open.lazada.com/apps/doc/doc?nodeId=10557&docId=108254) |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response body |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 5 | E005: Invalid Request Format | The request format is not valid. |
| 6 | E006: Unexpected internal error | Unexpected internal error. |
| 30 | E030: Empty Request | The request URL is not complete. |
| 200 | E200: Empty SellerSku | The Seller SKU is not specified. |
| 203 | E203: Too many images in one SKU | The number of images exceeds the limit (8 images). |
| 204 | E204: Too many SKU in one request | The number of SKUs exceeds the limit. |
| 504 | E504: Set product Image failed | Failed to set images for the product. |
| 1000 | Internal Application Error | Internal system error. |
| 504 | THD\_IC\_ERR\_F\_IC\_ABILITY\_PG\_004:THD\_IC\_ERR\_F\_IC\_ABILITY\_PG\_004 | The product is participating in a special Camapign that does not allow modification of images until the end of this Campaign. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/images/set)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/images/set

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/images/set");
request.addApiParameter("payload", "<Request><Product><Skus><Sku><SkuId>20692116001</SkuId><Images><Image>https://sg-test-11.slatic.net/p/fc83aeae8cf46456468c175970edee75.png</Image><Image>https://th-live.slatic.net/p/6993be3715b37d5ccf0ed4ea5b50b58a.png</Image><Image>https://th-live.slatic.net/p/d619ac00b273e442c8f60035f5fb74d5.png</Image><Image>https://th-live.slatic.net/p/dc4ad00eb9f4da013707d855b7dbbbc6.png</Image><Image>https://th-live.slatic.net/p/4b47161058edfa6593c55e8e0c1e12a3.png</Image><Image>https://th-live.slatic.net/p/d95763400b94e65cc24b91e2fa70c514.png</Image><Image>https://th-live.slatic.net/p/cb10a4bc14c839bb808f83848d3a8222.png</Image></Images></Sku></Skus></Product></Request>");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {},
  "request_id": "0ba2887315178178017221014"
}
```
