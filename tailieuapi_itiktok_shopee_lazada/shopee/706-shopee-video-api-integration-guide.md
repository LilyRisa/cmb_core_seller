# Shopee Video API Integration Guide

> Source: https://open.shopee.com/developer-guide/706
> Category: 
> Scraped: 2026-05-20T20:39:03.071Z

---

## 1\. Introduction to Shopee Video

  

Shopee Video is a content creation and marketing tool that enables sellers and affiliates to showcase products through engaging short videos. It provides flexibility for both sellers and affiliates to produce and share content. By leveraging visual storytelling, creators can increase engagement and drive higher conversion rates.

  

Benefits:

-   Gain Buyers and Followers: Attract attention and build a loyal customer base.
-   Increase Awareness: Showcase products and brand dynamically.
-   Drive Sales: Build trust, improve conversion, and encourage purchases.

  

Open Platform offers a set of Open APIs for video management and analytics, allowing developers to upload and manage videos, publish content, and track performance to optimize video-driven marketing strategies.

## 2\. Integration Overview

  

Integration with Shopee Video APIs consists of two main stages:

-   Uploading Video via Public Video Upload APIs – Upload video files and obtain a video\_upload\_id.
-   Managing and Publishing Video via Shopee Video APIs – Use v2.video.\* APIs to select covers, edit video info, publish, delete, query, and analyze video performance.

## 3\. Public Video Upload APIs

  

Open Platform provides a set of public video upload APIs and push mechanism, designed for all future video upload scenarios. Currently, these APIs are supported only for Shopee Video.

  

API and Push List:

  

-   [v2.media.init\_video\_upload](https://open.shopee.com/documents/v2/v2.media.init_video_upload?module=130&type=1): Initialize the video upload task.
-   [v2.media.upload\_video\_part](https://open.shopee.com/documents/v2/v2.media.upload_video_part?module=130&type=1): Upload video file in parts.
-   [v2.media.complete\_video\_upload](https://open.shopee.com/documents/v2/v2.media.complete_video_upload?module=130&type=1): Complete upload and notify the system for processing.
-   [v2.media.get\_video\_upload\_result](https://open.shopee.com/documents/v2/v2.media.get_video_upload_result?module=130&type=1): Query the upload result.
-   [v2.media.cancel\_video\_upload](https://open.shopee.com/documents/v2/v2.media.cancel_video_upload?module=130&type=1): Cancel the upload task.
-   [video\_upload\_result\_push](https://open.shopee.com/push-mechanism/43): Push notification when the upload reaches a final status: SUCCEEDED, FAILED, or CANCELLED. Intermediate statuses are not pushed.

  

Upload Workflow:

  

1.  Call [v2.media.init\_video\_upload](https://open.shopee.com/documents/v2/v2.media.init_video_upload?module=130&type=1) to initialize the upload task.
2.  Upload video parts via [v2.media.upload\_video\_part](https://open.shopee.com/documents/v2/v2.media.upload_video_part?module=130&type=1).
3.  Call [v2.media.complete\_video\_upload](https://open.shopee.com/documents/v2/v2.media.complete_video_upload?module=130&type=1) to finish the upload.
4.  System processes video asynchronously and pushes the final status via [video\_upload\_result\_push](https://open.shopee.com/push-mechanism/43).
5.  Optionally, call [v2.media.get\_video\_upload\_result](https://open.shopee.com/documents/v2/v2.media.get_video_upload_result?module=130&type=1) to check progress.
6.  After a successful upload, obtain the video\_upload\_id to call Shopee Video APIs.

  

Notes:

-   Use push mechanism to receive upload completion notifications asynchronously to avoid polling.
-   Each upload task corresponds to a unique video; avoid uploading the same video multiple times.

## 4\. Shopee Video APIs

  

Shopee Video APIs are divided into two modules: Video Management and Performance Analytics.

### 4.1 Video Management

  

1) [v2.video.get\_cover\_list](https://open.shopee.com/documents/v2/v2.video.get_cover_list?module=129&type=1): Get frame-by-frame screenshots of uploaded videos and select a specific frame as the video cover.

  

2) [v2.video.edit\_video\_info](https://open.shopee.com/documents/v2/v2.video.edit_video_info?module=129&type=1): Set or edit video information before post, including the video caption, cover, linked products, post time, and whether allow stitch and duet.

  

Note: Draft videos can be edited multiple times; once published, they cannot be edited.

  

3) [v2.video.post\_video](https://open.shopee.com/documents/v2/v2.video.post_video?module=129&type=1): Publish a draft video to Shopee Video. Must upload the video and edit its info first.

  

4) [v2.video.get\_video\_list](https://open.shopee.com/documents/v2/v2.video.get_video_list?module=129&type=1): Retrieve the list of videos for the account.

  

5) [v2.video.get\_video\_detail](https://open.shopee.com/documents/v2/v2.video.get_video_detail?module=129&type=1): Retrieve detailed information for a video.

  

6) [v2.video.delete\_video](https://open.shopee.com/documents/v2/v2.video.delete_video?module=129&type=1): Delete draft or published videos.

### 4.2 Performance Analytics

  

Overall performance of all videos:

  

1) [v2.video.get\_overview\_performance](https://open.shopee.com/documents/v2/v2.video.get_overview_performance?module=129&type=1): Retrieve overall content interaction and transaction conversion performance for all post videos.

  

2) [v2.video.get\_metric\_trend](https://open.shopee.com/documents/v2/v2.video.get_metric_trend?module=129&type=1): Retrieve trends of content interaction and transaction conversion for all post videos over time.

  

3) [v2.video.get\_user\_demographics](https://open.shopee.com/documents/v2/v2.video.get_user_demographics?module=129&type=1): Retrieve audience distribution data (age, gender, location, active time, content and product preferences) for all post videos.

  

4) [v2.video.get\_product\_performance\_list](https://open.shopee.com/documents/v2/v2.video.get_prodcut_performance_list?module=129&type=1): Retrieve performance data for products linked with videos.

  

Performance of single video:

  

1) [v2.video.get\_video\_performance\_list](https://open.shopee.com/documents/v2/v2.video.get_video_performance_list?module=129&type=1): Retrieve overview performance for a single video.

  

2) [v2.video.get\_video\_detail\_performance](https://open.shopee.com/documents/v2/v2.video.get_video_detail_performance?module=129&type=1): Retrieve content interaction and transaction conversion performance for a single video.

  

3) [v2.video.get\_video\_detail\_metric\_trend](https://open.shopee.com/documents/v2/v2.video.get_video_detail_metric_trend?module=129&type=1): Retrieve trends of content interaction and transaction conversion for a single video, to analyze performance changes over time.

  

4) [v2.video.get\_video\_detail\_audience\_distribution](https://open.shopee.com/documents/v2/v2.video.get_video_detail_audience_distribution?module=129&type=1): Retrieve audience distribution data (age, gender, location, active time, content and product preferences) for a single video, to analyze its audience profile.

  

5) [v2.video.get\_video\_detail\_product\_performance](https://open.shopee.com/documents/v2/v2.video.get_video_detail_product_performance?module=129&type=1): Retrieve transaction conversion performance of products linked to a single video, to evaluate the video’s impact on sales and conversions.

  

Note: All performance data usually has at least a one-day delay.

## 5\. Authorization & Authentication (User-type APIs)

  

Shopee Video APIs are User-type APIs, requiring user\_id and access\_token. The authorization and authentication logic is same as Livestream APIs with the following points:

  

-   Role-based Authorization

-   If the authorized role is Seller, set auth\_type=seller when generating the authorization link. After successful authorization, calling v2.public.get\_access\_token will return both shop\_id and user\_id.
-   If the authorized role is Affiliate, set auth\_type=user when generating the authorization link. After successful authorization, calling v2.public.get\_access\_token will only return user\_id.

-   Common Request Parameter: user\_id

-   All Shopee Video API calls must use user\_id as common request parameter instead of shop\_id.
-   API signature (sign) must also be generated based on user\_id.

-   Access Token & Refresh Token Management

-   Tokens must be managed separately for each user\_id.

  

For detailed authorization and authentication flow, refer to the [Livestream API Integration Guide](https://open.shopee.com/developer-guide/669).

## 6\. Developer Integration Notes

  

-   Only Shopee Video Management applications can access Video OpenAPI. Create the corresponding app type in the console before integration.
-   Must agree to Shopee Video's Terms & Conditions in Seller Center before performing any Video-related API operations.
