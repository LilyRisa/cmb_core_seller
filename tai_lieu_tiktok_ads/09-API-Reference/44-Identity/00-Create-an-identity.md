---
title: "Create an identity"
doc_id: 1740654203526146
path: "API Reference / Identity / Create an identity"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an identity

Use this endpoint to create a Custom User (`CUSTOMIZED_USER`) identity.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/identity/create/| /v1.3/identity/create/|
|Request parameter name|`avatar_icon_web_uri` |`image_uri` |
|Request parameter data type|`advertiser_id`: number |` advertiser_id`: string |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/identity/create/

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
| advertiser_id {Required} | string | Advertiser ID. |
| display_name {Required} | string | Display name. <br><br>The maximum length is 100 characters. |
| image_uri  | string |The ID of the avatar image for the identity. <br><br>The width and height ratio must be 1:1. <br><br>To obtain the ID of an avatar image, you can upload the image by using [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642).  <br><br>If this field is not specified, a default image will be used as the avatar.|
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
    private static final String PATH = "/open_api/v1.3/identity/create/";
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
        String image_uri = IMAGE_URI;
        String display_name = DISPLAY_NAME;

        // Args in JSON format
        String myArgs = String.format("{\"advertiser_id\": \"%s\", \"image_uri\": \"%s\", \"display_name\": \"%s\"}",advertiser_id, image_uri, display_name);
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
PATH = "/open_api/v1.3/identity/create/"


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
    image_uri = IMAGE_URI
    display_name = DISPLAY_NAME

    # Args in JSON format
    my_args = "{\"advertiser_id\": \"%s\", \"image_uri\": \"%s\", \"display_name\": \"%s\"}" % (advertiser_id, image_uri, display_name)
    print(post(my_args))

(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/identity/create/";

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
$image_uri = IMAGE_URI;
$display_name = DISPLAY_NAME;

/* Args in JSON format */
$my_args = sprintf("{\"advertiser_id\": \"%s\", \"image_uri\": \"%s\", \"display_name\": \"%s\"}", $advertiser_id, $image_uri, $display_name);
echo post($my_args);

(/code)

(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "image_uri": "IMAGE_URI",
    "display_name": "DISPLAY_NAME"
}' \
https://business-api.tiktok.com/open_api/v1.3/identity/create/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#|identity_id|string|Identity ID.|
|request_id|string|The log id of the request, which uniquely identifies a request. |
```


### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "LOG_ID",
    "data": {
        "identity_id": "IDENTITY_ID"
    }
}
(/code)
```


