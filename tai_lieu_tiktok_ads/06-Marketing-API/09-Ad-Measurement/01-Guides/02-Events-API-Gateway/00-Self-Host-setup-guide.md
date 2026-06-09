---
title: "Self-Host setup guide"
doc_id: 1835087586303106
path: "Marketing API / Ad Measurement / Guides / Events API Gateway / Self-Host setup guide"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Self-Host setup guide

## Prerequisites
1. Before deploying Events API Gateway, ensure you have an AWS account. If you don't have an AWS account, you can create one by following [Create and activate an AWS account](https://repost.aws/knowledge-center/create-and-activate-aws-account).
2. Decide hosting region and TikTok advertiser account. If you do not have a TikTok advertiser account, you can create it [here](https://ads.tiktok.com/i18n/signup/).
3. Share the hosting region i.e. us-east-1, and the TikTok advertiser account ID (see the screenshot below) to your TikTok representative, and we will generate an installation link for you.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/bcd430f920bdda02edcb29bf2608a3fa" alt="Advertiser Account ID" width="60%" style="color:#808080" />

4. AWS account permission requirements.

```xtable
|AWS Service{30%}|Access Level{70%}|
|---|---|
| CloudFormation | Full Access |
| EC2 | Full Access |
| EC2 Auto Scaling | Full Access |
| EKS | Full Access |
| IAM | Full Access |
| Security Token Service | AssumeRole |
| Systems Manager | Full Access |
| ACM | Write |
| ELB | Full Access |
| CloudWatch | Write |
| CloudWatch Logs | Write |
| EventBridge | Write |
```

## **Installation: AWS**

Click on the installation link which will redirect you to AWS CloudFormation stack. Initiate a quick deployment flow:

- The `Stack name` and `ProvisioningData` fields will be pre-populated. Please don’t change the `ProvisioningData` field.
- Name your Events API Gateway instance in the `InstanceName` field.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d2048b625bee756b24e1e3fa701b17f4" alt="Instance Name Field" width="60%" style="color:#808080" />

- Check **I acknowledge that AWS CloudFormation might create IAM resources with custom names.**
- Click **Create stack** to begin the installation.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/afa20353c4814a1c107acc36223b53c5" alt="Create Stack" width="60%" style="color:#808080" />

The deployment process typically takes 30 to 40 minutes. Once complete, three outputs will be listed under the "Outputs" tab for the new AWS instance. The process is finished when the status in the **Stack info** Tab is: **CREATE_COMPLETE**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e7eeef7b1b10c982f603e3a757c620df" alt="CREATE_COMPLETE" width="60%" style="color:#808080" />

Once complete, go to the **Outputs** tab. It will provide the following details:
- BastionHost (URL to view bastion host in AWS console)
- EapigHomePage (Home page URL for Events API Gateway UI)
- InstallationLogLocation (Installation status and progress will be available in this Cloudwatch Logs group)

If any issues arise during the deployment process, it will automatically initiate a rollback in deployment flow. The rollback is completed when the **Status** displays: **ROLLBACK_COMPLETE** in the **Stack info** Tab. Go to the **Events** tab for more detailed analysis. It lists all the events that occurred during the deployment process and provides which step the failures occurred and the explanations.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/aba1d9e5b24dff1903939387013cfe6f" alt="ROLLBACK_COMPLETE" width="60%" style="color:#808080" />

EventsAPIGateway/cloud-init-output.log in CloudWatch shows detailed installation logs.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7cd8056006c2c6b0b88b2ae278ff4a2a" alt="CloudWatch installation logs" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e4689f245548089845f2af5e7ddbff51" alt="First Tenant Data Sources" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b67edb3e56f04538c9ed53ee027816a1" alt="Copy Pixel Code" width="60%" style="color:#808080" />

