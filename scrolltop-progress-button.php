<?php
/**
 * Plugin Name: ScrollTop Progress Button
 * Description: Smooth back to top button with a circular scroll progress indicator, color controls, and Font Awesome icon selection.
 * Version: 1.2.0
 * Author: Sumon Rahman Kabbo
 * Author URI: https://sumonrahmankabbo.com/
 * License: GPLv2 or later
 * Text Domain: stpb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class STPB_Plugin {
	const OPTION_KEY = 'stpb_settings';
	const VERSION    = '1.2.0';

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_footer', [ $this, 'render_button' ] );
	}

	public static function defaults(): array {
		return [
			'enabled'        => 1,
			'position'       => 'right', // right | left
			'show_progress'  => 1,
			'offset_bottom'  => 28,
			'offset_side'    => 28,
			'show_after_px'  => 250,
			'size'           => 52,

			'bg_color'       => '#141414',
			'icon_color'     => '#ffffff',
			'ring_color'     => '#141414',
			'track_color'    => 'rgba(0,0,0,.12)',

			'icon_class'     => 'fa-solid fa-angle-up',
		];
	}

	public function get_settings(): array {
		$saved = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}
		return wp_parse_args( $saved, self::defaults() );
	}

	public function register_settings(): void {
		register_setting(
			'stpb_group',
			self::OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => self::defaults(),
			]
		);

		add_settings_section(
			'stpb_main',
			'ScrollTop Progress Button Settings',
			function () {
				echo '<p>Reliable and lightweight. Configure appearance and behavior here.</p>';
			},
			'stpb'
		);

		$fields = [
			[ 'enabled',       'Enable',                [ $this, 'field_enabled' ] ],
			[ 'position',      'Position',              [ $this, 'field_position' ] ],
			[ 'show_progress', 'Scroll Progress Ring',  [ $this, 'field_show_progress' ] ],
			[ 'show_after_px', 'Show After (px)',       [ $this, 'field_show_after' ] ],
			[ 'size',          'Button Size (px)',      [ $this, 'field_size' ] ],
			[ 'bg_color',      'Background Color',      [ $this, 'field_bg_color' ] ],
			[ 'icon_color',    'Icon Color',            [ $this, 'field_icon_color' ] ],
			[ 'ring_color',    'Progress Ring Color',   [ $this, 'field_ring_color' ] ],
			[ 'track_color',   'Ring Track Color',      [ $this, 'field_track_color' ] ],
			[ 'icon_class',    'Font Awesome Icon',     [ $this, 'field_icon_class' ] ],
		];

		foreach ( $fields as $f ) {
			add_settings_field(
				$f[0],
				$f[1],
				$f[2],
				'stpb',
				'stpb_main'
			);
		}
	}

	public function sanitize_settings( $input ): array {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : [];

		$out = [];

		$out['enabled']       = ! empty( $input['enabled'] ) ? 1 : 0;
		$out['show_progress'] = ! empty( $input['show_progress'] ) ? 1 : 0;

		$pos = isset( $input['position'] ) ? sanitize_text_field( $input['position'] ) : $defaults['position'];
		$out['position'] = in_array( $pos, [ 'right', 'left' ], true ) ? $pos : $defaults['position'];

		$out['offset_bottom'] = isset( $input['offset_bottom'] ) ? absint( $input['offset_bottom'] ) : $defaults['offset_bottom'];
		$out['offset_side']   = isset( $input['offset_side'] ) ? absint( $input['offset_side'] ) : $defaults['offset_side'];
		$out['show_after_px'] = isset( $input['show_after_px'] ) ? absint( $input['show_after_px'] ) : $defaults['show_after_px'];

		$size = isset( $input['size'] ) ? absint( $input['size'] ) : $defaults['size'];
		$out['size'] = ( $size >= 36 && $size <= 96 ) ? $size : $defaults['size'];

		$out['bg_color']    = $this->sanitize_color_or_rgba( $input['bg_color'] ?? $defaults['bg_color'], $defaults['bg_color'] );
		$out['icon_color']  = $this->sanitize_color_or_rgba( $input['icon_color'] ?? $defaults['icon_color'], $defaults['icon_color'] );
		$out['ring_color']  = $this->sanitize_color_or_rgba( $input['ring_color'] ?? $defaults['ring_color'], $defaults['ring_color'] );
		$out['track_color'] = $this->sanitize_color_or_rgba( $input['track_color'] ?? $defaults['track_color'], $defaults['track_color'] );

		$icon_class = isset( $input['icon_class'] ) ? sanitize_text_field( $input['icon_class'] ) : $defaults['icon_class'];
		$icon_class = preg_replace( '/[^a-zA-Z0-9\-\s_]/', '', $icon_class );
		$icon_class = trim( preg_replace( '/\s+/', ' ', $icon_class ) );

		if ( $icon_class === '' || strlen( $icon_class ) > 80 ) {
			$icon_class = $defaults['icon_class'];
		}
		$out['icon_class'] = $icon_class;

		return wp_parse_args( $out, $defaults );
	}

	private function sanitize_color_or_rgba( $value, $fallback ): string {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( $value === '' ) {
			return $fallback;
		}

		$hex = sanitize_hex_color( $value );
		if ( $hex ) {
			return $hex;
		}

		if ( preg_match( '/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(0|0?\.\d+|1(\.0)?)\s*\)$/i', $value, $m ) ) {
			$r = min( 255, max( 0, intval( $m[1] ) ) );
			$g = min( 255, max( 0, intval( $m[2] ) ) );
			$b = min( 255, max( 0, intval( $m[3] ) ) );
			$a = floatval( $m[4] );
			if ( $a < 0 ) $a = 0;
			if ( $a > 1 ) $a = 1;
			return sprintf( 'rgba(%d,%d,%d,%s)', $r, $g, $b, rtrim( rtrim( sprintf( '%.3f', $a ), '0' ), '.' ) );
		}

		return $fallback;
	}

	public function add_settings_page(): void {
		add_options_page(
			'ScrollTop Progress Button',
			'ScrollTop Progress',
			'manage_options',
			'stpb',
			[ $this, 'render_settings_page' ]
		);
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$s = $this->get_settings();
		?>
		<div class="wrap stpb-admin">
			<h1>ScrollTop Progress Button</h1>

			<div class="stpb-admin-grid">
				<div class="stpb-admin-card">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'stpb_group' );
						do_settings_sections( 'stpb' );
						submit_button();
						?>
					</form>
				</div>

				<div class="stpb-admin-card">
					<h2>Live Preview</h2>
					<p class="description">This preview reflects your current saved settings.</p>

					<div class="stpb-preview-area">
						<div
							class="stpb-preview"
							data-bg="<?php echo esc_attr( $s['bg_color'] ); ?>"
							data-icon="<?php echo esc_attr( $s['icon_color'] ); ?>"
							data-ring="<?php echo esc_attr( $s['ring_color'] ); ?>"
							data-track="<?php echo esc_attr( $s['track_color'] ); ?>"
							data-iconclass="<?php echo esc_attr( $s['icon_class'] ); ?>"
						>
							<button type="button" class="stpb-btn" style="background: <?php echo esc_attr( $s['bg_color'] ); ?>; color: <?php echo esc_attr( $s['icon_color'] ); ?>;">
								<i class="<?php echo esc_attr( $s['icon_class'] ); ?>" aria-hidden="true" style="color: <?php echo esc_attr( $s['icon_color'] ); ?>;"></i>
								<span class="screen-reader-text">Back to top</span>
								<svg class="stpb-ring" viewBox="0 0 120 120" aria-hidden="true">
									<circle class="track" cx="60" cy="60" r="52" style="stroke: <?php echo esc_attr( $s['track_color'] ); ?>;"></circle>
									<circle class="progress" cx="60" cy="60" r="52" style="stroke: <?php echo esc_attr( $s['ring_color'] ); ?>;"></circle>
								</svg>
							</button>
						</div>
					</div>

					<p class="description">Tip: Select an icon, or paste a Font Awesome class like <code>fa-solid fa-arrow-up</code>.</p>
				</div>
			</div>
		</div>
		<?php
	}

	public function admin_assets( $hook ): void {
		if ( $hook !== 'settings_page_stpb' ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style(
			'stpb-admin',
			plugins_url( 'assets/admin.css', __FILE__ ),
			[],
			self::VERSION
		);

		wp_enqueue_style(
			'stpb-fa',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
			[],
			'6.5.2'
		);

		wp_enqueue_script(
			'stpb-admin',
			plugins_url( 'assets/admin.js', __FILE__ ),
			[ 'jquery', 'wp-color-picker' ],
			self::VERSION,
			true
		);
	}

	public function field_enabled(): void {
		$s = $this->get_settings();
		printf(
			'<label><input type="checkbox" name="%1$s[enabled]" value="1" %2$s> Enabled</label>',
			esc_attr( self::OPTION_KEY ),
			checked( 1, (int) $s['enabled'], false )
		);
	}

	public function field_position(): void {
		$s = $this->get_settings();
		$name = esc_attr( self::OPTION_KEY ) . '[position]';
		?>
		<select name="<?php echo esc_attr( $name ); ?>">
			<option value="right" <?php selected( $s['position'], 'right' ); ?>>Bottom Right</option>
			<option value="left" <?php selected( $s['position'], 'left' ); ?>>Bottom Left</option>
		</select>
		<?php
	}

	public function field_show_progress(): void {
		$s = $this->get_settings();
		printf(
			'<label><input type="checkbox" name="%1$s[show_progress]" value="1" %2$s> Show progress ring</label>',
			esc_attr( self::OPTION_KEY ),
			checked( 1, (int) $s['show_progress'], false )
		);
	}

	public function field_show_after(): void {
		$s = $this->get_settings();
		printf(
			'<input type="number" min="0" step="10" name="%1$s[show_after_px]" value="%2$d" class="small-text"> <span class="description">Button appears after scrolling this many pixels.</span>',
			esc_attr( self::OPTION_KEY ),
			(int) $s['show_after_px']
		);
	}

	public function field_size(): void {
		$s = $this->get_settings();
		printf(
			'<input type="number" min="36" max="96" step="1" name="%1$s[size]" value="%2$d" class="small-text"> <span class="description">Recommended 48 to 60</span>',
			esc_attr( self::OPTION_KEY ),
			(int) $s['size']
		);
	}

	private function color_input( string $key, string $hint = '' ): void {
		$s = $this->get_settings();
		$val = (string) ( $s[ $key ] ?? '' );
		$name = esc_attr( self::OPTION_KEY ) . '[' . esc_attr( $key ) . ']';

		printf(
			'<input type="text" class="stpb-color-field" name="%1$s" value="%2$s" data-default-color="%3$s" /> %4$s',
			esc_attr( $name ),
			esc_attr( $val ),
			esc_attr( self::defaults()[ $key ] ),
			$hint ? '<span class="description">' . esc_html( $hint ) . '</span>' : ''
		);
	}

	public function field_bg_color(): void { $this->color_input( 'bg_color' ); }
	public function field_icon_color(): void { $this->color_input( 'icon_color' ); }
	public function field_ring_color(): void { $this->color_input( 'ring_color' ); }
	public function field_track_color(): void { $this->color_input( 'track_color', 'Hex or rgba(0,0,0,.12)' ); }

	public function field_icon_class(): void {
		$s = $this->get_settings();
		$name = esc_attr( self::OPTION_KEY ) . '[icon_class]';
		$val  = (string) $s['icon_class'];

		$icons = [
			'fa-solid fa-angle-up'   => 'Angle Up',
			'fa-solid fa-arrow-up'   => 'Arrow Up',
			'fa-solid fa-chevron-up' => 'Chevron Up',
			'fa-solid fa-circle-up'  => 'Circle Up',
			'fa-solid fa-caret-up'   => 'Caret Up',
		];
		?>
		<div class="stpb-icon-picker">
			<select class="stpb-icon-select">
				<?php foreach ( $icons as $class => $label ) : ?>
					<option value="<?php echo esc_attr( $class ); ?>" <?php selected( $val, $class ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
				<option value="custom" <?php selected( isset( $icons[ $val ] ) ? '' : 'custom', 'custom' ); ?>>Custom</option>
			</select>

			<input
				type="text"
				class="regular-text stpb-icon-class"
				name="<?php echo esc_attr( $name ); ?>"
				value="<?php echo esc_attr( $val ); ?>"
				placeholder="fa-solid fa-arrow-up"
			/>
			<div class="stpb-icon-preview" aria-hidden="true">
				<i class="<?php echo esc_attr( $val ); ?>"></i>
			</div>
		</div>
		<?php
	}

	public function enqueue_assets(): void {
		$s = $this->get_settings();
		if ( empty( $s['enabled'] ) || is_admin() ) {
			return;
		}

		if ( strpos( $s['icon_class'], 'fa-' ) !== false ) {
			wp_enqueue_style(
				'stpb-fa',
				'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
				[],
				'6.5.2'
			);
		}

		wp_enqueue_style(
			'stpb-front',
			plugins_url( 'assets/front.css', __FILE__ ),
			[],
			self::VERSION
		);

		wp_enqueue_script(
			'stpb-front',
			plugins_url( 'assets/front.js', __FILE__ ),
			[],
			self::VERSION,
			true
		);

		wp_localize_script(
			'stpb-front',
			'STPB',
			[
				'showAfter'    => (int) $s['show_after_px'],
				'smoothMs'     => 450,
				'showProgress' => (int) $s['show_progress'],
			]
		);

		$side_prop = ( $s['position'] === 'left' ) ? 'left' : 'right';

		$custom = sprintf(
			':root{--stpb-bg:%1$s;--stpb-icon:%2$s;--stpb-ring:%3$s;--stpb-track:%4$s;}
			.stpb-wrap{bottom:%5$dpx;%6$s:%7$dpx;}
			.stpb-btn{width:%8$dpx;height:%8$dpx;}',
			esc_html( $s['bg_color'] ),
			esc_html( $s['icon_color'] ),
			esc_html( $s['ring_color'] ),
			esc_html( $s['track_color'] ),
			(int) $s['offset_bottom'],
			$side_prop,
			(int) $s['offset_side'],
			(int) $s['size']
		);

		wp_add_inline_style( 'stpb-front', $custom );
	}

	public function render_button(): void {
		$s = $this->get_settings();
		if ( empty( $s['enabled'] ) || is_admin() ) {
			return;
		}

		$classes = 'stpb-wrap';
		if ( empty( $s['show_progress'] ) ) {
			$classes .= ' no-progress';
		}

		echo '<div class="' . esc_attr( $classes ) . '">';
		echo '  <button type="button" class="stpb-btn" aria-label="Back to top">';
		echo '    <i class="' . esc_attr( $s['icon_class'] ) . '" aria-hidden="true"></i>';
		echo '    <span class="screen-reader-text">Back to top</span>';
		echo '    <svg class="stpb-ring" viewBox="0 0 120 120" role="presentation" aria-hidden="true">';
		echo '      <circle class="track" cx="60" cy="60" r="52"></circle>';
		echo '      <circle class="progress" cx="60" cy="60" r="52"></circle>';
		echo '    </svg>';
		echo '  </button>';
		echo '</div>';
	}
}

new STPB_Plugin();
