---
title: "Access to Business Messaging API"
doc_id: 1832184145137922
path: "Business Messaging API / Guides / Get started / Access to Business Messaging API"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Access to Business Messaging API

TikTok's Business Messaging API enables Business Accounts to send and receive direct messages with TikTok users on API-integrated platforms. It helps business users enhance customer engagement and streamline messaging workflows through third-party solutions.

To ensure data compliance with TikTok's global privacy and data security standards and mitigate third-party risks, Business Messaging API developers are required to complete necessary reviews as a prerequisite for Business Messaging API access. 

## Regional availability and required reviews
- The Business Messaging API is not yet available in the European Economic Area, Switzerland or the UK market. For Business Accounts signed up in those regions, developers cannot call the Business Messaging API on behalf of these accounts. Additionally, direct messages sent from TikTok accounts in these regions cannot be retrieved via the Business Messaging API or Webhooks.
- For Business Accounts signed up in the US, only developers who have passed [the Data security & privacy review](https://business-api.tiktok.com/portal/docs?id=1832184206708737), [the US data security review](https://business-api.tiktok.com/portal/docs?id=1832184219753474), and agreed to the USDS Addendum, are permitted to call the Business Messaging API on behalf of these accounts. TikTok will reach out to developers who have passed the US Data Security Review for USDS Addendum execution via email.
- For Business Accounts signed up in all other regions (Rest of World), developers who have passed [the Data security & privacy review](https://business-api.tiktok.com/portal/docs?id=1832184206708737)  are permitted to call the Business Messaging API on behalf of these accounts.
- The availability of specific features of Business Messaging API may vary in regions. Please refer to the API documentation for details.

## Scope of permission application and review process
<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/31700610951fb329aca589b353567c5e" alt="Scope of permission application and review process" width="80%" style="color:#808080" />

- Before you start to apply for Business Messaging API access, ensure the following:
  - You have an approved developer app and a valid app ID. If you don't have an approved developer app yet, you can apply for a new one and include "**Ad Account Management**", "**CTX Events Management**" and "**Measurement**" scopes of permission in your application.
  - You have read [Data security & privacy review](https://business-api.tiktok.com/portal/docs?id=1832184206708737) carefully.
  - You have read [US data security review](https://business-api.tiktok.com/portal/docs?id=1832184219753474) carefully, if you intend to access Business Messaging API and manage direct messages for Business Accounts signed up in the US.
  
## Data security and privacy review intake form
- **Please start your application by submitting your information through** [this form](https://bytedance.sg.larkoffice.com/share/base/form/shrlg7vFArGhg9V20neYCEwIKrb). 
- The TikTok API for Business team will process your request and initiate the review process within 10 working days after your submission. 
  - For the Data Security & Privacy Review, an email with subject "**TikTok/ByteDance Third-Party Due Diligence Questionnaire**" ("DSPR DDQ") will be sent to the contact person's email address provided. 
  - If the US Data Security Review is applicable, an email with subject "**A questionnaire request from TikTok U.S. Data Security Inc.**" ("USDS VAQ") will be sent to the contact person's email address provided. This questionnaire is likely to arrive in the mailbox around one week later than the DSPR DDQ. 
<br><p><span style="color:darkred"><b>Note</b></span>: The USDS VAQ is the most critical initiative for developers intending to manage Business Accounts signed up in the US. **Please prioritize completing and submitting it by the deadline** in the email, as the review outcome will determine whether your access to TikTok US user data is approved.

## FAQs
**Q: My current focus markets are APAC, LATAM, and METAP. Shall I also include US in my application now, in case that I expect to serve Business Accounts signed up in the US in future?**

  A: Including the US in your application will require your company to undergo an additional US data security review, which may extend the overall review timeline. If the Business Accounts you plan to manage are currently registered in non-US regions, we recommend excluding the US from this application to help speed up the approval process. You can always apply to add US access later and complete the additional US review when your business needs it.

**Q: What should I prepare to expedite the data security & privacy review and US data security review process?**

A: To help speed up the review process, please complete and submit the DSPR DDQ and, if applicable, the USDS VAQ as soon as you receive them. When you are filling out the questionnaires, we recommend providing detailed responses along with relevant supporting documents, such as an ISO 27001 certificate, SOC 2 report, or recent vulnerability scan or penetration test report, to help facilitate quicker approval. After submission, please monitor the Contact Person's Email Address you provided. If TikTok reaches out with follow-up questions, be sure to respond promptly and include any additional documents requested.

**Q: Can I track the progress of data security & privacy review and US data security review?**

A: At this time, both reviews are manually managed by TikTok and progress tracking is not available through the developer portal for self-serve developers. If your developer app is approved without the need for additional clarification or documentation, you will receive a notification via email. Please note that developer support tickets are intended for technical issues only and cannot be used to check the status of review processes.

**Q: What can I do if I am rejected by the US data security review? Can I still use Business Messaging API?**

A: To avoid rejection, make sure to provide detailed answers in the USDS VAQ, and respond promptly to any follow-up emails. If you fail to pass the US data security review, the TikTok API for Business team will notify you via email, and ask if you'd like to continue with the data security & privacy Review. If proceeded and approved, you will still be able to use the Business Messaging API for Business Accounts registered outside the US.

**Q: What would happen if I attempted to manage direct messages for Business Accounts registered in the EEA, Switzerland, the UK, and the US (without passing the US data security review)?**

A: In this case, while the Business Account user successfully authorizes your developer app with multiple scopes of permissions, your app will not be able to retrieve or interact with any related data through the Business Messaging API. As a result, Business Messaging API calls will fail and you may encounter errors, even if authorization was technically successful.

**Q: How is a region defined, and how should I communicate available regions to my users for my integration?**

A: The region is determined by the sign-up location of the Business Account on TikTok. Your approved reviews determine which regions your Business Messaging API integration can support. In your marketing communications, you can say "TikTok Business Messaging features are available for all Business Accounts except those signed up in the US, the European Economic Area, Switzerland and the UK". You may remove "the US" from this statement if you have passed the US data security review.

**Q: Can TikTok personal account users manage direct messages via Business Messaging API? And what is RBA?**

A: The Business Messaging API only supports Business Accounts. Personal accounts are not eligible, but users can switch their account to business account type in TikTok app settings. Both Business Accounts and Registered Business Account ("RBA", Business Accounts verified with additional business details) are eligible for Business Messaging API. We recommend upgrading to an RBA to unlock more tools and features.

**Q: Can I manage both TikTok Direct Messages and TikTok Shop Messages with Business Messaging API?**

A: TikTok Business Messaging and TikTok Shop Messaging are distinct messaging systems. Integration with the TikTok Business Messaging API does not allow management of TikTok Shop Messages.
