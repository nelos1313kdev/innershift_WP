<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Two_Factor_Authentication_Registration extends CloudSecureWP_Admin_Common {
	private $two_factor_authentication;
	/**
	 * 管理画面にレンダリングするキー
	 *
	 * @var string
	 */
	private $default_key;

	function __construct( array $info, CloudSecureWP_Two_Factor_Authentication $two_factor_authentication ) {
		parent::__construct( $info );
		$this->two_factor_authentication = $two_factor_authentication;
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->default_key = get_user_option( 'cloudsecurewp_two_factor_authentication_secret' );
		if ( ! empty( $_POST ) && check_admin_referer( $this->two_factor_authentication->get_feature_key() . '_csrf' ) ) {
			if ( ! empty( $_POST['key'] ) ) {
				$this->default_key = sanitize_text_field( $_POST['key'] );
			}
			if ( empty( $_POST['key'] ) ) {
				$this->errors[] = 'セットアップキーを保存できませんでした。<br />セットアップキーを生成してください。';

				return;
			}
			if ( empty( $_POST['google_authenticator_code'] ) ) {
				$this->errors[] = 'セットアップキーを保存できませんでした。<br />QRコードをGoogle Authenticator アプリケーションでスキャンし、認証コードを入力してください。';

				return;
			}
			$key                       = sanitize_text_field( $_POST['key'] );
			$google_authenticator_code = sanitize_text_field( $_POST['google_authenticator_code'] );
			if ( CloudSecureWP_Time_Based_One_Time_Password::verify_code( $key, $google_authenticator_code, 2 ) ) {
				update_user_option( get_current_user_id(), 'cloudsecurewp_two_factor_authentication_secret', $key );
				$this->messages[] = 'セットアップキーを保存しました。';
			} else {
				$this->errors[] = 'セットアップキーを保存できませんでした。<br />認証コードが間違っています。';
			}
		}
	}

	/**
	 * ディスクリプション
	 */
	protected function admin_description(): void {
		?>
		<div class="title-block mb-12">
			<h1 class="title-block-title">デバイス登録 - 2段階認証</h1>
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank"
																href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/two_factor_authentication.php">こちら</a>
			</p>
		</div>
		<div class="title-bottom-text">
			2段階認証機能をアクティブにするには、セットアップキーを生成し、<a class="title-block-link" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Google Authenticator</a> 　アプリケーションでQRコードを読み込んでください。<br />
			QRコードを読み込めない場合、<a class="title-block-link" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Google Authenticator</a> 　アプリケーションにセットアップキーを入力してください。
		</div>
		<?php
	}

	/**
	 * ページコンテンツ
	 */
	protected function page(): void {
		?>
		<form method="post">
			<div class="box">
				<div class="box-bottom">
					<div class="box-row flex-start">
						<div class="box-row-title not-label pt-12">
							<label for="key">セットアップキー</label>
						</div>
						<div class="box-row-content">
							<div class="flex">
								<input type="text" id="key" name="key"
										value="<?php echo esc_attr( $this->default_key ); ?>" maxlength="16"
										readonly/>
								<button id="generate_key" type="button" class="button button-large">
									セットアップキーを生成
								</button>
							</div>
							<div id="qrcode_container" hidden>
								<div id="qrcode"></div>
								<div class="flex onetime-password-container">
									<label for="google_authenticator_code">認証コード:</label>
									<input type="text" id="google_authenticator_code" name="google_authenticator_code"
											maxlength="6"/>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->two_factor_authentication->get_feature_key() ); ?>
				<p id="guide_message" hidden>アプリケーションに表示された6桁の認証コードを入力し、「変更を保存」ボタンをクリックしてください。</p>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<style>
			#generate_key {
				margin-left: 16px;
			}

			#qrcode {
				margin: 32px 32px 16px;
			}

			.onetime-password-container {
				margin-left: -20px;
			}
		</style>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
				type="text/javascript"></script>
		<script type="text/javascript">
			function quintetCount(buff) {
				const quintets = Math.floor(buff.length / 5);
				return buff.length % 5 === 0 ? quintets : quintets + 1;
			}

			function bufferToBase32(plain) {
				let i = 0;
				let j = 0;
				let shiftIndex = 0;
				let digit = 0;
				const charTable = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
				const encoded = new Array(quintetCount(plain) * 8);

				/* byte by byte は、quintet by quintet ほどきれいではありませんが、テストは少し速くなります。 再訪する必要があります。 */
				while (i < plain.length) {
					const current = plain[i];

					if (shiftIndex > 3) {
						digit = current & (0xff >> shiftIndex);
						shiftIndex = (shiftIndex + 5) % 8;
						digit = (digit << shiftIndex) | ((i + 1 < plain.length) ?
							plain[i + 1] : 0) >> (8 - shiftIndex);
						i++;
					} else {
						digit = (current >> (8 - (shiftIndex + 5))) & 0x1f;
						shiftIndex = (shiftIndex + 5) % 8;
						if (shiftIndex === 0) {
							i++;
						}
					}

					encoded[j] = charTable[digit];
					j++;
				}

				for (i = j; i < encoded.length; i++) {
					encoded[i] = '=';
				}

				return encoded.join('');
			}

			function generateKey() {
				return bufferToBase32(crypto.getRandomValues(new Uint8Array(10)))
			}

			const qrcode = new QRCode('qrcode', {correctLevel: QRCode.CorrectLevel.M})

			function showQRIfExists() {
				const key = document.getElementById('key').value
				document.getElementById('qrcode_container').hidden = !key
				document.getElementById('guide_message').hidden = !key
				if (!key) return
				const name = document.querySelector('.display-name').textContent

				// https://github.com/google/google-authenticator/wiki/Key-Uri-Format
				qrcode.makeCode(`otpauth://totp/${encodeURIComponent(name)}?secret=${key}&issuer=${location.hostname}`)

				document.getElementById('google_authenticator_code').focus()
			}

			showQRIfExists()

			document.getElementById('generate_key').addEventListener('click', () => {
				document.getElementById('key').value = generateKey()
				showQRIfExists()
			})
		</script>
		<?php
	}
}
