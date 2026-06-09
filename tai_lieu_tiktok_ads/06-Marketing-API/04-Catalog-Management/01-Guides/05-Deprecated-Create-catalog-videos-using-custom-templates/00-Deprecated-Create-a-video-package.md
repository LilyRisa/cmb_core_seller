---
title: "(Deprecated) Create a video package"
doc_id: 1739470992872450
path: "Marketing API / Catalog Management / Guides / (Deprecated) Create catalog videos using custom templates / (Deprecated) Create a video package"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Create a video package

A video package is a group of videos that you can directly use in your ads, for example, Shopping Ads. You can now create a green screen type of video package with TikTok API for Business. 

## Materials needed

To create a green screen video package, you need the following materials:

* A green screen video
* A few regular videos
* A video template
* A music file

You'll need the IDs of these materials (`video_id`, `template_id`, and `music_id`) to create a video package. Therefore, you need to upload the videos, template and music to the Asset Library or Business Center account. 
* Use the [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) endpoint to upload the videos.
* Use the [/catalog/template/upload/](https://ads.tiktok.com/marketing_api/docs?id=1708594874651650) endpoint to upload a video template.
* Use the [/file/music/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740052650395650) endpoint to upload the music.

## Specifications of the materials

* You must have one and can have only one green screen video.
* The maximum duration of the green-screen video is 20 seconds. 
* The green-screen video must be longer than the video template.
* The duration of the music must be longer then the length of the combined video.
* The maximum resolution for the video template is 720x1280. 
* For the green screen section in the green screen video, the R and B values in the RGB color model must be less than 120, and the G value must be larger than 150. Additionally, the hue value as in the HSV representation must be larger than 70 but less than 154. For example, a RGB combination (50, 217, 70) would work.

## Creating a video package

Make a `POST` request to the [/catalog/video_package/create/](https://ads.tiktok.com/marketing_api/docs?id=1740665273039873) endpoint to create a video package. Use the following settings:
* Set `video_package_type` to `GREEN_SCREEN`. This is the only supported video package type now.
* Pass in values for `template_id` and `music_id`.
* Include the data about the videos in an object. The sequence of the videos in the object will determine their sequence in the generated video package.
* For the only green screen video, set `is_green_screen_video` to `true`. For the rest of the videos, set this field to `false`.

After a video package is created, you can update its name, preview it, or delete it.

## API reference

* [Get video packages](https://ads.tiktok.com/marketing_api/docs?id=1740574099715073)
* [Create video package](https://ads.tiktok.com/marketing_api/docs?id=1740665273039873)
* [Update video package](https://ads.tiktok.com/marketing_api/docs?id=1740665303669761)
* [Delete video package](https://ads.tiktok.com/marketing_api/docs?id=1740665332455426)
* [Upload video package](https://ads.tiktok.com/marketing_api/docs?id=1740665346584577)
* [Preview video template](https://ads.tiktok.com/marketing_api/docs?id=1740665368159233)

Here is an example of the data part in the request.
```xcodeblock
(code JSON JSON)
{
    "bc_id": 6725320885500118022,
    "catalog_id": 6986901069595002626,
    "video_package_type": "GREEN_SCREEN",
    "videos": [
        {"video_id": "v07033g50000c38pjfoupb082t6ln5f0", "is_green_screen_video": false},
        {"video_id": "v10033g50000c4574b3c77uaa2tq5100", "is_green_screen_video": true},
        {"video_id": "v07033g50000c23ptb2h6o7cqvaadut0", "is_green_screen_video": false}  
    ],
    "template_id": "6992826957676134402",
    "name": "green screen generation",
    "music_id": "6904951200983285762"
}
(/code)
```



