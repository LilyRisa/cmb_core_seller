---
title: "Get info about a Spark Ad post"
doc_id: 1738376324021250
path: "API Reference / Spark Ads Using Authorized Posts / Get info about a Spark Ad post"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get info about a Spark Ad post

Use this endpoint to get information about the Spark Ads post that you have been authorized to use as an ad. You need to pass in the authorization code from the post owner.

## Before you start
Ensure that you have obtained an authorization code from the creator (TikTok post owner). 
You can ask the creator to generate the authorization code by following these steps:
1. Open the TikTok app and tap on the three dots on the post you want to share.
2. In the share drawer, tap Ad settings.
3. Toggle on Ad authorization.
4. Select an authorization duration.
5. Generate and copy the authorization code.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{20%}|v1.2{40%}|v1.3{40%}|
|---|---|---|
|Endpoint path|/v1.2/tt_video/info/|/v1.3/tt_video/info/|
|Request parameter data type|`advertiser_id`: number|`advertiser_id`: string|
|Response parameter name|`url` (in `video_info`)|`preview_url` (in `video_info`)|
|Response parameter data type|`auth_end_time`: datetime<br>`auth_start_time`: datetime<br>`id` (in `anchor_list`): number<br>`item_id` (in `item_info`): number<br>`video_id` (in `video_info`): number|`auth_end_time`: string<br>`auth_start_time`: string<br>`id` (in `anchor_list`): string<br>`item_id` (in `item_info`): string<br>`video_id` (in `video_info`): string|
|Response parameter name and data type|`stitch_from_item_id` (in `item_info`): number<br>`duet_from_item_id` (in `item_info`): number|`stitch_original_item_id` (in `item_info`): string<br>`duet_original_item_id` (in `item_info`): string|
|New response parameters|/|`url` (in `anchor_list`)<br>`mentioned_item_ids`<br>`item_type`<br>`carousel_info`(including all parameters within the object)<br>`status`<br>`spu_id`<br>`spu_name`<br>`store_id`|
|Response parameter deprecated in v.1.3|/|`id` (in `video_info`): number|
```


## Request

**Endpoint**  
https://business-api.tiktok.com/open_api/v1.3/tt_video/info/

**Method** GET

**Header**

``` xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {required}|string| The content type of the request.<br>Allowed value: `"application/json"` . |
```

**Parameters**

``` xtable
|Field|Type|Description|
|--- |--- |--- |
|advertiser_id {required}|string| Advertiser ID.|
|auth_code {required}|string| The authorization code for the Spark Ads post. <br><br><p><span style="color:darkred"><b>Note</b></span>: Replace "+" in your authorization code with "%2B". Otherwise, you may get an invalid authorization code error. For example, you can replace the "+" in the code "#XtjblzbEuOGK/BvHj4V/K0i71JL3Wu2fgRtOJrZSSf4SrPAedegtn+IH+UXr3f4=" and specify "#XtjblzbEuOGK/BvHj4V/K0i71JL3Wu2fgRtOJrZSSf4SrPAedegtn%2BIH%2BUXr3f4=".|
```

### Example

``` xcodeblock
(code Java java)
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import okhttp3.*;
import org.apache.http.client.utils.URIBuilder;

import java.io.IOException;
import java.net.URI;
import java.net.URISyntaxException;
import java.net.URL;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

public class Demo {
    private static final String ACCESS_TOKEN = "xxx";
    private static final String PATH = "/open_api/v1.3/tt_video/info/";
    private static final ObjectMapper mapper = new ObjectMapper();

    /**
     * Build request URL
     *
     * @param path Request path
     * @return Request URL
     */
    private static String buildUrl(String path) throws URISyntaxException {
        URI uri = new URI("https", "business-api.tiktok.com", path, "", "");
        return uri.toString();
    }

    
    /**
     * Send GET request
     *
     * @param jsonStr:Args in JSON format
     * @return Response in JSON format
     */
    private static String get(String jsonStr) throws IOException, URISyntaxException {
        OkHttpClient client = new OkHttpClient().newBuilder().build();
        URIBuilder ub = new URIBuilder(buildUrl(PATH));
        Map< String, Object > map = mapper.readValue(jsonStr, Map.class);
        map.forEach((k, v) -> {
            try {
                ub.addParameter(k, v instanceof String ? (String) v : mapper.writeValueAsString(v));
            } catch (JsonProcessingException e) {
                e.printStackTrace();
            }
        });
        URL url = ub.build().toURL();

        Request request = new Request.Builder()
                .url(url)
                .method("GET", null)
                .addHeader("Access-Token", ACCESS_TOKEN)
                .build();
        Response response = client.newCall(request).execute();
        return response.body().string();
    }

    public static void main(String[] args) throws IOException, URISyntaxException {
        String advertiser_id = ADVERTISER_ID;
        String auth_code = AUTH_CODE;

        // Args in JSON format
        String myArgs = String.format("{\"advertiser_id\": \"%s\", \"auth_code\": \"%s\"}",advertiser_id, auth_code);
        System.out.println(get(myArgs));
    }
}

(/code)

(code Python python)
# coding=utf-8
import json
import requests

from six import string_types
from six.moves.urllib.parse import urlencode, urlunparse  # noqa

ACCESS_TOKEN = "xxx"
PATH = "/open_api/v1.3/tt_video/info/"


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

def get(json_str):
    # type: (str) -> dict
    """
    Send GET request
    :param json_str: Args in JSON format
    :return: Response in JSON format
    """
    args = json.loads(json_str)
    query_string = urlencode({k: v if isinstance(v, string_types) else json.dumps(v) for k, v in args.items()})
    url = build_url(PATH, query_string)
    headers = {
        "Access-Token": ACCESS_TOKEN,
    }
    rsp = requests.get(url, headers=headers)
    return rsp.json()

if __name__ == '__main__':
    advertiser_id = ADVERTISER_ID
    auth_code = AUTH_CODE

    # Args in JSON format
    my_args = "{\"advertiser_id\": \"%s\", \"auth_code\": \"%s\"}" % (advertiser_id, auth_code)
    print(get(my_args))
    
(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/tt_video/info/";

/**
 * Build request URL
 * @param $path : Request path
 * @return string
 */
function build_url($path)
{
    return "https://business-api.tiktok.com" . $path;
}

/**
 * Send GET request
 * @param $json_str : Args in JSON format
 * @return bool|string : Response in JSON format
 */
function get($json_str)
{
    global $ACCESS_TOKEN, $PATH;
    $curl = curl_init();

    $args = json_decode($json_str, true);

    /* Values of querystring is also in JSON format */
    foreach ($args as $key => $value) {
        $args[$key] = is_string($value) ? $value : json_encode($value);
    }

    $url = build_url($PATH) . "?" . http_build_query(
            $args
        );

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Access-Token: " . $ACCESS_TOKEN,
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}


$advertiser_id = ADVERTISER_ID;
$auth_code = AUTH_CODE;

/* Args in JSON format */
$my_args = sprintf("{\"advertiser_id\": \"%s\", \"auth_code\": \"%s\"}", $advertiser_id, $auth_code);
echo get($my_args);
(/code)

(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "auth_code=AUTH_CODE" \
https://business-api.tiktok.com/open_api/v1.3/tt_video/info/
(/code)
```

## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|message|string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|The returned data.|
#|auth_info |object| Information about the authorization.|
##|auth_end_time |string| The time when the authorization code expires (UTC+0), in the format of 2017-01-01 00:00:00.|
##|auth_start_time |string| The time when the authorization code becomes valid (UTC+0), in the format of 2017-01-01 00:00:00.|
#|item_info |object| Information about the Spark Ads post. |
##|auth_code |string| The authorization code for the Spark Ads post.|
##|item_id |string| The ID of the Spark Ads post. |
##| item_type | string | The type of Spark Ads post.<br><br> Enum values: <ul><li>`VIDEO`: video post.</li><li>`CAROUSEL`: photo post.</li></ul> |
##|text |string| The description of the Spark Ads post. |
##| status | string | The status of the Spark Ads post. <br><br>For enum values, see [Enumerations-TikTok Post Item Status](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-TikTok%20Post%20Item%20Status). |
##| carousel_info | object | Information about the photo post. <br> <br><p><span style="color:darkred"><b>Note</b></span>: When `item_type` is `VIDEO`, the value of this field will be null. |
###| image_info | object[] | Information about the images used in the photo post. <br> The images are returned in the same order as they are shown in the photo post. |
####| image_url | string | The URL of the image. <br><br>Validity period: 90 days. |
####| image_height | integer | The height of the image, measured in pixels. |
####| image_width | integer| The width of the image, measured in pixels. |
###| music_info | object | Information about the music used in the photo post. <br><br><p><span style="color:darkred"><b>Note</b></span>: If the photo post is directly published in the TikTok App without music, the value of this field will be null. |
####| music_url | string | The URL of the music.<br><br>Validity period: 90 days. |
####| music_duration | integer | The duration of the music, in seconds. |
##|stitch_original_item_id|string| If the post is a stitch from another post, the item ID of the original post.|
##|duet_original_item_id|string| If the post is a duet from another post, the item ID of the original post.|
##|is_multi_duet_stitch|boolean| Whether this is a duet or stitch post that is created based on another duet or stitch post.|
##|mentioned_item_ids|string[]|If the post mentions another video, the item IDs of the original posts. <br>Note: The post can only mention at most one video.|
##|anchor_list | object []|Information about anchors in the post (video).|
###|id|string | Anchor ID.|
###|title |string | Anchor title.|
###|status  | string | Anchor status. <br><br>Enum values: `CHECK_ING` (anchor review in progress), `CHECK_FAILED` (anchor review failed), `CHECK_SUCCESS` (anchor review successful). |
###|product_regions |string[] | Regions where the ad can be deployed.|
###|url|string| Item URL.|
###| spu_id | string | Returned only when the post contains a product anchor link <br><br> The SPU ID of the product that the post is associated with. |
###| spu_name | string | Returned only when the post contains a product anchor link <br><br> The name of the product that the post is associated with. |
###| store_id | string | Returned only when the post contains a product anchor link <br><br> The ID of the TikTok Shop that the product belongs to. |
#|user_info |object| Information about the TikTok account.|
##|tiktok_name |string| The user name of the TikTok account.|
##|identity_id |string| Identity ID. |
##|identity_type |string| Identity Type. |
#|video_info |object| Information about the video post.<br><br><p><span style="color:darkred"><b>Note</b></span>: When `item_type` is `CAROUSEL`, the values for the parameters within the `video_info` object will be null. |
##|bit_rate |integer|Video bit rate, in bps.  |
##|duration |float| The duration of the video, in seconds.  |
##|size |integer|The size of the video, in bytes.  |
##|width |integer| The width of the video.  |
##|height |integer|The height of the video. |
##|video_id|string| Video ID. |
##|file_name|string| Video name. |
##|poster_url |string|The URL to the video poster. It is valid for an hour. When it expires, you need to call the endpoint again to get a new URL.|
##|preview_url |string|The preview URL for the video. It is valid for an hour. When it expires, you need to call the endpoint again to get a new URL. |
##|signature |string|MD5 for the video. |
|request_id |string|The unique log ID for the request.  |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "auth_info": {
            "auth_start_time": "2022-07-08 07:32:45",
            "auth_end_time": "2022-08-07 07:32:44"
        },
        "video_info": {
            "file_name": "",
            "height": 1280,
            "size": 813340,
            "width": 720,
            "signature": "{{signature}}",
            "duration": 1.881,
            "preview_url": "{{preview_url}}",
            "poster_url": "{{poster_url}}",
            "bit_rate": {{bit_rate}},
            "video_id": "{{video_id}}"
        },
        "item_info": {
            "stitch_original_item_id": "0",
            "auth_code": "{{auth_code}}",
            "item_id": "{{item_id}}",
            "anchor_list": null,
            "text": "",
            "is_multi_duet_stitch": false,
            "duet_original_item_id": "0",
		    "carousel_info": null,
            "item_type": "VIDEO"
        },
        "user_info": {
            "identity_type": "AUTH_CODE",
            "tiktok_name": "{{tiktok_name}}",
            "identity_id": "{{identity_id}}"
        }
    }
}
(/code);
```

