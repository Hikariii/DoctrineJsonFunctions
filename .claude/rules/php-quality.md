---
name: php-quality
description: PHP coding conventions: constructor promotion, enums vs constants, docblocks and comments, parameter validation, array types.
paths:
  - "src/**/*.php"
  - "tests/**/*.php"
---

# PHP coding conventions

## Best practices (not enforced by tools)

- **Constructor property promotion**: use whenever possible.
- **Prefer enums over constants, when the type does work.** Does anything take, match on, or validate the value?

| Shape | Choice |
|---|---|
| A parameter or return type, an exhaustive `match`, membership validation | backed `enum` |
| A name written at one or two call sites: wire field, array key handed to a library | `const` |
| Written by one class of ours, read by another | `public const` on the **writing** class |
| Several readers, no class that owns the name | `const` on the interface that owns the namespace, never a constants-only class or an enum |
| The same name in two different documents | a local constant in each; one shared constant would assert an identity that isn't there |
| An existing enum whose every use is `->value` | demote to constants |

  - Migrate opportunistically when reworking the area, in **both** directions, never as a standalone sweep.
  - Adding a parameter type that takes the enum so the type finally "does work" is manufacturing consumption to justify the shape.
- **Constructor promotion can't carry a narrowed type**: a promoted property is assigned before the constructor body, so `Assert::stringNotEmpty()` cannot narrow it and `@var non-empty-string` has nowhere to attach. When a field must satisfy a narrowed contract downstream, drop promotion for that field: declare it, annotate `@var`, assert, then assign. This is the exception to the promotion rule above, not a reason to widen the downstream type.
- **Self-documenting code**: route what you know into a name, a type or an assert first; a comment holds only what none of those can. See [code-quality.md § Comments](code-quality.md#comments-route-it-before-you-write-it).
- **Fix root causes, not symptoms**: for disabled or broken code, investigate why before working around it; don't duplicate functionality because something is disabled, enable and fix it.
- **Minimal docblocks**: document only what name + signature can't convey (non-obvious invariants, preconditions, edge cases). No non-obvious semantics → no docblock. Keep `@param`/`@return` where Psalm/PHPStan need them.
- **Never restate an annotation the interface already declares** (`@return non-empty-string` on both): let it inherit and narrow the value with an assert in the body.
- **Multi-line code comments use `/* … */`, not stacked `//`**: PHP-CS-Fixer style tools reformat `//` unpredictably, breaking sentences across lines; a `/* */` block stays one unit. Stacked `//` only for genuinely separate one-liners (labels).
- **An inline docblock between an operator and its expression only applies there**: never move the operator across it or hoist it above the statement, which drops the annotation silently. Grep `= /**` and `=> /**`.
- **Place actionable TODOs at the affected code, not the class docblock**: a `// TODO (...)` above the affected line is found when the work happens; a docblock TODO with `{@see self::method()}` is invisible from the call site and rots on rename or inline. Docblock TODOs only for class-wide design concerns.
- **Cross-references to other code use names, not "as above"**: "see previous" / "as above" / "counterpart to the above" rot on reorder, rename or move. Reference class + method, a concrete identifier, or a section heading. Same for internal code labels in test reasons, docblocks and comments ("branch A", "the second case"): state what the thing *does*. A reader who never opened the implementation must understand the comment.
- **Use `@psalm-param` for `@psalm-import-type`'d parameters**: prefer `@psalm-param TypeAlias $name` over plain `@param`; both work, but it signals to readers and non-Psalm tools that the type is an extended alias.
- **Tighten types at the source, not at the reader**: when an implementation always returns more specific than the interface declares (`list<ConcreteNode>` vs `list<Node>`), narrow it in the implementation's docblock; one annotation there beats N `@var` casts at call sites and stays in sync.
- **Prefer `final readonly` over `@psalm-immutable` / `@psalm-mutation-free`**: `final readonly class` enforces structural immutability at runtime; the psalm markers add a behavioural claim (no side effects, purity propagation) easy to forget as the class evolves. Use them only for a concrete static-analysis benefit `final readonly` can't provide.
- **Use `foreach + push` for filters that narrow the element type** (`instanceof X`, `!== null`, any psalm-narrowed predicate): prefer `foreach { if (...) $result[] = ... }` over `array_values(array_filter(...))` wrapped in a helper. Native `array_filter` has hardcoded narrowing in psalm's analyser; a user-defined wrapper can't inherit it: its declared `@return list<T>` wins and narrowing is lost.
- **Reach for the current language and stdlib features** where they express the intent at least as clearly as the older idiom. The supported version floor is in `composer.json` (`php: ^8.1`), so anything above it needs a polyfill or a version guard. A scan carrying running state stays a `foreach`.
- **A helper wrapping a native function keeps the native signature**: keep every parameter and loosen the callback type rather than drop one.

## Type-annotation approach

PHPStan and Psalm both gate a change and must pass simultaneously; when an annotation satisfies one but not the other, **prefer a less-strict type that satisfies both** over parallel `@phpstan-`/`@psalm-` docblocks. Resolving a reported type error (less-strict types, template parameters, `MixedAssignment`, invariant generics, tool-specific docblocks as last resort): [php-type-errors skill](../skills/php-type-errors/SKILL.md).

## Parameter validation

Assert at the boundary; don't silently convert.

```php
// ✅ Throws on invalid input, keeps the type contract
Assert::nullOrString($identifier);

// ❌ Silently coerces invalid input to null, hides bugs
$identifier = is_string($identifier) ? $identifier : null;
```

## Array types

| Situation | Type |
|---|---|
| Sequential (list) | `list<ValueType>` |
| Associative | `array<string, ValueType>` |
| Unknown keys | `array<array-key, mixed>` (preferred) |
| Mixed usage patterns | `array<mixed>` |
| Known structure | `array{filename: string, key: string}` |

Unions: `TypeA|TypeB`. Nullable in PHPDoc: `TypeA|null` (not `?TypeA`).
