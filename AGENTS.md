# CMS Capstone — Codex Rules

## Required References

**Always consult these canonical references before writing any frontend code:**

- **Canonical ONE UI Design System** — `DESIGN.md` (Mandatory shared specification for all 4 subsystems: CMS/CRMS, SERMS, PRS, and TS)
- **Vue expert skill** — `frontend/web/skills/vue-expert/SKILL.md` (and its `references/` folder)
- **Frontend rules** — `context/frontend-skills/frontend-rule.md`

These override any default behavior. Read them at the start of every frontend task.

## Canonical ONE UI Design System (`DESIGN.md`) Directives

All AI agents and developers must strictly follow the specifications in `DESIGN.md`:

1. **Brand Color Palette**:
   - Primary: `#252578` (Brand Navy)
   - Accent: `#2E85D8` (Brand Accent Blue)
   - Deep Surface: `#3A3A85` / Border `#4E4E91` / `#2F2F73`
   - Page Background: `#F8F9FA` / Pure White `#FFFFFF`
   - Semantic Statuses: Error `#EF4444`, Warning/Pending `#F59E0B`, Success/Approved `#10B981`
   - Never inject arbitrary colors or off-brand gradients.
2. **Typography Standards**:
   - **Primary Font**: `Poppins` (Headings, titles, buttons, navigation, table headers, forms).
   - **Secondary Font**: `Montserrat` (Descriptions, helper text, subtitles, table subtext, timestamps).
3. **8px Grid Hierarchy**:
   - Margins & padding must strictly adhere to the 8px grid (`8px`, `16px`, `24px`, `32px`, `48px`). 4px is reserved for micro-badges.
4. **Icons**:
   - Exclusively import icons from `lucide-vue-next`. Never use external icon fonts or unvetted SVGs.
5. **Loading States**:
   - **Skeleton Loaders**: Mandatory for data fetching and layout rendering (tables, cards, profiles).
   - **Spinner Graphics (`Loader2`)**: Reserved strictly for actions, button submissions, and network uploads.
6. **Toast Notifications**:
   - Fixed Top-Right (`top: 24px; right: 24px; z-index: 1200;`) with a 4px vertical accent stripe (`border-l-4`), auto-dismiss timer of 4 seconds, and timer pauses on hover.
7. **Status Badges**:
   - Must strictly be formatted in **Title Case** (e.g. `Active`, `Pending`, `Approved`, `Rejected`), never ALL-CAPS.
8. **Sidebar Brand Lockup**:
   - Subsystem Name: `CONTRACT` (Poppins Bold uppercase) on top, `MANAGEMENT SYSTEM` (Poppins Regular) underneath.

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

## Dependencies

Install new packages via Docker only:
```
docker compose exec web npm install <package>
```

## Dummy Data

All views use local `ref<T[]>([...])` dummy data until backend integration. No API calls yet.

## Task Workflow

**For every task the user gives, always come up with a plan first before executing.**

1. State what files will be created or modified.
2. List the specific changes per file (types, props, emits, template additions, etc.).
3. Call out any potential gotchas (TypeScript constraints, overflow clipping, shared component impact, etc.).
4. Wait for implicit or explicit user confirmation, then execute.

Do not write any code until the plan is laid out.
