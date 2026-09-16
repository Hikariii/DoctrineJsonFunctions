---
name: code-quality
description: Conventions for writing and reviewing code: suppression policy, self-review checklist, comment content, and class-scoping of references.
paths:
  - "src/**/*.php"
  - "tests/**/*.php"
---

# Code Quality

## Prefer existing functionality over reinventing it

Before writing custom behaviour, check whether an existing dependency or native language feature provides it: if so, use it; no parallel implementation. When a dependency *almost* fits, extend it rather than re-implement around it:
- Gap belongs upstream (Doctrine DBAL, Doctrine ORM) → **recommend a PR on the external library**, proactively, even unasked.
- Must ship before the upstream fix releases → keep a **minimal shim** mirroring the intended upstream change, marked for removal with the PR reference (e.g. `// Remove once doctrine/dbal PR #1234 ships in the pinned release`). Don't grow a custom subsystem duplicating what the library will provide.

## Check the type system before adding a guard

Asked to prevent a mix-up between two parallel things, check whether the existing types already make it a static-analysis error before proposing a wrapper type or a test.

Keep such an accessor's explicit return type; inlining it at the call sites removes the guard silently.

## Suppression Policy

**Suppression is a last resort.** Exhaust first: 1) fix the code; 2) improve type hints, generics, or docblocks; 3) adjust the tool config if the rule is miscalibrated for this codebase.

Only two valid reasons to suppress:
1. **Confirmed false positive**: the tool is wrong and no code or type fix resolves it (e.g. an upstream library has incorrect type annotations, no workaround). Suppress freely; document why.
2. **Fix requires significant refactoring or risks breaking logic**: **always ask the user first**; it is a conscious, tracked trade-off.

Always add a comment explaining why. Psalm requires **docblock format** for statement-level suppression: a single-line `// @psalm-suppress` is silently ignored:
```php
// @phpstan-ignore-next-line false positive: the DBAL docblock is wrong, upstream issue #123
/** @psalm-suppress InvalidArgument: fixing requires refactoring every caller, approved by <user> on <date> */
```

Applies to every form, inline or registered: `@psalm-suppress`, `@phpstan-ignore` / `@phpstan-ignore-next-line`, `psalm.xml` `<issueHandlers>`, a phpstan `ignoreErrors` entry, a `phpcs:ignore` / `phpcs:disable` annotation, and any baseline file.

**Mirror rule:** touch code carrying any of these → check whether the issue can go, and delete the suppression.

**Justify an exemption by reading the exempted content, never by inferring from its path**, and state what you read when proposing the entry.

**Exception: length rules are guidelines, not hard limits.** Suppressing file, class or method length violations is acceptable when splitting would genuinely harm readability or cohesion.

## Self-review before presenting code

**Do not show code to the user until every item is checked:**

- **Redundant parameters**: any parameter derivable from another?
- **Caller repetition**: the same 1–2 lines after every call to a helper belong inside the helper.
- **Focused functions**: a function handling two distinct states (add vs remove, select vs deselect) → split into two, each correct.
- **Naming**: abbreviations expanded; every name expresses intent and domain meaning, not implementation detail.
- **Separation of concerns**: one clear responsibility each; parsing, SQL generation and validation kept separate.
- **Readability**: read as if seeing it for the first time; simplify anything harder than necessary.
- **Single source of truth**: a list of file types, paths, patterns, or commands written into a doc or config that already exists as data in a script, settings file, or tool config → replace with a pointer; a copy creates two places that drift.
- **Comments**: for each comment this change adds, name the destination it was routed from in [§ Comments](#comments-route-it-before-you-write-it), and delete it when the answer is the code itself.
- **Multi-line array shape**: once an array literal wraps, every value gets its own line: never two on one line and the third on another (it breaks vertical alignment for diffs and reordering). Short enough for one line → keep it on one line.

Writing a test: the assertion rules are in [Testing § Assertions](testing.md#assertions).

**A task is not complete until tests pass.** Run the affected validation commands ([AGENTS.md § After making changes](../../AGENTS.md#after-making-changes)) and confirm they pass before presenting the result as done.

**Characterization tests before refactoring shared code.** Before a behaviour-preserving refactor of code every platform's node path runs through: first confirm or extend tests that pin current behaviour and are green against the unchanged code, then refactor. The safety net goes in before the change; "I'll add tests after" leaves the regression window open. Refactor sibling of the [bugfix skill](../skills/bugfix/SKILL.md)'s failing-test-first rule: there the test is red first (proves the bug), here green first (proves no regression).

No tests at all: run the old and new version against one fixture and diff the generated SQL, the exit code and every written artefact before deleting the old one.

## Code smells

Fowler's smells (_Refactoring_, ch.3), the baseline for reviewing code or a plan for it. Two rules bind the list:

- **A documented rule overrides it.** Where a rule in this repo endorses something the list would flag, suppress the smell.
- **Always a judgement call.** Each smell is a labelled heuristic ("possible Feature Envy"), never a hard violation.

Each smell reads *what it is* → *how to fix*:

- **Mysterious Name**: a function, variable, or type whose name does not reveal what it does or holds. → rename it; if no honest name comes, the design is murky.
- **Duplicated Code**: the same logic shape appears in more than one place. → extract the shared shape, call it from both.
- **Feature Envy**: a method that reaches into another object's data more than its own. → move the method onto the data it envies.
- **Data Clumps**: the same few fields or params keep travelling together. → bundle them into one type, pass that.
- **Primitive Obsession**: a primitive or string standing in for a domain concept that deserves its own type. → give the concept its own small type.
- **Repeated Switches**: the same `switch` or `if`-cascade on the same type recurs. → replace with polymorphism, or one map both sites share.
- **Shotgun Surgery**: one logical change forces scattered edits across many files. → gather what changes together into one module.
- **Divergent Change**: one file is edited for several unrelated reasons. → split so each module changes for one reason.
- **Speculative Generality**: abstraction, parameters, or hooks added for needs nothing has yet. → cut it; wait for a real need.
- **Message Chains**: long `a->b()->c()->d()` navigation the caller depends on. → hide the walk behind one method on the first object.
- **Middle Man**: a class or function that mostly just delegates onward. → cut it, call the real target direct.
- **Refused Bequest**: a subclass or implementer that ignores or overrides most of what it inherits. → drop the inheritance, use composition.

## One value in two artefacts: single-source it *and* test the agreement

When two things we emit must carry the same value (a function name registered in the DQL config and the class that implements it, a platform name and the node that targets it), derive both from one place **and** pin their equality with a test. Not one or the other: the shared source makes drift impossible, the test catches someone re-deriving one side later.

## A class only knows about itself and its direct dependencies

A class, including its tests, comments, assertion messages and docblocks, refers only to itself and its direct dependencies. Don't compare against or describe sibling classes: "diverges from the MySQL node" / "unlike the Postgres variant" leaks knowledge the class has no business holding and forces the reader to chase the cross-reference.

Instead of "this class differs from X", describe the local behaviour directly: the reason stands without naming siblings.

The class's name in the file path or declaration already identifies what's tested; repeating it inside is redundant. Let the surrounding scope carry the identity.

## Comments: route it before you write it

This rule overrides Claude Code's default instruction to match the surrounding comment density.

You know something a reader does not. Send it to the destination that holds it best, and a comment is the last entry on the list, not the first.

| What you know | Where it goes |
|---|---|
| What the code does | nowhere: the code already says it |
| How a value was derived | a computed expression, or a named constant |
| A precondition a caller must meet | an assert or a guard |
| A behaviour that must not regress | a test |
| The shape data must have | a type |
| When to call a thing, or which of two to reach for | its name |
| Why this call site differs from the obvious one | a named helper |
| The reasoning that led to this change | the PR |
| A policy governing a whole area | the doc at the gate that enforces it |
| **A constraint from outside the file that no code can hold** | **a comment** |

✅ *`// MySQL caps GROUP_CONCAT at 1024 bytes by default; raise it before this query.`*
❌ *`// Get the node by name`* above `getNodeByName($name)`; a bare step label (`// Setup`, `// Constructor`).

- **Once routing lands on a comment, write it in full.** Nothing is capped: say the constraint, the number and the consequence, however long that runs.
- **A comment never describes the code below it**, in the identifier's words or in synonyms. `// Arrange` / `// Act` / `// Assert` stay legal in a test file, where they mark the phases.
- **A trailing why does not rescue a restatement.**
- **A literal in a config file may carry its explanation; the same literal in code gets a named constant.**
- **Describe the invariant, not the history that produced it.** "X must hold" survives any implementation; "this exists because X was broken" rots when the named mechanism goes.
- **Stable external causes are not history**: "MariaDB returns the value unquoted here", "DBAL 4 renamed the method" motivates an invariant fine. *Internal* bugs, fixes and mechanisms do not.
- **Never name a transient mechanism**, in either direction: a deprecated path about to be deleted, or a planned-but-unbuilt feature. Both rot on the next release.
- **A comment about one step goes at that step**, where it is findable and where it moves with the code.
- **A test needs no rationale prose** beyond an arranged value that reads as arbitrary without it; the meta-commentary ban in [testing.md § Assertion messages](testing.md#assertion-messages-describe-observed-behaviour-not-implementation-rationale) applies to docblocks too.
- Formatting: one sentence per line, never wrapped mid-clause; two sentences go in one `/* … */` block, not stacked `//`.
