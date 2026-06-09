---
title: "Upgraded Smart+ ad creative combinations by identity and ad type"
doc_id: 1847839781968897
path: "Appendix / Upgraded Smart+ ad creative combinations by identity and ad type"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upgraded Smart+ ad creative combinations by identity and ad type

The following table outlines the various combinations of ad creative combinations based on identity type and ad type.

To learn more about Spark Ads, see [Create Spark Ads](https://business-api.tiktok.com/portal/docs?id=1739470744631298).

<table>
    <thead>
        <tr>
            <th style="width:16%"><b>Identity Type</b></th>
            <th style="width:15%"><b>Ad Type</b></th>
            <th style="width:18%"><b>Available creative combination</b></th>
            <th style="width:23%"><b>Details</b></th>
            <th style="width:28%"><b>Configurations</b></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="3">Custom User
(<code>identity_type</code> in the <code>ad_configuration</code> object is <code>CUSTOMIZED_USER</code>)</td>
            <td rowspan="3">non-Spark Ads</td>
            <td>Videos only creatives</td>
            <td><ul><li>one to 50 videos with cover images</li><li>one to five ad texts</li></ul></td>
            <td><ul><li>Specify one to 50 videos (<code>video_info</code>) with cover images (<code>image_info</code>)</li><li>Specify one to five ad texts (<code>ad_text_list</code>)</li><li>Do not specify <code>music_info</code> and <code>tiktok_item_id</code></li></ul></td>
        </tr>
        <tr>
            <td>Carousel only creatives</td>
            <td><ul><li>one to 50 carousels with each carousel created through one to 35 images and one piece of music</li><li>one to five ad texts</li></ul></td>
            <td><ul><li>Specify one to 50 carousels with each carousel created through one to 35 images (<code>image_info</code>) and one piece of music (<code>music_info</code>)</li><li>Specify one to five ad texts (<code>ad_text_list</code>)</li><li>Do not specify <code>video_info</code> and <code>tiktok_item_id</code></li></ul></td>
        </tr>
        <tr>
            <td>Combination of videos and carousel creatives</td>
            <td><ul><li>A combination of up to 50 videos with cover images and carousels, with each carousel created through one to 35 images and one piece of music</li><li>one to five ad texts</li></ul></td>
            <td><ul><li>Specify a combination of up to 50 videos (<code>video_info</code>) with cover images (<code>image_info</code>) and carousels, with each carousel created through one to 35 images (<code>image_info</code>) and one piece of music (<code>music_info</code>)</li><li>Specify one to five ad texts (<code>ad_text_list</code>)</li><li>Do not specify <code>tiktok_item_id</code></li></ul></td>
        </tr>
        <tr>
            <td rowspan="3">TikTok User<br>/TikTok Account User in Business Center<br>(<code>identity_type</code> within the <code>creative_info</code> object is <code>TT_USER</code> or <code>BC_AUTH_TT</code>)</td>
            <td rowspan="3">Spark Ads created through Spark Ads PUSH</td>
            <td>Videos only creatives</td>
            <td><ul><li>one to 50 videos with cover images</li><li>one to five ad texts</li><li>(Optional) Enable "Show through ads only" to limit your posts to paid traffic or disable the setting to allow the post to appear on your TikTok profile and be eligible to receive organic traffic.</li></ul></td>
            <td><ul><li>Specify one to 50 videos (<code>video_info</code>) with cover images (<code>image_info</code>)</li><li>Specify one to five ad texts (<code>ad_text_list</code>)</li><li>Do not specify <code>music_info</code> and <code>tiktok_item_id</code></li><li>You can either set et <code>dark_post_status</code> to <code>ON</code> to enable "Show through ads only" or <code>OFF</code> to disable "Show through ads only".</li></ul></td>
        </tr>
        <tr>
            <td>Carousel only creatives</td>
            <td><ul><li>one to 50 carousels with each carousel created through one to 35 images and one piece of music</li><li>one to five ad texts</li><li>(Optional) Enable "Show through ads only" to limit your posts to paid traffic or disable the setting to allow the post to appear on your TikTok profile and be eligible to receive organic traffic.</li></ul></td>
            <td><ul><li>Specify one to 50 carousels with each carousel created through one to 35 images (<code>image_info</code>) and one piece of music (<code>music_info</code>)</li><li>Specify one to five ad texts (<code>ad_text_list</code>)</li><li>Do not specify <code>video_info</code> and <code>tiktok_item_id</code></li><li>You can either set et <code>dark_post_status</code> to <code>ON</code> to enable "Show through ads only" or <code>OFF</code> to disable "Show through ads only".</li></ul></td>
        </tr>
        <tr>
            <td>Combination of videos and carousel creatives</td>
            <td><ul><li>A combination of up to 50 videos with cover images and carousels, with each carousel created through one to 35 images and one piece of music</li><li>one to five ad texts</li><li>(Optional) Enable "Show through ads only" to limit your posts to paid traffic or disable the setting to allow the post to appear on your TikTok profile and be eligible to receive organic traffic.</li></ul></td>
            <td><ul><li>Specify a combination of up to 50 videos (<code>video_info</code>) with cover images (<code>image_info</code>) and carousels, with each carousel created through one to 35 images (<code>image_info</code>) and one piece of music (<code>music_info</code>)</li><li>Specify one to five ad texts (<code>ad_text_list</code>)</li><li>Do not specify <code>tiktok_item_id</code></li><li>You can either set et <code>dark_post_status</code> to <code>ON</code> to enable "Show through ads only" or <code>OFF</code> to disable "Show through ads only".</li></ul></td>
        </tr>
        <tr>
            <td rowspan="3">TikTok User<br>/TikTok Account User in Business Center<br>/Authorized User <br>(<code>identity_type</code> within the <code>creative_info</code> object is <code>TT_USER</code>,<code>BC_AUTH_TT</code>, or <code>AUTH_CODE</code>)</td>
            <td rowspan="3">Spark Ads created through Spark Ads PULL</td>
            <td>Video posts only creatives</td>
            <td>one to 50 TikTok video posts</td>
            <td><ul><li>Specify one to 50 TikTok video posts through <code>tiktok_item_id</code>. </li><li>Do not specify  <code>video_info</code>, <code>image_info</code>, <code>music_info</code>, and <code>ad_text_list</code></li></ul></td>
        </tr>
        <tr>
            <td>Photo posts only creatives</td>
            <td>one to 50 TikTok photo posts</td>
            <td><ul><li>Specify one to 50 TikTok photo posts through <code>tiktok_item_id</code>. </li><li>Do not specify  <code>video_info</code>, <code>image_info</code>, <code>music_info</code>, and <code>ad_text_list</code></li></ul></td>
        </tr>
        <tr>
            <td>Combination of video and photo post creatives</td>
            <td>a combination of up to 50 TikTok video posts and photo posts</td>
            <td><ul><li>Specify a combination of up to 50 TikTok video posts and photo posts through <code>tiktok_item_id</code>. </li><li>Do not specify  <code>video_info</code>, <code>image_info</code>, <code>music_info</code>, and <code>ad_text_list</code></li></ul></td>
        </tr>
    </tbody>
</table>

