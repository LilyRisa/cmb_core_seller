# video_upload_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Product Push
> Scraped: 2026-05-20T20:44:46.501Z

---

Push Mechanism

Product Push

\>

video\_upload\_push

Basics

Push Parameters

Push Contents

Update Log

Product Push

-   reserved\_stock\_change\_push
-   video\_upload\_push
-   brand\_register\_result
-   violation\_item\_push
-   item\_price\_update\_push
-   item\_scheduled\_publish\_failed\_push

Order Push

-   order\_status\_push
-   order\_trackingno\_push
-   shipping\_document\_status\_push
-   booking\_status\_push
-   booking\_trackingno\_push
-   booking\_shipping\_document\_status\_push
-   package\_fulfillment\_status\_push
-   courier\_delivery\_binding\_status\_push
-   package\_info\_push

Return Push

-   return\_updates\_push

Marketing Push

-   item\_promotion\_push
-   promotion\_update\_push

Shopee Push

-   shopee\_updates
-   open\_api\_authorization\_expiry
-   shop\_authorization\_push
-   shop\_authorization\_canceled\_push
-   shop\_penalty\_update\_push
-   video\_upload\_result\_push

Webchat Push

-   webchat\_push

Consignment Service Push

-   inbound\_status\_push
-   supplier\_create\_product\_push
-   supplier\_prouduct\_review\_result\_push
-   purchase\_order\_Push

Fulfillment by Shopee Push

-   fbs\_sellable\_stock
-   fbs\_br\_invoice\_error\_push
-   fbs\_br\_block\_shop\_push
-   fbs\_br\_block\_sku\_push
-   fbs\_br\_invoice\_issued\_push

video\_upload\_push

Last Updated: 19 Aug 2022

## Basics

Collapse

| 
Property

 | 

Value

 |
| --- | --- |
| 

Category

 | 

Product Push

 |
| 

Push Mechanism Name

 | 

video\_upload\_push

 |
| 

Push Mechanism Code

 | 

11

 |
| 

Push Mechanism Description

 | 

Get the video upload push result

 |
| 

Push Mechanism Subscription Rules

 | 

ERP System/Seller In House System/Product Management/Swam ERP

 |
| 

Time Out Seconds

 | 

3s

 |
| 

Sequence Guaranteed

 | 

No

 |
| 

Can Repeated Same Message

 | 

Yes

 |
| 

Retry Seconds

 | 

300s,1800s,10800s

 |

## Push Parameters

Collapse

| 
Name

 | 

Type

 | 

Sample

 | 

Description

 |
| --- | --- | --- | --- |
| 

data

 | 

object

 | 

 | 

 |
| 

video\_upload\_id

 | 

string

 | 

sg\_a106a162-8a60-4a3e-9eba-7844d7a6f4f2\_000197

 | 

The identifier of this upload session, used in following video upload request and item creating and/or updating.  

 |
| 

status

 | 

string

 | 

FAILED

 | 

Video upload result. Can be SUCCEEDED / FAILED  

 |
| 

message

 | 

string

 | 

Video is too short

 | 

More about failed uploads.

 |
| 

video\_info

 | 

object

 | 

 | 

Video id and video url for each markets.  

 |
| 

video\_id

 | 

string

 | 

 | 

Equal to video\_upload\_id, the identifier for this upload session, used for subsequent video upload requests and item creation and/or updates  

 |
| 

video\_url

 | 

object\[\]

 | 

 | 

Video URLs for each markets  

 |
| 

video\_url\_region

 | 

string

 | 

 | 

Video region.

 |
| 

video\_url

 | 

string

 | 

 | 

Video URL

 |
| 

thumbnail\_url

 | 

object\[\]

 | 

 | 

Video thumbnails for each markets.  

 |
| 

image\_url\_region

 | 

string

 | 

 | 

Video Thumbnail region.  

 |
| 

image\_url

 | 

string

 | 

 | 

image url.

 |
| 

shop\_id

 | 

int

 | 

608118757

 | 

Shopee's unique identifier for a shop.  

 |
| 

code

 | 

int

 | 

11

 | 

Shopee's unique identifier for a push notification.  

 |
| 

timestamp

 | 

timestamp

 | 

1660125113

 | 

Timestamp that indicates the message was sent.  

 |

## Push Contents

Collapse

If video upload successfully, push notification will be

Json

```
{
	"data": {
		"video_upload_id": "sg_f12ebfcb-a415-4c8a-b873-cc2ea8803c11_000203",
		"status": "SUCCEEDED",
		"message": "Success",
		"video_info": {
			"video_id": "0b6b0046852b64676545cf734ef52441",
			"video_url": [{
				"video_url_region": "MX",
				"video_url": "https://cvf.shopee.com.mx/file/0b6b0046852b64676545cf734ef52441"
			}, {
				"video_url_region": "CO",
				"video_url": "https://cvf.shopee.com.co/file/0b6b0046852b64676545cf734ef52441"
			}, {
				"video_url_region": "BR",
				"video_url": "https://cvf.shopee.com.br/file/0b6b0046852b64676545cf734ef52441"
			}, {
				"video_url_region": "ID",
				"video_url": "https://cvf.shopee.co.id/file/0b6b0046852b64676545cf734ef52441"
			}, {
				"video_url_region": "PH",
				"video_url": "https://cvf.shopee.ph/file/0b6b0046852b64676545cf734ef52441"
			}, {
				"video_url_region": "TH",
				"video_url": "https://cvf.shopee.co.th/file/0b6b0046852b64676545cf734ef52441"
			}, {
				"video_url_region": "TW",
				"video_url": "https://cvf.shopee.tw/file/0b6b0046852b64676545cf734ef52441"
			}, {
				"video_url_region": "VN",
				"video_url": "https://cvf.shopee.vn/file/0b6b0046852b64676545cf734ef52441"
			}, {
				"video_url_region": "CL",
				"video_url": "https://cvf.shopee.cl/file/0b6b0046852b64676545cf734ef52441"
			}, {
				"video_url_region": "PL",
				"video_url": "https://cvf.shopee.pl/file/0b6b0046852b64676545cf734ef52441"
			}, {
				"video_url_region": "MY",
				"video_url": "https://cvf.shopee.com.my/file/0b6b0046852b64676545cf734ef52441"
			}, {
				"video_url_region": "SG",
				"video_url": "https://cvf.shopee.sg/file/0b6b0046852b64676545cf734ef52441"
			}],
			       "thumbnail_url": [          {             
				"image_url_region": "BR",
				             "image_url": "https://cf.shopee.com.br/file/023397b8fe760c31c2185dd6dc93be64"          
			},            {             
				"image_url_region": "SG",
				             "image_url": "https://cf.shopee.sg/file/023397b8fe760c31c2185dd6dc93be64"          
			},            {             
				"image_url_region": "TW",
				             "image_url": "https://cf.shopee.tw/file/023397b8fe760c31c2185dd6dc93be64"          
			},            {             
				"image_url_region": "VN",
				             "image_url": "https://cf.shopee.vn/file/023397b8fe760c31c2185dd6dc93be64"          
			},            {             
				"image_url_region": "PH",
				             "image_url": "https://cf.shopee.ph/file/023397b8fe760c31c2185dd6dc93be64"          
			},            {             
				"image_url_region": "TH",
				             "image_url": "https://cf.shopee.co.th/file/023397b8fe760c31c2185dd6dc93be64"          
			},            {             
				"image_url_region": "ID",
				             "image_url": "https://cf.shopee.co.id/file/023397b8fe760c31c2185dd6dc93be64"          
			},            {             
				"image_url_region": "MY",
				             "image_url": "https://cf.shopee.com.my/file/023397b8fe760c31c2185dd6dc93be64"          
			}, {             
				"image_url_region": "CO",
				             "image_url": "https://cf.shopee.com.co/file/023397b8fe760c31c2185dd6dc93be64"          
			},{             
				"image_url_region": "CL",
				             "image_url": "https://cf.shopee.cl/file/023397b8fe760c31c2185dd6dc93be64"          
			},{             
				"image_url_region": "MX",
				             "image_url": "https://cf.shopee.com.mx/file/023397b8fe760c31c2185dd6dc93be64"          
			},{             
				"image_url_region": "PL",
				             "image_url": "https://cf.shopee.com.pl/file/023397b8fe760c31c2185dd6dc93be64"          
			}
                                    ],
			       "duration": 60    
		} 
	},
	"shop_id":908118757,
	"code":11,
	"timestamp":1660125113
}
```

  

If video upload failed, push notification will be

Json

```
{"data":{"video_upload_id":"sg_a106a162-8a60-4a3e-9eba-7844d7a6f4f2_000197","status":"FAILED","message":"Video is too short"},"shop_id":608118757,"code":11,"timestamp":1660125113}
```

## Update Log

Collapse

| 
Date

 | 

Update Details

 |
| --- | --- |
| 

2022-08-18

 | 

New Push Mechanism

 |
