// All supported layout types. Add new entries here when new scene components are built.
export type LayoutType =
  // ── original 8 ──────────────────────────────────────────────────────────
  | 'intro'
  | 'problem'
  | 'framework'
  | 'process'
  | 'contrast'
  | 'evaluation'
  | 'options'
  | 'conclusion'
  // ── v2 additions ─────────────────────────────────────────────────────────
  | 'card_list'           // stacked cards; featured item rises gold
  | 'branching_flow'      // 1 input → animated lines → 2–4 outputs
  | 'before_after'        // single card: bad-state → good-state transition
  | 'quad_grid'           // 2×2 grid; checkmarks light up sequentially
  | 'three_step_flow'     // 3 rectangular boxes, animated arrows
  | 'cycle_loop'          // 4 nodes in diamond cycle with animated arcs
  | 'split_blueprint'     // two-column: left = inputs, right = outputs
  | 'fuel_engine'         // inputs → engine box → output
  | 'checklist_reveal'    // ordered list; checkmarks reveal one by one
  | 'deployment_circles' // 4 concentric circles expanding outward
  // ── v3 additions ─────────────────────────────────────────────────────────
  | 'editorial';         // rich text blocks: badge + heading + sub + paragraph + callout

/** Properties for a single item within a scene's items array. */
export interface SceneItem {
  text: string;
  sub_label?: string;
  /** Mark one item as the featured/highlighted/recommended choice. */
  featured?: boolean;
  /** Semantic role used by branching_flow, before_after, split_blueprint, fuel_engine. */
  role?: 'input' | 'output' | 'bad' | 'good';
  /** Used by evaluation / checklist_reveal to colour code items. */
  status?: 'pass' | 'gap' | 'warn';
  icon?: string;
  /** Short ALL-CAPS keyword label rendered as an accent pill tag (e.g. "KEY CONCEPT", "TIP"). */
  badge?: string;
  /** Body paragraph text rendered below sub_label; required in editorial layout. */
  description?: string;
}

export interface Scene {
  layout: LayoutType;
  title: string;
  on_screen_text: string;
  narration: string;
  items: SceneItem[];
  duration_frames: number;
  /** Highlighted insight block — the scene's single most important takeaway. */
  callout?: string;
}

export interface InputProps {
  title: string;
  accent?: 'gold' | 'cyan_orange' | 'emerald' | 'violet' | 'rose' | 'teal';
  scenes: Scene[];
}
