---
title: "Rate limits"
doc_id: 1740029171730433
path: "Get Started / Rate limits"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Rate limits

To ensure service availability and performance, we have global and endpoint-specific rate limits.

Global rate limits apply to API requests to all endpoints by a developer application.

Endpoint-specifc rate limits apply to API requests to a certain endpoint or endpoint group. Rate limits for different endpoints are independent. For example, if the rate limits for `/campaign/get` endpoint are reached for a developer app, the developer app can still make requests to `/ad/get/` endpoint. 

# Global rate limits

We set four different call limit levels for each developer application, and each level includes three different types, QPS Limit (Queries-Per-Second Rate Limiting), QPM Limit (Queries-Per-Minute Rate Limiting) and QPD Limit  (Queries-Per-Day Rate Limiting).

Once the rate limit is met, the server returns `"code": 40100`, which means your request was throttled. You will need to suspend calls to that interface for a period of time and resume calls after the suspension has been lifted. The suspension time depends on the type of rate limit set. For QPM limit, you need to wait 5 minutes before you can make API requests again. For QPD limit, you need to wait until the next day  (UTC+0 time) to make API requests again. Note that the QPD limit resets at 00:00:00 UTC+0 time every day.

## Rate limit levels

| Level | QPS| QPM| QPD|
| --- | --- | ------- | ------ |
| Basic | 10   | 600      | 864,000  |
| Advanced | 20   | 1,200     | 1,728,000 |
| Premium | 30   | 1,800     | 2,592,000 |
| Ultimate | 50  | 3,000     | 4,320,000 |


> <span style="color: DodgerBlue">**Note**</span><br>
> *  All apps are set to Basic level by default. To change your QPS limit, please apply through **My Apps > App Detail > Authorization**.<br>
> *  We can only increase API rate limiting one level at a time (from Basic to Advanced / from Advanced to Premium / from Premium to Ultimate). When applying, provide the reason for API rate limit increase. Here is an example for your reference:
 <br>The current API Rate Limit is not sufficient in the reporting use case, where we encounter errors occasionally. Therefore, we are applying for rate limit increase.
> *  You don't need to apply for rate limit level change for Events API, because the rate limit is the same for all levels.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b42c1fc9137fdd9353d232d65edacc63" width="60%" style="color:#808080"/>


# Endpoint-specific rate limits
> <span style="color:DodgerBlue">**Note**</span><br>
> Unique rate limits apply to the TTO API endpoints, including [/tto/creator/authorized/](https://business-api.tiktok.com/portal/docs?id=1834913529338882), [/tto/creator/authorized/video/list/](https://business-api.tiktok.com/portal/docs?id=1834913554657282), [/tto/tcm/creator/public/](https://business-api.tiktok.com/portal/docs?id=1828185358712897), [/tto/tcm/creator/public/video/list/](https://business-api.tiktok.com/portal/docs?id=1828185411810305), [/tto/tcm/rank/](https://business-api.tiktok.com/portal/docs?id=1816378813274113), and [/tto/tcm/creator/discover/](https://business-api.tiktok.com/portal/docs?id=1825017307843585). Refer to [Rate limits for TTO API](https://business-api.tiktok.com/portal/docs?id=1825117901366274) documentation for more details.

<div style="overflow-x:auto;">
 <table style="width:1400px">
 <thead>
   <tr>
     <th >Endpoint </th>
     <th colspan="3">Basic </th>
     <th colspan="3">Advanced </th>
     <th colspan="3">Premium </th>
     <th colspan="3">Ultimate <br> </th>
   </tr>
 </thead>
 <tbody>
   <tr>
     <td>Limit type </td>
     <td>QPS </td>
     <td>QPM </td>
     <td>QPD </td>
     <td>QPS </td>
     <td>QPM </td>
     <td>QPD </td>
     <td>QPS </td>
     <td>QPM </td>
     <td>QPD </td>
     <td>QPS </td>
     <td>QPM </td>
     <td>QPD </td>
   </tr>
   <tr>
     <td>/creative/quick_optimization/create/ </td>
     <td>1 </td>
     <td>30 </td>
     <td>5,000 </td>
     <td>2 </td>
     <td>60 </td>
     <td>10,000 </td>
     <td>3 </td>
     <td>180 </td>
     <td>15,000 </td>
     <td>3 </td>
     <td>180 </td>
     <td>15,000 </td>
   </tr>
   <tr>
     <td>/creative/smart_video/create/ </td>
     <td>1 </td>
     <td>30 </td>
     <td>5,000 </td>
     <td>2 </td>
     <td>60 </td>
     <td>10,000 </td>
     <td>3 </td>
     <td>180 </td>
     <td>15,000 </td>
     <td>3 </td>
     <td>180 </td>
     <td>15,000 </td>
   </tr>
   <tr>
     <td>/ad/create/</td>
     <td>5 </td>
     <td>150 </td>
     <td>86,400 </td>
     <td>10 </td>
     <td>200 </td>
     <td>86,400 </td>
     <td>10 </td>
     <td>300 </td>
     <td>86,400 </td>
     <td>15 </td>
     <td>300 </td>
     <td>86,400 </td>
   </tr>
   <tr>
     <td>/video_template/task/create/ </td>
     <td>1 </td>
     <td>30 </td>
     <td>5,000 </td>
     <td>2 </td>
     <td>60 </td>
     <td>10,000 </td>
     <td>3 </td>
     <td>180 </td>
     <td>15,000 </td>
     <td>3 </td>
     <td>180 </td>
     <td>15,000 </td>
   </tr>
  <tr>
    <td>/catalog/product/file/</td>
    <td>5<br></td>
    <td>300</td>
    <td>432,000</td>
    <td>5</td>
    <td>300</td>
    <td>432,000</td>
    <td>10</td>
    <td>600</td>
    <td>864,000</td>
    <td>10</td>
    <td>600</td>
    <td>864,000</td>
  </tr>
  <tr>
    <td>/catalog/product/upload/</td>
    <td>5<br></td>
    <td>300</td>
    <td>432,000</td>
    <td>5</td>
    <td>300</td>
    <td>432,000</td>
    <td>10</td>
    <td>600</td>
    <td>864,000</td>
    <td>10</td>
    <td>600</td>
    <td>864,000</td>
  </tr>
     <td>/catalog/product/update/ </td>
     <td>2 </td>
     <td>120 </td>
     <td>172,800 </td>
     <td>2 </td>
     <td>120 </td>
     <td>172,800 </td>
     <td>5 </td>
     <td>300 </td>
     <td>432,000 </td>
     <td>5 </td>
     <td>300 </td>
     <td>432,000 </td>
   </tr>
   <tr>
     <td>/catalog/product/delete/ </td>
     <td>2 </td>
     <td>120 </td>
     <td>172,800 </td>
     <td>2 </td>
     <td>120 </td>
     <td>172,800 </td>
     <td>5 </td>
     <td>300 </td>
     <td>432,000 </td>
     <td>5 </td>
     <td>300 </td>
     <td>432,000 </td>
   </tr>
   <tr>
     <td>Asynchronous reports  <br>(/report/task/create/ with POST method) </td>
     <td>2 </td>
     <td>60 </td>
     <td>4,500 </td>
     <td>2 </td>
     <td>60 </td>
     <td>4,500 </td>
     <td>2 </td>
     <td>60 </td>
     <td>4,500 </td>
     <td>2 </td>
     <td>60 </td>
     <td>4,500 </td>
   </tr>
     <tr>
     <td>/event/track/</td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
   </tr>
   <tr>
     <td>App Events (/app/track/  with POST method) </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
   </tr>
   <tr>
     <td>/app/batch/  with POST method </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
   </tr>
   <tr>
     <td>Web Events (/pixel/track/  with POST method) </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
   </tr>
   <tr>
     <td>/pixel/batch/   with POST method </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
   </tr>
      <tr>
     <td>Offline Events(/offline/track/  with POST method) </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
   </tr>
   <tr>
     <td>/offline/batch/  with POST method </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
     <td>1,000 </td>
     <td>600,000 </td>
     <td>86,400,000 </td>
   </tr>
     <tr>
     <td>Streaming API </td>
     <td>10,000 </td>
     <td>600,000 </td>
     <td>864,000,000 </td>
     <td>20,000 </td>
     <td>1,200,000 </td>
     <td>1,728,000,000 </td>
     <td>30,000 </td>
     <td>1,800,000 </td>
     <td>2,592,000,000 </td>
     <td>50,000 </td>
     <td>3,000,000 </td>
     <td>4,320,000,000 </td>
   </tr>
   <tr>
    <td>/campaign/copy/task/create/</td>
    <td>1</td>
    <td>30</td>
    <td>432,000</td>
    <td>1</td>
    <td>30</td>
    <td>432,000</td>
    <td>1</td>
    <td>30</td>
    <td>432,000</td>
    <td>1</td>
    <td>30</td>
    <td>432,000</td>
  </tr>
    <tr>
    <td>/campaign/copy/task/check/</td>
    <td>2</td>
    <td>60</td>
    <td>864,000</td>
    <td>2</td>
    <td>60</td>
    <td>864,000</td>
    <td>2</td>
    <td>60</td>
    <td>864,000</td>
    <td>2</td>
    <td>60</td>
    <td>864,000</td>
  </tr>
     <tr>
    <td>/gmv_max/report/get/</td>
    <td>8</td>
    <td>240</td>
    <td>20,000</td>
    <td>12</td>
    <td>360</td>
    <td>30,000</td>
    <td>20</td>
    <td>600</td>
    <td>50,000</td>
    <td>20</td>
    <td>600</td>
    <td>50,000</td>
  </tr>
 </tbody>
 </table>
</div>


# Best practices

To avoid your request being throttled and better utilize the resources, you can send requests in bulk and limit the number of concurrent requests.

## 1. Make bulk requests

The read (get) type of endpoints allows bulk requests. With one request, you can operate on multiple data at the same time rather than operating on one data per request. This reduces the number of calls you make.

For example, you can `Get` multiple `ad groups` by passing in multiple `adgroup_ids` instead of making multiple requests and passing in only one ` adgroup_ids` per request.

**Recommended**:

```xcodeblock
(code Good http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id={{ADVERTISER_ID}}" \
--data-urlencode "filtering={\"adgroup_ids\": [ADGROUP_ID1,ADGROUP_ID2,ADGROUP_ID3,...]" \
--data-urlencode "page=1" \
--data-urlencode "page_size=100" \
https://ads.tiktok.com/open_api/2/adgroup/get/
(/code);
```

**Not recommended**:
```xcodeblock
(code Not-recommended http)
for ADGROUP_ID in {ADGROUP_ID1,ADGROUP_ID2,ADGROUP_ID3,...} do
  curl --get -H "Access-Token:xxx" \
  --data-urlencode "advertiser_id={{ADVERTISER_ID}}" \
  --data-urlencode "filtering={\"adgroup_ids\": [$ADGROUP_ID1]" \
  --data-urlencode "page=1" \
  --data-urlencode "page_size=1" \
  https://ads.tiktok.com/open_api/2/adgroup/get/ \
(/code);
```

## 2. Limit the number of concurrent requests

For time-synchronized data, if a large number of API interfaces are called at the same time, the request may be throttled.

In this case, you can implement [Rate Limiting](https://en.wikipedia.org/wiki/Rate_limiting) on the client side, to control the number of concurrent requests. An example is the [Token Bucket Algorithm](https://en.wikipedia.org/wiki/Token_bucket). Tokens are issued according to the rate limit rules (for example, 10 tokens are issued per second). Before making a request, try to obtain the token, if there are no token currently available, wait for a period of time, then try again.

A more robust implementation is to use [Message Queuing](https://en.wikipedia.org/wiki/Message_queue). The timing script is not directly responsible for requesting the API, but uses a [producer-consumer](https://en.wikipedia.org/wiki/Producer–consumer_problem) Model. Push the requested task to the message queue, and a dedicated consumer takes the task from the queue for consumption (through an API request). You can limit the number of concurrent users by limiting the number of concurrent consumers for a certain type of task or using a rate limit. Tasks that fail to execute, can be pushed back into the queue (and set a certain delay) to retry.
