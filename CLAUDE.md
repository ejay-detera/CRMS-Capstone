# CRMS Capstone — Claude Code Rules

## Required References

**Always consult these two files before writing any frontend code:**

- **Vue expert skill** — `frontend/web/skills/vue-expert/SKILL.md` (and its `references/` folder)
- **Frontend rules** — `context/frontend-skills/frontend-rule.md`

These override any default behavior. Read them at the start of every frontend task.

## Component Architecture

**Always break large view files into sub-components.** Any view or component that grows beyond ~150 lines must be split into a folder-based structure:

```
views/admin/PageName/
  index.vue          ← thin shell: owns all state, logic, and data
  SomeTable.vue      ← receives props, emits events
  SomeDialog.vue     ← self-contained with internal form state
  ...
```

Rules:
- `index.vue` holds `ref`, `reactive`, `computed`, and event handlers. Its template is a short composition of sub-components.
- Sub-components receive data via `defineProps<T>()` and communicate up via `defineEmits<T>()`.
- Dialogs own their internal form state (touched, validation, reset). They emit `submit` with the final data; the parent mutates the list.
- Shared types go in `src/types/<domain>.ts` and are imported everywhere — never re-declare interfaces across files.
- Shared UI patterns (toggle switches, setting cards, etc.) go in `src/components/shared/`.

## Vue 3 Rules

- Always use `<script setup lang="ts">` — no Options API, no `defineComponent`.
- Use `ref()` for primitives, `reactive()` for objects/forms.
- Use `provide` / `inject` only for deep trees (e.g. settings tabs sharing a config object). Prefer props+emits for direct parent↔child.
- No `any` casts unless unavoidable; prefer explicit TypeScript types.

## Design System

- Brand colors only: `#252578` (navy), `#2E85D8` (blue), `#2F2F73` (dark navy). No off-brand colors.
- Avatar palette restricted to those three colors.
- Font: Poppins.
- Table headers: `bg-black/[0.018]` background, `border-black/[0.04]` bottom border.
- Pagination layout: `grid grid-cols-3` — Previous anchored left, page numbers centered, Next anchored right.

## Dependencies

Install new packages via Docker only:
```
docker compose exec web npm install <package>
```

## Dummy Data

All views use local `ref<T[]>([...])` dummy data until backend integration. No API calls yet.
