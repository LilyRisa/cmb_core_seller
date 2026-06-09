---
title: "Accounts Insights data latency"
doc_id: 1746624508278786
path: "Organic API / Accounts API / Guides / Accounts Insights data latency"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Accounts Insights data latency

There might be a latency when you try to pull data about TikTok account’s follower base and profile engagement via [/business/get/](https://ads.tiktok.com/marketing_api/docs?id=1762228399168514), or pull reach and engagement data for a TikTok account’s organic posts via [/business/video/list/](https://ads.tiktok.com/marketing_api/docs?id=1762228421622786). 

## Data latency levels
Latencies for data fields of `/business/get/` and `/business/video/list/` fall into two categories: no latency, and day-level latency.
- No latency: Real-time data (such as `username`, and `share_url`) usually have no latency.
- Day-level data latency: Offline data (such as `likes`, and `audience_countries`) usually have a delay of 24-48 hours (UTC Time).

## Reference table for data latency
To quickly see the expected latency for the data field you want to get, check the reference table below.

<table style="width: 80%">
<colgroup>
<col style="width: 20%">
<col style="width: 35%">
<col style="width: 25%">
<thead>
  <tr>
    <th>Endpoint</th>
    <th>Fields</th>
    <th>Latency</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="2">/business/get/</td>
    <td><code>username</code><br><code>display_name</code><br><code>profile_image</code><br><code>followers_count</code> (current total number)</td>
    <td>No</td>
  </tr>
  <tr>
    <td><code>audience_countries</code><br><code>audience_genders</code><br><code>likes</code> <br><code>comments</code><br><code>shares</code><br><code>followers_count</code> (net number gained on the day)<br><code>profile_views</code> <br><code>video_views</code><br><code>audience_activity</code><br><code>date</code></td>
    <td>24-48 hours (UTC Time)</td>
  </tr>
  <tr>
    <td rowspan="2">/business/video/list/</td>
    <td><code>item_id</code><br><code>create_time</code><br><code>thumbnail_url</code><br><code>share_url</code><br><code>embed_url</code><br><code>caption</code></td>
    <td>No</td>
  </tr>
  <tr>
    <td><code>video_views</code><br><code>likes</code><br><code>comments</code><br><code>shares</code><br><code>reach</code> <br><code>video_duration</code><br><code>full_video_watched_rate</code><br><code>total_time_watched</code><br><code>average_time_watched</code><br><code>impression_sources</code><br><code>audience_countries</code></td>
    <td>24-48 hours (UTC Time)</td>
  </tr>
</tbody>
</table>

