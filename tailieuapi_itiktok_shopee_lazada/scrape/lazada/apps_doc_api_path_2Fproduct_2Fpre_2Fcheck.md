# GET/POSTProductCheck

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fpre%2Fcheck
> Scraped: 2026-05-20T22:51:50.661Z

---

Latest update2022-07-29 13:25:14

7186

ProductCheck

GET/POST

/product/pre/check

Authorization Required

Description:Use this API to check CB seller quantity limit of adding product .

## Service Endpoints

| 
Region

 | 

Endpoint

 |
| --- | --- |
| 

Vietnam

 | 

https://api.lazada.vn/rest

 |
| 

Singapore

 | 

https://api.lazada.sg/rest

 |
| 

Philippines

 | 

https://api.lazada.com.ph/rest

 |
| 

Malaysia

 | 

https://api.lazada.com.my/rest

 |
| 

Thailand

 | 

https://api.lazada.co.th/rest

 |
| 

Indonesia

 | 

https://api.lazada.co.id/rest

 |

Did this chapter help you?

YesNo

## Common Parameters

| 
Name

 | 

Type

 | 

Required or not

 | 

Description

 |
| --- | --- | --- | --- |
| 

app\_key

 | 

String

 | 

Yes

 | 

Unique app ID issued by LAZADA Open Platform console when you apply for an app category

 |
| 

timestamp

 | 

String

 | 

Yes

 | 

The time stamp of the request e.g. 1517820392000 (which translates to 5 February 2018 08:46:32) with less than 7200s difference from UTC time

 |
| 

access\_token

 | 

String

 | 

Yes

 | 

API interface call credentials

 |
| 

sign\_method

 | 

String

 | 

Yes

 | 

The HMAC hash algorithm you are using to calculate your signature

 |
| 

sign

 | 

String

 | 

Yes

 | 

Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details)

 |

Did this chapter help you?

YesNo

## Parameters

| 
Name

 | 

Type

 | 

Required or not

 | 

Description

 |
| --- | --- | --- | --- |
| 

payload

 | 

String

 | 

Yes

 | 

[Parameter description](https://open.lazada.com/apps/doc/doc?nodeId=10557&docId=108253)

 |

Did this chapter help you?

YesNo

## Response Parameters

| 
Name

 | 

Type

 | 

Description

 |
| --- | --- | --- |
| 

data

 | 

Object

 | 

Response body

 |

Did this chapter help you?

YesNo

## Error Code

| 
Error Code

 | 

Error Message

 | 

Solution

 |
| --- | --- | --- |
| 

No Data

 |

Did this chapter help you?

YesNo

[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/product/pre/check)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/product/pre/check

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/product/pre/check");
request.addApiParameter("payload", "<Request><Product><PrimaryCategory>6614</PrimaryCategory><SPUId/><AssociatedSku/><Images><Image>https://my-live-02.slatic.net/p/765888ef9ec9e81106f451134c94048f.jpg</Image><Image>https://my-live-02.slatic.net/p/9eca31edef9f05f7e42f0f19e4d412a3.jpg</Image></Images><Attributes><name>api create product test sample</name><short_description>This is a nice product</short_description><brand>Remark</brand><model>asdf</model><kid_years>Kids (6-10yrs)</kid_years><video>12345 (fill with the video id of the previously uploaded video) optional</video><delivery_option_sof>Yes</delivery_option_sof></Attributes><Skus><Sku><SellerSku>api-create-test-1</SellerSku><color_family>Green</color_family><size>40</size><quantity>1</quantity><price>388.50</price><package_length>11</package_length><package_height>22</package_height><package_weight>33</package_weight><package_width>44</package_width><package_content>this is what's in the box</package_content><Images><Image>http://sg.s.alibaba.lzd.co/original/59046bec4d53e74f8ad38d19399205e6.jpg</Image><Image>http://sg.s.alibaba.lzd.co/original/179715d3de39a1918b19eec3279dd482.jpg</Image></Images></Sku></Skus></Product></Request>");
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

Please rate this article

Popular Articles
