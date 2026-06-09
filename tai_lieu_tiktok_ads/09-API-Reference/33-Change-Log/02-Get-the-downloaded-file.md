---
title: "Get the downloaded file"
doc_id: 1739924165710849
path: "API Reference / Change Log / Get the downloaded file"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the downloaded file

Use this endpoint to download the change log file. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/activities/task/download/|/v1.3/changelog/task/download/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint**  
https://business-api.tiktok.com/open_api/v1.3/changelog/task/download/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID |
|task_id {Required}|string| Task ID |
```

### Example

``` xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "task_id=TASK_ID" \
https://business-api.tiktok.com/open_api/v1.3/changelog/task/download/
(/code)
```

## Response
``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see【[Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)】 |
|message |string|Return messages, see【[Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)】|
|data |object|Return data|
#|status |string| Task status. Enum values: <ul><li>`PROCESSING`</li> <li>`SUCCESS`</li><li>`FAILED`</li></ul>|
#|changelog |string| CSV file data of the log  |
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example
``` xcodeblock
(code Success-Response CSV)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "changelog": "{'file_data': b'Budget changes,2\\r\\nBid changes,0\\r\\nStatus changes,0\\r\\n\\r\\nTime,log_object_type,Object ID,Object,Operator,Source,App ID,Activity details
    \\r\\n2024-10-04 15:20,Ad group,{{object_id}},Budget & Schedule,m**********t@gmail.com,Marketing API,{{app_id}},\"[{\"\"action\"\": \"\"Change \"\", \"\"name\"\": \"\"Budget\"\", \"\"before_after\"\": [{\"\"before\"\": \"\"b\\'50.00 USD\\'\"\", \"\"after\"\": \"\"b\\'20.00 USD\\'\"\"}]}]\"
    \\r\\n2024-10-04 15:21,Ad group,{{object_id}},On/Off Status,m**********t@gmail.com,Marketing API,{{app_id}},\"[{\"\"action\"\": \"\"Delete\"\", \"\"name\"\": \"\"Campaign\"\"}]\"
    \\r\\n2024-10-04 15:20,Ad group,{{object_id}},Ad group,m**********t@gmail.com,Marketing API,{{app_id}},\"[{\"\"action\"\": \"\"Create\"\", \"\"name\"\": \"\"Ad group\"\"}]\"
    \\r\\n2024-10-04 15:21,Ad group,{{object_id}},Settings,m**********t@gmail.com,Marketing API,{{app_id}},\"[{\"\"action\"\": \"\"Change \"\", \"\"name\"\": \"\"Name\"\", \"\"before_after\"\": [{\"\"before\"\": \"\"b\\'{{adgroup_name}}'\"\", \"\"after\"\": \"\"b\\'{{adgroup_name}}'\"\"}]}]\"
    \\r\\n2024-10-04 15:20,Ad group,{{object_id}},Ad group,m**********t@gmail.com,Marketing API,{{app_id}},\"[{\"\"action\"\": \"\"Create\"\", \"\"name\"\": \"\"Ad group\"\"}]\"
    \\r\\n2024-10-04 15:20,Ad group,{{object_id}},On/Off Status,m**********t@gmail.com,Marketing API,{{app_id}},\"[{\"\"action\"\": \"\"Delete\"\", \"\"name\"\": \"\"Ad group\"\"}]\"
    \\r\\n2024-10-04 15:20,Ad group,{{object_id}},Settings,m**********t@gmail.com,Marketing API,{{app_id}},\"[{\"\"action\"\": \"\"Change \"\", \"\"name\"\": \"\"Name\"\", \"\"before_after\"\": [{\"\"before\"\": \"\"b\\'{{adgroup_name}}'\"\", \"\"after\"\": \"\"b\\'{{adgroup_name}}'\"\"}]}]\"
    \\r\\n2024-10-04 15:21,Ad group,{{object_id}},On/Off Status,m**********t@gmail.com,Marketing API,{{app_id}},\"[{\"\"action\"\": \"\"Delete\"\", \"\"name\"\": \"\"Campaign\"\"}]\"\\r\\n2024-10-04 15:21,Ad group,{{object_id}},On/Off Status,m**********t@gmail.com,Marketing API,{{app_id}},\"[{\"\"action\"\": \"\"Delete\"\", \"\"name\"\": \"\"Ad group\"\"}]\"
    \\r\\n2024-10-04 15:21,Ad group,{{object_id}},Budget & Schedule,m**********t@gmail.com,Marketing API,{{app_id}},\"[{\"\"action\"\": \"\"Change \"\", \"\"name\"\": \"\"Budget\"\", \"\"before_after\"\": [{\"\"before\"\": \"\"b\\'50.00 USD\\'\"\", \"\"after\"\": \"\"b\\'20.00 USD\\'\"\"}]}]\"
    \\r\\n2024-10-04 15:20,Ad group,{{object_id}},Targeting,m**********t@gmail.com,Marketing API,{{app_id}},\"[{\"\"action\"\": \"\"Change \"\", \"\"name\"\": \"\"Smart audience\"\", \"\"before_after\"\": [{\"\"before\"\": \"\"b\\'On\\'\"\", \"\"after\"\": \"\"b\\'Off\\'\"\"}]}]\"\\r\\n', 
    'file_name': '{{file_name}}'}",
        "status": "SUCCESS"
    }
}
(/code);
```
