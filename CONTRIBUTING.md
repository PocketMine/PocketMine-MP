![](http://cdn.pocketmine.net/img/PocketMine-MP-h.png)

# PocketMine-MP Contribution Guidelines

You must follow these guidelines if you wish to contribute to the PocketMine-MP code base, or participate in issue tracking.

## I have a question
* For questions, please refer to the _#pocketmine_ or _#mcpedevs_ IRC channel on Freenode. There is a [WebIRC](http://webchat.freenode.net?channels=pocketmine,mcpedevs&uio=d4) if you want.
* You can ask directly to _[@PocketMine](https://twitter.com/PocketMine)_ in Twitter, but don't expect an immediate reply.
* You may use our [Forum](http://forums.pocketmine.net) to ask questions.
* We do not accept questions or support requests in our issue tracker.

## Creating an Issue
 - First, use the [Issue Search](https://github.com/PocketMine/PocketMine-MP/search?ref=cmdform&type=Issues) to check if anyone has reported it.
 - If your issue is related to a plugin, you must contact their original author instead of reporting it here.
 - If your issue is related to a PocketMine official plugin, or our Android application, you must create an issue on that specific repository.
 - **Support requests are not bugs.** Issues such as "How do I do this" are not bugs and are closed as soon as a collaborator spots it. They are referred to our Forum to seek assistance.
 - **No generic titles** such as "Question", "Help", "Crash Report" etc. If an issue has a generic title they will either be closed on the spot, or a collaborator will edit it to describe the actual symptom.
 - Information must be provided in the issue body, not in the title. No tags are allowed in the title, and do not change the title if the issue has been solved.
 - Similarly, no generic issue reports. It is the issue submitter's responsibility to provide us an issue that is **trackable, debuggable, reproducible, reported professionally and is an actual bug**. If you do not provide us with a summary or instructions on how to reproduce the issue, it is a support request until the actual bug has been found and therefore the issue is closed.
 - To express appreciation, objection, confusion or other supported reactions on pull requests, issues or comments on them, use GitHub [reactions](https://github.com/blog/2119-add-reactions-to-pull-requests-issues-and-comments) rather than posting an individual comment with an emoji only. This helps keeping the issue/pull rqeuest conversation clean and readable.

## Contributing Code
* Use the [Pull Request](https://github.com/PocketMine/PocketMine-MP/pull/new) system, your request will be checked and discussed.
* __Create a single branch for that pull request__
* Code using the syntax as in PocketMine-MP. See below for an example.
* The code must be clear and written in English, comments included.
* Use descriptive commit titles
* __No merge commits are allowed, or multiple features per pull request__

**Thanks for contributing to PocketMine-MP!**

### Code Syntax

It is mainly [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) with a few exceptions.
* Opening braces MUST go on the same line, and MUST NOT have spaces before.
* `else if` MUST be written as `elseif`. _(It is in PSR-2, but using a SHOULD)_
* Control structure keywords or opening braces MUST NOT have one space before or after them.
* Code MUST use tabs for indenting.
* Long arrays MAY be split across multiple lines, where each subsequent line is indented once. 
* Files MUST use only the `<?php` tag.
* Files MUST NOT have an ending `?>` tag.
* Code MUST use namespaces.
* Strings SHOULD use the double quote `"` except when the single quote is required.

```php
<?php 

namespace pocketmine\example;

class ExampleClass{
	const EXAMPLE_CLASS_CONSTANT = 1;
	public $examplePublicVariable = "defaultValue";
	private $examplePrivateVariable;
	
	public function __construct($firstArgument, &$secondArgument = null){
		if($firstArgument === "exampleValue"){ //Remember to use === instead == when possible
			//do things
		}elseif($firstArgument === "otherValue"){
			$secondArgument = function(){
				return [
					0 => "value1",
					1 => "value2",
					2 => "value3",
					3 => "value4",
					4 => "value5",
					5 => "value6",
				];
			}
		}
	}

}
```

## Bug Tracking for Collaborators

### Labels
To provide a concise bug tracking environment, prevent the issue tracker from over flowing and to keep support requests out of the bug tracker, PocketMine-MP uses a label scheme a bit different from the default GitHub Issues labels.

PocketMine-MP uses GitHub Issues Labels. There are a total of 12 labels.

Note: For future reference, labels must not be longer than 15 letters.

#### Categories
Category labels are prefixed by `C:`. Multiple category labels may be applied to a single issue(but try to keep this to a minimum and do not overuse category labels).
 - C: Core - This label is applied when the bug results in a fatal crash, or is related to neither Gameplay nor Plugin API.
 - C: Gameplay - This label is applied when the bug effects the gameplay.
 - C: API - This label is applied when the bug effects the Plugin API.

#### Pull Requests
Pull Requests are prefixed by `PR:`. Only one label may be applied for a Pull Request.
 - PR: Bug Fix - This label is applied when the Pull Request fixes a bug. 
 - PR: Contribution - This label is applied when the Pull Request contributes code to PocketMine-MP such as a new feature or an improvement.
 - PR: RFC - Request for Comments

#### Status
Status labels show the status of the issue. Multiple status labels may be applied.
 - Reproduced - This label is applied when the bug has been reproduced, or multiple people are reporting the same issue and symptoms in which case it is automatically assumed that the bug has been reproduced in different environments.
 - Debugged - This label is applied when the cause of the bug has been found.
 - Priority - This label is applied when the bug is easy to fix, or if the scale of the bug is global.
 - Won't Fix - This label is applied if the bug has been decided not be fixed for some reason. e.g. when the bug benefits gameplay. *This label may only be applied to a closed issue.*

#### Miscellaneous
Miscellaneous labels are labels that show status not related to debugging that bug. The To-Do label and the Mojang label may not be applied to a single issue at the same time.
 - To-Do - This label is applied when the issue is not a bug, but a feature request or a list of features to be implemented that count towards a milestone.
 - Mojang - This label is applied when the issue is suspected of being caused by the Minecraft: Pocket Edition client, but has not been confirmed.
 - Invalid - This label is applied when the issue is reporting a false bug that works as intended, a support request, etc. *This label may only be applied to a closed issue.*

### Closing Issues
To keep the bug tracker clear of non-related issues and to prevent it from overflowing, **issues must be closed as soon as possible** (This may sound unethical, but it is MUCH better than having the BUG TRACKER filled with SUPPORT REQUESTS and "I NEED HELP").

If an issue does not conform to the "Creating an Issue" guidelines above, the issue should be closed.

### Milestones
PocketMine-MP uses GitHub Milestones to set a goal for a new release. A milestone is set on the following occasions.

 - A new Beta release
 - A new Stable release

A milestone must use the following format:
```
Alpha_<version_number> [release_title][release_version]
```
For example:
```
Alpha_1.4 beta2
```
## Request for Comments
A Request for Comments Pull Request is used to gather votes from developers democratically to allow the majority to rule in making important decisions to the project. This allows implementation of controversial changes and features to be conducted in an orderly fashion.

A Request for Comments is critical to the autonomous governing of open source projects such as PocketMine, because it allows major decisions to be made by the community of developers, and not developers who just happen to be maintainers and have write access.

Anyone may open a Request for Comments, not just developers.

### When is Request for Comments required?
In the PocketMine-MP project, The PocketMine Team (referred to as we from now on) has decided that changes, features, improvements and bug/pull requests matching any of the following criteria necessiate a Request for Comments.

- Changes to the code syntax guidelines - so basically any edits to CONTRIBUTION.md (unless it is a minor fix).
- Project-wide changes such as but not limited to:
	- Namespace changes for all files
	- Case changes for all files
- Major API changes such as but not limited to:
	- An entire re-work of an existing API(not the entire project but rather just one API - for example, a rework of the BanAPI) that will destroy all backwards compatibility, unless it is for a new major version.
- Changes to the way The PocketMine Project is run. Such as but not limited to:
	- New / or modifying labels (CONTRIBUTION.md)
	- New / or modifying milestones
	- Naming standards such as branch names
- Changes affecting this Request for Comments guideline, existing project names (which includes codenames, versioning etc.) and LICENSE.

### When is a Request for Comments NOT required?
To avoid confusion, the following things as an example do not require a Request for Comments.

Essentially, do not create Request for Comments for minor changes, features and bug fixes. They can go through the normal process of discussion in a Pull Request for necessary length of time, and then get merged without any voting.

- Bug fixes
- Feature implementations
- And pretty much anything that you can think of - there should be minimal need for a Request for Comments.

### How Requests for Comments are processed
Firstly, every Request for Comments must follow a very specific format, which is described below. Anything that does not conform will either be edited, will pend until the submitter edits the RFC or removed. Anything that does not fit into the RFC criteria will also be removed.

Secondly, after an RFC is in the correct format for presentation and voting, it will go into a **sunrise** period where the community will finalise the changes which will be made for the topic. This will either be a **1 week** long period **or** when the authors / editors of the RFC declares it finalised, whichever comes later.

For example, if an RFC is finalised in 2 days, it must still wait another 5 days. If an RFC is past the week(12 days, for example) period but still not finalised, then it will stay in the sunrise period.

After an RFC is finalised, it can no longer be edited. No more commits to that branch will be accepted(Maintainers, please do not disobey this rule. No exceptions.) If you wish to make changes to a finalised RFC, you must wait until it exits its **sunset** period (not sunrise!), and publish a new RFC or Pull Request, whichever is more appropriate.

The reason for a sunrise period and finalisation is so that the votes know what they are voting for. Developers will have time to accept pull requests from others, and voters will know that what they vote for will be the final implementation.

Thirdly, the RFC will be voted upon. The voting period of an RFC will start 1 day after its finalisation(with authorisation from a PocketMine-MP Maintainer). No more votes will be accepted after the voting period finishes.

Fourthly and lastly, the sunset period starts immediately after voting finishes. Depending on the outcome of the vote, the RFC may either be merged (any conflicts during the merge is to be solved by PocketMine-MP Maintainers) or ignored.

If significant challenges are introduced while trying to implement the change, the sunset period may take longer than expected.

### Voting on an RFC
An RFC will be open to vote after 1 day of its finalisation. When voting on an RFC, please make note of the following things:
- You must either vote with a **Yes.** or a **No.** No vague answers please.
- Your vote consisting of either **Yes.** or **No.** *MUST* come at the start of your comment. If you demonstrate your opinion mid-comment or at the end of your comment, your vote will not be counted. **This is a strict rule, and NO exceptions to this will be made.**
- You may write a comment explaining your vote and decision. You may omit an explanation if you wish to. No one liners with a yes or no please. If you do this, your vote will not be counted.
- Obviously, duplicate votes will not be counted.

There are one type of majorities that can be used on a Request for Comments voting.
- **Simple Majority** - This is where the yes vote must simply reach over 50% to be accepted. 50% will NOT count as accepted.

### How to format a Request for Comments
The title must be in the format of:

	RFC: <Topic Sentence>

The Pull Request must also be tagged with the tag `PR: RFC`. This will be done for you by one of the maintainers.

In the body of the PR, your first paragraph should be the status. You can copy paste this in - it is static. Change the date and time to match your RFC.

Next must be the introduction and preface. Introduce the issue clearly. Explain what the current issue is, why this change needs to be implemented/merged, what this change will do and how it will benefit PocketMine.

Next must be your topic sentence. It must be one-sided to allow for a Yes or No voting. You must not introduce any new questions besides the topic sentence, as this may confuse the voters. Make this sentence as simple as possible. The format for displaying the topic sentence is static - you must follow it.

Next is the How to vote section. It is the same for all RFCs. You can just copy paste it in. You must include it however.

After the voting period finishes, the total votes counted table will go here. This will be done by maintainers, so you don't have to worry.

You may also decide to put a Revision section in to indicate revisions.

### Example RFC
---
Note: This is an example RFC. This issue has already been discussed internally. The topic is only for example's sake.

Title: RFC: Should the namespace be changed to CamelCase?
Label: PR: RFC

## Status
**Status:** Sunrise
**RFC Created at:** {Date and time in UTC} UTC.

## Introduction
Currently, PocketMine uses the namespace `pocketmine`. However, the rest of the PHP world uses CamelCase for their namespacing. To ensure consistency and increase legibility for PHP developers, the namespace should be changed to `PocketMine`.

## Topic Sentence
The topic for this RFC is the following:
**Should the namespace be changed to CamelCase?**

## How to vote
Votes must display their side clearly and must start with either **Yes.** or **No.** You may include an explanation for your vote, but it is not required.

Examples:
```
Yes. I think it should be merged...

```
```
Yes.
```
```
No. It is of my opinion that this should not be merged due to...
```

**The voting period has not yet started.**

## Revision
**Revision 1** by @octocat at **{Date and time in UTC} UTC**: Fix grammar mistake.

---

### Keeping an RFC decision democratic
If an RFC is approved, it should be accepted. No maintainer or developer may overturn this decision. However, The PocketMine Team may disallow a an RFC from being implemented or merged after a thorough internal discussion. Possible grounds for rejection are:
- Security Issues with the implementation / suggestion
- Licencing issue (e.g. GPL patch in LGPL PocketMine)
- Not enough votes for the RFC to be considered "voted"
