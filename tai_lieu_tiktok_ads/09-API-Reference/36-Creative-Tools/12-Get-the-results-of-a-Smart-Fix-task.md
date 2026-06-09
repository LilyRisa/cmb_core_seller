---
title: "Get the results of a Smart Fix task"
doc_id: 1741469487859714
path: "API Reference / Creative Tools / Get the results of a Smart Fix task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the results of a Smart Fix task

Use this endpoint to get the results of a Smart Fix task. 

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/video/fix/task/get/ 

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|--- |--- |--- |
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
| Field | Data Type | Description |
|---|---|---|
|task_id {required}|string|Fix task ID. Pass in the `fix_task_id` you get from the response of the [/video/fix/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1741468875279361) or [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449)endpoint. |
|advertiser_id {required} | string | Advertiser ID. <br>Note: If `task_id` and `advertiser_id` do not match, we will return an error message.|
```
### Examples
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/video/fix/task/get/?advertiser_id={{advertiser_id}}&task_id={{task_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data|object|The returned data|
#|status|string|Task status. Enum values: `PROCESSING`, `FAILED`, `SUCCESS`.|
#|error_msg|string|Error message. Returned only when the status is `FAILED`. |
#|videos|object[]|Fixed video info. Max size is 3. Returned only when the status is `SUCCESS`.  |
##|video_id|string|Fixed video ID.|
##|video_url|string|Fixed video preview URL. Valid only for 7 days. |
|request_id |string|The log id of a request, which uniquely identifies the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "202208180952320101860032142108B920",
    "data": {
        "error_msg": null,
        "videos": [
            {
                "video_url": "http://v16m-default.akamaized.net/a17b15b1c623eea1856c89d521af1bfc/63074676/video/tos/alisg/tos-alisg-v-0000/c98a4fb0433d4e3e9adf27228c7543cb/?a=0&ch=0&cr=0&dr=0&er=0&lr=default&cd=0%7C0%7C0%7C0&br=748&bt=374&cs=0&ds=1&ft=uSV.Ng3-Inz.Jx9oABM&mime_type=video_mp4&qs=0&rc=NTk2NWk7Nzg0O2VlMzloZUBpamtvcmU6ZnIzZTMzODYzNEAuMV9gLTIwNmAxLWMyNGI1YSNxLjNicjRnanNgLS1kMC1zcw%3D%3D&l=202208180952320101860032142108B920&btag=80000",
                "video_id": "v10033g50000cbv0mojc77ubeo6l1htg"
            },
            {
                "video_url": "http://v16m-default.akamaized.net/85d8eb552878e7f12f81c692e987bdb8/63074676/video/tos/alisg/tos-alisg-v-0000/67029d0cd5a7411bbd25c42062765a21/?a=0&ch=0&cr=0&dr=0&er=0&lr=default&cd=0%7C0%7C0%7C0&br=748&bt=374&cs=0&ds=1&ft=uSV.Ng3-Inz.Jx9oABM&mime_type=video_mp4&qs=0&rc=M2k4OTs3Zjg6aWU0aTU7O0BpM3BqeWY6ZnIzZTMzODYzNEA1Yl81NS8vXjExNWJfYTY1YSNnZmAucjRnanNgLS1kMC1zcw%3D%3D&l=202208180952320101860032142108B920&btag=80000",
                "video_id": "v10033g50000cbv0mojc77uc1vcgimj0"
            },
            {
                "video_url": "http://v16m-default.akamaized.net/11609ae2c7055dfee98b657103e4e3e0/63074676/video/tos/alisg/tos-alisg-v-0000/cd9e274c0def4d398b1228a35ebabd19/?a=0&ch=0&cr=0&dr=0&er=0&lr=default&cd=0%7C0%7C0%7C0&br=746&bt=373&cs=0&ds=1&ft=uSV.Ng3-Inz.Jx9oABM&mime_type=video_mp4&qs=0&rc=NjNkNzo8NGhoNmlmaTxpNkBpM2U4bzk6ZnMzZTMzODYzNEBeMDJeXzY2NmExNDMxMV4yYSNwNWhhcjQwanNgLS1kMC1zcw%3D%3D&l=202208180952320101860032142108B920&btag=80000",
                "video_id": "v10033g50000cbv0mp3c77u6dlk58bs0"
            }
        ],
        "status": "SUCCESS"
    }
}
(/code);
```

><span style="font-size:14px">Related Links</span> 
>* <span style="font-size:14px">[Smart Fix](https://ads.tiktok.com/marketing_api/docs?id=1741472523309058)</span>
