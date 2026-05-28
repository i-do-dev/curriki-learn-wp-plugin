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
} from './Scenes';
import { NAVY, PALETTE_GOLD, PALETTE_CYAN, type Palette } from './theme';

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
};

export const LessonVideo: React.FC<InputProps> = ({ scenes, accent }) => {
  const palette = accent === 'cyan_orange' ? PALETTE_CYAN : PALETTE_GOLD;
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
