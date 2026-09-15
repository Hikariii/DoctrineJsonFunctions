---
name: php-type-errors
description: Use when PHPStan or Psalm reports a type error on PHP code: resolving it with a less-strict type that satisfies both tools, declaring template parameters, the MixedAssignment and invariant-generic patterns, and tool-specific docblocks as the last resort.
---

# PHPStan and Psalm type errors

Both tools gate a change and must pass simultaneously (`composer phpstan`, `composer psalm`). Proactive PHP conventions (docblocks, promotion, array types): [rules/php-quality.md](../../rules/php-quality.md). Whether to suppress at all: [rules/code-quality.md § Suppression Policy](../../rules/code-quality.md#suppression-policy).

Unfamiliar error identifier → read `https://phpstan.org/error-identifiers/<identifier>` before guessing.

## Prefer a less-strict type that satisfies both tools

When an annotation satisfies one tool but not the other, loosen it rather than writing parallel `@phpstan-`/`@psalm-` docblocks: one annotation, easier to maintain, usually matches real call-site usage.

- Check actual call-site usage before tightening; if call sites vary, loosen the annotation rather than changing dozens of callers.
- `array<array-key, mixed>` not `array<int, mixed>`: survives inheritance, both tools accept.
- `array<mixed>` when call sites mix enums, associative, and list shapes.
- Flexible property types on base classes children extend: `@var array<array-key, string>`, not `array{0: string, 1: string}|array{}`.

Applies to: abstract nodes with per-platform implementations that disagree, constructors with varied params, properties inherited by many children, any case where PHPStan passes but Psalm reports an inheritance or usage conflict.

## `MissingTemplateParam`: declare the parameter explicitly

Declare the parameter on every link in the chain: the interface, the implementation, and each concrete child of a generic base.

```php
/** @template T of \Doctrine\ORM\Query\AST\Node */
abstract class NodeCollection
{
	/** @param list<T> $nodes */
	public function __construct(protected array $nodes) {}
}

/** @template-extends NodeCollection<\Doctrine\ORM\Query\AST\PathExpression> */
final class PathExpressionCollection extends NodeCollection {}
```

## Psalm `MixedAssignment`: pass the mixed value, don't assign it

The check fires on assigning a `mixed` expression to a variable, never on passing one to a parameter declared `mixed`. Narrow an untyped source (a decoded JSON payload, a raw driver result row) by handing it straight to a helper:

```php
// ✅ no suppression needed
return self::asNonEmptyString($row['data']);

private static function asNonEmptyString(mixed $value): string | null
{
	return is_string($value) && $value !== '' ? $value : null;
}

// ❌ reaching for the suppression first
/** @psalm-suppress MixedAssignment */
$data = $row['data'];
```

Go to the helper first for an array offset: `is_string($arr['k'] ?? null)` does not narrow the offset, so keeping the guard inline just moves the error to `MixedArgument` on the next call.

## `Assert::allPositiveInteger` narrows for PHPStan, not Psalm

Satisfying a `list<positive-int>` parameter from a `list<int>` via inline `Assert::allPositiveInteger($list)` passes PHPStan (phpstan-webmozart-assert) but leaves Psalm reporting `ArgumentTypeCoercion`. Narrow at the source (`@return positive-int` on the producing method and `@param list<positive-int>` on the intermediate), not at the inline `all*` assert at the call.

## Invariant generic looks like a false positive

PHPStan reports `argument.type` with identical types on both sides and `💡 Template type T is not covariant`. Not a false positive: the hint is the diagnosis: an invariant `@template T` can't prove exact type identity when a variable annotated `Foo<X>` is passed to another `Foo<X>` parameter.

| Approach | Verdict |
|---|---|
| Remove the generic annotation (leave the raw PHP type) | ✅ preferred first attempt: accepts any instantiation |
| Declare `@template-covariant T` on the class or interface you own | ✅ best long-term fix |
| `@param Foo<covariant T>` (generic tag) | ❌ Psalm parses `covariant` as a namespace |
| `@phpstan-param Foo<covariant T>` + `@psalm-param Foo<T>` | ✅ last resort only |

**Never** `@param Foo<covariant T>` in `src/`: Psalm misparses `covariant` regardless of PHPStan.

## Tool-specific docblock tags (last resort)

Only when one tool needs syntax that crashes the other (PHPStan `covariant`, star projection, call-site variance). Write parallel tags: Psalm reads `@psalm-*` and ignores `@phpstan-*`; PHPStan the reverse. Drop the plain `@param`/`@return` to avoid ambiguity.

```php
/**
 * @phpstan-param Collection<covariant Node> $nodes
 * @psalm-param   Collection<Node> $nodes
 */
```

## Registered suppressions already in this repo

`psalm.xml` holds the registered `<issueHandlers>`; read it before concluding an issue is unsilenced. A new entry there is a suppression like any other and goes through the [suppression policy](../../rules/code-quality.md#suppression-policy). `findUnusedBaselineEntry` is on, so a stale entry fails the run.
