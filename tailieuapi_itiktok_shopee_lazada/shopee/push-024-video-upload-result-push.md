# video_upload_result_push

> Source: https://open.shopee.com/push-mechanism/5
> Category: Shopee Push
> Scraped: 2026-05-20T20:45:05.357Z

---

Push Mechanism

Shopee Push

\>

video\_upload\_result\_push

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

video\_upload\_result\_push

Last Updated: 6 Nov 2025

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

Shopee Push

 |
| 

Push Mechanism Name

 | 

video\_upload\_result\_push

 |
| 

Push Mechanism Code

 | 

38

 |
| 

Push Mechanism Description

 | 

Get notified immediately when a video upload reaches a final status (SUCCEEDED, FAILED, or CANCELLED). Intermediate status (INITIATED, UPLOADING, UPLOADED, PROCESSING) are not pushed to avoid unnecessary callbacks, can call v2.media.get\_video\_upload\_result if need progress updates.

 |
| 

Push Mechanism Subscription Rules

 | 

ERP System/Seller In House System/Product Management/Customer Service/Consignment Service System/Shopee Video Management

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

sg-11110201-6kh48-mepm7a0ttcw3c3

 | 

The unique ID of the upload task.

 |
| 

status

 | 

string

 | 

SUCCEEDED

 | 

Final status of the upload task. Possible values:

\- SUCCEEDED

\- FAILED

\- CANCELLED

  

Note: Intermediate status (INITIATED, UPLOADING, UPLOADED, PROCESSING) are not pushed to avoid unnecessary callbacks, can call v2.media.get\_video\_upload\_result if need progress updates.

 |
| 

reason

 | 

string

 | 

 | 

Detailed fail or cancel reason, will be returned if status is FAILED or CANCELLED.

 |
| 

video\_info

 | 

object

 | 

 | 

Transcoded video info, will be returned if status is SUCCEEDED.

 |
| 

video\_url

 | 

string

 | 

http://play-src.vod.shopee.com/api/v4/11110201/mms/sg-11110201-6kh48-mepm7a0ttcw3c3.

 | 

Video playback URL.

 |
| 

video\_thumbnail\_url

 | 

string

 | 

http://img.sp.mms.shopee.sg/sg-11110201-6kh48-mepm7a0ttcw3c3\_cover

 | 

Video thumbnail image URL.

 |
| 

thumbnail\_width

 | 

int32

 | 

1920

 | 

Video thumbnail image width.

 |
| 

thumbnail\_height

 | 

int32

 | 

1080

 | 

Video thumbnail image height.

 |
| 

duration

 | 

int32

 | 

105

 | 

Video duration in seconds.

 |
| 

resolution

 | 

string

 | 

960x540

 | 

Video resolution, e.g., "1280x1280".

 |
| 

update\_time

 | 

timestamp

 | 

1758018336

 | 

The time of video status updates.

 |
| 

code

 | 

int32

 | 

38

 | 

Shopee's unique identifier for a push notification.

 |
| 

timestamp

 | 

timestamp

 | 

1758018336

 | 

Timestamp that indicates the message was sent.

 |

## Push Contents

Collapse

Json

```
{
   "data": {
       "status": "SUCCEEDED",
       "reason": "",
       "update_time": 1758018336,
       "video_info": {
           "video_url": "http://play-src.vod.shopee.com/api/v4/11110201/mms/sg-11110201-6kh48-mepm7a0ttcw3c3.",
           "video_thumbnail_url": "http://img.sp.mms.shopee.sg/sg-11110201-6kh48-mepm7a0ttcw3c3_cover",
           "thumbnail_width": 1920,
           "thumbnail_height": 1080,
           "duration": 105,
           "resolution": "960x540"
       }
   },
   "code": 37,
   "timestamp": 1758018336
}
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

2025-09-19

 | 

New Push

 |
