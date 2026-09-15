# MainPadel Implementation Plan

This plan is derived from `prd.md` and `design.md`. It is intended to be executed sequentially, with each milestone independently reviewable before the next one begins.

## Current implementation status

Core implementation through M13 is in place and verified where checked below. Remaining unchecked items are intentionally limited to manual viewport checks, external shared-hosting verification, and a production-like MySQL suite that should use a separately approved test database rather than the configured local Game database.

## Implementation guardrails

- Use Laravel, MySQL, Blade, Alpine.js, and Tailwind CSS.
- Keep the application server-rendered and shared-hosting-friendly: normal PHP requests, MySQL persistence, and lightweight Alpine interactions only.
- Authentication is now explicitly approved as a prerequisite for multi-user ownership; keep it limited to Laravel session-based register, sign in, and sign out.
- Do not add payments, subscriptions, venue or court booking, social features, AI features, WebSockets, queues, Redis, Livewire, Inertia, React, Vue, or Docker-only infrastructure unless a later approved requirement makes one necessary.
- Use “Game” or “Session” in the UI. An internal `Tournament` model/table is acceptable if it matches the existing domain direction.
- Treat completed match assignments and completed rounds as immutable. Standings must be derived from completed matches rather than stored in a duplicate standings table.
- The drawing engine must be isolated from HTTP controllers, views, and Eloquent persistence so it can be tested with plain input/output data.
- Mobile is the primary experience. Every screen must make the next organizer action obvious.

## Milestone sequence

M0 → M0.5 → M1 → M2 → M3 → M4 → M5 → M6 → M7 → M8 → M9 → M10 → M11 → M12 → M13

---

## M0 — Confirm product contracts and implementation decisions

### Objective

Turn the PRD and design specification into explicit domain, lifecycle, drawing, and UX contracts before application code is written.

### Tasks

- [x] Document the route/screen map for Home, Create Game, Players, Drawing/Current Round, Score Entry, Standings, and All Rounds.
- [x] Define the state transitions for game, round, and match statuses, including draft, ongoing, completed, cancelled, scheduled, and ongoing states where applicable.
- [x] Define the lock rule: completed match assignments and every round containing a completed match cannot be redrawn or structurally changed.
- [x] Define the roster-change rule for a round that has not started, a round that is partially played, and future rounds. Recommended MVP behavior: an entirely unplayed current round may be redrawn; once any match in a round is completed, that round is locked and changes apply from the next unplayed round.
- [x] Decide and document the Auto Rounds policy. Recommended MVP behavior: use one configurable bounded heuristic for the default number of rounds, while allowing the organizer to stop only at a completed-round boundary; do not hide the rule in a controller.
- [x] Define the duplicate-name policy. Recommended MVP behavior: player identity is session-scoped, duplicate names are rejected case-insensitively within one Game, and the same display name may exist in a different Game.
- [x] Define the drawing-engine input/output contract, including active roster, court count, prior locked-round history, round number, configurable weights, deterministic seed, and returned fairness metrics.
- [x] Define the ranking configuration and deterministic final tie-breaker after Points For, Point Difference, and Wins.
- [x] Define error behavior for fewer than four active players, too many requested courts, unfinished matches, invalid scores, and failed draw generation.
- [x] Record the decisions above in the implementation notes or issue history so later milestones do not reinterpret the product behavior.

### Acceptance Criteria

- [x] The team can explain how a Game moves from creation through final standings without inventing behavior during implementation.
- [x] The exact meaning of “future unplayed rounds” and the treatment of a partially played round are documented.
- [x] Auto round count, duplicate-name handling, score correction expectations, and ranking ties have explicit decisions.
- [x] The Drawing Engine contract can be tested without a database or HTTP request.

### Tests

- [x] Review the contracts against every relevant PRD edge case and design “next action” principle.
- [x] Walk through a 6-player/1-court session and a 12-player/3-court session on paper, including a late join and a withdrawal.

### Dependencies

- None. This milestone must be approved before implementation starts.

---

## M0.5 — Authentication and multi-user Game ownership

### Objective

Add the approved account boundary so each organizer can register, sign in, and access only their own Games.

### Tasks

- [x] Add the users table, Eloquent User model, password hashing, and factory support.
- [x] Build register, sign-in, sign-out routes and mobile-first Blade forms with validation and session regeneration.
- [x] Protect the application and Game routes with Laravel session authentication and preserve CSRF/session security behavior.
- [x] Add nullable Game ownership metadata and assign newly created Games to the authenticated user without changing existing Game records.
- [x] Scope the Home list and all Game, match, and roster operations to the authenticated owner; return not found for cross-user access attempts.
- [x] Keep authentication intentionally small: no email verification, password reset, social login, roles, admin dashboard, or account platform features yet.

### Acceptance Criteria

- [x] A new user can register, is signed in, and reaches their empty Home screen.
- [x] An existing user can sign in, sign out, and be redirected correctly when accessing a protected page as a guest.
- [x] A newly created Game is owned by its creator and is not visible or editable by another user.
- [x] Authentication does not require additional runtime services beyond the existing Laravel/MySQL/shared-hosting baseline.

### Tests

- [x] Feature tests cover registration, sign in, invalid credentials, sign out, guest protection, and per-user Game ownership.
- [x] Existing Game-flow tests run as an authenticated user and continue to pass after ownership enforcement.

### Dependencies

- M0.

---

## M1 — Project foundation and shared-hosting baseline

### Objective

Create the smallest deployable Laravel application foundation with the required frontend stack and visual primitives.

### Tasks

- [x] Create or configure the Laravel application for the repository’s supported PHP and MySQL versions.
- [x] Configure environment handling for MySQL, application URL, timezone, and production-safe error behavior.
- [x] Install and configure Blade, Alpine.js, Tailwind CSS, and the asset build pipeline without introducing an SPA framework.
- [x] Establish the base layout, typography defaults, color tokens, spacing, borders, and restrained lime accent from `design.md`.
- [x] Add reusable presentation primitives only where they will be shared: Button, Input, NumberStepper, StatusBadge, and layout containers.
- [x] Add a minimal health/home route and a consistent validation/error rendering convention.
- [x] Add CSRF protection, session flash messaging, and old-input preservation for normal Laravel form flows.
- [x] Define a shared-hosting deployment baseline: public document root, built assets, migrations, cache commands, writable directories, and no runtime dependency on workers or containers.

### Acceptance Criteria

- [x] A fresh environment can install dependencies, connect to MySQL, run migrations, build assets, and render a Blade page.
- [x] The base page is centered and mobile-first, with readable text, visible focus states, and no dashboard/sidebar treatment.
- [x] The implementation contains no authentication or unused platform infrastructure.
- [x] The application can run using ordinary PHP web requests suitable for shared hosting.

### Tests

- [x] Feature test for the health/home response.
- [x] Verify asset compilation and production asset loading.
- [ ] Manual checks at phone and desktop widths for overflow, typography, focus visibility, and touch targets.

### Dependencies

- M0.

---

## M2 — Database schema and domain model

### Objective

Persist the Game, roster, round, match, and match-player relationships with constraints that protect historical results.

### Tasks

- [x] Add migrations for `tournaments`/Games, `players`, `tournament_players`, `rounds`, `matches`, and `match_players` using the PRD fields and statuses.
- [x] Add useful indexes and uniqueness rules, including unique round numbers per Game, unique court numbers within a round, and prevention of duplicate player membership in one match where supported by MySQL.
- [x] Add any minimal metadata needed for reproducibility and locking, such as draw seed, generation timestamp, or lock timestamp, without duplicating standings.
- [x] Create Eloquent models, relationships, casts/enums, and status helpers for all core entities.
- [x] Define transaction boundaries for creating a Game, generating/persisting rounds, saving scores, and applying roster changes.
- [x] Add domain-level safeguards so completed match assignments cannot be altered by normal application services.
- [x] Define the query shape for completed-match history that will feed both standings and Drawing Engine fairness history.

### Acceptance Criteria

- [x] A Game can be represented with its roster, rounds, matches, and player assignments using the documented relationships.
- [x] Active/withdrawn roster state and joined/left round metadata are persisted.
- [x] Completed match assignments cannot be accidentally overwritten by a redraw or roster update.
- [x] There is no standings table or second source of truth for player statistics.

### Tests

- [x] Migration tests on the supported MySQL version.
- [x] Model relationship tests covering membership, rounds, matches, and teams.
- [x] Constraint/service tests for duplicate membership, invalid status transitions, and attempted mutation of completed assignments.
- [x] Transaction rollback test for a failed multi-record write.

### Dependencies

- M1.

---

## M3 — Game creation and initial player management

### Objective

Let an organizer create a Game and prepare a valid roster quickly from one mobile-first form.

### Tasks

- [x] Implement Home’s empty state, New Game action, and a lightweight recent-games list without analytics or promotional content.
- [x] Implement the single-page Create Game form for name, date, players, courts, target points, and Auto/Custom rounds.
- [x] Default target points to 21 and provide the specified point choices plus custom validation.
- [x] Implement fast player entry with Enter-to-add, one-tap removal, accessible labels, and no repeated modal flow.
- [x] Validate at least four players and show the contextual capacity message when requested courts exceed `floor(active_players / 4)`; do not treat that valid condition as a destructive error.
- [x] Validate positive courts, valid target points, non-empty names, and case-insensitive duplicate names within the Game.
- [x] Persist the Game and session-scoped roster in a transaction, then route to the draw-generation/preview flow.
- [x] Keep all validation server-authoritative; use Alpine only for immediate form feedback and small interactions.

### Acceptance Criteria

- [x] An authenticated organizer can enter a Game and its initial roster and reach the next drawing action.
- [x] Generate Draw is disabled or blocked with “Add at least 4 players to start” when appropriate.
- [x] A 6-player/2-court setup is accepted and clearly explains that only one court can be filled per round.
- [x] Duplicate names are handled according to the M0 decision and no invalid roster is persisted.
- [x] The form works with keyboard input and 44px-or-larger touch targets.

### Tests

- [x] Feature tests for valid creation, defaults, validation errors, duplicate names, and capacity messaging.
- [ ] Browser/manual test for Enter-to-add, remove, number stepper, custom points, and mobile form usability.
- [x] Verify failed creation leaves no partial Game or roster records.

### Dependencies

- M2.

---

## M4 — Drawing Engine foundation and hard-constraint validation

### Objective

Build an independently testable engine that can generate structurally valid rounds without relying on a simple random shuffle.

### Tasks

- [x] Create the isolated Drawing namespace and plain request/result/history objects, following the suggested separation of `DrawingService`, `CandidateGenerator`, `DrawingScorer`, and `DrawingValidator`.
- [x] Implement candidate generation as bounded guided search/backtracking or beam-style generation: select the correct number of participants, partition them into matches, assign two-player teams, and produce multiple candidates.
- [x] Use deterministic canonical ordering and an injectable seeded pseudo-random tie-breaker only where useful; randomness must never be the algorithm’s sole fairness mechanism.
- [x] Calculate the required match count as `min(number_of_courts, floor(active_players / 4))`.
- [x] Implement hard-constraint validation for active-player eligibility, no player collision, exactly four players per match, exactly two players per team, valid court count, unique court assignment, and complete coverage of generated matches.
- [x] Return a clear domain failure when fewer than four active players are available, and expose the maximum usable court count for contextual UI messaging.
- [x] Ensure candidate generation has a bounded candidate/restart budget and a safe failure path for unsupported or unexpectedly large rosters.
- [x] Keep Eloquent adapters outside the core algorithm; map database records into engine inputs in an application service.

### Acceptance Criteria

- [x] Given plain player IDs and history, the engine returns a valid round result or a typed, actionable failure.
- [x] Every generated result satisfies all H1–H5 constraints before any fairness score is considered.
- [x] The engine can generate one match for 4 active players and the correct number of matches for multi-court inputs.
- [x] Repeated calls with the same input and seed return the same canonical drawing.
- [x] Controllers and Blade views contain no pairing, collision, or fairness algorithm logic.

### Tests

- [x] Unit/property tests for every hard constraint and for malformed candidate data.
- [x] Validity scenarios for 4 players/1 court, 5 players/1 court, 6 players/1 court, 8 players/1 court, 8 players/2 courts, 10 players/2 courts, 12 players/2 courts, and 12 players/3 courts.
- [x] Include 7 players/1 court, inactive players, excessive court requests, duplicate IDs, and fewer-than-four active players.
- [x] Deterministic-seed test and bounded-failure test.

### Dependencies

- M2 and M3.

---

## M5 — Drawing fairness scoring, metrics, and tuning

### Objective

Make valid candidate selection fair over time, measurable in tests, and tunable without rewriting controllers or persistence code.

### Tasks

- [x] Implement a `FairnessHistory` builder from locked prior rounds, tracking per-player matches, rests, last-round rest, partner encounters, and opponent encounters.
- [x] Implement separate penalty components for match-count imbalance, rest imbalance, repeated partners, repeated opponents, and consecutive rests.
- [x] Normalize or otherwise document component scales, then add configurable scoring weights with defaults that preserve the PRD priority order: matches, rests, partners, opponents, consecutive rests.
- [x] Compare only hard-valid candidates and select the lowest weighted penalty with deterministic tie-breaking.
- [x] Expose a `FairnessMetrics` result containing per-player counts and aggregate metrics such as min/max spread, variance or deviation, repeated-pair counts, unique partners/opponents, and consecutive-rest counts.
- [x] Make the scoring configuration injectable or config-backed so development can tune weights without changing the engine contract.
- [x] Define how scheduled rounds are scored when generating a batch: regenerate future rounds from locked history and feed each newly accepted round into the next round’s temporary history.
- [x] Add instrumentation or debug-only result data sufficient to compare candidate scores during development without exposing noisy internals in the UI.

### Acceptance Criteria

- [x] A valid but unfair candidate cannot win solely because it is structurally valid when a lower-penalty candidate is available.
- [x] Match and rest balance are prioritized ahead of partner/opponent variety by the documented default configuration.
- [x] Partner and opponent repeat penalties use actual prior encounters, not only the immediately previous round.
- [x] Consecutive rest is avoided when the roster/court combination makes that reasonably possible.
- [x] Fairness metrics are available to automated tests and can be compared across algorithm revisions.
- [x] Same history plus same seed and weights is reproducible; changing weights can change candidate selection without changing hard validity.

### Tests

- [x] Score-component unit tests with hand-built histories where the expected penalty ordering is known.
- [x] Fairness tests for all required combinations: 4/1, 5/1, 6/1, 8/1, 8/2, 10/2, 12/2, and 12/3.
- [x] Multi-round simulations that assert bounded match-count and rest spreads, partner diversity, opponent diversity, and no avoidable consecutive rests.
- [x] Regression tests proving a completed-round history is included while regenerated future-round history is discarded.
- [x] Seed reproducibility and weight-tuning tests that assert metrics and constraints rather than brittle exact player pairings unless the seed contract explicitly requires an exact result.

### Dependencies

- M4.

---

## M6 — Drawing preview and current-round play experience

### Objective

Connect the engine to the primary “Draw → Play” flow and make the current round immediately understandable on a phone.

### Tasks

- [x] Implement the application service that maps a Game roster/history into Drawing Engine input, persists the generated round in one transaction, and moves the Game into its appropriate active state.
- [x] Build the drawing preview with Game metadata, “Drawing ready”, current round, courts, teams, resting players, and Start Game action.
- [x] Keep the current round as the primary screen; make View All Rounds a secondary action.
- [x] Build the Play screen with round indicator, vertically ordered court sections, reusable MatchCard, team names, Enter Result action, and resting-player section.
- [x] Add the simple session bottom navigation: Play, Rounds, Standings, Players, with labels, active state, fixed mobile positioning, and safe-area support.
- [x] Add loading and error states such as “Creating a fair draw...” and actionable draw-generation failures.
- [x] Prevent a user from starting or displaying an invalid/incomplete persisted drawing.

### Acceptance Criteria

- [x] Generate Draw produces the first valid preview for the organizer’s Game.
- [ ] The Play screen makes it obvious who plays next within three seconds on a phone-sized viewport.
- [x] All courts are shown in a clear vertical flow without a desktop dashboard layout.
- [x] Resting players and current round state are visible without overwhelming the primary action.
- [x] The engine remains the only place responsible for drawing validity and fairness.

### Tests

- [x] Feature tests for generating, persisting, and starting a first round.
- [x] Failure-path tests for fewer than four active players and persistence rollback.
- [ ] Manual mobile/desktop checks for hierarchy, navigation, court stacking, focus order, and daylight readability.

### Dependencies

- M3, M4, and M5.

---

## M7 — Fast score entry and completed-match handling

### Objective

Allow an organizer to record valid results in seconds while keeping score rules and historical assignments safe.

### Tasks

- [x] Implement the Score Entry screen and reusable ScoreInput for each scheduled match.
- [x] Support numeric keyboard entry, large readable scores, labels, and direct entry for both teams.
- [x] Add Alpine behavior that may fill the opposing score when the target total makes it unambiguous; always validate again on the server.
- [x] Validate non-negative integer scores and require `team_a_score + team_b_score = target_points`.
- [x] Show inline “Scores must total X points” feedback and disable Save Result when the client can already determine the total is invalid.
- [x] Save scores and mark the match completed transactionally; do not alter match-player assignments.
- [x] Render completed matches as FINAL with both scores and a readable winner indication; do not fade the losing team excessively.
- [x] Define the approved behavior for score correction, if needed, so any correction changes only the score fields and derived standings.

### Acceptance Criteria

- [x] A valid score is saved in one normal form flow and the match becomes completed.
- [x] Invalid, negative, non-integer, or incorrectly totaled scores cannot be persisted.
- [x] A completed match cannot be redrawn or have its players/teams/court changed.
- [x] The score-entry flow works with a phone numeric keyboard and keyboard-only navigation.
- [x] The target point value is taken from the Game, not hard-coded to 21.

### Tests

- [x] Form/request tests for valid 11–10, 12–9, custom target, zero edge values, and invalid totals.
- [x] Transaction and duplicate-submit tests for score persistence.
- [x] Feature test that completed assignments remain unchanged after score entry.
- [ ] Manual timing/usability check for the tap → type → save flow.

### Dependencies

- M6 and M2.

---

## M8 — Individual live standings

### Objective

Compute and display accurate individual standings from completed matches while the Game is in progress or finished.

### Tasks

- [x] Implement a standalone standings query/service that derives statistics from completed matches and match-player team assignments.
- [x] Calculate Played, Wins, Losses, Points For, Points Against, and Point Difference for each Game participant.
- [x] Include historical results for withdrawn players and zero-result participants according to the documented ranking policy.
- [x] Implement configurable ranking keys with the MVP order Points For, Point Difference, Wins, followed by the deterministic tie-breaker from M0.
- [x] Build the mobile standings rows with rank, player, points, played, and +/-; provide the wider desktop table with P/W/L/PF/PA/+/-.
- [x] Label the view LIVE STANDINGS while ongoing and FINAL STANDINGS after completion.
- [x] Add light top-three distinction without podium graphics or large decorative elements.

### Acceptance Criteria

- [x] Each player receives the team’s Points For/Against and win/loss result for every completed match they played.
- [x] Standings change immediately after a valid score is saved without a standings write or cache invalidation step.
- [x] Ranking order follows configurable rules and remains deterministic for ties.
- [x] Mobile standings are scannable without horizontal scrolling; desktop may show the full table.

### Tests

- [x] Unit tests using the PRD example score and multiple completed matches.
- [x] Tests for wins, losses, ties if the product permits them, zero matches, withdrawn players, custom target points, and ranking ties.
- [x] Feature test that correcting an allowed score changes derived standings while leaving assignments unchanged.
- [ ] Manual mobile and desktop readability check.

### Dependencies

- M7 and M2.

---

## M9 — Round progression, Auto/Custom rounds, and All Rounds

### Objective

Run sequential rounds reliably, retain the clear next action, and support both configured Custom rounds and the approved Auto policy.

### Tasks

- [x] Implement the round-generation coordinator that creates the next round only from the current eligible roster and locked history.
- [x] Update round statuses only at defined boundaries: scheduled, ongoing, and completed when every match in the round is completed.
- [x] Keep the organizer on the completed round long enough to review results; provide an explicit Continue to Round X action.
- [x] Generate the next drawing using the Drawing Engine and temporary prior-round history, including fairness context from earlier generated rounds that are still locked.
- [x] Implement Custom round count and the M0 Auto round-count heuristic/configuration.
- [x] Stop progression with an actionable message when fewer than four active players remain or when an unfinished match blocks progression.
- [x] Build All Rounds with clear Completed, Current, and Upcoming states, without dumping all rounds onto the primary Play screen.
- [x] Prevent duplicate round creation on refresh or repeated Continue requests.

### Acceptance Criteria

- [x] An organizer can complete several rounds using Play → Score → Continue to Next Round.
- [x] Completed rounds and their assignments remain unchanged while subsequent rounds are generated.
- [x] Custom rounds stop at the configured count; Auto follows the documented policy.
- [x] Multiple courts are progressed as one round and cannot advance until all required matches are completed.
- [x] Refreshes and repeated actions do not create duplicate rounds or duplicate matches.
- [x] All Rounds accurately distinguishes historical, current, and upcoming drawings.

### Tests

- [x] Feature tests for first-to-second-round progression, multiple courts, final-round detection, and insufficient-roster handling.
- [x] Idempotency tests for repeated Continue requests and browser refreshes.
- [x] Regression test that score/assignment history from prior rounds remains intact.
- [ ] Manual review of the next-action hierarchy on mobile.

### Dependencies

- M5, M7, and M8.

---

## M10 — Late join, player withdrawal, and confirmed future redraw

### Objective

Handle roster changes during a live Game without changing completed history or silently replacing future drawings.

### Tasks

- [x] Add a player during a session through the Players screen using the compact bottom-sheet/modal flow specified in design.md.
- [x] Store `joined_at_round` and make the player eligible only from the approved effective round.
- [x] Add Stop Playing with confirmation explaining that completed results remain and future rounds change.
- [x] Set withdrawn status and `left_at_round`; exclude the player from newly generated drawings while retaining historical matches and standings.
- [x] Calculate the exact locked/unplayed range in the redraw confirmation, for example “Rounds 1–3 will not change; Rounds 4–7 will be regenerated.”
- [x] Require explicit Redraw Future Rounds confirmation; never silently replace an existing future drawing.
- [x] Regenerate only eligible unplayed rounds in one transaction, rebuilding fairness history from locked rounds and applying the changed roster.
- [x] Preserve completed matches and completed rounds byte-for-byte at the domain level, including their participants, teams, court, and scores.
- [x] Define safe behavior for a withdrawal when the player is assigned to an uncompleted current match according to M0’s lock rule.
- [x] Show contextual errors when a roster change leaves fewer than four active players for the next round.

### Acceptance Criteria

- [x] A late joiner appears with the correct effective round and is considered in future fairness calculations without being promised catch-up parity.
- [x] A withdrawn player is absent from future eligible drawings but remains visible in historical results and derived standings.
- [x] Completed rounds never change after either roster operation.
- [x] Future redraw is explicit, reviewable, and reproducible; cancelling the dialog or omitting confirmation leaves the existing drawing untouched.
- [x] No regenerated round contains a player who is inactive, withdrawn, or not yet joined.
- [x] Partially played-round behavior matches the M0 decision and cannot invalidate a completed match.

### Tests

- [x] Required mid-session tests for player joins and leaves.
- [x] Tests for joins before a round starts, joins after completed rounds, withdrawal before a round starts, and withdrawal during a partially played round.
- [x] Tests for 4→5 players, 6→5 players, and falling below four active players.
- [x] Snapshot/regression test proving all completed match and round records remain unchanged after redraw.
- [x] Confirmation/cancellation feature tests and duplicate-submit/idempotency tests.
- [x] Fairness test proving the new roster is included in future history calculations.

### Dependencies

- M9, M5, and M2.

---

## M11 — Game completion, history, and result review

### Objective

Finish a Game cleanly and make past sessions/results easy to revisit without expanding into a tournament administration product.

### Tasks

- [x] Mark a Game completed only after the final required round and matches are complete.
- [x] Build the restrained completion state with Game name, winner/leader, Final Standings, View All Results, and New Game actions.
- [x] Make historical rounds and completed match scores viewable after completion.
- [x] Complete Home’s recent-games list with date, player count, and status; retain a useful no-games empty state.
- [x] Keep unfinished-match messaging actionable; Game cancellation is not exposed in the approved scope and remains reserved for a later product decision.
- [x] Ensure completed Game data is read-only for drawing/roster structure while any approved score-correction behavior remains explicit and safe.

### Acceptance Criteria

- [x] A Game reaches GAME COMPLETE only when its configured progression is complete.
- [x] Final standings match the same derived standings service used during play.
- [x] Historical rounds and results remain available after completion.
- [x] Home remains focused on starting a Game and returning to recent Games; no analytics dashboard is introduced.

### Tests

- [x] End-to-end test from Game creation through final score and completion.
- [x] Tests for incomplete final rounds and historical read-only behavior; cancellation tests are not applicable while cancellation is not exposed.
- [x] Home empty/recent Game feature tests.

### Dependencies

- M9 and M10.

---

## M12 — Responsive, accessible, and visual refinement

### Objective

Bring the implemented flow into alignment with the mobile-first design specification without adding unnecessary visual or architectural complexity.

### Tasks

- [x] Consolidate reusable UI components used by repeated structures: PlayerRow, MatchCard, ScoreInput, StatusBadge, StandingRow, BottomNavigation, dialog/bottom-sheet, and flash feedback primitives.
- [x] Apply the restrained palette, Inter/system typography, whitespace, light borders, and limited use of lime for primary/current/winner states.
- [x] Keep cards for true bounded objects such as matches and recent Games; replace unnecessary nested cards with whitespace and dividers.
- [x] Verify mobile content width around 480–640px and centered desktop behavior rather than a three-column dashboard.
- [x] Verify all primary controls meet the 44px minimum touch target and primary actions are approximately 48–52px high.
- [x] Add semantic labels, visible focus states, adequate contrast, keyboard score entry, and non-color-only state indicators.
- [x] Add indexable public Home metadata, canonical URL, Open Graph/Twitter metadata, and WebApplication structured data while keeping authenticated screens noindex.
- [x] Make fixed bottom navigation safe-area aware and ensure it does not obscure content or actions.
- [x] Keep motion limited to approximately 150–250ms for press feedback, score transitions, sheets, tabs, and subtle standings updates.
- [x] Review every screen against the anti-AI-slop rules: no gradients, glowing cards, excessive pills, giant marketing copy, decorative blobs, purple/blue AI palette, fake charts, unnecessary icons/emoji, or verbose helper text.
- [x] Verify contextual loading, validation, empty, and error states explain what the organizer can do next.

### Acceptance Criteria

- [ ] The complete flow is comfortable to use one-handed on a smartphone in a court-side context.
- [ ] The current match, score, next action, and standings hierarchy are immediately clear.
- [x] Navigation works with pointer, keyboard, and assistive technology basics.
- [x] The UI looks like a focused recreational sports utility rather than a generic admin dashboard or marketing page.
- [x] Desktop remains usable without changing the application into a dashboard layout.

### Tests

- [ ] Manual responsive pass at phone, tablet, and desktop widths for every MVP screen.
- [ ] Keyboard-only pass for forms, score entry, navigation, modals, and bottom sheets.
- [ ] Contrast and focus inspection, including states not distinguished by color alone.
- [ ] Touch-target and safe-area inspection on a real or emulated mobile viewport.

### Dependencies

- M3 through M11, with the core P0 flow complete before this milestone is considered finished.

---

## M13 — Final integration, regression, and deployment verification

### Objective

Prove the MVP success criteria across the full workflow and leave a practical shared-hosting release candidate.

### Tasks

- [ ] Run the full automated suite against the supported PHP/Laravel/MySQL versions and a production-like configuration.
- [x] Run the complete happy path: create Game → add players → generate draw → play → enter scores → next round → live individual standings → final standings.
- [x] Run the full roster-change path with late join, withdrawal, confirmation, future redraw, and immutable completed history.
- [x] Run all required Drawing Engine combinations and compare stored fairness metrics for regressions.
- [x] Add regression coverage for CSRF, validation, duplicate submissions, refreshes, stale forms, and transaction rollback.
- [x] Check draw-generation runtime and memory for the supported roster range, with bounded search behavior and actionable failure if limits are exceeded.
- [x] Verify MySQL indexes, foreign keys, status transitions, and production migration order.
- [ ] Verify that built assets, storage permissions, cache configuration, and public document-root setup work on standard shared hosting.
- [x] Remove debug output, development-only instrumentation, unused dependencies, and any accidental scope expansion.
- [x] Record the final known limitations, especially the approved Auto-round heuristic and partially played-round roster-change behavior.

### Acceptance Criteria

- [x] The MVP success criterion is met: an organizer can create a session, generate valid non-colliding draws, run multiple rounds, enter scores, and see correct individual standings.
- [x] The required 4/1, 5/1, 6/1, 8/1, 8/2, 10/2, 12/2, and 12/3 scenarios pass hard-constraint and fairness regression tests.
- [x] Late join and withdrawal preserve completed history and regenerate only approved future unplayed rounds after confirmation.
- [x] The application is deployable with ordinary PHP/MySQL shared-hosting operations and no prohibited runtime services.
- [x] The M0 implementation gate was approved before application implementation began.

### Tests

- [x] Full automated test suite, including unit, feature, integration, and Drawing Engine simulation tests.
- [ ] Manual end-to-end smoke test on a phone-sized viewport and a desktop viewport.
- [ ] Production-like deployment smoke test: migrate, build/load assets, create Game, generate draw, save score, inspect standings, and complete Game.

### Dependencies

- M0 through M12.

## Important planning decisions and ambiguities

- The PRD defines Auto mode but does not specify the number of rounds or a stopping rule. This plan intentionally makes the Auto heuristic configurable and requires the exact default policy to be approved in M0.
- The PRD says future unplayed rounds may be regenerated but does not fully specify a roster change during a partially played current round. The recommended rule is to lock any round once one of its matches is completed; an entirely unplayed current round can be redrawn, while later unplayed rounds are regenerated.
- Player identity remains Game-scoped even with organizer accounts: duplicate-name prevention is case-insensitive within one Game, while the same name may be entered in another Game without being treated as the same person.
- Authentication is now approved for organizer ownership. Existing pre-authentication Games retain nullable ownership metadata for safety; newly authenticated users can access only Games assigned to their account until an explicit migration policy is chosen.
- “Completed match immutable” is interpreted as immutable assignment, team, court, and historical participation. If score correction is required, it should change only validated score fields and let the derived standings update automatically.
- Fairness quality should be evaluated through exposed metrics and reproducible simulations rather than brittle assertions about one exact pairing sequence; the seed/weight configuration remains the source of deterministic regression behavior.
- Fairness metrics are returned by the Drawing Engine and persisted in `rounds.drawing_metrics`; a later product decision may add dedicated history/export if that becomes useful.
- Game cancellation is not exposed by the approved scope. The current product keeps unfinished states actionable and reserves a cancelled state for a future explicit requirement.
