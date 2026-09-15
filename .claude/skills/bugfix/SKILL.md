---
name: bugfix
description: Use when fixing a reported bug or a broken behaviour: proving the path is reachable, deciding whether it's actually broken, and writing the failing test before the fix.
---

# Bug Fix Workflow

- **Reachability first.** Enumerate entry points: which DQL function, which platform, which DBAL and ORM major. Confirm at least one combination triggers the behaviour with realistic input. No real caller → an internal asymmetry: document or defer; don't expand scope to "fix" it.
- **Confirm it's actually broken.** Check what the platform actually does before assuming the node is wrong; a difference between MySQL and MariaDB output may be the documented platform behaviour, not our bug. When accepting a difference: a code comment at the site stating the durable contract, and the decision plus rejected alternatives in the issue or PR body.
- **Failing test before production code** (non-negotiable):
  1. a test exercising the broken behaviour, asserting the correct outcome: unit for a wrong SQL string, integration for SQL the platform rejects;
  2. run → **fails** (proves the bug);
  3. fix;
  4. run → **passes**.

  Refactor sibling in [code-quality.md](../../rules/code-quality.md): characterization tests go in green first, before a behaviour-preserving refactor.
- **A wrapped exception names where it was caught, not why.** Doctrine wraps driver errors at the DBAL boundary, so walk to the deepest non-null `Throwable::getPrevious()` and search for that deepest message, never the visible top-level one.
- **Historic evidence describes the code that ran, not `HEAD`.** A stack trace or issue report whose line number does not match the symptom needs the released revision: `git log --before=<report-date> -1 --format=%H -- <path>`, then `git show <hash>:<path>`.
- **Reproduce on the platform that reported it.** `composer test:integration:<platform>` against the docker-compose stack, not the unit suite; a SQL-string assertion cannot reproduce a driver rejection.
- **Stop at the second attempt.** Trying the same approach twice means the hypothesis is rejected, not that it needs more code; "almost there" after three failed attempts is bulldozing.
- **Fixing shared behaviour reaches every caller.** A change to an abstract node or a shared walker touches every platform that extends it: enumerate them, confirm each is still correct, and pin the ones whose behaviour you reasoned about with a test.

## Reasoning about an unclear cause

- **Three hypotheses minimum, never one.** Think across categories: data (wrong input, missing field, type mismatch, encoding), logic (wrong condition, off-by-one, operator precedence, ordering), environment (platform version, DBAL or ORM major, driver, extension), state (stale cache, leaked state, initialization order).
- **Each hypothesis carries supports, conflicts and its test:** the evidence for it, the evidence against it, and the minimal experiment that would prove or disprove it.
- **The root hypothesis is the one with supporting evidence and no conflicting evidence.** Several qualify → pick the easiest to test.
- **Try to falsify, not to confirm.** You are looking for the evidence that kills the theory.
- **Maximum 5 lines of change per experiment.** Needing more means the hypothesis is too vague to test.
- **One variable at a time**, never two fixes combined to save time.
- **Diagnostic code, not production fix code**, and revert it once the result is recorded.
- **Inconclusive is a result.** Record it and test the next hypothesis.
