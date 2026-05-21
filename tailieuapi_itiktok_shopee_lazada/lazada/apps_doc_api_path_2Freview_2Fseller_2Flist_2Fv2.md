# GETGetReviewListByIdList

> Source: https://open.lazada.com/apps/doc/api?path=%2Freview%2Fseller%2Flist%2Fv2
> API path: /review/seller/list/v2
> Category: Product Review API
> Scraped: 2026-05-20T23:14:36.804Z

---

Latest update2022-08-16 15:02:19

13041

GetReviewListByIdList

GET

/review/seller/list/v2

Authorization Required

Description:get review list by id list, need get id list first

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
| id\_list | Number\[\] | Yes | id list, maxLength = 10 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | response data |
| review\_list | Object\[\] | review list |
| submit\_time | Number | the time when buyer submited this review |
| can\_reply | Boolean | if review can be replied by seller |
| product\_id | Number | Product Item ID |
| order\_id | Number | Order ID |
| review\_videos | Object\[\] | video list |
| video\_cover\_url | String | cover image url |
| video\_url | String | video url |
| review\_content | String | review content in text |
| ratings | Object | review ratings(only PRODUCT\_REVIEW has ratings, FOLLOW\_UP\_REVIEWS doesn't have) |
| logistics\_rating | Number | subRatings - logistics rating |
| overall\_rating | Number | overall rating |
| seller\_rating | Number | subRatings - seller rating |
| product\_rating | Number | subRatings - product rating |
| review\_type | String | PRODUCT\_REVIEW or FOLLOW\_UP\_REVIEW. |
| id | Number | id |
| review\_images | String\[\] | image url list |
| seller\_reply | String | seller reply in text |
| create\_time | Number | the time when review data created, this is the same with "start\_time" and "end\_time" in the request data of interface(/review/seller/history/list) |
| outdated\_reviews | Number\[\] | id list if review is not exist or won't show(outdated/rejected) any more |
| success | Boolean | \* |
| error\_code | String | \* |
| error\_msg | String | \* |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| PARAMS\_VALIDATE\_ERROR | NULL\_SELLERID | Cannot recognize sellerid |
| PARAMS\_VALIDATE\_ERROR | NULL\_ID | id list is null |
| TRAFFIC\_CONTROL | TRAFFIC\_CONTROL | Traffic control |
| Mp3SellerApiLimit | Mp3 Seller not support the api - apipath | MP3 sellers cannot call the current API, please readthis document for a list of APIs that can be called by MP3 sellers, and you can call the GetSeller API and check the marketplaceEaseMode field to confirm that the current seller is of type MP3. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/review/seller/list/v2)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/review/seller/list/v2

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/review/seller/list/v2");
request.setHttpMethod("GET");
request.addApiParameter("id_list", "[111111111111,11111111112]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "null",
  "code": "0",
  "data": {
    "outdated_reviews": [
      1111111111,
      1111111112
    ],
    "review_list": [
      {
        "review_images": [],
        "can_reply": "true",
        "create_time": "1658235676532",
        "submit_time": "1658235676532",
        "review_content": "this is a good product",
        "ratings": {
          "seller_rating": "5",
          "overall_rating": "2",
          "logistics_rating": "4",
          "product_rating": "2"
        },
        "product_id": "11111111111",
        "review_videos": [
          {
            "video_url": "http:****",
            "video_cover_url": "http:*****"
          }
        ],
        "id": "11111111111",
        "seller_reply": "thanks for your review",
        "order_id": "111111111111",
        "review_type": "PRODUCT_REVIEW"
      }
    ]
  },
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
