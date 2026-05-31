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
    // NAVY stays as the fallback for any letterbox edges; when a clip is present it fills the frame.
    <AbsoluteFill style={{ background: NAVY }}>
      {hasClip && (
        // Bottom layer: the external clip plays full-screen for the whole video. It carries its own
        // audio (the only audio in the render) and is truncated at the composition end automatically,
        // since total duration = sum of scene durations (the author's target length).
        <OffthreadVideo
          src={background_clip as string}
          style={{ width: '100%', height: '100%', objectFit: 'cover' }}
        />
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
