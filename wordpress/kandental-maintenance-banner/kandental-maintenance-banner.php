<?php
/**
 * Plugin Name:       Kan Dental – Maintenance Banner
 * Plugin URI:        https://kandental.com/
 * Description:       Banner thông báo bảo trì website cho kandental.com. Hỗ trợ chế độ ảnh (dùng file thiết kế có sẵn) hoặc chế độ text (tiêu đề, mô tả, đếm ngược, nút CTA, nút đóng). Không phụ thuộc theme, tương thích cache.
 * Version:           1.0.0
 * Requires at least: 5.5
 * Requires PHP:      7.4
 * Author:            Kan Dental
 * License:           GPL-2.0-or-later
 * Text Domain:       kdmb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KDMB_VERSION', '1.0.0' );
define( 'KDMB_OPTION', 'kdmb_settings' );

/* -------------------------------------------------------------------------
 * 1. Cấu hình mặc định
 * ---------------------------------------------------------------------- */

function kdmb_defaults() {
	return array(
		'enabled'         => 1,
		'mode'            => 'image', // image | text | both
		'position'        => 'top',   // top | bottom
		'sticky'          => 1,
		'image_url'       => 'https://kandental.com/wp-content/uploads/2026/09/WEBSITE.png',
		'image_alt'       => 'Thông báo bảo trì website Kan Dental',
		'title'           => 'Website đang được bảo trì & nâng cấp',
		'message'         => 'Một số chức năng có thể tạm thời gián đoạn. Quý khách vui lòng liên hệ hotline để được hỗ trợ nhanh nhất.',
		'link_url'        => 'tel:0918196246',
		'link_text'       => 'Liên hệ hỗ trợ',
		'end_time'        => '', // Y-m-d\TH:i (giờ theo timezone của site)
		'show_countdown'  => 1,
		'auto_hide'       => 1,  // tự ẩn khi hết thời gian bảo trì
		'dismissible'     => 1,
		'hide_logged_in'  => 0,
		'bg_from'         => '#0F4C81',
		'bg_to'           => '#1B7FA8',
		'text_color'      => '#FFFFFF',
		'accent'          => '#F5B301',
	);
}

function kdmb_get_settings() {
	$saved = get_option( KDMB_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, kdmb_defaults() );
}

/* -------------------------------------------------------------------------
 * 2. Logic hiển thị
 * ---------------------------------------------------------------------- */

/**
 * Thời điểm kết thúc bảo trì, quy về timestamp UTC. 0 nếu không đặt.
 */
function kdmb_end_timestamp( $settings ) {
	if ( empty( $settings['end_time'] ) ) {
		return 0;
	}
	$raw = str_replace( 'T', ' ', $settings['end_time'] );
	try {
		$dt = new DateTime( $raw, wp_timezone() );
	} catch ( Exception $e ) {
		return 0;
	}
	return $dt->getTimestamp();
}

function kdmb_should_display( $settings ) {
	if ( empty( $settings['enabled'] ) ) {
		return false;
	}
	if ( ! empty( $settings['hide_logged_in'] ) && is_user_logged_in() ) {
		return false;
	}
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	// Server-side chỉ chặn khi auto_hide bật và đã quá hạn (JS xử lý phần realtime).
	$end = kdmb_end_timestamp( $settings );
	if ( ! empty( $settings['auto_hide'] ) && $end && time() > $end ) {
		return false;
	}
	return true;
}

/* -------------------------------------------------------------------------
 * 3. Render banner
 * ---------------------------------------------------------------------- */

function kdmb_render_banner( $force = false ) {
	static $rendered = false;
	if ( $rendered ) {
		return '';
	}

	$s = kdmb_get_settings();
	if ( ! $force && ! kdmb_should_display( $s ) ) {
		return '';
	}
	$rendered = true;

	$end       = kdmb_end_timestamp( $s );
	$mode      = in_array( $s['mode'], array( 'image', 'text', 'both' ), true ) ? $s['mode'] : 'image';
	$position  = ( 'bottom' === $s['position'] ) ? 'bottom' : 'top';
	$sticky    = ! empty( $s['sticky'] );
	// Đổi nội dung -> banner hiện lại với người đã đóng trước đó.
	$version   = substr( md5( wp_json_encode( $s ) ), 0, 8 );

	ob_start();
	?>
	<style id="kdmb-style">
		#kdmb-banner{
			--kdmb-from: <?php echo esc_attr( $s['bg_from'] ); ?>;
			--kdmb-to: <?php echo esc_attr( $s['bg_to'] ); ?>;
			--kdmb-text: <?php echo esc_attr( $s['text_color'] ); ?>;
			--kdmb-accent: <?php echo esc_attr( $s['accent'] ); ?>;
			position: <?php echo $sticky ? 'fixed' : 'relative'; ?>;
			<?php echo $sticky ? esc_html( $position ) . ':0;left:0;right:0;' : ''; ?>
			z-index: 99990;
			background: linear-gradient(90deg, var(--kdmb-from) 0%, var(--kdmb-to) 100%);
			color: var(--kdmb-text);
			font-family: inherit;
			line-height: 1.5;
			box-shadow: 0 2px 14px rgba(0,0,0,.18);
			transform: translateY(<?php echo 'top' === $position ? '-110%' : '110%'; ?>);
			transition: transform .35s ease;
		}
		#kdmb-banner.kdmb-visible{ transform: translateY(0); }
		#kdmb-banner .kdmb-inner{
			max-width:1200px; margin:0 auto; padding:12px 52px 12px 18px;
			display:flex; align-items:center; gap:16px; flex-wrap:wrap;
		}
		#kdmb-banner.kdmb-mode-image .kdmb-inner{ max-width:100%; padding:0 52px 0 0; }
		#kdmb-banner .kdmb-image{ display:block; width:100%; height:auto; }
		#kdmb-banner .kdmb-icon{ flex:0 0 auto; width:26px; height:26px; }
		#kdmb-banner .kdmb-icon svg{ width:100%; height:100%; display:block; fill:var(--kdmb-accent); }
		#kdmb-banner .kdmb-content{ flex:1 1 320px; min-width:0; }
		#kdmb-banner .kdmb-title{ margin:0; font-size:15px; font-weight:700; letter-spacing:.2px; color:var(--kdmb-text); }
		#kdmb-banner .kdmb-msg{ margin:2px 0 0; font-size:13.5px; opacity:.92; color:var(--kdmb-text); }
		#kdmb-banner .kdmb-msg a{ color:var(--kdmb-accent); text-decoration:underline; }
		#kdmb-banner .kdmb-countdown{ flex:0 0 auto; display:flex; gap:6px; font-variant-numeric:tabular-nums; }
		#kdmb-banner .kdmb-unit{
			background:rgba(255,255,255,.14); border-radius:8px; padding:5px 9px; text-align:center; min-width:46px;
		}
		#kdmb-banner .kdmb-num{ display:block; font-size:15px; font-weight:700; }
		#kdmb-banner .kdmb-lbl{ display:block; font-size:10px; text-transform:uppercase; opacity:.75; letter-spacing:.4px; }
		#kdmb-banner .kdmb-cta{
			flex:0 0 auto; background:var(--kdmb-accent); color:#111; font-weight:700; font-size:13.5px;
			padding:9px 18px; border-radius:999px; text-decoration:none; white-space:nowrap;
			transition:filter .2s ease;
		}
		#kdmb-banner .kdmb-cta:hover{ filter:brightness(1.08); color:#111; }
		#kdmb-banner .kdmb-close{
			position:absolute; top:50%; right:12px; transform:translateY(-50%);
			width:32px; height:32px; border:0; border-radius:50%; cursor:pointer;
			background:rgba(255,255,255,.16); color:var(--kdmb-text); font-size:20px; line-height:1;
			display:flex; align-items:center; justify-content:center; padding:0;
		}
		#kdmb-banner .kdmb-close:hover{ background:rgba(255,255,255,.3); }
		@media (max-width:782px){
			#kdmb-banner .kdmb-inner{ padding:10px 46px 10px 14px; gap:10px; }
			#kdmb-banner .kdmb-title{ font-size:14px; }
			#kdmb-banner .kdmb-msg{ font-size:12.5px; }
			#kdmb-banner .kdmb-cta{ width:100%; text-align:center; }
		}
		@media (prefers-reduced-motion: reduce){
			#kdmb-banner{ transition:none; }
		}
	</style>

	<div id="kdmb-banner"
		class="kdmb-mode-<?php echo esc_attr( $mode ); ?> kdmb-pos-<?php echo esc_attr( $position ); ?>"
		role="region"
		aria-label="Thông báo bảo trì website"
		data-version="<?php echo esc_attr( $version ); ?>"
		data-position="<?php echo esc_attr( $position ); ?>"
		data-sticky="<?php echo $sticky ? '1' : '0'; ?>"
		data-end="<?php echo $end ? esc_attr( $end ) : ''; ?>"
		data-autohide="<?php echo ! empty( $s['auto_hide'] ) ? '1' : '0'; ?>"
		data-dismissible="<?php echo ! empty( $s['dismissible'] ) ? '1' : '0'; ?>">

		<?php if ( 'text' !== $mode && ! empty( $s['image_url'] ) ) : ?>
			<?php if ( ! empty( $s['link_url'] ) ) : ?><a href="<?php echo esc_url( $s['link_url'] ); ?>"><?php endif; ?>
				<img class="kdmb-image" src="<?php echo esc_url( $s['image_url'] ); ?>" alt="<?php echo esc_attr( $s['image_alt'] ); ?>" loading="eager" decoding="async" />
			<?php if ( ! empty( $s['link_url'] ) ) : ?></a><?php endif; ?>
		<?php endif; ?>

		<?php if ( 'image' !== $mode ) : ?>
			<div class="kdmb-inner">
				<span class="kdmb-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24"><path d="M12 2 1 21h22L12 2Zm0 5.5 7.1 12.2H4.9L12 7.5ZM11 10v5h2v-5h-2Zm0 6.5V18h2v-1.5h-2Z"/></svg>
				</span>
				<div class="kdmb-content">
					<?php if ( ! empty( $s['title'] ) ) : ?>
						<p class="kdmb-title"><?php echo esc_html( $s['title'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $s['message'] ) ) : ?>
						<p class="kdmb-msg"><?php echo wp_kses_post( $s['message'] ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( $end && ! empty( $s['show_countdown'] ) ) : ?>
					<div class="kdmb-countdown" aria-live="off">
						<span class="kdmb-unit"><span class="kdmb-num" data-kdmb="d">--</span><span class="kdmb-lbl">Ngày</span></span>
						<span class="kdmb-unit"><span class="kdmb-num" data-kdmb="h">--</span><span class="kdmb-lbl">Giờ</span></span>
						<span class="kdmb-unit"><span class="kdmb-num" data-kdmb="m">--</span><span class="kdmb-lbl">Phút</span></span>
						<span class="kdmb-unit"><span class="kdmb-num" data-kdmb="s">--</span><span class="kdmb-lbl">Giây</span></span>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $s['link_url'] ) && ! empty( $s['link_text'] ) ) : ?>
					<a class="kdmb-cta" href="<?php echo esc_url( $s['link_url'] ); ?>"><?php echo esc_html( $s['link_text'] ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $s['dismissible'] ) ) : ?>
			<button type="button" class="kdmb-close" aria-label="Đóng thông báo bảo trì">&times;</button>
		<?php endif; ?>
	</div>

	<script id="kdmb-script">
	(function(){
		var el = document.getElementById('kdmb-banner');
		if (!el) { return; }

		var key         = 'kdmb_dismissed_' + el.dataset.version;
		var dismissible = el.dataset.dismissible === '1';
		var sticky      = el.dataset.sticky === '1';
		var position    = el.dataset.position;
		var end         = el.dataset.end ? parseInt(el.dataset.end, 10) * 1000 : 0;
		var autoHide    = el.dataset.autohide === '1';

		function store(k, v){ try { window.localStorage.setItem(k, v); } catch(e){} }
		function read(k){ try { return window.localStorage.getItem(k); } catch(e){ return null; } }

		if (dismissible && read(key) === '1') { el.remove(); return; }
		if (autoHide && end && Date.now() > end) { el.remove(); return; }

		// Đẩy nội dung trang xuống/lên để không che header hoặc footer.
		function offset(){
			if (!sticky) { return; }
			var h = el.offsetHeight;
			document.body.style[ position === 'top' ? 'paddingTop' : 'paddingBottom' ] = h + 'px';
			if (position === 'top') {
				var bar = document.getElementById('wpadminbar');
				el.style.top = bar ? bar.offsetHeight + 'px' : '0px';
			}
		}
		function clearOffset(){
			if (!sticky) { return; }
			document.body.style[ position === 'top' ? 'paddingTop' : 'paddingBottom' ] = '';
		}

		function close(){
			el.classList.remove('kdmb-visible');
			clearOffset();
			window.setTimeout(function(){ el.remove(); }, 350);
		}

		var btn = el.querySelector('.kdmb-close');
		if (btn) {
			btn.addEventListener('click', function(){
				store(key, '1');
				close();
			});
		}

		// Đếm ngược
		var nums = el.querySelectorAll('[data-kdmb]');
		if (end && nums.length) {
			var pad = function(n){ return (n < 10 ? '0' : '') + n; };
			var tick = function(){
				var diff = end - Date.now();
				if (diff <= 0) {
					if (autoHide) { close(); }
					diff = 0;
				}
				var s = Math.floor(diff / 1000);
				var map = {
					d: Math.floor(s / 86400),
					h: Math.floor(s % 86400 / 3600),
					m: Math.floor(s % 3600 / 60),
					s: s % 60
				};
				nums.forEach(function(n){ n.textContent = pad(map[n.dataset.kdmb]); });
			};
			tick();
			window.setInterval(tick, 1000);
		}

		window.requestAnimationFrame(function(){
			offset();
			el.classList.add('kdmb-visible');
		});
		window.addEventListener('resize', offset);
		window.addEventListener('load', offset);
	})();
	</script>
	<?php
	return ob_get_clean();
}

/**
 * Chèn banner: ưu tiên wp_body_open, fallback wp_footer với theme cũ.
 */
function kdmb_output() {
	echo kdmb_render_banner(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm render.
}
add_action( 'wp_body_open', 'kdmb_output', 5 );
add_action( 'wp_footer', 'kdmb_output', 99 );

/** Shortcode chèn thủ công vào bài viết / widget: [kandental_banner] */
function kdmb_shortcode() {
	return kdmb_render_banner( true );
}
add_shortcode( 'kandental_banner', 'kdmb_shortcode' );

/* -------------------------------------------------------------------------
 * 4. Trang cấu hình trong Admin
 * ---------------------------------------------------------------------- */

function kdmb_sanitize( $input ) {
	$d   = kdmb_defaults();
	$out = array();

	$out['enabled']        = empty( $input['enabled'] ) ? 0 : 1;
	$out['sticky']         = empty( $input['sticky'] ) ? 0 : 1;
	$out['show_countdown'] = empty( $input['show_countdown'] ) ? 0 : 1;
	$out['auto_hide']      = empty( $input['auto_hide'] ) ? 0 : 1;
	$out['dismissible']    = empty( $input['dismissible'] ) ? 0 : 1;
	$out['hide_logged_in'] = empty( $input['hide_logged_in'] ) ? 0 : 1;

	$mode         = isset( $input['mode'] ) ? $input['mode'] : $d['mode'];
	$out['mode']  = in_array( $mode, array( 'image', 'text', 'both' ), true ) ? $mode : $d['mode'];
	$pos          = isset( $input['position'] ) ? $input['position'] : $d['position'];
	$out['position'] = in_array( $pos, array( 'top', 'bottom' ), true ) ? $pos : $d['position'];

	$out['image_url'] = isset( $input['image_url'] ) ? esc_url_raw( trim( $input['image_url'] ) ) : '';
	$out['image_alt'] = isset( $input['image_alt'] ) ? sanitize_text_field( $input['image_alt'] ) : '';
	$out['title']     = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : '';
	$out['message']   = isset( $input['message'] ) ? wp_kses_post( $input['message'] ) : '';
	$out['link_text'] = isset( $input['link_text'] ) ? sanitize_text_field( $input['link_text'] ) : '';

	$link = isset( $input['link_url'] ) ? trim( $input['link_url'] ) : '';
	$out['link_url'] = $link ? esc_url_raw( $link, array( 'http', 'https', 'tel', 'mailto' ) ) : '';

	$end = isset( $input['end_time'] ) ? trim( $input['end_time'] ) : '';
	$out['end_time'] = preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $end ) ? $end : '';

	foreach ( array( 'bg_from', 'bg_to', 'text_color', 'accent' ) as $c ) {
		$val       = isset( $input[ $c ] ) ? sanitize_hex_color( $input[ $c ] ) : '';
		$out[ $c ] = $val ? $val : $d[ $c ];
	}

	return $out;
}

function kdmb_register_settings() {
	register_setting(
		'kdmb_group',
		KDMB_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kdmb_sanitize',
			'default'           => kdmb_defaults(),
		)
	);
}
add_action( 'admin_init', 'kdmb_register_settings' );

function kdmb_admin_menu() {
	add_options_page(
		'Banner bảo trì',
		'Banner bảo trì',
		'manage_options',
		'kdmb-settings',
		'kdmb_settings_page'
	);
}
add_action( 'admin_menu', 'kdmb_admin_menu' );

function kdmb_settings_link( $links ) {
	$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=kdmb-settings' ) ) . '">Cài đặt</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'kdmb_settings_link' );

function kdmb_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = kdmb_get_settings();
	$n = KDMB_OPTION;
	?>
	<div class="wrap">
		<h1>Banner bảo trì – Kan Dental</h1>
		<p>Banner hiển thị trên toàn site. Múi giờ đang dùng: <code><?php echo esc_html( wp_timezone_string() ); ?></code>.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'kdmb_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Kích hoạt banner</th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>> Hiển thị banner trên front-end</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[hide_logged_in]" value="1" <?php checked( $s['hide_logged_in'], 1 ); ?>> Ẩn với người dùng đã đăng nhập</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[dismissible]" value="1" <?php checked( $s['dismissible'], 1 ); ?>> Cho phép khách đóng banner</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[sticky]" value="1" <?php checked( $s['sticky'], 1 ); ?>> Ghim cố định khi cuộn trang</label>
					</td>
				</tr>
				<tr>
					<th scope="row">Chế độ hiển thị</th>
					<td>
						<select name="<?php echo esc_attr( $n ); ?>[mode]">
							<option value="image" <?php selected( $s['mode'], 'image' ); ?>>Ảnh thiết kế sẵn</option>
							<option value="text"  <?php selected( $s['mode'], 'text' ); ?>>Text (tiêu đề + mô tả + đếm ngược)</option>
							<option value="both"  <?php selected( $s['mode'], 'both' ); ?>>Ảnh + text</option>
						</select>
						<select name="<?php echo esc_attr( $n ); ?>[position]">
							<option value="top"    <?php selected( $s['position'], 'top' ); ?>>Đầu trang</option>
							<option value="bottom" <?php selected( $s['position'], 'bottom' ); ?>>Cuối trang</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="kdmb_image_url">URL ảnh banner</label></th>
					<td>
						<input type="url" class="large-text code" id="kdmb_image_url" name="<?php echo esc_attr( $n ); ?>[image_url]" value="<?php echo esc_attr( $s['image_url'] ); ?>">
						<p class="description">Ảnh nên rộng tối thiểu 1920px, dung lượng &lt; 300KB (nén WebP nếu được).</p>
						<input type="text" class="large-text" name="<?php echo esc_attr( $n ); ?>[image_alt]" value="<?php echo esc_attr( $s['image_alt'] ); ?>" placeholder="Alt text cho ảnh">
						<?php if ( $s['image_url'] ) : ?>
							<p><img src="<?php echo esc_url( $s['image_url'] ); ?>" alt="" style="max-width:600px;height:auto;border:1px solid #ccd0d4"></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="kdmb_title">Tiêu đề</label></th>
					<td><input type="text" class="large-text" id="kdmb_title" name="<?php echo esc_attr( $n ); ?>[title]" value="<?php echo esc_attr( $s['title'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="kdmb_message">Nội dung</label></th>
					<td><textarea class="large-text" rows="3" id="kdmb_message" name="<?php echo esc_attr( $n ); ?>[message]"><?php echo esc_textarea( $s['message'] ); ?></textarea>
					<p class="description">Cho phép thẻ HTML cơ bản (&lt;a&gt;, &lt;strong&gt;, &lt;br&gt;).</p></td>
				</tr>
				<tr>
					<th scope="row">Nút CTA</th>
					<td>
						<input type="text" name="<?php echo esc_attr( $n ); ?>[link_text]" value="<?php echo esc_attr( $s['link_text'] ); ?>" placeholder="Chữ trên nút">
						<input type="text" class="regular-text code" name="<?php echo esc_attr( $n ); ?>[link_url]" value="<?php echo esc_attr( $s['link_url'] ); ?>" placeholder="https://… hoặc tel:…">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="kdmb_end">Kết thúc bảo trì</label></th>
					<td>
						<input type="datetime-local" id="kdmb_end" name="<?php echo esc_attr( $n ); ?>[end_time]" value="<?php echo esc_attr( $s['end_time'] ); ?>">
						<p>
							<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[show_countdown]" value="1" <?php checked( $s['show_countdown'], 1 ); ?>> Hiện đồng hồ đếm ngược</label><br>
							<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[auto_hide]" value="1" <?php checked( $s['auto_hide'], 1 ); ?>> Tự ẩn banner khi hết thời gian</label>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Màu sắc</th>
					<td>
						<label>Nền trái <input type="color" name="<?php echo esc_attr( $n ); ?>[bg_from]" value="<?php echo esc_attr( $s['bg_from'] ); ?>"></label>
						<label>Nền phải <input type="color" name="<?php echo esc_attr( $n ); ?>[bg_to]" value="<?php echo esc_attr( $s['bg_to'] ); ?>"></label>
						<label>Chữ <input type="color" name="<?php echo esc_attr( $n ); ?>[text_color]" value="<?php echo esc_attr( $s['text_color'] ); ?>"></label>
						<label>Nhấn <input type="color" name="<?php echo esc_attr( $n ); ?>[accent]" value="<?php echo esc_attr( $s['accent'] ); ?>"></label>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Lưu cấu hình' ); ?>
		</form>
		<p><strong>Shortcode:</strong> <code>[kandental_banner]</code> để chèn banner vào một trang cụ thể.</p>
	</div>
	<?php
}
