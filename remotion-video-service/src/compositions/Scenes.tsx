/**
 * All lesson video scene / layout components.
 *
 * Visual style: deep navy (#0B1A3B) background.
 * Accent colour comes from the `palette` prop (gold or cyan/orange).
 *
 * Every component signature: React.FC<{ scene: Scene; palette: Palette }>
 * Remotion frame numbers are relative to the start of each <Sequence>.
 */
import React from 'react';
import {
  AbsoluteFill,
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
} from 'remotion';
import type { Scene, SceneItem } from './types';
import type { Palette } from './theme';
import {
  NAVY, WHITE, WHITE_DIM, AMBER,
  CARD_BG, CARD_PLAIN, SAFE, FONT,
} from './theme';

// ─────────────────────────────────────────────────────────────────────────────
// SHARED ANIMATION HOOKS
// ─────────────────────────────────────────────────────────────────────────────

function useFadeIn(delay = 0, dur = 18): number {
  const frame = useCurrentFrame();
  return interpolate(frame - delay, [0, dur], [0, 1], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });
}

function useSlideUp(delay = 0, distance = 36): React.CSSProperties {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const p = spring({ frame: Math.max(0, frame - delay), fps, config: { damping: 15, stiffness: 110 } });
  return {
    opacity: interpolate(Math.max(0, frame - delay), [0, 12], [0, 1], {
      extrapolateLeft: 'clamp', extrapolateRight: 'clamp',
    }),
    transform: `translateY(${(1 - p) * distance}px)`,
  };
}

function useScaleIn(delay = 0): React.CSSProperties {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const p = spring({ frame: Math.max(0, frame - delay), fps, config: { damping: 12, stiffness: 130 } });
  return {
    opacity: interpolate(Math.max(0, frame - delay), [0, 8], [0, 1], {
      extrapolateLeft: 'clamp', extrapolateRight: 'clamp',
    }),
    transform: `scale(${p})`,
  };
}

// ─────────────────────────────────────────────────────────────────────────────
// SHARED COMPONENTS
// ─────────────────────────────────────────────────────────────────────────────

const SceneWrap: React.FC<{ children: React.ReactNode }> = ({ children }) => (
  <AbsoluteFill style={{
    background: NAVY,
    fontFamily: FONT,
    padding: SAFE,
    boxSizing: 'border-box',
    overflow: 'hidden',
  }}>
    {children}
  </AbsoluteFill>
);

const AccentTitle: React.FC<{ text: string; palette: Palette; delay?: number; size?: number }> =
  ({ text, palette, delay = 0, size = 58 }) => {
    const style = useSlideUp(delay, 40);
    return (
      <div style={{ fontSize: size, fontWeight: 800, color: palette.accent, lineHeight: 1.1, letterSpacing: '-0.5px', marginBottom: 28, ...style }}>
        {text}
      </div>
    );
  };

const WhitePhrase: React.FC<{ text: string; delay?: number; size?: number; italic?: boolean }> =
  ({ text, delay = 0, size = 32, italic }) => {
    const style = useSlideUp(delay, 30);
    return (
      <div style={{ fontSize: size, fontWeight: 500, color: WHITE, lineHeight: 1.35, fontStyle: italic ? 'italic' : 'normal', ...style }}>
        {text}
      </div>
    );
  };

const GlassCard: React.FC<{
  children: React.ReactNode;
  style?: React.CSSProperties;
  accent?: boolean;
  palette?: Palette;
}> = ({ children, style, accent, palette }) => (
  <div style={{
    background: CARD_BG,
    border: `1px solid ${accent && palette ? palette.cardBorder : CARD_PLAIN}`,
    borderRadius: 16,
    padding: '22px 28px',
    boxShadow: accent && palette ? palette.glow : 'none',
    ...style,
  }}>
    {children}
  </div>
);

const NarrationBar: React.FC<{ text: string }> = ({ text }) => {
  const op = useFadeIn(20, 16);
  if (!text) return null;
  return (
    <div style={{
      position: 'absolute',
      bottom: SAFE - 28,
      left: SAFE,
      right: SAFE,
      opacity: op,
      fontSize: 21,
      color: WHITE_DIM,
      textAlign: 'center',
      lineHeight: 1.5,
      fontStyle: 'italic',
    }}>
      {text}
    </div>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// SCENE 1 — INTRO
// ─────────────────────────────────────────────────────────────────────────────

const BADGE_OFFSETS: Array<Partial<{ top: number; left: number; right: number; bottom: number }>> = [
  { top: -130, left: -70 },
  { top: -125, right: -130 },
  { top:   10, left: -215 },
  { top:   10, right: -220 },
  { bottom: -115, left: -60 },
  { bottom: -110, right: -75 },
];

export const IntroScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const phraseStyle = useSlideUp(40, 30);

  return (
    <SceneWrap>
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100%' }}>
        <div style={{ position: 'relative', marginBottom: 52 }}>
          <GlassCard accent palette={palette} style={{ padding: '32px 60px', textAlign: 'center', ...useScaleIn(0) }}>
            <div style={{ fontSize: 34, fontWeight: 700, color: palette.accent }}>{scene.title}</div>
          </GlassCard>
          {scene.items.slice(0, 6).map((item, i) => {
            const pos = BADGE_OFFSETS[i] ?? { top: -130, left: 0 };
            const delay = i * 8 + 12;
            const p = spring({ frame: Math.max(0, frame - delay), fps, config: { damping: 14, stiffness: 100 } });
            const opacity = interpolate(Math.max(0, frame - delay), [0, 10], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
            return (
              <div key={i} style={{
                position: 'absolute', ...(pos as React.CSSProperties),
                opacity, transform: `scale(${p})`,
                background: CARD_BG, border: `1px solid ${CARD_PLAIN}`,
                borderRadius: 24, padding: '7px 20px',
                fontSize: 20, color: WHITE_DIM, whiteSpace: 'nowrap',
                display: 'flex', alignItems: 'center', gap: 7,
              }}>
                {item.icon && <span style={{ fontSize: 18, lineHeight: 1 }}>{item.icon}</span>}
                {item.text}
              </div>
            );
          })}
        </div>
        <div style={phraseStyle}>
          <div style={{ fontSize: 38, fontWeight: 600, color: WHITE, textAlign: 'center', fontStyle: 'italic' }}>
            "{scene.on_screen_text}"
          </div>
        </div>
      </div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// SCENE 2 — PROBLEM  (item.featured or last item rises accent-gold)
// ─────────────────────────────────────────────────────────────────────────────

export const ProblemScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const hi = scene.items.findIndex(it => it.featured);
  const highlightIdx = hi !== -1 ? hi : scene.items.length - 1;
  const riseDelay = scene.items.length * 9 + 28;
  const riseP = spring({ frame: Math.max(0, frame - riseDelay), fps, config: { damping: 14, stiffness: 90 } });

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', flexDirection: 'column', gap: 18 }}>
        {scene.items.map((item, i) => {
          const isHl = i === highlightIdx;
          const appearOp = interpolate(Math.max(0, frame - i * 9), [0, 15], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          const lit = isHl && frame > riseDelay;
          return (
            <GlassCard key={i} accent={lit} palette={palette} style={{
              opacity: appearOp,
              transform: `translateY(${isHl ? -riseP * 16 : 0}px) scale(${isHl ? 1 + riseP * 0.02 : 1})`,
              borderColor: lit ? palette.accent : CARD_PLAIN,
            }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
                {item.icon && <span style={{ fontSize: 28, lineHeight: 1, flexShrink: 0 }}>{item.icon}</span>}
                <span style={{ fontSize: 24, color: lit ? palette.accent : WHITE, fontWeight: isHl ? 700 : 400 }}>{item.text}</span>
              </div>
            </GlassCard>
          );
        })}
      </div>
      <div style={{ marginTop: 32, ...useSlideUp(riseDelay + 18) }}>
        <WhitePhrase text={scene.on_screen_text} />
      </div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// SCENE 3 — FRAMEWORK  (numbered blueprint grid)
// ─────────────────────────────────────────────────────────────────────────────

export const FrameworkScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const cols = Math.min(scene.items.length, 3);

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <WhitePhrase text={scene.on_screen_text} delay={15} size={26} />
      <div style={{ display: 'grid', gridTemplateColumns: `repeat(${cols}, 1fr)`, gap: 24, marginTop: 28 }}>
        {scene.items.map((item, i) => {
          const delay = i * 10 + 22;
          const p = spring({ frame: Math.max(0, frame - delay), fps, config: { damping: 15, stiffness: 110 } });
          const opacity = interpolate(Math.max(0, frame - delay), [0, 12], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          return (
            <div key={i} style={{
              opacity, transform: `translateX(${(1 - p) * 45}px)`,
              background: CARD_BG, border: `1px solid ${palette.cardBorder}`,
              borderRadius: 14, padding: '24px 22px 20px', position: 'relative',
            }}>
              <div style={{
                position: 'absolute', top: -12, left: 16,
                background: palette.accent, color: NAVY,
                fontSize: 13, fontWeight: 800, width: 26, height: 26, borderRadius: '50%',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
              }}>{i + 1}</div>
              {item.icon && <div style={{ fontSize: 26, lineHeight: 1, marginBottom: 8, marginTop: 6 }}>{item.icon}</div>}
              <div style={{ fontSize: 20, color: WHITE, fontWeight: 600, marginTop: item.icon ? 0 : 6 }}>{item.text}</div>
              {item.sub_label && <div style={{ fontSize: 15, color: WHITE_DIM, marginTop: 4 }}>{item.sub_label}</div>}
            </div>
          );
        })}
      </div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// SCENE 4 — PROCESS  (horizontal pipeline with animated accent line)
// ─────────────────────────────────────────────────────────────────────────────

export const ProcessScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const nodeCount = scene.items.length;
  const lineProgress = interpolate(frame, [18, 85], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', alignItems: 'center', marginTop: 70 }}>
        {scene.items.map((item, i) => {
          const delay = i * 12 + 5;
          const p = spring({ frame: Math.max(0, frame - delay), fps, config: { damping: 14, stiffness: 110 } });
          const opacity = interpolate(Math.max(0, frame - delay), [0, 12], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          const isLast = i === nodeCount - 1;
          return (
            <React.Fragment key={i}>
              <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', opacity }}>
                <div style={{
                  width: 76, height: 76, borderRadius: '50%',
                  background: CARD_BG, border: `3px solid ${palette.accent}`,
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  transform: `scale(${p})`, boxShadow: palette.glow,
                }}>
                  <div style={{ fontSize: 24, fontWeight: 800, color: palette.accent }}>{i + 1}</div>
                </div>
                <div style={{ fontSize: 20, color: WHITE, marginTop: 14, textAlign: 'center', maxWidth: 160 }}>{item.text}</div>
              </div>
              {!isLast && (() => {
                const segStart = i / (nodeCount - 1);
                const segEnd = (i + 1) / (nodeCount - 1);
                const segP = interpolate(lineProgress, [segStart, segEnd], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
                return (
                  <div style={{ flex: 1, height: 3, background: CARD_PLAIN, position: 'relative', marginBottom: 30 }}>
                    <div style={{ position: 'absolute', left: 0, top: 0, height: '100%', width: `${segP * 100}%`, background: palette.accent }} />
                  </div>
                );
              })()}
            </React.Fragment>
          );
        })}
      </div>
      <div style={{ marginTop: 52, ...useSlideUp(75) }}><WhitePhrase text={scene.on_screen_text} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// SCENE 5 — CONTRAST  (before card fades; two after cards slide in)
// role:'bad' = before, role:'good' = after; positional fallback.
// ─────────────────────────────────────────────────────────────────────────────

export const ContrastScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const splitDelay = 52;
  const splitP = spring({ frame: Math.max(0, frame - splitDelay), fps, config: { damping: 13, stiffness: 90 } });
  const overloadedOp = interpolate(frame - splitDelay, [0, 18], [1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
  const focusedOp = interpolate(frame - splitDelay, [8, 28], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });

  const badItems  = scene.items.filter(it => it.role === 'bad');
  const goodItems = scene.items.filter(it => it.role === 'good');
  const original = badItems[0]?.text  ?? scene.items[0]?.text  ?? 'Before';
  const focused  = goodItems.length > 0 ? goodItems : scene.items.slice(1);
  const focused1 = focused[0]?.text ?? 'Focused A';
  const focused2 = focused[1]?.text ?? 'Focused B';

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', marginTop: 40, height: 420, position: 'relative' }}>
        <div style={{ position: 'absolute', opacity: overloadedOp }}>
          <div style={{ background: CARD_BG, border: '1px solid rgba(239,68,68,0.45)', borderRadius: 16, padding: '28px 52px', minWidth: 440, textAlign: 'center' }}>
            <div style={{ fontSize: 28, color: WHITE, fontWeight: 700, marginBottom: 12 }}>{original}</div>
            {focused.map((it, i) => <div key={i} style={{ fontSize: 18, color: WHITE_DIM, marginTop: 6 }}>+ {it.text}</div>)}
          </div>
        </div>
        <div style={{ display: 'flex', gap: 44, opacity: focusedOp, transform: `translateY(${(1 - splitP) * 28}px)` }}>
          <GlassCard accent palette={palette} style={{ padding: '30px 44px', minWidth: 280, textAlign: 'center' }}>
            <div style={{ fontSize: 22, color: palette.accent, fontWeight: 700 }}>{focused1}</div>
          </GlassCard>
          <GlassCard accent palette={palette} style={{ padding: '30px 44px', minWidth: 280, textAlign: 'center' }}>
            <div style={{ fontSize: 22, color: palette.accent, fontWeight: 700 }}>{focused2}</div>
          </GlassCard>
        </div>
      </div>
      <div style={{ ...useSlideUp(splitDelay + 28) }}><WhitePhrase text={scene.on_screen_text} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// SCENE 6 — EVALUATION  (item.status:'gap' shows warning then resolves)
// ─────────────────────────────────────────────────────────────────────────────

export const EvaluationScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const explicitGap = scene.items.findIndex(it => it.status === 'gap');
  const gapIdx = explicitGap !== -1 ? explicitGap : Math.floor(scene.items.length / 2);

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        {scene.items.map((item, i) => {
          const delay = i * 15 + 10;
          const opacity = interpolate(Math.max(0, frame - delay), [0, 14], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          const isGap = i === gapIdx;
          const resolveAt = delay + 38;
          const isResolved = frame > resolveAt;
          const checkP = spring({ frame: Math.max(0, frame - delay - 14), fps, config: { damping: 14, stiffness: 140 } });
          return (
            <GlassCard key={i} style={{
              opacity, display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 24px',
              borderColor: isGap && !isResolved ? 'rgba(245,158,11,0.5)' : CARD_PLAIN,
            }}>
              <span style={{ fontSize: 22, color: isGap && !isResolved ? AMBER : WHITE }}>{item.text}</span>
              <div style={{ transform: `scale(${checkP})`, fontSize: 20, fontWeight: 700, color: isGap && !isResolved ? AMBER : palette.accent }}>
                {isGap && !isResolved ? '⚠ GAP' : '✓'}
              </div>
            </GlassCard>
          );
        })}
      </div>
      <div style={{ marginTop: 24, ...useSlideUp(scene.items.length * 15 + 42) }}><WhitePhrase text={scene.on_screen_text} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// SCENE 7 — OPTIONS  (circles; item.featured or last gets accent)
// ─────────────────────────────────────────────────────────────────────────────

export const OptionsScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const fi = scene.items.findIndex(it => it.featured);
  const selectIdx = fi !== -1 ? fi : scene.items.length - 1;
  const selectDelay = scene.items.length * 12 + 28;
  const selectedP = spring({ frame: Math.max(0, frame - selectDelay), fps, config: { damping: 13, stiffness: 100 } });

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 52, marginTop: 60 }}>
        {scene.items.map((item, i) => {
          const delay = i * 12 + 10;
          const p = spring({ frame: Math.max(0, frame - delay), fps, config: { damping: 14, stiffness: 100 } });
          const opacity = interpolate(Math.max(0, frame - delay), [0, 12], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          const isSelected = i === selectIdx && frame > selectDelay;
          return (
            <div key={i} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', opacity, transform: `scale(${p})` }}>
              <div style={{
                width: 190, height: 190, borderRadius: '50%',
                background: CARD_BG, border: `3px solid ${isSelected ? palette.accent : CARD_PLAIN}`,
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                boxShadow: isSelected ? palette.glow : 'none',
                transform: `scale(${1 + (isSelected ? selectedP * 0.06 : 0)})`,
              }}>
                <div style={{ fontSize: item.icon ? 48 : 52, fontWeight: 800, color: isSelected ? palette.accent : WHITE_DIM }}>{item.icon ?? (i + 1)}</div>
              </div>
              <div style={{ fontSize: 22, color: isSelected ? palette.accent : WHITE, marginTop: 20, textAlign: 'center', maxWidth: 200, fontWeight: isSelected ? 700 : 400 }}>
                {item.text}
              </div>
            </div>
          );
        })}
      </div>
      <div style={{ marginTop: 44, ...useSlideUp(selectDelay + 14) }}><WhitePhrase text={scene.on_screen_text} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// SCENE 8 — CONCLUSION  (workflow line + rotating badge)
// ─────────────────────────────────────────────────────────────────────────────

export const ConclusionScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const nodeCount = scene.items.length;
  const badgeDelay = nodeCount * 15 + 28;
  const badgeP = spring({ frame: Math.max(0, frame - badgeDelay), fps, config: { damping: 12, stiffness: 80 } });

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', alignItems: 'center', marginTop: 60 }}>
        {scene.items.map((item, i) => {
          const delay = i * 15 + 5;
          const p = spring({ frame: Math.max(0, frame - delay), fps, config: { damping: 14, stiffness: 120 } });
          const opacity = interpolate(Math.max(0, frame - delay), [0, 12], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          const isLast = i === nodeCount - 1;
          return (
            <React.Fragment key={i}>
              <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', opacity }}>
                <div style={{
                  width: 60, height: 60, borderRadius: '50%', background: palette.accent,
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  transform: `scale(${p})`, boxShadow: palette.glow,
                }}>
                  <div style={{ fontSize: item.icon ? 26 : 22, fontWeight: 800, color: NAVY }}>{item.icon ?? (i + 1)}</div>
                </div>
                <div style={{ fontSize: 20, color: WHITE, marginTop: 12, textAlign: 'center', maxWidth: 180 }}>{item.text}</div>
              </div>
              {!isLast && (
                <div style={{
                  flex: 1, height: 3, background: palette.accent, marginBottom: 32,
                  opacity: interpolate(Math.max(0, frame - delay - 4), [0, 12], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' }),
                  boxShadow: `0 0 12px ${palette.accentAlt}80`,
                }} />
              )}
            </React.Fragment>
          );
        })}
      </div>
      <div style={{
        position: 'absolute', bottom: SAFE, right: SAFE,
        opacity: badgeP, transform: `scale(${0.7 + 0.3 * badgeP}) rotate(${(1 - badgeP) * -12}deg)`,
      }}>
        <div style={{
          width: 220, height: 220, borderRadius: '50%', border: `4px solid ${palette.accent}`,
          background: CARD_BG, display: 'flex', flexDirection: 'column',
          alignItems: 'center', justifyContent: 'center', boxShadow: palette.glow,
          textAlign: 'center', padding: 24,
        }}>
          <div style={{ fontSize: 38, marginBottom: 8, color: palette.accent }}>★</div>
          <div style={{ fontSize: 18, fontWeight: 700, color: palette.accent, lineHeight: 1.2 }}>{scene.title}</div>
        </div>
      </div>
      <div style={{ position: 'absolute', bottom: SAFE + 32, left: SAFE, ...useSlideUp(badgeDelay + 6) }}>
        <WhitePhrase text={scene.on_screen_text} />
      </div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// V2 — CARD LIST  (alias of ProblemScene for explicit layout naming)
// ─────────────────────────────────────────────────────────────────────────────

export const CardListScene: React.FC<{ scene: Scene; palette: Palette }> = ProblemScene;

// ─────────────────────────────────────────────────────────────────────────────
// V2 — BRANCHING FLOW  (1 input -> animated lines -> 2-4 outputs)
// ─────────────────────────────────────────────────────────────────────────────

export const BranchingFlowScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const inputItem  = scene.items.find(it => it.role === 'input') ?? scene.items[0];
  const outputItems = scene.items.filter(it => it.role === 'output');
  const targets = outputItems.length > 0 ? outputItems : scene.items.slice(1);
  const lineReveal = interpolate(frame, [30, 75], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', alignItems: 'center', marginTop: 40, height: 480, position: 'relative' }}>
        <div style={{ ...useScaleIn(5), flexShrink: 0 }}>
          <GlassCard accent palette={palette} style={{ padding: '28px 36px', minWidth: 220, textAlign: 'center' }}>
            {inputItem?.icon && <div style={{ fontSize: 26, lineHeight: 1, marginBottom: 8 }}>{inputItem.icon}</div>}
            <div style={{ fontSize: 22, fontWeight: 700, color: palette.accent }}>{inputItem?.text ?? ''}</div>
          </GlassCard>
        </div>
        <div style={{ position: 'relative', flex: 1, height: '100%', display: 'flex', alignItems: 'center' }}>
          {targets.map((_, i) => {
            const totalTargets = targets.length;
            const spread = (totalTargets - 1) * 90;
            const yOffset = -spread / 2 + i * 90;
            const lineW = interpolate(lineReveal, [i / totalTargets, (i + 0.8) / totalTargets], [0, 100], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
            return (
              <div key={i} style={{
                position: 'absolute', left: 0, top: '50%',
                width: `${lineW}%`, height: 3, background: palette.accent, opacity: 0.7,
                transform: `translateY(${yOffset}px) rotate(${Math.atan2(yOffset, 300) * (180 / Math.PI)}deg)`,
                transformOrigin: 'left center',
                boxShadow: `0 0 8px ${palette.accent}80`,
              }} />
            );
          })}
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 24, flexShrink: 0 }}>
          {targets.map((item, i) => (
            <div key={i} style={{ ...useSlideUp(55 + i * 12, 28) }}>
              <GlassCard style={{ padding: '20px 30px', minWidth: 240 }}>
                {item.icon && <div style={{ fontSize: 22, lineHeight: 1, marginBottom: 6 }}>{item.icon}</div>}
                <div style={{ fontSize: 20, color: WHITE, fontWeight: 600 }}>{item.text}</div>
                {item.sub_label && <div style={{ fontSize: 14, color: WHITE_DIM, marginTop: 4 }}>{item.sub_label}</div>}
              </GlassCard>
            </div>
          ))}
        </div>
      </div>
      <div style={{ ...useSlideUp(80) }}><WhitePhrase text={scene.on_screen_text} size={26} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// V2 — BEFORE / AFTER  (card transitions bad->good at frame 55)
// ─────────────────────────────────────────────────────────────────────────────

export const BeforeAfterScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const transitionAt = 55;
  const badItem  = scene.items.find(it => it.role === 'bad')  ?? scene.items[0];
  const goodItem = scene.items.find(it => it.role === 'good') ?? scene.items[1];
  const badOp  = interpolate(frame - transitionAt, [0, 20], [1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
  const goodOp = interpolate(frame - transitionAt, [10, 30], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
  const goodP  = spring({ frame: Math.max(0, frame - transitionAt - 10), fps, config: { damping: 13, stiffness: 100 } });

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 400, position: 'relative' }}>
        <div style={{ position: 'absolute', opacity: badOp }}>
          <div style={{ background: CARD_BG, border: '2px solid rgba(239,68,68,0.55)', borderRadius: 20, padding: '40px 80px', minWidth: 500, textAlign: 'center' }}>
            <div style={{ fontSize: 15, fontWeight: 700, color: 'rgba(239,68,68,0.8)', letterSpacing: 2, marginBottom: 12 }}>BEFORE</div>
            <div style={{ fontSize: 28, color: WHITE, fontWeight: 600 }}>{badItem?.text ?? ''}</div>
          </div>
        </div>
        <div style={{ position: 'absolute', opacity: goodOp, transform: `scale(${0.88 + 0.12 * goodP})` }}>
          <div style={{ background: CARD_BG, border: `2px solid ${palette.accent}`, borderRadius: 20, padding: '40px 80px', minWidth: 500, textAlign: 'center', boxShadow: palette.glow }}>
            <div style={{ fontSize: 15, fontWeight: 700, color: palette.accent, letterSpacing: 2, marginBottom: 12 }}>AFTER</div>
            <div style={{ fontSize: 28, color: WHITE, fontWeight: 600 }}>{goodItem?.text ?? ''}</div>
          </div>
        </div>
      </div>
      <div style={{ ...useSlideUp(transitionAt + 30) }}><WhitePhrase text={scene.on_screen_text} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// V2 — QUAD GRID  (2x2; checkmarks light up one at a time)
// ─────────────────────────────────────────────────────────────────────────────

export const QuadGridScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const items = scene.items.slice(0, 4);

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 28, marginTop: 28 }}>
        {items.map((item, i) => {
          const cardDelay  = i * 14 + 10;
          const checkDelay = cardDelay + 22;
          const cardOp = interpolate(Math.max(0, frame - cardDelay), [0, 14], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          const checkP = spring({ frame: Math.max(0, frame - checkDelay), fps, config: { damping: 14, stiffness: 150 } });
          const checked = frame > checkDelay;
          return (
            <GlassCard key={i} accent={checked} palette={palette} style={{ opacity: cardOp, padding: '28px 32px', display: 'flex', alignItems: 'flex-start', gap: 18 }}>
              <div style={{
                width: 40, height: 40, borderRadius: '50%', flexShrink: 0,
                border: `2px solid ${checked ? palette.accent : CARD_PLAIN}`,
                background: checked ? `${palette.accent}22` : 'transparent',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                transform: `scale(${checkP})`,
              }}>
                {checked && <div style={{ fontSize: 20, fontWeight: 800, color: palette.accent }}>✓</div>}
              </div>
              <div>
                <div style={{ fontSize: 22, fontWeight: 600, color: checked ? palette.accent : WHITE }}>
                  {item.icon && <span style={{ marginRight: 8 }}>{item.icon}</span>}
                  {item.text}
                </div>
                {item.sub_label && <div style={{ fontSize: 15, color: WHITE_DIM, marginTop: 4 }}>{item.sub_label}</div>}
              </div>
            </GlassCard>
          );
        })}
      </div>
      <div style={{ marginTop: 28, ...useSlideUp(items.length * 14 + 42) }}><WhitePhrase text={scene.on_screen_text} size={26} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// V2 — THREE STEP FLOW  (3 boxes with animated arrows between them)
// ─────────────────────────────────────────────────────────────────────────────

export const ThreeStepFlowScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const items = scene.items.slice(0, 3);
  const arrowReveal = interpolate(frame, [25, 80], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', alignItems: 'center', marginTop: 80, gap: 0 }}>
        {items.map((item, i) => {
          const delay = i * 16 + 8;
          const p = spring({ frame: Math.max(0, frame - delay), fps, config: { damping: 14, stiffness: 110 } });
          const opacity = interpolate(Math.max(0, frame - delay), [0, 14], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          const isLast = i === items.length - 1;
          const segP = interpolate(arrowReveal, [i / (items.length - 1 || 1), (i + 0.7) / (items.length - 1 || 1)], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          return (
            <React.Fragment key={i}>
              <div style={{ opacity, transform: `scale(${p})`, flex: 1 }}>
                <div style={{ background: CARD_BG, border: `2px solid ${palette.cardBorder}`, borderRadius: 14, padding: '28px 20px', textAlign: 'center', boxShadow: palette.glow }}>
                  <div style={{ width: 36, height: 36, borderRadius: '50%', background: palette.accent, color: NAVY, fontSize: 16, fontWeight: 800, display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 14px' }}>{i + 1}</div>
                  <div style={{ fontSize: 22, color: WHITE, fontWeight: 600, lineHeight: 1.3 }}>{item.text}</div>
                  {item.sub_label && <div style={{ fontSize: 14, color: WHITE_DIM, marginTop: 6 }}>{item.sub_label}</div>}
                </div>
              </div>
              {!isLast && (
                <div style={{ width: 60, flexShrink: 0, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <div style={{ position: 'relative', width: '100%', height: 3, background: CARD_PLAIN }}>
                    <div style={{ position: 'absolute', left: 0, top: 0, height: '100%', width: `${segP * 100}%`, background: palette.accent }} />
                    <div style={{ position: 'absolute', right: -8, top: -7, width: 0, height: 0, borderTop: '8px solid transparent', borderBottom: '8px solid transparent', borderLeft: `14px solid ${palette.accent}`, opacity: segP }} />
                  </div>
                </div>
              )}
            </React.Fragment>
          );
        })}
      </div>
      <div style={{ marginTop: 60, ...useSlideUp(90) }}><WhitePhrase text={scene.on_screen_text} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// V2 — CYCLE LOOP  (4 nodes in diamond; animated arc arrows)
// ─────────────────────────────────────────────────────────────────────────────

export const CycleLoopScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const items = scene.items.slice(0, 4);
  const positions = [
    { x: '50%', y: '0%',   tx: '-50%', ty: '0%' },
    { x: '100%', y: '50%', tx: '-100%', ty: '-50%' },
    { x: '50%', y: '100%', tx: '-50%', ty: '-100%' },
    { x: '0%', y: '50%',   tx: '0%', ty: '-50%' },
  ];
  const arcAngles = [45, 135, 225, 315];

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ position: 'relative', width: '100%', height: 400, marginTop: 20 }}>
        {items.map((item, i) => {
          const delay = i * 18 + 10;
          const p = spring({ frame: Math.max(0, frame - delay), fps, config: { damping: 13, stiffness: 100 } });
          const opacity = interpolate(Math.max(0, frame - delay), [0, 14], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          const pos = positions[i];
          return (
            <div key={i} style={{ position: 'absolute', left: pos.x, top: pos.y, transform: `translate(${pos.tx}, ${pos.ty}) scale(${p})`, opacity }}>
              <div style={{
                width: 150, height: 150, borderRadius: '50%',
                background: CARD_BG, border: `3px solid ${palette.accent}`,
                boxShadow: palette.glow, display: 'flex', flexDirection: 'column',
                alignItems: 'center', justifyContent: 'center', textAlign: 'center', padding: 16,
              }}>
                <div style={{ fontSize: item.icon ? 32 : 28, fontWeight: 800, color: palette.accent, marginBottom: 6 }}>{item.icon ?? (i + 1)}</div>
                <div style={{ fontSize: 17, color: WHITE, lineHeight: 1.3 }}>{item.text}</div>
              </div>
            </div>
          );
        })}
        {arcAngles.map((angle, i) => {
          const arcOp = interpolate(Math.max(0, frame - (i * 18 + 38)), [0, 14], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          return (
            <div key={`arc-${i}`} style={{
              position: 'absolute', left: '50%', top: '50%',
              width: 24, height: 24,
              transform: `translate(-50%,-50%) rotate(${angle}deg) translate(0, -155px)`,
              opacity: arcOp, fontSize: 22, color: palette.accentAlt,
            }}>➜</div>
          );
        })}
      </div>
      <div style={{ marginTop: 8, ...useSlideUp(85) }}><WhitePhrase text={scene.on_screen_text} size={26} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// V2 — SPLIT BLUEPRINT  (two columns: role:'input' left, role:'output' right)
// ─────────────────────────────────────────────────────────────────────────────

export const SplitBlueprintScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const leftItems  = scene.items.filter(it => it.role === 'input');
  const rightItems = scene.items.filter(it => it.role === 'output');
  const left  = leftItems.length  > 0 ? leftItems  : scene.items.slice(0, Math.ceil(scene.items.length / 2));
  const right = rightItems.length > 0 ? rightItems : scene.items.slice(Math.ceil(scene.items.length / 2));
  const dividerOp = interpolate(frame, [20, 40], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', gap: 0, marginTop: 24, height: 460 }}>
        <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 20, paddingRight: 40 }}>
          {left.map((item, i) => (
            <div key={i} style={{ ...useSlideUp(i * 12 + 8, 32) }}>
              <GlassCard style={{ padding: '18px 26px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                  {item.icon && <span style={{ fontSize: 20, lineHeight: 1, flexShrink: 0 }}>{item.icon}</span>}
                  <div>
                    <div style={{ fontSize: 21, color: WHITE, fontWeight: 600 }}>{item.text}</div>
                    {item.sub_label && <div style={{ fontSize: 14, color: WHITE_DIM, marginTop: 4 }}>{item.sub_label}</div>}
                  </div>
                </div>
              </GlassCard>
            </div>
          ))}
        </div>
        <div style={{ width: 3, background: palette.accent, opacity: dividerOp, borderRadius: 2, boxShadow: `0 0 18px ${palette.accent}80`, alignSelf: 'stretch' }} />
        <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 20, paddingLeft: 40 }}>
          {right.map((item, i) => (
            <div key={i} style={{ ...useSlideUp(i * 12 + 32, 32) }}>
              <GlassCard accent palette={palette} style={{ padding: '18px 26px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                  {item.icon && <span style={{ fontSize: 20, lineHeight: 1, flexShrink: 0 }}>{item.icon}</span>}
                  <div>
                    <div style={{ fontSize: 21, color: palette.accent, fontWeight: 600 }}>{item.text}</div>
                    {item.sub_label && <div style={{ fontSize: 14, color: WHITE_DIM, marginTop: 4 }}>{item.sub_label}</div>}
                  </div>
                </div>
              </GlassCard>
            </div>
          ))}
        </div>
      </div>
      <div style={{ marginTop: 8, ...useSlideUp(70) }}><WhitePhrase text={scene.on_screen_text} size={26} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// V2 — FUEL ENGINE  (inputs -> engine -> output)
// ─────────────────────────────────────────────────────────────────────────────

export const FuelEngineScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const inputItems  = scene.items.filter(it => it.role === 'input');
  const outputItems = scene.items.filter(it => it.role === 'output');
  const inputs  = inputItems.length  > 0 ? inputItems  : scene.items.slice(0, -1);
  const outputs = outputItems.length > 0 ? outputItems : [scene.items[scene.items.length - 1]];
  const enginePulse = interpolate(Math.sin((frame / 8) * Math.PI), [-1, 1], [0.85, 1.0]);
  const engineOp = interpolate(frame, [30, 50], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
  const flowProgress = interpolate(frame, [35, 90], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
  const outDelay = 65;
  const outP  = spring({ frame: Math.max(0, frame - outDelay), fps, config: { damping: 14, stiffness: 100 } });
  const outOp = interpolate(Math.max(0, frame - outDelay), [0, 14], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', alignItems: 'center', marginTop: 48, gap: 28, height: 380 }}>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 20, flexShrink: 0, width: 240 }}>
          {inputs.map((item, i) => (
            <div key={i} style={{ ...useSlideUp(i * 10 + 5, 30) }}>
              <GlassCard style={{ padding: '16px 22px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                  {item.icon && <span style={{ fontSize: 18, lineHeight: 1, flexShrink: 0 }}>{item.icon}</span>}
                  <div style={{ fontSize: 18, color: WHITE_DIM, fontWeight: 500 }}>{item.text}</div>
                </div>
              </GlassCard>
            </div>
          ))}
        </div>
        <div style={{ flex: 1, height: 3, background: CARD_PLAIN, position: 'relative' }}>
          <div style={{ position: 'absolute', left: 0, top: 0, height: '100%', width: `${flowProgress * 50}%`, background: palette.accent }} />
        </div>
        <div style={{ flexShrink: 0, opacity: engineOp, transform: `scale(${enginePulse})` }}>
          <div style={{ width: 150, height: 150, borderRadius: 20, background: CARD_BG, border: `3px solid ${palette.accent}`, boxShadow: palette.glow, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center' }}>
            <div style={{ fontSize: 36, color: palette.accent }}>⚙</div>
            <div style={{ fontSize: 14, color: WHITE_DIM, marginTop: 6, fontWeight: 600 }}>AI Engine</div>
          </div>
        </div>
        <div style={{ flex: 1, height: 3, background: CARD_PLAIN, position: 'relative' }}>
          <div style={{ position: 'absolute', left: 0, top: 0, height: '100%', width: `${Math.max(0, flowProgress * 100 - 50) * 2}%`, background: palette.accent }} />
        </div>
        <div style={{ flexShrink: 0, opacity: outOp, transform: `scale(${outP})` }}>
          <GlassCard accent palette={palette} style={{ padding: '28px 32px', minWidth: 200, textAlign: 'center' }}>
            <div style={{ fontSize: 20, fontWeight: 700, color: palette.accent }}>{outputs[0]?.text ?? 'Output'}</div>
            {outputs[0]?.sub_label && <div style={{ fontSize: 14, color: WHITE_DIM, marginTop: 6 }}>{outputs[0].sub_label}</div>}
          </GlassCard>
        </div>
      </div>
      <div style={{ marginTop: 28, ...useSlideUp(80) }}><WhitePhrase text={scene.on_screen_text} size={26} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// V2 — CHECKLIST REVEAL  (items check off; status:'gap' shows warning briefly)
// ─────────────────────────────────────────────────────────────────────────────

export const ChecklistRevealScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ display: 'flex', flexDirection: 'column', gap: 22, marginTop: 20 }}>
        {scene.items.map((item, i) => {
          const itemDelay  = i * 18 + 8;
          const checkDelay = itemDelay + 16;
          const opacity = interpolate(Math.max(0, frame - itemDelay), [0, 14], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          const checkP = spring({ frame: Math.max(0, frame - checkDelay), fps, config: { damping: 14, stiffness: 150 } });
          const isGap  = item.status === 'gap' || item.status === 'warn';
          const showGap = isGap && frame <= checkDelay + 28;
          return (
            <div key={i} style={{ opacity, display: 'flex', alignItems: 'center', gap: 20 }}>
              <div style={{
                width: 44, height: 44, borderRadius: '50%', flexShrink: 0,
                border: `2px solid ${showGap ? AMBER : palette.accent}`,
                background: showGap ? `${AMBER}22` : `${palette.accent}22`,
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                transform: `scale(${checkP})`,
              }}>
                <div style={{ fontSize: 20, fontWeight: 800, color: showGap ? AMBER : palette.accent }}>
                  {showGap ? '⚠' : '✓'}
                </div>
              </div>
              <div style={{ flex: 1 }}>
                <div style={{ fontSize: 23, color: showGap ? AMBER : WHITE, fontWeight: showGap ? 700 : 400, lineHeight: 1.3 }}>
                  {item.icon && <span style={{ marginRight: 10 }}>{item.icon}</span>}
                  {item.text}
                </div>
                {item.sub_label && <div style={{ fontSize: 15, color: WHITE_DIM, marginTop: 2 }}>{item.sub_label}</div>}
              </div>
            </div>
          );
        })}
      </div>
      <div style={{ marginTop: 24, ...useSlideUp(scene.items.length * 18 + 40) }}><WhitePhrase text={scene.on_screen_text} size={26} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};

// ─────────────────────────────────────────────────────────────────────────────
// V2 — DEPLOYMENT CIRCLES  (4 concentric rings expanding outward)
// ─────────────────────────────────────────────────────────────────────────────

export const DeploymentCirclesScene: React.FC<{ scene: Scene; palette: Palette }> = ({ scene, palette }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const items = scene.items.slice(0, 4);
  const sizes = [170, 290, 410, 530];

  return (
    <SceneWrap>
      <AccentTitle text={scene.title} palette={palette} />
      <div style={{ position: 'relative', width: '100%', height: 460, display: 'flex', alignItems: 'center', justifyContent: 'center', marginTop: 16 }}>
        {items.map((item, i) => {
          const delay = i * 20 + 8;
          const p = spring({ frame: Math.max(0, frame - delay), fps, config: { damping: 12, stiffness: 80 } });
          const opacity = interpolate(Math.max(0, frame - delay), [0, 16], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          const size = sizes[i] ?? 170 + i * 120;
          return (
            <div key={i} style={{
              position: 'absolute', width: size, height: size, borderRadius: '50%',
              border: `2px solid ${palette.accent}`,
              opacity: opacity * (0.7 - i * 0.12),
              transform: `scale(${p})`,
              boxShadow: i === 0 ? palette.glow : 'none',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
            }}>
              {frame > delay + 20 && (
                <div style={{
                  position: 'absolute', top: -18, fontSize: 16, fontWeight: 700,
                  color: i === 0 ? palette.accent : WHITE_DIM,
                  background: NAVY, padding: '2px 10px', borderRadius: 8, whiteSpace: 'nowrap',
                }}>{item.text}</div>
              )}
            </div>
          );
        })}
        <div style={{ width: 20, height: 20, borderRadius: '50%', background: palette.accent, boxShadow: palette.glow, ...useScaleIn(5) }} />
      </div>
      <div style={{ marginTop: 4, ...useSlideUp(90) }}><WhitePhrase text={scene.on_screen_text} size={26} /></div>
      <NarrationBar text={scene.narration} />
    </SceneWrap>
  );
};