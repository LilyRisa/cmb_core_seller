# GETReverseOrderOnlyRefundDecide

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Freverse%2Fonlyrefund%2Fseller%2Fdecide
> API path: /order/reverse/onlyrefund/seller/decide
> Category: Return and Refund API
> Scraped: 2026-05-20T23:25:43.715Z

---

Latest update2025-03-20 11:44:01

2955

ReverseOrderOnlyRefundDecide

GET

/order/reverse/onlyrefund/seller/decide

Authorization Required

Description:Seller can use this API to operate only refund requests

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
| action | String | Yes | agreeRefund, startDispute |
| reverse\_order\_id | Number | Yes | reverse order id |
| reverse\_order\_item\_ids | Number\[\] | Yes | reverse order item id list, currently list size can be only 1 |
| comment | String | No | comment, required if action is startDispute |
| image\_info\_list | Object\[\] | No | image info list, required if action is startDispute |
| file\_name | String | No | image name |
| file\_url | String | No | image url |
| video\_info\_list | Object\[\] | No | video info list |
| cover\_url | String | No | cover url |
| video\_url | String | No | video url |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | null |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 116 | E0116: no seller id | E0116: no seller id |
| 118 | E0108: reason can't be empty if you want to refuse return or refund | E0108: reason can't be empty if you want to refuse return or refund |
| 100 | E0100: reverse order list is empty | E0100: reverse order list is empty |
| 125 | E0125: invalid reverse id | E0125: invalid reverse id |
| 112 | E0112: no reverse order found | E0112: no reverse order found |
| 133 | E0133: do not support batch operation | E0133: do not support batch operation |
| 126 | E0126: invalid reverse order lines | E0126: invalid reverse order lines |
| 114 | E0114: this reverse does not support this action | E0114: this reverse does not support this action |
| 107 | E0107: invalid action | E0107: invalid action |
| 109 | E0109: comment can't be empty if startDispute | E0109: comment can't be empty if startDispute |
| 110 | E0110: image can't be empty if startDispute | E0110: image can't be empty if startDispute |
| 106 | E0106: ROC internal error | E0106: ROC internal error |
| 113 | E0113: reverse order line have unknown status | E0113: reverse order line have unknown status |
| 114 | E0114: this reverse does not support this action | E0114: this reverse does not support this action |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/reverse/onlyrefund/seller/decide)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/order/reverse/onlyrefund/seller/decide

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/reverse/onlyrefund/seller/decide");
request.setHttpMethod("GET");
request.addApiParameter("action", "agreeRefund");
request.addApiParameter("reverse_order_id", "123");
request.addApiParameter("reverse_order_item_ids", "[]");
request.addApiParameter("comment", "\"\"");
request.addApiParameter("image_info_list", "[{\"file_url\":\"\\\"\\\"\",\"file_name\":\"\\\"\\\"\"}]");
request.addApiParameter("video_info_list", "[{\"cover_url\":\"\\\"\\\"\",\"video_url\":\"\\\"\\\"\"}]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
