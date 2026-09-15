---
name: review-standards
description: Use when reviewing a diff, a PR or local changes: the severity classification and the evidence bar a finding must clear before it is reported.
---

# Review Standards

Severity classification and evidence bar. These rules exist so findings are classified correctly from the start, rather than filtered afterwards.

## Severity

| Severity | Qualifies |
|---|---|
| **critical** | Logic errors causing runtime failures or incorrect generated SQL; clear violations of documented patterns (`.claude/rules/`, `.claude/skills/`); injection risks in generated SQL; type issues that will cause runtime errors |
| **advisory** | Code genuinely hard to follow or maintain; a missed established codebase pattern. Keep only the strongest-evidence advisories |

## Evidence bar

- **Critical = broken now, not "could break".** Cite the exact code path: input → broken call → observable failure. Can't replace "could fail" with "does fail" backed by specific lines → downgrade or drop.
- **A crash claim resting on a data state needs a producer of that state.** "Throws when the list is empty / the value is NULL" is only critical with a cited call path, fixture, or test that produces that state. No producer found → the invariant probably holds outside the diff; ask as an advisory question, never post as critical.
- **Read the PR description and linked issue before finalizing.** A scenario the description already rules out or declares intentional is refuted context, not a finding.
- **A claim about what a platform accepts or rejects is runnable.** Add the case to the matching integration suite and run it, rather than asserting from memory.
- **Repo grep can't prove a third-party claim.** A claim about a Doctrine DBAL or ORM method's existence or behaviour needs the installed package's source or the vendor doc. This repo supports DBAL 3 and 4 and ORM 2 and 3 at once, so check which majors the claim holds for. Unverifiable → don't post.
- **A behaviour change applied consistently across the diff is presumed intentional.** When the diff itself systematically implements the "bug" (every platform node, every call site), post one confirm-intent finding per mechanism, not per-site criticals.
- **Pre-existing defects get labelled and get a disposition.** A defect predating the change (check the old side of the hunk, or `git blame`) is at most an advisory opening with "Pre-existing:" that names which way it goes, fix now or a follow-up issue, never a critical against this change.
- **Read ±20 lines around each hunk before claiming.** Diffs hide guards, error handlers, and fallbacks; many "missing null check" claims evaporate two lines outside the hunk.
- **Advisory needs one of**: a failing scenario, a concrete code suggestion, or a reference to an existing codebase pattern. None of the three → drop.
- **A proposed change ships as code.** Write the replacement into the comment; can't write it out → drop it.
- **A suggested replacement passes the repo's own lints** (`composer phpcs`, `composer phpstan`, `composer psalm`). A suggestion tripping an enforced rule is incomplete: fix the suggestion, or name the suppression it needs.
- **A fix with more than one viable shape names them all**, each with what it costs; don't pick for the author.
- **The finding states what breaks and what to do.** Keep out the library internals you read to confirm it and any line about what you read or ran.
- **An item left open ends with the question you want answered**, not with a statement of what you want from the author.
- **Every referral names its target**: a code location by symbol or `file:line`, never "the earlier comment" or "the header".
- **A convention advisory also needs a consequence.** Name what the deviation breaks or risks; "consistency" alone doesn't qualify. A finding that hedges itself ("purely a nit", "isn't broken today") is below the bar: drop it, don't reword the hedge away.
- **Pattern-violation claims need verification.** Confirm the pattern exists (in `.claude/rules/`, a skill, or sibling code) before flagging a violation.
- **Zero findings is a valid outcome.** Don't manufacture comments to look thorough.
