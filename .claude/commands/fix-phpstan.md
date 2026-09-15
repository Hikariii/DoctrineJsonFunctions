---
description: Fix every error PHPStan reports and nothing more, keeping psalm green too.
---

# PHPStan Error Resolution

Fix only what PHPStan reports: no refactoring, cleanup, or improvements beyond making PHPStan pass.

## Before Starting

Ask if not provided:
- **Scope** (e.g. `src/Query/AST`): blank means the whole project, which is what `phpstan.neon.dist` configures (`src` and `tests`)
- **Level**: blank means the level in `phpstan.neon.dist` (currently `0`); read the file if unsure

## Never limit PHPStan output

**Never pipe PHPStan through `grep`, `tail`, `head`, or any filter**: read the complete output. A filtered run hides errors introduced elsewhere and makes a fix look successful when it isn't.

```
composer phpstan > /tmp/phpstan.log 2>&1; echo "exit: $?"
```

Then open `/tmp/phpstan.log` with the Read tool. The log makes re-reading free: never re-run PHPStan just to see its output again.

Scoped run: `vendor/bin/phpstan analyse --memory-limit=512M <path> > /tmp/phpstan.log 2>&1`. Level override: add `--level=<n>`.

## Workflow

### 1. Run and triage

Group errors by file. More than 20 errors → announce the count and work file by file. Errors needing major refactoring → flag and ask before touching them.

### 2. Research unfamiliar errors

For any identifier you don't fully understand (e.g. `missingType.iterableValue`, `argument.type`, `return.missing`): WebFetch `https://phpstan.org/error-identifiers/<identifier>`, read the explanation, apply the documented solution: don't guess. The output shows the identifier (`🪪 missingType.iterableValue`) and often a `💡 See:` link.

### 3. Read before touching

Read each affected file with enough context to understand what the code does: don't patch blindly. A node class is read together with the platform variants that extend it.

### 4. Fix or suppress: pick the right approach

Priority order:
1. **Real code fix** when small and safe: a cast, a missing return type or PHPDoc annotation, or fixing a clearly wrong type.
2. **Less-strict types**: ALWAYS try first for PHPStan/Psalm conflicts; see the [php-type-errors skill](../skills/php-type-errors/SKILL.md) and [rules/php-quality.md § Array types](../rules/php-quality.md#array-types).
3. **Suppression** only per [code-quality.md § Suppression Policy](../rules/code-quality.md#suppression-policy): two valid reasons (false positive, or risky/large refactor), and when to ask the user first.
4. **Tool-specific docblocks** as absolute last resort when less-strict types fail ([php-type-errors § Tool-specific docblock tags](../skills/php-type-errors/SKILL.md#tool-specific-docblock-tags-last-resort)).

Read the tool configs (`phpstan.neon.dist`, `phpcs.xml.dist`) before fixing; don't guess at style rules.

### 5. Verify

Re-run PHPStan until it reports `[OK] No errors`. Once, after that loop: `composer psalm`; a Psalm error sends you back to step 4. Then `composer phpcs`, since a docblock edit can trip the coding standard.

## No baseline

The project has no PHPStan baseline: do not introduce one. `phpstan.neon.dist` sets `reportUnmatchedIgnoredErrors: false`, so a stale `ignoreErrors` entry passes silently; that is not permission to add one. If a fix needs significant refactoring, propose a documented inline `@phpstan-ignore` per the suppression policy and get explicit approval first.

## Completion Criteria

PHPStan reports `[OK] No errors`, with no new Psalm or phpcs errors introduced.
