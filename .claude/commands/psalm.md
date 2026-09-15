---
description: Run psalm across the codebase and fix every reported error, keeping phpstan green.
---

# Analyze and fix psalm errors

Fix only the errors psalm reports: nothing else.

## Never limit Psalm output

**Never pipe psalm through `grep`, `tail`, `head`, or any filter.** Psalm is slow and resource-intensive; filtered output can hide errors introduced elsewhere and make a fix look successful when it isn't. Run exactly:

```
composer psalm -- --no-progress > /tmp/psalm.log 2>&1; echo "exit: $?"
```

Then open `/tmp/psalm.log` with the Read tool and read every reported error before drawing any conclusion. The log makes re-reading free: never re-run psalm just to see its output again.

## Workflow

1. Run the exact capture command above on the whole codebase, never scope it or add arguments. Output per issue: error name, file and line, a dash, extended description, code snippet.
2. Todo-list the reported errors; the output already gives file + line per error, so don't go searching for similar signatures yourself.
3. Analyze the reported files and the surrounding code.
4. Fix: make the code compliant with the ruleset without changing its behaviour.
5. Verify: re-run the capture command.
6. Repeat 2 to 5 until psalm reports `No errors found!`.
7. Once, after the loop: `composer phpstan`, then `composer phpcs`; an error there sends you back to step 4.

## Dual-tool compatibility

**Always prefer less-strict types over tool-specific docblocks**: one annotation usually satisfies both Psalm and PHPStan.

Error patterns and their fixes: [php-type-errors skill](../skills/php-type-errors/SKILL.md). Proactive conventions: [rules/php-quality.md](../rules/php-quality.md).

Fixing conventions:
- Adhere to the file's existing style: PHPDoc syntax, variable naming, code structure.
- Precise PHPDoc annotations, per [rules/php-quality.md § Array types](../rules/php-quality.md#array-types).
- `psalm.xml` runs at `errorLevel="4"` with `findUnusedBaselineEntry="true"`, so a stale suppression fails the run. There is no baseline file; do not introduce one.

## Suppression Policy

Before any `@psalm-suppress` or a new `psalm.xml` handler, follow [code-quality.md § Suppression Policy](../rules/code-quality.md#suppression-policy): suppression is a last resort; exhaust proper fixes first, and remember Psalm only honours the **docblock** form for statement-level suppression.
