---
title: "(Deprecated) Create a Smart Video task"
doc_id: 1739352294717441
path: "API Reference / Creative Tools / (Deprecated) Create a Smart Video task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Create a Smart Video task

Use this endpoint to create a Smart Video task. 

See [Smart Video Soundtrack, Smart Video, and Quick Optimization](https://ads.tiktok.com/marketing_api/docs?id=1749022727865345) to find out the brief introduction of the Smart Video feature.

Generated video name is `video<timestamp>_smart_video_<index>.mp4`.

The rate limits for this endpoint are: 
```xtable
| Level {20%} | Query Per Second{20%} | Query Per Minute{20%} | Query Per Day{20%} |
|---|---|---|
| Basic | 1 | 30 | 5000 |
| Advanced | 2 | 60 | 10000 |
| Premium | 3 | 90 | 15000 |
```

> <span style="color:darkred">**Copyright Notice**</span><br>You shall not download, make available, distribute or perform the video and/or the music file incorporated in the video on any media other than TikTok, unless you have obtained separate permission form the music rights holders.<br>You shall be solely responsible for any third party claims and disputes caused by your breach of these terms, and will defend and indemnify TikTok and its affiliates for all losses sustained.



## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/creative/smart_video/create/|/v1.3/creative/smart_video/create/ |
| Request parameter name|`video_list` <br> `image_list` <br> `text_list` <br> `value`| `videos` <br> `images` <br> `texts` <br> `text`|
| Request parameter data type | `advertiser_id`: number |`advertiser_id`: string |
```

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/smart_video/create/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|--- |--- |--- |
|Content-Type {Required}|string|Request content type. <br>Accepted Value: `"application/json"`.  |
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
| Field                     | Type | Description                                              |
| ----------------------------- | -------- | ------------------------------------------------------------ |
| advertiser_id{Required}       | string   | Advertiser ID.                                               |
| videos {Required}                   | object[]     | Video list. Number of videos <=5. <br>Note that either video list (`videos`) or image list (`images`) is required for creating a smart video, but not necessarily both of them at the same time.                                         |
#| video_id{Required}            | string   | Video ID.                                                     |
#| tag                           | string   | Material tag. For enum values, see [Enumeration-Creative Management-Material Tag](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).              |
| images{Required}          |  object[]      | Image list. Number of images <=10.<br>Note that either video list  (`videos`) or image list (`images`) is required for creating a smart video, but not necessarily both of them at the same time.                                           |
#| image_id{Required}            | string   | Image ID.                                                     |
#| tag                           | string   | Material tag. For enum values, see [Enumeration-Creative Management-Material Tag](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).              |
| texts                     |  object[]      | Text list.                                                   |
#| text{Required}               | string   | Text.                                                         |
#| tag                           | string   | Material tag. For enum values, see [Enumeration-Creative Management-Material Tag](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).            |
| layout{Required}              | string   | Horizontal screen or vertical screen. Enum values: `VERTICAL`，`HORIZONTAL`. |
| style{Required}               | string   | Background Music style. Enum values: `PEACEFUL`，`DYNAMIC`，`CUSTOM`.                |
| music_id{+Conditional}       | string   | Music ID. Required when `style` is `CUSTOM`.                    |
| duration                      | number   | Duration of generated video. Max duration is 60s.             |
| frame                         |  object[]      | Keyframe.                                                    |
#| frame_type      | string   | Frame type. Currently we only support `FINAL_FRAME` (Closing frame). |
#| material_type   | string   | Material type. Enum values: `VIDEO`, `IMAGE`, `TEMPLATE`.        |
#| video_id{+Conditional}       | string   | Video ID. Required when `material_type` is `VIDEO`.               |
#| image_id{+Conditional}       | string   | Image ID. Required when `material_type` is `IMAGE`.             |
#| template_type{+Conditional}  | string   | Generated template. Required when `material_type` is `TEMPLATE`.  For enum values, see [Enumeration-Creative Management-Frame Template Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
#| logo{+Conditional}           | string   | Logo Image ID. Required when `material_type` is `TEMPLATE`.       |
#| call_to_action{+Conditional} | string   | Instructions. Required when `material_type` is `TEMPLATE`. Recommended length is less than 20 characters. You can improve the click rate of your ad by adding a call to action in the final frame of your ad.  |
#| slogan{+Conditional}         | string   | Slogan. Required when `material_type` is `TEMPLATE`. Recommended length is less than 40 characters. |
#| brand_name{+Conditional}      | string   | Brand name. Required when `material_type` is `TEMPLATE`. Recommended length is less than 40 characters. <br><p><span style="color:darkred"><b>Note</b></span>: When you specify `template_type` as `end_frame_vertical_3`，`end_frame_vertical_5`，`end_frame_horizontal_3`，or `end_frame_horizontal_5`, `brand_name` will be ignored because these four templates does not show brand names.|
| callback_info                 | object   | Callback information.                                         |
#| callback_url{+Conditional}      | string   | Callback address. Required when you set `callback_info`.                                     |
#| callback_extra_info           | object   | Additional callback information.                              |
```

### Example
``` xcodeblock
(code Python python)
# coding=utf-8
import json
import requests

from six import string_types
from six.moves.urllib.parse import urlencode, urlunparse  # noqa

ACCESS_TOKEN = "xxx"
PATH = "/open_api/v1.3/creative/smart_video/create/"


def build_url(path, query=""):
    # type: (str, str) -> str
    """
    Build request URL
    :param path: Request path
    :param query: Querystring
    :return: Request URL
    """
    scheme, netloc = "https", "business-api.tiktok.com"
    return urlunparse((scheme, netloc, path, "", query, ""))

def post(json_str):
    # type: (str) -> dict
    """
    Send POST request
    :param json_str: Args in JSON format
    :return: Response in JSON format
    """
    url = build_url(PATH)
    args = json.loads(json_str)
    headers = {
        "Access-Token": ACCESS_TOKEN,
        "Content-Type": "application/json",
    }
    rsp = requests.post(url, headers=headers, json=args)
    return rsp.json()


if __name__ == '__main__':
    advertiser_id = ADVERTISER_ID
    style = STYLE
    layout = LAYOUT
    video_list = [{"video_id":VIDEO_ID, "tag":VIDEO_TAG}]
    videos = json.dumps(video_list)
    image_list = [{"image_id": IMAGE_ID}]
    images = json.dumps(image_list)
    text_list = [{"text": TEXT1, "tag": TEXT_TAG1}, {"text": TEXT2, "tag": TEXT_TAG2}]
    texts = json.dumps(text_list)
    frame_list = [{"frame_type": "FINAL_FRAME", "material_type": "VIDEO", "video_id": VIDEO_ID}]
    frame = json.dumps(frame_list)
    callback_info={
        "callback_url": "CALLBACK_URL",
        "callback_extra_info": {
            "info": "EXTRA_INFO",
            "subject": "EXTRA_SUBJECT"
        }
    }
    callback_info=json.dumps(callback_info)

    # Args in JSON format
    my_args = "{\"advertiser_id\": \"%s\", \"style\": \"%s\", \"layout\": \"%s\", \"videos\": %s, \"images\": %s, \"texts\": %s, \"frame\": %s, \"callback_info\":%s}" % (advertiser_id, style, layout, videos, images, texts, frame, callback_info)
    print(post(my_args))
(/code)

(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "style": "STYLE",
    "layout": "LAYOUT",
    "videos": [
        {
            "video_id": "VIDEO_ID",
            "tag": "VIDEO_TAG"
        }
    ],
    "images": [
        {
            "image_id": "IMAGE_ID"
        }
    ],
    "texts": [
        {
            "text": "TEXT1",
            "tag": "TEXT_TAG1"
        },
        {
            "text": "TEXT2",
            "tag":"TEXT_TAG2"
        }
    ],
    "frame": [
        {
            "frame_type": "FINAL_FRAME",
            "material_type": "VIDEO",
            "video_id": "VIDEO_ID"
        }
    ],
    "callback_info": {
        "callback_url": "CALLBACK_URL",
        "callback_extra_info": {
        }
    }
}' \
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data|json|Returned data.|
#|task_id |string|Task ID. |
|request_id|string|The log ID of a request, which uniquely identifies the request.
```

### Example
```xcodeblock
S(code Success-Response python)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "task_id": "1864502164429996038"
    },
    "request_id": "2020082410541601018904922344E10F07"
}
(/code)
```
