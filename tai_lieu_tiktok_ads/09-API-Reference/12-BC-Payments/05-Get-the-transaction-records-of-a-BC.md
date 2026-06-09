---
title: "Get the transaction records of a BC"
doc_id: 1739939140408322
path: "API Reference / BC Payments / Get the transaction records of a BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the transaction records of a BC

Use this endpoint to get the transaction records of your Business Centers. 

Both Agency and Direct Business Centers are able to perform payment-related operations. 

> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/transaction/get/| /v1.3/bc/transaction/get/|
|Request parameter data type|`bc_id`: number |`bc_id`: string |
|Response parameter name|`invoice_serial`<br>`total_transfer`<br>` deposit_amount`<br>` transfer_amount` |`invoice_serial_number` <br>` transaction_summary `<br>` amount_charged`<br>` amount_paid`|
|Response parameter data type|`page_info`: number|`page_info`: object|
|New response parameter |/|`page` : number<br>`page_size`: number<br>`total_number`: number<br>`total_page `: number |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/transaction/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required} |string| Business Center ID. |
|filtering|object|Filtering conditions.|
#| funds_type | string | Fund type. If not specified, both types of fund will be returned. Enum values: `FUNDS_TYPE_CASH` (cash), `FUNDS_TYPE_GRANT` (non-cash). |
| start_date | string | Start date of transaction records that you want to get, in the format of "YYYY-MM-DD". The default value is the date 90 days earlier. |
| end_date | string | End date of transaction records that you want to get, in the format of "YYYY-MM-DD". The default value is the date today. |
| page | number | Page number. |
| page_size | number | Page size. Value range: 1-50. |
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
    private static final String PATH = "/open_api/v1.3/bc/transaction/get/";
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
        Long bc_id = BC_ID;
        String funds_type = FUNDS_TYPE;
        String start_date = START_DATE;
        String end_date = END_DATE;
        Long page = PAGE;
        Long page_size = PAGE_SIZE;

        // Args in JSON format
        String myArgs = String.format("{\"bc_id\": \"%s\", \"filtering\": {\"funds_type\": \"%s\"}, \"start_date\": \"%s\", \"end_date\": \"%s\", \"page\": \"%s\", \"page_size\": \"%s\"}",bc_id, funds_type, start_date, end_date, page, page_size);
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
PATH = "/open_api/v1.3/bc/transaction/get/"


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
    bc_id = BC_ID
    funds_type = FUNDS_TYPE
    start_date = START_DATE
    end_date = END_DATE
    page = PAGE
    page_size = PAGE_SIZE

    # Args in JSON format
    my_args = "{\"bc_id\": \"%s\", \"filtering\": {\"funds_type\": \"%s\"}, \"start_date\": \"%s\", \"end_date\": \"%s\", \"page\": \"%s\", \"page_size\": \"%s\"}" % (bc_id, funds_type, start_date, end_date, page, page_size)
    print(get(my_args))
(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/bc/transaction/get/";

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


$bc_id = BC_ID;
$funds_type = FUNDS_TYPE;
$start_date = START_DATE;
$end_date = END_DATE;
$page = PAGE;
$page_size = PAGE_SIZE;

/* Args in JSON format */
$my_args = sprintf("{\"bc_id\": \"%s\", \"filtering\": {\"funds_type\": \"%s\"}, \"start_date\": \"%s\", \"end_date\": \"%s\", \"page\": \"%s\", \"page_size\": \"%s\"}", $bc_id, $funds_type, $start_date, $end_date, $page, $page_size);
echo get($my_args);
(/code)

(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "filtering={\"funds_type\": \"FUNDS_TYPE\"}" \
--data-urlencode "start_date=START_DATE" \
--data-urlencode "end_date=END_DATE" \
--data-urlencode "page=PAGE" \
--data-urlencode "page_size=PAGE_SIZE" \
https://business-api.tiktok.com/open_api/v1.3/bc/transaction/get/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#| page_info | object | Pagination information. |
##|page|number|Current page number. |
##|total_number|number|Total number of results. |
##|page_size|number|Page size. |
##|total_page|number|Total pages of results. |
#| list | object[] | List of transaction records. |
##| date | number | Transaction date. |
##| amount | number | Transaction amount. |
##| timezone | string | Timezone. |
##| currency | string | Currency used in the transaction. |
##| funds_type | string | Fund type. |
##| invoice_id | string | Invoice ID. |
##| invoice_serial_number | string | Invoice serial number |
#| transaction_summary | object | Sum of transactions. |
##| amount_charged | number | Total amount of money charged to the account. |
##| amount_paid | number | Total amount of money transferred out. |
##| currency | string | Currency. |
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


