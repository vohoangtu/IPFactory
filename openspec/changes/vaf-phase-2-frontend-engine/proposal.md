## Why

Chronicle list hien thi has_animation flag nhung khong co cach nao xem animation. Backend tra ve animation_script JSON (2-8 scenes voi background, atmosphere, camera, effects, narration, transition) nhung frontend khong co player de render. User khong co trai nghiem "cinematic" cho narrative — chi co text.

## What Changes

- **TypeScript types**: Mirror 7 Pydantic models (VAFBackground, VAFAtmosphere, VAFCameraMovement, VAFEffect, VAFTransition, VAFScene, AnimationScript) thanh TS interfaces
- **Script parser + validator**: Parse animation_script JSON, validate structure, cung cap defaults cho missing fields
- **Timeline controller**: State machine quan ly playback (play/pause/seek), current scene index, elapsed time
- **Effect scheduler**: Queue va trigger effects (particles, flash, shake) dung thoi diem trong timeline
- **5 renderers**: Background (CSS gradient/solid), Atmosphere (CSS filter overlays), Particles (Canvas 2D), Camera (CSS transform), Text overlay (Framer Motion)
- **useVAFPlayer hook**: Orchestrate timeline + renderers, expose play/pause/seek/progress API
- **CinematicPlayer page**: Next.js page tai /narrative-cinema/[chronicleId] — fullscreen cinematic experience
- **Chronicle detail hook**: useChronicleDetail(id) fetch single chronicle voi animation_script
- **Integration**: Them nut "Cinema" vao ChronicleList khi has_animation === true

## Capabilities

### New Capabilities
- `vaf-player`: Cinematic animation player rendering VAF animation scripts
- `chronicle-detail`: Single chronicle fetch with animation_script data
- `narrative-cinema-page`: Fullscreen cinematic page for chronicle playback

### Modified Capabilities
- `chronicle-list`: Them nut Cinema link den /narrative-cinema/[id] khi has_animation

## Impact

- `frontend/src/types/api.ts` — add Chronicle.has_animation, animation_script fields + VAF type interfaces
- `frontend/src/lib/vaf/types.ts` — VAF-specific types (NEW)
- `frontend/src/lib/vaf/parser.ts` — script parser + validator (NEW)
- `frontend/src/lib/vaf/timeline.ts` — timeline controller (NEW)
- `frontend/src/lib/vaf/scheduler.ts` — effect scheduler (NEW)
- `frontend/src/components/vaf/BackgroundRenderer.tsx` — CSS gradient/solid background (NEW)
- `frontend/src/components/vaf/AtmosphereRenderer.tsx` — filter overlay (NEW)
- `frontend/src/components/vaf/ParticleRenderer.tsx` — Canvas 2D particles (NEW)
- `frontend/src/components/vaf/CameraRenderer.tsx` — CSS transform wrapper (NEW)
- `frontend/src/components/vaf/NarrationOverlay.tsx` — text overlay with Framer Motion (NEW)
- `frontend/src/components/vaf/SceneCompositor.tsx` — compose all layers per scene (NEW)
- `frontend/src/components/vaf/CinematicPlayer.tsx` — main player component (NEW)
- `frontend/src/components/vaf/PlayerControls.tsx` — play/pause/seek/progress bar (NEW)
- `frontend/src/hooks/useVAFPlayer.ts` — player state hook (NEW)
- `frontend/src/hooks/useChronicleDetail.ts` — single chronicle fetch hook (NEW)
- `frontend/src/app/narrative-cinema/[chronicleId]/page.tsx` — cinematic page (NEW)
- `frontend/src/components/dashboard/tabs/library/ChronicleList.tsx` — add Cinema button
