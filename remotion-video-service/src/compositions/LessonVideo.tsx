import React from 'react';
import { AbsoluteFill, Sequence } from 'remotion';
import type { InputProps, LayoutType, Scene } from './types';
import {
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
};

export const LessonVideo: React.FC<InputProps> = ({ scenes, accent }) => {
  const palette = resolvePalette(accent);
  let offset = 0;
  return (
    <AbsoluteFill style={{ background: NAVY }}>
      {scenes.map((scene, i) => {
        const from = offset;
        offset += scene.duration_frames;
        const Component: SceneFC = LAYOUT_MAP[scene.layout] ?? IntroScene;
        return (
          <Sequence key={`${i}-${scene.layout}`} from={from} durationInFrames={scene.duration_frames}>
            <Component scene={scene} palette={palette} />
          </Sequence>
        );
      })}
    </AbsoluteFill>
  );
};
