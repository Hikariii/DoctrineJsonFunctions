---
name: writing
description: Use when writing human-facing text (commit messages, PR descriptions, issue text, code comments and docblocks, README and docs, review replies): plain words, the AI tells to avoid, no em dashes.
---

# Writing Style

The **human-facing** register: chat replies, commit messages, PR and issue text, README and docs, code comments and docblocks. Concise but readable prose. Not code itself.

`.claude/` config docs, `AGENTS.md` and `CONTEXT.md` are Claude-facing → a terser register: dense, telegraphic, no narrative paragraphs. Don't mix them: no narrative paragraphs in a rule, no telegraphic fragments in a PR description.

This is a public repository. Every commit message, PR description and issue reply is read by contributors who have no context from this session.

## Brevity

- **Say it straight.** No throat-clearing, hedging, pleasantries, filler.
- **Effect first, mechanism after.** Open on what the reader observes, even when the whole thing is technical; the explanation goes in its own block below, only where it changes their next step.
- **Detail to the reader's need, not to your investigation.** Having just worked something out is the signal you are about to over-specify it; name the mechanism once, at the level the reader acts on.
- **Simple never costs an exact token.** Shortening keeps the path, `file:line`, class name, sha, flag; "the earlier comment" or "the helper" is vagueness, not brevity.

## AI tells

- **Plain words.** Terms the codebase already uses, said as a teammate would out loud. No abstract or anglicised jargon, no inflated compound nouns, no invented labels.
- **No AI tells:**
  - triads ("clear, concise, and maintainable": keep the word that carries the weight; a list of genuinely distinct constraints is fine)
  - inflated adjectives (robust, seamless, comprehensive, powerful, elegant, significantly, greatly, carefully, simply: state what the thing does)
  - summary restatement ("In summary", a closing line repeating the body: end on the last real point)
  - symmetric scaffolding ("Not only X but also Y", "While X, Y": say X, say Y)
  - transition padding ("Additionally", "Moreover", "Furthermore", "It's important to note that": start with the fact)
  - restating the task before doing it
  - vague value verbs ("ensures a consistent experience": name the concrete change)
  - label lead-ins ("Proof:", "Note:", "Key takeaway:" announcing a point in conversational text: state it directly)
- **No business jargon:** navigate, unpack, deep-dive, circle back, leverage, game-changer.
- **Active voice.** Name the actor. **No em dashes.**

## Word and code hygiene

- **Spell words out.** `vulnerability`, not `vuln`. Acronyms and initialisms stay (`ID`, `SQL`, `DQL`, `AST`, `HTTP`, `PR`); a few entrenched clippings are fine (`config`, `repo`); keep that set small, unsure → spell out.
- **Keep the qualifier.** A compound term keeps every word in prose: path expression, not expression; result variable, not variable.
- **Never hard-wrap to a width**, in chat, drafts and comments alike: break only at sentence or list-item ends.
- **No doc vocabulary in code.** Don't transplant `.claude/` rule labels into comments, docblocks or assertion messages; describe behaviour in the code's own terms.
- **Issue numbers rarely belong in code.** "fixes #138, handles X" → "handles X"; keep the reference only when the comment can't be understood without it. Issue refs go in PR descriptions and commit messages, not long-lived code.
- **"Throws" gets an object.** Name the exception or the failing assert, never a bare "the walker throws".

```
Bad:  This PR introduces a robust and comprehensive solution that seamlessly
      unifies JSON path handling across all platforms, significantly improving
      maintainability. In summary, it ensures a consistent experience.
Good: Use one path-expression parser for every platform. Drops the per-platform
      branching in JsonExtract.
```
