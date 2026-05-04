## 1. VAF Types + Chronicle Type Update

- [x] 1.1 Create frontend/src/lib/vaf/types.ts with TypeScript interfaces mirroring Pydantic models: VAFBackground, VAFAtmosphere, VAFCameraMovement, VAFEffect, VAFTransition, VAFScene, AnimationScript. Include union literal types for all string enums (scene type, camera type, filter type, effect type, transition type, easing).
- [x] 1.2 Update frontend/src/types/api.ts Chronicle interface: add has_animation: boolean and animation_script: AnimationScript | null fields. Add ChronicleDetail interface extending Chronicle with guaranteed animation_script shape.
- [x] 1.3 Create frontend/src/hooks/useChronicleDetail.ts — useChronicleDetail(chronicleId: number) hook using TanStack Query, fetches GET /worldos/chronicles/{id}, returns { chronicle, isLoading, isError }.

## 2. Script Parser + Validator

- [x] 2.1 Create frontend/src/lib/vaf/parser.ts — parseAnimationScript(raw: unknown): AnimationScript | null. Validate: total_duration_ms (15000-60000), scenes array (2-8 items), each scene has required fields. Fill defaults: atmosphere filter "none" intensity 0, camera "static" speed 0.5 easing "linear", effects []. Return null on invalid input.
- [x] 2.2 Create frontend/src/lib/vaf/defaults.ts — default values for each VAF sub-type (DEFAULT_ATMOSPHERE, DEFAULT_CAMERA, DEFAULT_TRANSITION). Used by parser for missing fields.

## 3. Timeline Controller + Effect Scheduler

- [x] 3.1 Create frontend/src/lib/vaf/timeline.ts — TimelineState type (status: idle|playing|paused|ended, currentSceneIndex, elapsedMs, totalDurationMs), TimelineAction union type, timelineReducer function. Compute currentSceneIndex from elapsedMs by accumulating scene durations.
- [x] 3.2 Create frontend/src/lib/vaf/scheduler.ts — EffectScheduler class. Constructor takes VAFScene[]. Method getActiveEffects(sceneIndex, sceneElapsedMs) returns effects that should be active at given time. Handles trigger_at_ms per effect.
- [x] 3.3 Create frontend/src/hooks/useVAFPlayer.ts — Combines timeline reducer + rAF loop + effect scheduler. Exposes: { state, play, pause, seek, restart, currentScene, activeEffects, progress }. rAF loop dispatches TICK action with delta_ms. Cleans up on unmount.

## 4. Renderers — Background + Atmosphere + Narration

- [x] 4.1 Create frontend/src/components/vaf/BackgroundRenderer.tsx — Renders VAFBackground. Gradient type: CSS linear-gradient from colors array. Solid: single background-color. Pattern: repeating pattern (simple CSS). Smooth transition between scenes via Framer Motion animate.
- [x] 4.2 Create frontend/src/components/vaf/AtmosphereRenderer.tsx — Renders VAFAtmosphere as CSS overlay. Filters: mist (blur + white overlay), sepia (sepia filter), grain (noise SVG filter), glitch (clip-path animation), aurora (color shift), dust (warm tint), none. Intensity maps to opacity/strength. Weather overlay: rain/snow/fire_embers/sandstorm as CSS animation or particle hint.
- [x] 4.3 Create frontend/src/components/vaf/NarrationOverlay.tsx — Renders scene narration text. Typewriter effect via Framer Motion (char-by-char reveal). Positioned bottom-center with semi-transparent backdrop. Font: monospace or serif based on scene type.

## 5. Renderers — Particles + Camera + Effects

- [x] 5.1 Create frontend/src/components/vaf/ParticleRenderer.tsx — Canvas 2D particle system. Props: effects (VAFEffect[] filtered to type=particles), intensity, color, isPlaying. Object pool of max 200 particles. Each particle: x, y, vx, vy, alpha, size, lifetime. Spawn rate based on intensity. Render loop synced to player rAF. Cleanup Canvas on unmount.
- [x] 5.2 Create frontend/src/components/vaf/CameraRenderer.tsx — Wraps children in div with CSS transform. Maps VAFCameraMovement to transform: zoom_in (scale 1->1.15), zoom_out (scale 1.15->1), pan_left/right (translateX), dolly (scale + translateY), shake (random translate oscillation). Easing mapped to CSS transition-timing-function. Speed controls transition duration.
- [x] 5.3 Create frontend/src/components/vaf/EffectOverlay.tsx — Renders non-particle effects: screen_shake (CSS animation), flash (white overlay fade), ripple (radial gradient pulse), energy_burst (scale + opacity burst), glow (box-shadow pulse). Triggered by activeEffects from scheduler. Each effect is a short animation (200-800ms).

## 6. Scene Compositor + Transitions

- [x] 6.1 Create frontend/src/components/vaf/SceneCompositor.tsx — Stacks all 5 layers in correct z-order: Background (z-0) -> Atmosphere (z-10) -> Particles (z-20) -> EffectOverlay (z-30) -> NarrationOverlay (z-40). CameraRenderer wraps the entire stack. Props: scene (VAFScene), activeEffects, isPlaying.
- [x] 6.2 Add scene transitions in SceneCompositor using Framer Motion AnimatePresence. Transition types: fade (opacity 0->1), dissolve (opacity crossfade), wipe_left/wipe_right (clipPath animation), zoom_through (scale + opacity), cut (instant). Duration from scene.transition.duration_ms.

## 7. Player Controls + Cinematic Player Component

- [x] 7.1 Create frontend/src/components/vaf/PlayerControls.tsx — Play/pause button, progress bar (click to seek), current time / total time display, scene dots indicator (which scene is active), restart button. Tailwind styling: dark theme, semi-transparent, auto-hide after 3s of inactivity (show on mouse move).
- [x] 7.2 Create frontend/src/components/vaf/CinematicPlayer.tsx — Main player component. Props: animationScript (AnimationScript), chronicleTitle (string). Composes useVAFPlayer + SceneCompositor + PlayerControls. Fullscreen container (100vw x 100vh). Shows loading state, error state (invalid script), fallback state (no animation). Keyboard shortcuts: Space (play/pause), Left/Right (seek -5s/+5s), R (restart), Escape (exit).

## 8. Cinematic Page + Integration

- [x] 8.1 Create frontend/src/app/narrative-cinema/[chronicleId]/page.tsx — Next.js dynamic route page. Fetches chronicle via useChronicleDetail. If has_animation: render CinematicPlayer. If not: redirect back or show static text. Back button in top-left corner. Page title: chronicle.title.
- [x] 8.2 Update frontend/src/components/dashboard/tabs/library/ChronicleList.tsx — Add Cinema icon button next to expand chevron. Only show when chronicle.has_animation is true. Links to /narrative-cinema/{chronicle.id}. Use Film icon from lucide-react.
- [x] 8.3 Verify frontend/src/types/api.ts Chronicle interface has has_animation field (done in 1.2).

## 9. Polish + Status

- [x] 9.1 Add loading skeleton to CinematicPlayer while chronicle is fetching.
- [x] 9.2 Add error boundary around CinematicPlayer to catch render errors gracefully.
- [x] 9.3 Update .dev_status.md with VAF Phase 2 completion.
