---
title: "(Deprecated) Callback request details"
doc_id: 1739067004677122
path: "API Reference / Creative Tools / (Deprecated) Callback request details"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Callback request details

If the `callback_url` is passed in when you create a creative tool task (for example, in [/quick_optimization/create/](https://ads.tiktok.com/marketing_api/docs?id=1739355078757377)), it will be requested with an HTTP POST method as soon as the task is done. The `extra_callback_info` will be returned as it is passed in the body along with the task result information.

If the request is received successfully, please return a 200 status within 5 seconds. Otherwise our server will resend the request, and will attempt up to 2 times. Please make sure the url is idempotent because of potential retries.

# Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Request parameter name |`err_msg`|`error_msg`|
```
# Request
**Endpoint**
The `callback_url` provided at the time of video task creation.

**Method**
POST

**Header**
```xtable
|Field|Data Type|Description|
|--- |--- |--- |
|Content-Type{Required}|string|Request content type, accepted content type：`application/json`|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|-|-|-|
|task_id|string|Task ID.|
|status |string| Task status. For enum values, see [Enumeration - Task Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
|video_ids{+ Conditional}|string[]|Video IDs, valid only when `status` is `SUCCESS`.|
|error_msg{+ Conditional}|string|Error message.|
|extra_info{+ Conditional} |object |callback_extra_info that was specified when the task was created. |
```

**Example**

```xcodeblock
(code Sample Parameters)
{
    "status": "SUCCESS",
    "video_ids": [
        "v09033a60000bt1posj6hujpgbgomlo2",
        "v090336c0000bt1pp2ac3teiuedco26s"
    ],
    "extra_info": {
        "name": "test"
    }
}
(/code)
```


