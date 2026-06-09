---
title: "Migrate to v1.3"
doc_id: 1740591846408194
path: "Overview / V1.3 updates / Migrate to v1.3"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Migrate to v1.3

As you start to migrate, please read this doc carefully to ensure a smooth transition.
# Checklist
Here is a checklist to help you quickly get started with the migration process:
1. Go through [V1.3 updates](https://ads.tiktok.com/marketing_api/docs?id=1740579480076290) to get a comprehensive overview of the changes between the two versions.
2. Check out the v1.2 vs v1.3 comparison table for each endpoint in the [API reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) chapter to see the detailed changes.
3. If your endpoint has **breaking changes** in v1.3, then you might need to change your code. Otherwise your integration might break.
# Changes
Changes to endpoints can be split into two broad categories: breaking and non-breaking.
## Breaking Changes
Breaking changes usually require you to revise your code to maintain existing functionality of your application. Example of breaking changes include:
- Removing an existing endpoint
- Removing any response parameter
- Removing any request parameter
- Addition of a new request parameter (either required or conditional)
- Changing the name or data type of an existing parameter in the request or response
- URL updates
## Non-breaking Changes
Non-breaking changes usually require no coding work from your end, and you can adapt to them at your own discretion and pace without disruption to existing functionality. Examples of non-breaking changes include:
- Addition of a new endpoint
- Addition of a new request parameter (optional)
- Addition of a new response parameter
- More detailed error messages
# Notes
-  As a common best practice, our v1.3 APIs do not support request parameters in GET requests. Please do not put parameters into request bodies to avoid failures or unexpected behaviors.
-  Make sure your endpoint URL ends with `/`. Otherwise, some unexpected errors might occur.
     Example:    <br>**Correct** :  https://business-api.tiktok.com/open_api/v1.3/campaign/get/ <br>**Incorrect** : https://business-api.tiktok.com/open_api/v1.3/campaign/get

