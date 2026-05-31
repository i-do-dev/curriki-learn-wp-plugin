import React from 'react';
import { Composition } from 'remotion';
import { LessonVideo } from './compositions/LessonVideo';
import type { InputProps } from './compositions/types';

const defaultProps: InputProps = {
  title: 'Sample Lesson',
  accent: 'gold',
  // To preview overlay mode in Studio, set background_clip to a PUBLIC, direct video URL
  // (.mp4/.webm/.mov). Scenes render transparent over it; the clip is trimmed to the total length.
  // background_clip: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
  scenes: [
    {
      layout: 'intro',
      title: 'Getting Started with AI Tools',
      on_screen_text: 'You are ready to build.',
      narration: 'Welcome to this lesson on building effective AI tools for educators.',
      items: [
        { text: 'Lesson Planning' },
        { text: 'Feedback' },
        { text: 'Grading' },
        { text: 'Reports' },
      ],
      duration_frames: 180,
    },
    {
      layout: 'branching_flow',
      title: 'One Prompt, Many Outputs',
      on_screen_text: 'Context shapes every response.',
      narration: 'A single well-crafted prompt can generate differentiated outputs across multiple student needs.',
      items: [
        { text: 'Core Lesson Prompt', role: 'input' },
        { text: 'Struggling Learners', role: 'output' },
        { text: 'On-Grade Students', role: 'output' },
        { text: 'Advanced Track', role: 'output' },
      ],
      duration_frames: 210,
    },
    {
      layout: 'before_after',
      title: 'Scope Matters',
      on_screen_text: 'Two focused tools beat one overloaded tool.',
      narration: 'Avoid building a master tool that tries to do everything. Split responsibilities to get sharper results.',
      items: [
        { text: 'One Master Tool — Grading, Feedback, Reports, Lesson Plans', role: 'bad' },
        { text: 'Focused Feedback Tool — clear, fast, consistent', role: 'good' },
      ],
      duration_frames: 210,
    },
    {
      layout: 'quad_grid',
      title: 'Four Pillars of Prompt Design',
      on_screen_text: 'Role. Logic. Context. Constraints.',
      narration: 'Every effective AI tool is built on these four architectural pillars.',
      items: [
        { text: 'Role & Purpose', sub_label: 'Who the AI is acting as' },
        { text: 'Staged Logic', sub_label: 'Step-by-step reasoning flow' },
        { text: 'Data & Context', sub_label: 'Rubrics, templates, examples' },
        { text: 'Constraints', sub_label: 'Scope and compliance limits', featured: true },
      ],
      duration_frames: 240,
    },
    {
      layout: 'checklist_reveal',
      title: 'Pre-Launch Quality Check',
      on_screen_text: 'Find and close generic gaps.',
      narration: 'Run these checks before sharing your tool with students or staff.',
      items: [
        { text: 'Output stays on topic', status: 'pass' },
        { text: 'No student PII in prompts', status: 'pass' },
        { text: 'Tone is consistent across test cases', status: 'gap' },
        { text: 'Edge cases handled gracefully', status: 'pass' },
      ],
      duration_frames: 210,
    },
    {
      layout: 'cycle_loop',
      title: 'The Improvement Cycle',
      on_screen_text: 'Draft. Test. Share. Refine.',
      narration: 'AI tools are never finished — they improve with each iteration through this four-stage cycle.',
      items: [
        { text: 'Draft' },
        { text: 'Test' },
        { text: 'Share' },
        { text: 'Refine' },
      ],
      duration_frames: 180,
    },
  ],
};

export const Root: React.FC = () => {
  return (
    <Composition
      id="LessonVideo"
      component={LessonVideo}
      durationInFrames={1230}
      fps={30}
      width={1920}
      height={1080}
      defaultProps={defaultProps}
      calculateMetadata={({ props }) => ({
        durationInFrames: props.scenes.reduce((sum, s) => sum + s.duration_frames, 0),
      })}
    />
  );
};
