---
title: "Upgraded Smart+ Campaign"
doc_id: 1853452461203458
path: "Marketing API / Campaign Management / Guides / Campaign / Upgraded Smart+ Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upgraded Smart+ Campaign

The Upgraded Smart+ Campaigns feature a new, unified workflow, known as the Upgraded Smart+ experience. This streamlined process enables you to access both [manual](https://business-api.tiktok.com/portal/docs?id=1739315728330753) and [Smart+](https://business-api.tiktok.com/portal/docs?id=1765314260115458) features when creating your campaign. [Learn more about the Upgraded Smart+ experience](https://ads.tiktok.com/help/article/about-updates-to-smart-plus).

The Upgraded Smart+ API supports an enhanced campaign structure with upgraded Smart+ experience built-in campaign management. By combining **AI-powered automation** with **granular control**, this API enables advanced campaign structures, allowing you to fine-tune targeting settings while leveraging AI-driven automation capabilities to boost your ad performance.

## New endpoints to support Upgraded Smart+ experience
To better support the enhanced campaign structure, a new set of endpoints is launched for creating, editing, reporting, and retrieving data for Upgraded Smart+ Campaigns.

Driven by market feedback, [the Upgraded Smart+ API](https://business-api.tiktok.com/portal/docs?id=1843301794874370) unlocks:

- **Granular control** over bidding, targeting, placement, and the ability to include multiple creative elements within a single campaign—a feature previously unavailable.
- **Advanced automation capabilities** to tailor strategies at the component level.
- **The ability to combine automation with manual controls**: advertisers can now customize automation settings per component for greater transparency, optimization, and strategic flexibility.

## Migration guidance
**The Upgraded Smart+ API will serve as the unified set of endpoints for all future campaign creation processes, replacing both the existing legacy Smart+ API for Smart+ Campaigns and Campaign Management API for Manual Campaigns.**

- [Legacy Smart+ API](https://business-api.tiktok.com/portal/docs?id=1765314260115458) (Urgent: Immediate Migration Required)
	- After **March 31, 2026**, you will no longer be able to create legacy Smart+ Campaigns using the [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362) endpoint.
		- Note that legacy Smart+ Campaigns that have not been migrated will not be transitioned to the new API automatically. Attempts to use the legacy Smart+ API endpoint afterward will result in errors, rendering new campaign creation via this endpoint unavailable.
	- [/campaign/spc/update/](https://business-api.tiktok.com/portal/docs?id=1767334250066945) and [/campaign/status/update/](https://business-api.tiktok.com/portal/docs?id=1739320994354178) endpoints will remain functional until **June 30, 2026**.

- [Manual Campaign Management API Flows](https://business-api.tiktok.com/portal/docs?id=1735713781404673): 
	- After **December 31, 2026**, you will no longer have the ability to create Manual Campaigns using the [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602) endpoint.
		- Similar to the legacy Smart+ API, Manual Campaigns that have not been migrated will not be transitioned to the new API automatically. Attempts to use the Manual Campaign Management API endpoint afterward will result in errors, rendering new campaign creation via this endpoint unavailable.
		- [/campaign/update/](https://business-api.tiktok.com/portal/docs?id=1739320422086657) and [/campaign/status/update/](https://business-api.tiktok.com/portal/docs?id=1739320994354178) endpoints will remain functional, and a deprecation notice will be provided three months prior to these endpoints’ discontinuation.

### Get Started
- **Reporting and data retrieval**: No additional allowlist is required. You can immediately access these Upgraded Smart+ endpoints without restrictions.
- **Campaign Management**: To access the upgraded Smart+ experience, contact your TikTok sales representative to have your ad account allowlisted. If your ad account is already in the allowlist, you can directly use the Upgraded Smart+ API creation and modification endpoints.

We strongly encourage you to integrate the Upgraded Smart+ API at your earliest convenience to unlock a host of powerful features, enjoy flexible ad group management, and enhance performance with TikTok’s latest automation technology.

If you have any questions or require assistance, don’t hesitate to contact your TikTok sales representative or fill out the the [survey](https://bytedance.sg.larkoffice.com/share/base/form/shrlgoVNNGEVuhzzSjgaXtrx9kd).

## Learn more
- Explore the Upgraded Smart+ API endpoints: [Discover the available endpoints for campaigns, ad groups, ads, ad reviews, and reporting](https://business-api.tiktok.com/portal/docs?id=1843301794874370).
- Understand the workflows: [Learn the detailed processes for creating various types of Upgraded Smart+ Campaigns, along with code examples tailored to different scenarios](https://business-api.tiktok.com/portal/docs?id=1843309449749698).
- Retrieve campaign data: [Find out how to access data for Upgraded Smart+, Smart+, and Manual Campaigns](https://business-api.tiktok.com/portal/docs?id=1854103649826817).
- Get answers to FAQs: [Review the frequently asked questions about migrating to Upgraded Smart+ Campaigns](https://business-api.tiktok.com/portal/docs?id=1854104036724929).
- Know the compatibility changes: [Understand the compatibility adjustments for Upgraded Smart+ Campaigns across different endpoints](https://business-api.tiktok.com/portal/docs?id=1854112742370306).

