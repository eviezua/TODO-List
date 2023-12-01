Todo List API

You need to implement an API that will allow you to manage the task list.

The API should provide the ability to:


get a list of their tasks according to the filter
create your task
edit your task
mark your task as completed
delete your task

When receiving the task list, the user must be able to:


filter by status field
filter by priority field
filter by title, description field (full-text search must be implemented)
Sort by createdAt, completedAt, priority - requires support for sorting by two fields. For example, priority desc, createdAt asc.

The user should not be able to:


modify or delete other people's tasks
delete an already completed task
mark as completed a task that has failed tasks

Each task must have the following properties:


status (todo, done)
priority (1...5)
title
description
createdAt
completedAt

Any task can have subtasks, the nesting level of subtasks must be unlimited.

Minimum version: PHP 8.1
Framework: Laravel/Symfony
The code must be uploaded to the public repository

Recommendations:
Design:
accompany the test with competent and understandable README.md
support the test Open API with documentation
wrap project in docker compose
use only English for documentation and comments in code

Architecture:


use as much functionality built into the framework as possible
use service layer for business logic
use repositories to retrieve data from the database
use DTO
use Enum
use strict typing
use REST approach for routing
use recursion or reference to form a task tree

Style code
follow PSR-12
stop working with arrays

Database
use ciders/fixtures to fill the database
use indexes






