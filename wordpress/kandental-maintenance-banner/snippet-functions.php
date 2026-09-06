<?php
/**
 * BẢN RÚT GỌN – dán vào functions.php của child theme,
 * hoặc tạo snippet mới trong plugin WPCode / Code Snippets.
 *
 * Không có trang cấu hình: sửa trực tiếp trong mảng $cfg bên dưới.
 */

add_action( 'wp_body_open', function () {

	$cfg = array(
		'image'    => 'https://kandental.com/wp-content/uploads/2026/09/WEBSITE.png',
		'alt'      => 'Thông báo bảo trì website Kan Dental',
		'link'     => 'tel:0918196246',
		'end'      => '', // ví dụ '2026-09-10 08:00' — để trống nếu không đếm ngược
		'version'  => 'v1', // đổi giá trị này để banner hiện lại với khách đã đóng
	);

	if ( is_admin() ) {
		return;
	}
	if ( $cfg['end'] && current_time( 'timestamp' ) > strtotime( $cfg['end'] ) ) {
		return;
	}
	?>
	<style>
	#kd-maint{position:fixed;top:0;left:0;right:0;z-index:99990;background:#0F4C81;
		box-shadow:0 2px 14px rgba(0,0,0,.18);transform:translateY(-110%);transition:transform .35s ease}
	#kd-maint.on{transform:translateY(0)}
	#kd-maint img{display:block;width:100%;height:auto}
	#kd-maint button{position:absolute;top:8px;right:10px;width:32px;height:32px;border:0;border-radius:50%;
		background:rgba(0,0,0,.45);color:#fff;font-size:20px;line-height:1;cursor:pointer}
	</style>
	<div id="kd-maint" role="region" aria-label="Thông báo bảo trì website">
		<a href="<?php echo esc_url( $cfg['link'] ); ?>">
			<img src="<?php echo esc_url( $cfg['image'] ); ?>" alt="<?php echo esc_attr( $cfg['alt'] ); ?>">
		</a>
		<button type="button" aria-label="Đóng thông báo">&times;</button>
	</div>
	<script>
	(function(){
		var el=document.getElementById('kd-maint'),k='kd_maint_<?php echo esc_js( $cfg['version'] ); ?>';
		try{ if(localStorage.getItem(k)==='1'){el.remove();return;} }catch(e){}
		function fit(){ var b=document.getElementById('wpadminbar');
			el.style.top=(b?b.offsetHeight:0)+'px';
			document.body.style.paddingTop=el.offsetHeight+'px'; }
		el.querySelector('button').addEventListener('click',function(){
			try{ localStorage.setItem(k,'1'); }catch(e){}
			el.classList.remove('on'); document.body.style.paddingTop='';
			setTimeout(function(){ el.remove(); },350);
		});
		requestAnimationFrame(function(){ fit(); el.classList.add('on'); });
		addEventListener('resize',fit); addEventListener('load',fit);
	})();
	</script>
	<?php
}, 5 );
