---
title: "Delete a comment"
doc_id: 1738957772267522
path: "API Reference / Ad Comments / Delete a comment"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete a comment

Use this endpoint to delete a comment. 

For users who add comments to TikTok ads that are created by users linked to Custom User or TikTok User types of identities (`identity_type` = `CUSTOMIZED_USER` or `TT_USER`), they can delete their own comments.


## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Endpoint path | /v1.2/comment/delete/ | /v1.3/comment/delete/ |
| Request parameter data type | `advertiser_id`: number<br> `comment_id`: number <br>`ad_id`: number  | `advertiser_id`: string <br>`comment_id`: string <br>`ad_id`: string  |
| Request parameter name and data type | `item_id`: number| `tiktok_item_id`: string|
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/comment/delete/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|The content type of the request. <br>Allowed value: `"application/json"`.|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID |
| ad_id {Required} | string | Ad ID |
| tiktok_item_id {Required} | string | TikTok Video ID |
| comment_id {Required} | string | ID of the comment you want delete |
| identity_type {Required} | string | Type of the identity that are linked to the user. Enum values: `CUSTOMIZED_USER` (Custom User), `TT_USER` (TikTok User). For details about identity types, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097). |
| identity_id {Required} | string | Identity ID |
```

### Example

```xcodeblock
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
    private static final String PATH = "/open_api/v1.3/comment/delete/";
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
     * Send POST request
     *
     * @param jsonStr Args in JSON format
     * @return Response in JSON format
     */
    private static String post(String jsonStr) throws IOException, URISyntaxException {
        OkHttpClient client = new OkHttpClient().newBuilder().build();
        String url = buildUrl(PATH);

        RequestBody body = RequestBody.create(MediaType.parse("application/json"), jsonStr);
        Request request = new Request.Builder()
                .url(url)
                .method("POST", body)
                .addHeader("Content-Type", "application/json")
                .addHeader("Access-Token", ACCESS_TOKEN)
                .build();
        Response response = client.newCall(request).execute();
        return response.body().string();
    }


    public static void main(String[] args) throws IOException, URISyntaxException {
        String advertiser_id = ADVERTISER_ID;
        String ad_id = AD_ID;
        String tiktok_item_id = TIKTOK_ITEM_ID;
        String comment_id = COMMENT_ID;
        String identity_type = IDENTITY_TYPE;
        String identity_id = IDENTITY_ID;

        // Args in JSON format
        String myArgs = String.format("{\"advertiser_id\": \"%s\", \"ad_id\": \"%s\", \"tiktok_item_id\": \"%s\", \"comment_id\": \"%s\", \"identity_type\": \"%s\", \"identity_id\": \"%s\"}",advertiser_id, ad_id, tiktok_item_id, comment_id, identity_type, identity_id);
        System.out.println(post(myArgs));
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
PATH = "/open_api/v1.3/comment/delete/"


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
    ad_id = AD_ID
    tiktok_item_id = TIKTOK_ITEM_ID
    comment_id = COMMENT_ID
    identity_type = IDENTITY_TYPE
    identity_id = IDENTITY_ID

    # Args in JSON format
    my_args = "{\"advertiser_id\": \"%s\", \"ad_id\": \"%s\", \"tiktok_item_id\": \"%s\", \"comment_id\": \"%s\", \"identity_type\": \"%s\", \"identity_id\": \"%s\"}" % (advertiser_id, ad_id, tiktok_item_id, comment_id, identity_type, identity_id)
    print(post(my_args))
(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/comment/delete/";

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
 * Send POST request
 * @param $json_str : Args in JSON format
 * @return bool|string : Response in JSON format
 */
function post($json_str)
{
    global $ACCESS_TOKEN, $PATH;
    $curl = curl_init();

    $url = build_url($PATH);

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $json_str,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Access-Token: " . $ACCESS_TOKEN,
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}


$advertiser_id = ADVERTISER_ID;
$ad_id = AD_ID;
$tiktok_item_id = TIKTOK_ITEM_ID;
$comment_id = COMMENT_ID;
$identity_type = IDENTITY_TYPE;
$identity_id = IDENTITY_ID;

/* Args in JSON format */
$my_args = sprintf("{\"advertiser_id\": \"%s\", \"ad_id\": \"%s\", \"tiktok_item_id\": \"%s\", \"comment_id\": \"%s\", \"identity_type\": \"%s\", \"identity_id\": \"%s\"}", $advertiser_id, $ad_id, $tiktok_item_id, $comment_id, $identity_type, $identity_id);
echo post($my_args);
(/code)

(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "ad_id": "AD_ID",
    "tiktok_item_id": "TIKTOK_ITEM_ID",
    "comment_id": "COMMENT_ID",
    "identity_type": "IDENTITY_TYPE",
    "identity_id": "IDENTITY_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/comment/delete/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
|request_id|string|The log id of the request, which uniquely identifies a request. |
```


### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code)
```
