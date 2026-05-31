import React from 'react';
import { AbsoluteFill, OffthreadVideo, Sequence } from 'remotion';
import type { InputProps, LayoutType, Scene } from './types';
import { OverlayContext, PaletteContext } from './Scenes';
import {
  ComparisonScene,
  GateScene,
  RoutingScene,
  StatHighlightScene,
  TransformTextScene,
  IntroScene,
  ProblemScene,
  FrameworkScene,
  ProcessScene,
  ContrastScene,
  EvaluationScene,
  OptionsScene,
  ConclusionScene,
  CardListScene,
  BranchingFlowScene,
  BeforeAfterScene,
  QuadGridScene,
  ThreeStepFlowScene,
  CycleLoopScene,
  SplitBlueprintScene,
  FuelEngineScene,
  ChecklistRevealScene,
  DeploymentCirclesScene,
  EditorialScene,
} from './Scenes';
import {
  NAVY,
  PALETTE_GOLD, PALETTE_CYAN,
  PALETTE_EMERALD, PALETTE_VIOLET, PALETTE_ROSE, PALETTE_TEAL,
  type Palette,
} from './theme';

function resolvePalette(accent?: string): Palette {
  switch (accent) {
    case 'cyan_orange': return PALETTE_CYAN;
    case 'emerald':     return PALETTE_EMERALD;
    case 'violet':      return PALETTE_VIOLET;
    case 'rose':        return PALETTE_ROSE;
    case 'teal':        return PALETTE_TEAL;
    default:            return PALETTE_GOLD;
  }
}

type SceneFC = React.FC<{ scene: Scene; palette: Palette }>;

const LAYOUT_MAP: Record<LayoutType, SceneFC> = {
  // original 8
  intro:               IntroScene,
  problem:             ProblemScene,
  framework:           FrameworkScene,
  process:             ProcessScene,
  contrast:            ContrastScene,
  evaluation:          EvaluationScene,
  options:             OptionsScene,
  conclusion:          ConclusionScene,
  // v2 additions
  card_list:           CardListScene,
  branching_flow:      BranchingFlowScene,
  before_after:        BeforeAfterScene,
  quad_grid:           QuadGridScene,
  three_step_flow:     ThreeStepFlowScene,
  cycle_loop:          CycleLoopScene,
  split_blueprint:     SplitBlueprintScene,
  fuel_engine:         FuelEngineScene,
  checklist_reveal:    ChecklistRevealScene,
  deployment_circles:  DeploymentCirclesScene,
  // v3 additions
  editorial:           EditorialScene,
  // v4 additions
  comparison:          ComparisonScene,
  gate:                GateScene,
  routing:             RoutingScene,
  stat_highlight:      StatHighlightScene,
  transform_text:      TransformTextScene,
};

export const LessonVideo: React.FC<InputProps> = ({ scenes, accent, background_clip }) => {
  const palette = resolvePalette(accent);
  const hasClip = !!background_clip;
  let offset = 0;
  return (
    // Solid NAVY base. Scene content always renders on this dark field (crisp text); when a clip is
    // present the footage is immersed as a right-side band that melts into the navy (see below).
    <AbsoluteFill style={{ background: NAVY }}>
      {hasClip && (
        // Split-frame immersion: footage occupies the right ~38% only. A left→right navy gradient
        // dissolves its inner edge into the dark frame (no hard seam) and a faint full-band tint
        // harmonises it with the palette. The clip plays its own audio (the only audio in the
        // render); it is not looped — it holds its last frame once it ends. Total duration = the
        // scene-duration sum (the author's set length), so the lesson is never trimmed.
        <div style={{ position: 'absolute', top: 0, right: 0, width: '38%', height: '100%', overflow: 'hidden' }}>
          {/* Footage is wider than the band and biased right (center at ~60% of the band) so the
              clip's subject lands in the gradient's CLEAR zone, not under the navy melt. The >100%
              width guarantees no empty gap on the left after the shift; overflow:hidden clips it. */}
          <OffthreadVideo
            src={background_clip as string}
            style={{ position: 'absolute', top: 0, height: '100%', width: '132%', left: '60%', transform: 'translateX(-50%)', objectFit: 'cover' }}
          />
          <AbsoluteFill style={{
            // Soft navy melt on the inner (left) edge only; clear from ~52% onward where the subject sits.
            background:
              'linear-gradient(to right,' +
              ' #0B1A3B 0%, rgba(11,26,59,0.82) 10%, rgba(11,26,59,0.38) 30%,' +
              ' rgba(11,26,59,0.10) 52%, rgba(11,26,59,0.10) 100%)',
          }} />
        </div>
      )}
      <PaletteContext.Provider value={palette}>
        {scenes.map((scene, i) => {
          const from = offset;
          offset += scene.duration_frames;
          const Component: SceneFC = LAYOUT_MAP[scene.layout] ?? IntroScene;
          return (
            <Sequence key={`${i}-${scene.layout}`} from={from} durationInFrames={scene.duration_frames}>
              <OverlayContext.Provider value={{ overlay: hasClip, anchor: scene.overlay_anchor ?? 'bottom' }}>
                <Component scene={scene} palette={palette} />
              </OverlayContext.Provider>
            </Sequence>
          );
        })}
      </PaletteContext.Provider>
    </AbsoluteFill>
  );
};
