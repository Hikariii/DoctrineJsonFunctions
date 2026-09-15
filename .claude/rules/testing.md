---
name: testing
description: Testing principles: strategy, structure, assertions, and proving a test actually guards the branch it names.
paths:
  - "tests/**"
---

# Testing

Two layers here, both driven by `phpunit.xml.dist`:

| Suite | Command | What it proves |
|---|---|---|
| `unit` | `composer test:unit` | A DQL function parses and walks to the expected SQL string, without a database |
| `integration-<platform>` | `composer test:integration:mysql` (also `mariadb`, `postgres`, `sqlite`, `mssql`) | The generated SQL actually runs on that platform and returns what we claim |

A new function needs both: the unit test pins the SQL we generate, the integration test proves the platform accepts it. A unit test alone passes happily on SQL no database will run.

## Test Strategy: Shift-left, Top-down, Behaviour-driven

### Principles

- **Prefer black-box testing**: test the stable public API of a module, not internals.
- **Avoid mocks at all costs**: only to isolate at module boundaries.
- **A "unit" is not a single class**: any piece of software with a clear boundary.
- **Test only behaviour unique to the class under test.** Before adding an assertion: would this exact behaviour hold for any other caller of the same underlying library? Yes → it pins Doctrine's behaviour, skip it. An internal inconsistency between two methods of the same class (A accepts X, B rejects X) is class-specific: keep.

### ❌ Test implementation / ✅ Test behaviour

```php
// ❌ Wrong: tests implementation
$walkerMock->expects($this->exactly(3))->method('walkArithmeticPrimary');

// ✅ Correct: tests outcome
self::assertSame("JSON_EXTRACT(e.data, '$.a')", $sql);
```

## Test Structure: Baseline → Action → Result

Assert the baseline state before the action under test, unit and integration alike:

```php
// ✅ Correct
self::assertCount(2, $repo->findAll());   // baseline
$entity->archive();
$repo->store($entity);
self::assertCount(1, $repo->findAll());   // result

// ❌ Wrong: only asserts the result, doesn't prove anything changed
$entity->archive();
$repo->store($entity);
self::assertCount(1, $repo->findAll());
```

## Assertions

- **A helper asserts its own postcondition.** Callers don't repeat the same assert after calling it.
- **Cover the input form that actually reaches the check.**
- **A precedence or fallback needs both sides present and disagreeing.**
- **Asserted values uniquely identify the tested contract.** A test pinning a specific behaviour asserts a value *only* that behaviour produces.
  - ❌ `0` also matches "matched nothing", `[]` also matches "no data", `[$x]` also matches "no filter applied" on a one-element set: the test passes for the wrong reason.
  - Arrange data so the value is unique: seed three rows where one matches, so the count is `1`, not `0` or `3`.
  - Value that still reads as arbitrary → name what it rules out at the arranging line, under [code-quality.md § Comments](code-quality.md#comments-route-it-before-you-write-it).

### Assertion messages describe observed behaviour, not implementation rationale

Under multiple variants (data providers over platforms, DBAL versions, ORM versions), a reason string must describe behaviour that holds across every variant, never "MariaDB ignores X" / "DBAL 4 caches Y".

- A passing assertion means the behaviour is consistent, so implementation-specific framing misleads; platform differences go in a divergence summary, or in separate cases.
- **No meta-commentary about the test itself**: *"pins current behaviour"*, *"captures this"*, *"this test ensures Y"*. Every assertion does that; drop them. Contextual scenario explanation (*"X but Y"*, *"returned even though Z"*) describes what the code does and stays.
- **Suspect behaviour belongs in the PR description or commit message, not the assertion reason.**

## Pinning differences between two platforms

When a test pins that two platforms produce different results *for the same input*, the input equivalence must be visually obvious in the test body. Different query builders or construction paths show only "different calls → different results", which proves nothing; the asymmetry could come from the differing inputs.

Fix: extend **the same builder** with an extra parameter, not a different builder for the second call. The reader scans the two calls side by side and confirms "every argument identical except the one expected to differ". Same rule in reverse for pinning that two platforms agree.

## Break the branch to prove the test guards it

A test written for a guard, a rejection path, or an early return that passes on first run has proven nothing yet: it may assert something true regardless. Break that one branch, confirm **exactly** that test fails, revert.

- Drop the guard clause, invert the condition, widen the accepted type, whichever single thing the test exists for.
- Exactly one test failing is the signal, and zero means the test doesn't reach the branch.
- Several failures are fine when each extra is explained: the same rule pinned at two layers, or another test reaching the branch through a different observable.
- Only unexplained extras mean the tests overlap and one isn't pinning what its name claims.
- Applies to characterization tests too: a green run against unchanged code is only meaningful once you've seen it go red.
- Revert the probe with a targeted edit, never `git checkout` or `git restore` on the file: that discards the fix under test too.

## Test-First Optimization Pattern

A risky performance change takes the same characterization tests as any behaviour-preserving refactor: [code-quality.md § Self-review before presenting code](code-quality.md#self-review-before-presenting-code).
