/**
 * Named SVG icon set for lesson-video scenes.
 *
 * These give crisp, palette-coloured icons for the recurring concepts in the course briefs,
 * replacing inconsistent emoji where a name match exists. Every icon is a 24×24 stroke glyph
 * that inherits the surrounding text colour via `currentColor`, so it tints to the active accent.
 *
 * Usage: `renderIcon(item.icon, size)` in Scenes.tsx — returns the SVG when `item.icon` is a known
 * name, otherwise returns the raw string (emoji fallback) so existing emoji keep working.
 */
import React from 'react';

type IconProps = { size?: number };

const svgProps = (size: number) => ({
  width: size,
  height: size,
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: 2,
  strokeLinecap: 'round' as const,
  strokeLinejoin: 'round' as const,
});

const Shield: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><path d="M12 2l8 3v6c0 5-3.5 8.5-8 11-4.5-2.5-8-6-8-11V5l8-3z" /></svg>
);
const Lock: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><rect x="4" y="11" width="16" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" /></svg>
);
const Globe: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18M12 3c-2.5 2.5-2.5 15 0 18" /></svg>
);
const Building: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><rect x="5" y="3" width="14" height="18" rx="1" /><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2M10 21v-3h4v3" /></svg>
);
const Mic: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><rect x="9" y="3" width="6" height="11" rx="3" /><path d="M5 11a7 7 0 0 0 14 0M12 18v3" /></svg>
);
const Calendar: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M3 9h18M8 3v4M16 3v4" /></svg>
);
const Fuel: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16M4 21h12M9 8h4" /><path d="M15 9l3 3v6a2 2 0 0 1-4 0" /></svg>
);
const Target: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="5" /><circle cx="12" cy="12" r="1.4" /></svg>
);
const Gauge: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><path d="M4 18a8 8 0 1 1 16 0" /><path d="M12 14l4-4" /><circle cx="12" cy="14" r="1.4" /></svg>
);
const Document: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><path d="M6 2h8l4 4v16H6z" /><path d="M14 2v4h4M9 12h6M9 16h6" /></svg>
);
const Network: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><circle cx="12" cy="5" r="2.5" /><circle cx="5" cy="19" r="2.5" /><circle cx="19" cy="19" r="2.5" /><path d="M12 7.5L6.5 16.7M12 7.5l5.5 9.2M7.5 19h9" /></svg>
);
const Checkmark: React.FC<IconProps> = ({ size = 24 }) => (
  <svg {...svgProps(size)}><path d="M4 12l5 5L20 6" /></svg>
);

/** Lookup of known icon names → component. Keys are the vocabulary the AI may emit in `item.icon`. */
export const ICON_SET: Record<string, React.FC<IconProps>> = {
  shield: Shield,
  lock: Lock,
  globe: Globe,
  building: Building,
  mic: Mic,
  calendar: Calendar,
  fuel: Fuel,
  target: Target,
  gauge: Gauge,
  document: Document,
  network: Network,
  checkmark: Checkmark,
};
