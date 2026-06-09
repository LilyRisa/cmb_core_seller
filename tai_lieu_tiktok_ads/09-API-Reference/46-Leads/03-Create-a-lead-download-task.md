---
title: "Create a lead download task"
doc_id: 1739052936772610
path: "API Reference / Leads / Create a lead download task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a lead download task

Use this endpoint to create a download task for all the leads for the `page_id` or `ad_id` passed in the request.

After the asynchronous download task is created, call `/page/lead/task/` again to check whether the task is complete (`status` = `SUCCEED`). Once the task is complete, use [/page/lead/task/download/](https://ads.tiktok.com/marketing_api/docs?id=1739053563877378) to download all the leads.

Leads data is stored in various locations. Depending on the locations that your ads target, you might need to use different URLs to access the `/page/lead/task/` and [/page/lead/task/download/](https://ads.tiktok.com/marketing_api/docs?id=1739053563877378) endpoints. To determine the locations that your ads target, which also correspond to the locations where the leads data is stored, use [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922). The targeted locations will be returned through `location_ids`, or `zipcode_ids`, or both.
- If you want to access leads for the European Economic Area (EEA), Switzerland (CH), or the United Kingdom (UK), use `https://business-api.tiktok.com/open_api/` and include the header `x-lead-region: eu`.
> <span style="color: DodgerBlue">**Note**</span><br>
 The new URL with the `eu` header above is effective from July 13th (UTC+0), 2023. After July 13th (UTC+0), if you continue using the old URL `https://ads.us.tiktok.com/open_api/`, you will only receive US leads. If you use the URL `https://business-api.tiktok.com/open_api/` without specifying the region in the header, you will only receive leads from other countries/regions.

- If you want to access leads for the US, use one of the following:
  -  (recommended) `https://business-api.tiktok.com/open_api/` with the header `x-lead-region: us`.
  -  `https://ads.us.tiktok.com/open_api/` (planned to be deprecated in future versions)

- If you want to access leads for other countries/regions,  use `https://business-api.tiktok.com/open_api/` without the `x-lead-region` header.

><span style="color:DodgerBlue">**Note**</span><br> To protect customer information, only ad account admins can create and query lead download tasks or download the leads. Ad account roles are configured in the Business Center.<br><br> The leads file will expire in 10 minutes after it was generated. You will get a `file not exist` error if the file has expired.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/pages/leads/task/|/v1.3/page/lead/task/|
|Method|GET|POST|
|Request parameter data type |`advertiser_id`:number<br>`library_id `:number<br>`page_id`:number<br>`ad_id `:number|`advertiser_id`: string<br>`library_id `:string<br>`page_id`:string<br>`ad_id `:string|
```

## Request
**Endpoint** 
- **For leads data in EEA/CH/UK**:  `https://business-api.tiktok.com/open_api/v1.3/page/lead/task/` and the header `x-lead-region: eu`

- **For leads data in US:** 
  - (recommended) `https://business-api.tiktok.com/open_api/v1.3/page/lead/task/` with the header `x-lead-region: us`
  - **[Or]** `https://ads.us.tiktok.com/open_api/v1.3/page/lead/task/`(planned to be deprecated in future versions)

- **For leads data in other countries/regions**:  `https://business-api.tiktok.com/open_api/v1.3/page/lead/task/` 

**Method** POST

**Header**
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {+Conditional}|string|Either `advertiser_id` or `library_id` has to be set. <ul><li>If you haven't migrated the leads of the ad account to a Business Center via [/page/library/transfer/](https://business-api.tiktok.com/portal/docs?id=1740029931324417), specify the `advertiser_id`.</li><li>If you have migrated the leads of the ad account to a Business Center via [/page/library/transfer/](https://business-api.tiktok.com/portal/docs?id=1740029931324417), specify the `library_id`.</li></ul><br>Advertiser ID.|
|library_id {+Conditional}|string|Either `advertiser_id` or `library_id` has to be set. <ul><li>If you haven't migrated the leads of the ad account to a Business Center via [/page/library/transfer/](https://business-api.tiktok.com/portal/docs?id=1740029931324417), specify the `advertiser_id`.</li><li>If you have migrated the leads of the ad account to a Business Center via [/page/library/transfer/](https://business-api.tiktok.com/portal/docs?id=1740029931324417), specify the `library_id`.</li></ul><br>Form library ID.<br><br>To obtain the IDs of form libraries that you can access, use [/page/library/get/](https://business-api.tiktok.com/portal/docs?id=1740029915327489).|
|ad_id {+Conditional}|string| Either `ad_id` or `page_id` has to be set. <ul><li>If you specify `ad_id`, you will create a lead download task for a specific ad.</li><li>If you specify `page_id`, you will create a lead download task for a specific Instant Form. </li></ul><br>Ad ID. <br>Specify the ID of an ad that uses an Instant Form to collect leads. To learn about how to create such ads, see [Create a Lead Generation ad with optimization location as Instant Form](https://business-api.tiktok.com/portal/docs?id=1774482920012801).<br><br>To obtain the IDs of ads within an ad account, use [/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770).|
|page_id {+Conditional}|string| Either `ad_id` or `page_id` has to be set. <ul><li>If you specify `ad_id`, you will create a lead download task for a specific ad.</li><li>If you specify `page_id`, you will create a lead download task for a specific Instant Form. </li></ul><br>Instant form ID.<br><br> To obtain the IDs of Instant Forms within an ad account, use [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) and set `business_type` to `LEAD_GEN`.|
|task_id |string| Task ID.<ul><li>If you do not pass in this field, the request will create a lead download task and you will get the `task_id` in the response.</li><li>If you pass in this field, the request will poll the task status to check whether the task has been completed (`status` = `SUCCEED`).</li></ul>|
```

### Example
#### Create a lead download task for an Instant Form within a form library (EEA/CH/UK region)
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/page/lead/task/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'x-lead-region: eu' \
--data '{
    "library_id":"{{library_id}}",
    "page_id":"{{page_id}}"
}'
(/code)
```

#### Create a lead download task for an ad (US region)
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/page/lead/task/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'x-lead-region: us' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "ad_id":"{{ad_id}}"
}'
(/code)
```

#### Create a lead download task for an Instant Form within an ad account (other countries/regions)
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/page/lead/task/' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "page_id":"{{page_id}}"
}'
(/code)


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
    private static final String PATH = "/open_api/v1.3/page/lead/task/";
    private static final ObjectMapper mapper = new ObjectMapper();

    /**
     * Build request URL
     *
     * @param path Request path
     * @return Request URL
     */
    private static String buildUrl(String path) throws URISyntaxException {
        URI uri = new URI("https", "ads.tiktok.com", path, "", "");
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
        Long advertiser_id = ADVERTISER_ID;
        Long page_id = PAGE_ID;

        // Args in JSON format
        String myArgs = String.format("{\"advertiser_id\": \"%s\", \"page_id\": \"%s\"}",advertiser_id, page_id);
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
PATH = "/open_api/v1.3/page/lead/task/"


def build_url(path, query=""):
    # type: (str, str) -> str
    """
    Build request URL
    :param path: Request path
    :param query: Querystring
    :return: Request URL
    """
    scheme, netloc = "https", "ads.tiktok.com"
    return urlunparse((scheme, netloc, path, "", query, ""))

def post(json_str):
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
    rsp = requests.post(url, headers=headers)
    return rsp.json()

if __name__ == '__main__':
    advertiser_id = ADVERTISER_ID
    page_id = PAGE_ID

    # Args in JSON format
    my_args = "{\"advertiser_id\": \"%s\", \"page_id\": \"%s\"}" % (advertiser_id, page_id)
    print(get(my_args))
    
(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/page/lead/task/";

/**
 * Build request URL
 * @param $path : Request path
 * @return string
 */
function build_url($path)
{
    return "https://ads.tiktok.com" . $path;
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
$page_id = PAGE_ID;

/* Args in JSON format */
$my_args = sprintf("{\"advertiser_id\": \"%s\", \"page_id\": \"%s\"}", $advertiser_id, $page_id);
echo get($my_args);
(/code)
```
#### Query the status of a lead download task for an Instant Form within a form library (EEA/CH/UK region)
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/page/lead/task/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'x-lead-region: eu' \
--data '{
    "library_id":"{{library_id}}",
    "page_id":"{{page_id}}",
    "task_id": "{{task_id}}"
}'
(/code)
```

## Response
```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Return data.|
#|status |string| Task status.<br><br>Enum values: `CREATED`, `FAILED`, `RUNNING`, `SUCCEED`  |
#|task_id |string| Task ID. <br>You can pass in `task_id` in the request to poll the task status to check whether the task has been completed (`status` = `SUCCEED`). |
#|file_name |string| Returned only when `status` is `SUCCEED`.<br><br>File name of the lead file. |
#|file_type |string| Returned only when `status` is `SUCCEED`.<br><br>File type of the lead file. <br><br>Enum values: `csv`, `zip`. |
|request_id |string| Log id of the request, which uniquely identifies a request.|
```

### Example
#### A running task
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "status": "RUNNING",
        "task_id": "{{task_id}}"
    }
}
(/code);
```

#### A successful task (when `task_id` is passed in the request)
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "file_name": "lead_generation_0_2024-01-01.csv",
        "task_id": "{{task_id}}"
        "file_type": "csv",
        "status": "SUCCEED"
    }
}
(/code);
```

