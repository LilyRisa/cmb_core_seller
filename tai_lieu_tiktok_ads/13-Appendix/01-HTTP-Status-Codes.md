---
title: "HTTP Status Codes"
doc_id: 1749734684478466
path: "Appendix / HTTP Status Codes"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# HTTP Status Codes


HTTP Status Codes help you properly handle Success and Errors with TikTok for Business APIs. 

The return code value (`0` or `4xxxx`/`5xxxx`) will  be returned if the HTTP Status Code is `200`. Therefore, on receiving an HTTP Status Code `200`, you need to also check the return code to confirm if the call to TikTok for Business APIs was successful or caused an error. A `code : 0` indicates a successful response from TikTok for Business APIs, and any other value indicates an error with the API call (see [Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097) to find out what the error is).

## Recommended actions for different  HTTP Status Codes 
While most calls to TikTok for Business APIs will return an HTTP Status Code `200`, there are cases where an HTTP Status Code `4xx` or `5xx` is returned. Please see below for more information on how to handle different HTTP Status Codes.

``` xtable
| HTTP status codes {20%} | Description {40%} | Recommended action  {40%}|
|---|---|---|
| 2xx | Your request is successful.<br><br>The common Status Code for this class is `200`, the expected Status Code for all calls to TikTok for Business APIs. | Review and confirm the return code ( `code` value should be `0` ).<br><br>If `code` value is not `0`, then TikTok API for Business returns an error. Please check the response message (`message` value) to confirm why. |
| 4xx | Your request is invalid. The common reasons are: <ul><li>API path is incorrect.</li><li>API method is incorrect.</li></ul> | See HTTP response for detail and confirm whether the API path and method are correct |
| 5xx | The server fails to process your request. | Wait for about 5 minutes and retry the API call.<br><br>If you see the same response, please submit an Athena ticket [here](https://ads.tiktok.com/athena/requester/boards?identify_key=b25033ef7782d41050cbac74f751d547e9d8ad35a39a541a0f0efbbd51fe722f) for technical support. |
```
## Examples with HTTP Status Code 200

Both API calls below have the HTTP Status Code `200`. You can see different return codes indicating which one is successful and which one is an error.

### Successful call  - "code" : 0

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "2022110918340701025107710223BCBDD9",
    "data": {
        "list": [],
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_page": 0,
            "total_number": 0
        }
    }
}
(/code)
```

### Unsuccessful call - "code" : 40002

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 40002,
    "message": "Missing required field(s): account_id.",
    "request_id": "2022110918305001025108614416C7CC00"
}
(/code)
```
