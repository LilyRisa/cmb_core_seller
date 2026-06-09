---
title: "Upload videos, images and music"
doc_id: 1738963828459521
path: "Marketing API / Creatives / Guides / Creative assets / Upload videos, images and music"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload videos, images and music

This article walks you through the process of uploading creatives (videos, images, and music) to the TikTok Asset Library and using them in ads on TikTok, Pangle, or Global App Bundle.

# Upload videos

Use [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) endpoint to upload videos. For videos to be used as TikTok ads, The width & height ratio must be 1:1, 9:16, or 16:9. The resolution must be ≥540\*960px, ≥640\*640px, or ≥960\*540px. 

For detailed video specifications that apply to the TikTok, Global App Bundle, and Pangle placements, see [Video ads specifications](https://ads.tiktok.com/help/article?aid=9626).

# Upload images

Images can be divided into three categories: images to be used as ads, images as video covers, and profile images.

## Image to be used as ads
Use [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642) to upload images to the Asset Library. In the response, the image ID (`image_id` field) will be returned. This ID can be used as the image ID when you create an ad. 

For detailed specifications that apply to the Global App Bundle, and Pangle placements, see [Image ads specifications](https://ads.tiktok.com/help/article/image-ads-specification?lang=zh).

To learn about the steps of creating single image ads, refer to [Create single image ads](https://business-api.tiktok.com/portal/docs?id=1777633230937090).

To learn about the steps of creating Carousel Ads, refer to [Create Carousel Ads](https://business-api.tiktok.com/portal/docs?id=1766217791987713).

## Video covers
For video ads on TikTok, you need to have an image as the video cover. An image that will be used as a video cover must have the same width & height ratio as the video. TikTok provides an endpoint for generating video covers (thumbnails) for videos. If you do not have video cover images at hand and want to save the hassle of manually creating one, you can use [/file/video/suggestcover/](https://ads.tiktok.com/marketing_api/docs?id=1740051189071873) to generate a few suggested cover images. In the response, image IDs and preview links will be returned. You can view and decide on which one to use.

## Profile images
Use [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642) to upload images to the TikTok Asset Library.  If the image to upload is used as the profile image (`avatar_icon_web_uri`), the width & height ratio must be 1:1.

# Upload music
Use [/file/music/get/](https://ads.tiktok.com/marketing_api/docs?id=1740053909509122) to get the list of available music that can be used for video creation. If you cannot find a piece of music that is suitable for your ads, use [/file/music/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740052650395650) to upload a piece of music. 

# Upload video, image and music files
You can upload videos, music, and images to TikTok's temporary file repository. Once a file is uploaded to the file repository, it is kept for 24 hours, after which it will expire. For a file that is larger than 20 MB, you can segment it into chunks and then upload the chunks to the file repository. Files in the repository can be transferred to TikTok’s Asset Library and then used in your ads.

## Upload a single file to file repository
For music, videos, or images, you can use a consolidated endpoint [/file/temporarily/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737720893061122) to upload them to the TikTok Asset Library. You need to specify the upload type (`FILE` or `URL`) and the file type (`image`, `music`, `video`). In the response, the creative ID will be returned. You can specify this creative ID when creating an ad.

## Upload file chunks to file repository
For a file that is larger than 20 MB, you can segment it into chunks, upload one chunk at a time, and then package the chunks into a single file.

To upload chunked files to the file repository, follow the steps below:
1. Call [/file/start/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737721229896705) to initiate a request for the upload.
2. Use [/file/transfer/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737721489945601) to upload one chunk at a time. 
<br>The chunk size for the chunks excluding the last chunk is fixed at 20MB. The size of the last chunk can be between 5MB and 25 MB. You can check the values for `start_offset` and `end_offset` returned from `/file/transfer/upload/` to determine whether it will be the last chunk.
3. When you have uploaded the last chunk, call [/file/finish/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737721642924033) to finish the upload. 
<br>On the server side, the chunks will be pieced together to a single file. A `file_id` will be returned in the response. You can use `file_id` to retrieve the file in the repository and upload it to the Asset Library within 24 hours. 

# Next steps

When creating or updating an ad via [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) or [/ad/update/](https://ads.tiktok.com/marketing_api/docs?id=1739953405142018&rid=4popgjyknru), specify these fields to include creatives in ads. 
- `image_ids`: For an video cover image, pass in ONLY ONE ID to the list. If you are not sure about the ID to use, use [/file/image/ad/search/](https://ads.tiktok.com/marketing_api/docs?id=1740052016789506) endpoint to get the list of images you have access to and locate the ID for the image that you want to use. 
- `video_id`: Pass in the ID of the video that you want to use for the video ad. If you are not sure about the ID to use, use [/file/video/ad/search/](https://ads.tiktok.com/marketing_api/docs?id=1740050472224769) endpoint to get the list of videos you have access to and locate the ID for the video that you want to use. 
