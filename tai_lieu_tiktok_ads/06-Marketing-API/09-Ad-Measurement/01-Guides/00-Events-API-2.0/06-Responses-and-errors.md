---
title: "Responses and errors"
doc_id: 1771101162079234
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Responses and errors"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Responses and errors

## API response
Events API 2.0 returns a response with the following fields:
``` xtable
|Field{20%}|Data Type{20%}|Description{60%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.<br>For a successful request,  this field will be an empty object (`{}`). <br>For a failed request, this field will not be returned.|
```
## API response codes 
For a full list of possible API response codes returned by TikTok API for Business, refer to [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). 

### For successful calls
If the event is received successfully by TikTok, the API will return a response with `200` HTTP Status Code. The response body will contain an API response code (TikTok API for Business return code) of `0`. 
```xtable
| Return code{20%} | HTTP Status Code{20%} | Description{30%} | Detail {30%}|
|---|---|---|---|
| 0 | 200 | Successful | Request succeeded. |
```

#### Example
```xcodeblock
(code curl http)
HTTP 1.1 200 OK
{
  "code": 0,
  "message": "OK",
  "request_id": "{{request_id}}",
  "data":{} 
}
(/code)
```
### For failed calls
If the request fails, you will get a `4XX` or `5XX` HTTP Status Code. The response body will contain a corresponding return code and a message explaining the details about the failure. Check the `message` field in the response body for error details.

If multiple events are sent in the same API call, the `message` field will also indicate the index (zero-based) of the first invalid event. For example, the error message below suggests that the **third** event's `event_id` is not a valid string.

```xcodeblock
(code)
{
  "code":40002,
  "message":"Invalid value for data.<span style="background-color: green;">2</span>.event_id: not a valid string.",
  "request_id":"202308291437415F6E70BA7E095091A6F4"
}
(/code)
```

Below is a list of commonly seen errors for Events API 2.0:
```xtable
| Return Code{20%} | HTTP Status Code {20%}| Error reason{60%} |
|---|---|---|
| 40001   | 400 | No permission. <br>You don't not have the necessary permission to operate the advertiser account. |
| 40002   | 400 | Invalid payload. <br><br><strong>Common causes</strong>: <ul><li> The payload is not a valid JSON.</li><li>Missing required field or wrong field type (for instance, passing in string when a number is required).</li><li>An unhashed value is used for a field (for instance, `email`) that requires SHA256 hashing</li><li>Sending too many events (>1000) in a single request.</li></ul>|
| 40100   |  401 | Too many requests (exceeded API quota limit). <br><br><strong>Common causes</strong>: <ul><li>The rate limit for the endpoint (1,000 queries per second) has been reached, thus your request is throttled. </li></ul> |
| 40104   | 401 | Access token is empty. |
```
#### Example
```xcodeblock
(code curl http)
HTTP/1.1 400 Bad Request
{
  "code": 40002
  "message": "Invalid value for email: not a valid SHA256-hashed string."
  "request_id": "{{request_id}}"
}
(/code)
```

`

