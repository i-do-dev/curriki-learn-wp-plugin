// ─── Base colours ────────────────────────────────────────────────────────────
export const NAVY        = '#0B1A3B';
export const GOLD        = '#F0B429';
export const WHITE       = '#FFFFFF';
export const WHITE_DIM   = 'rgba(255, 255, 255, 0.72)';
export const AMBER       = '#F59E0B';
export const CARD_BG     = 'rgba(255, 255, 255, 0.07)';
export const CARD_BORDER = 'rgba(240, 180, 41, 0.28)';
export const CARD_PLAIN  = 'rgba(255, 255, 255, 0.13)';
export const GOLD_GLOW   = '0 0 32px rgba(240, 180, 41, 0.40)';
export const SAFE        = 90;
export const FONT        = "'Inter', 'Helvetica Neue', Arial, sans-serif";

// ─── v2 accent colours ───────────────────────────────────────────────────────
export const CYAN        = '#22D3EE';
export const ORANGE      = '#F97316';
export const CYAN_GLOW   = '0 0 32px rgba(34, 211, 238, 0.40)';
export const ORANGE_GLOW = '0 0 32px rgba(249, 115, 22, 0.40)';
export const CYAN_BORDER  = 'rgba(34, 211, 238, 0.28)';
export const ORANGE_BORDER= 'rgba(249, 115, 22, 0.28)';

// ─── Palette objects passed as props to scene components ─────────────────────
export interface Palette {
  accent: string;
  accentAlt: string;   // secondary accent (AMBER for gold palette, ORANGE for cyan)
  glow: string;
  cardBorder: string;  // accent-tinted card border
}

export const PALETTE_GOLD: Palette = {
  accent:     GOLD,
  accentAlt:  AMBER,
  glow:       GOLD_GLOW,
  cardBorder: CARD_BORDER,
};

export const PALETTE_CYAN: Palette = {
  accent:     CYAN,
  accentAlt:  ORANGE,
  glow:       CYAN_GLOW,
  cardBorder: CYAN_BORDER,
};
