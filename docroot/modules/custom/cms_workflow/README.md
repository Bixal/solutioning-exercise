# CMS Workflow

## Setup
This module depends on roles, workflow, and moderation.
This has been preconfigured in the CMS and config lives in the default sync.

## Choosing a reviewer
A user with an appropriate role assigned to them can create content. During the
creation process a user will have to select a reviewer. The reviewer list is unique
to a role that has review privileges on the node being created. After the node
been saved/updated in the review state it will send a notification email to
the reviewers account.
