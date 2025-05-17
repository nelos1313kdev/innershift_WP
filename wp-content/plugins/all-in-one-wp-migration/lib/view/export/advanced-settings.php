<?php
/**
 * Copyright (C) 2014-2025 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Attribution: This code is part of the All-in-One WP Migration plugin, developed by
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}
?>

<div class="ai1wm-field-set">
	<div class="ai1wm-accordion ai1wm-expandable">
		<h4>
			<i class="ai1wm-icon-arrow-right"></i>
			<?php esc_html_e( 'Advanced options', 'all-in-one-wp-migration' ); ?>
			<small><?php esc_html_e( '(click to expand)', 'all-in-one-wp-migration' ); ?></small>
		</h4>
		<ul>
			<?php if ( ai1wm_can_encrypt() ) : ?>
				<li class="ai1wm-encrypt-backups-container">
					<label for="ai1wm-encrypt-backups">
						<input type="checkbox" id="ai1wm-encrypt-backups" name="options[encrypt_backups]" />
						<?php esc_html_e( 'Encrypt this backup with a password', 'all-in-one-wp-migration' ); ?>
					</label>
					<div class="ai1wm-encrypt-backups-passwords-toggle">
						<div class="ai1wm-encrypt-backups-passwords-container">
							<div class="ai1wm-input-password-container">
								<input type="password" placeholder="<?php esc_attr_e( 'Enter a password', 'all-in-one-wp-migration' ); ?>" name="options[encrypt_password]" id="ai1wm-backup-encrypt-password">
								<a href="#ai1wm-backup-encrypt-password" class="ai1wm-toggle-password-visibility ai1wm-icon-eye-blocked"></a>
								<div class="ai1wm-error-message"><?php esc_html_e( 'A password is required', 'all-in-one-wp-migration' ); ?></div>
							</div>
							<div class="ai1wm-input-password-container">
								<input type="password" name="options[encrypt_password_confirmation]" placeholder="<?php esc_attr_e( 'Repeat the password', 'all-in-one-wp-migration' ); ?>" id="ai1wm-backup-encrypt-password-confirmation">
								<a href="#ai1wm-backup-encrypt-password-confirmation" class="ai1wm-toggle-password-visibility ai1wm-icon-eye-blocked"></a>
								<div class="ai1wm-error-message"><?php esc_html_e( 'The passwords do not match', 'all-in-one-wp-migration' ); ?></div>
							</div>
						</div>
					</div>
				</li>
			<?php else : ?>
				<li class="ai1wm-encrypt-backups-container-disabled">
					<input type="checkbox" id="ai1wm-encrypt-backups" name="options[encrypt_backups]" disabled />
					<?php esc_html_e( 'Password-protect and encrypt backups', 'all-in-one-wp-migration' ); ?>
					<a href="https://help.servmask.com/knowledgebase/unable-to-encrypt-and-decrypt-backups/" target="_blank"><span class="ai1wm-icon-help"></span></a>
				</li>
			<?php endif; ?>
			<li>
				<label for="ai1wm-no-spam-comments">
					<input type="checkbox" id="ai1wm-no-spam-comments" name="options[no_spam_comments]" />
					<?php esc_html_e( 'Exclude spam comments', 'all-in-one-wp-migration' ); ?>
				</label>
			</li>
			<li>
				<label for="ai1wm-no-post-revisions">
					<input type="checkbox" id="ai1wm-no-post-revisions" name="options[no_post_revisions]" />
					<?php esc_html_e( 'Exclude post revisions', 'all-in-one-wp-migration' ); ?>
				</label>
			</li>
			<li>
				<label for="ai1wm-no-media">
					<input type="checkbox" id="ai1wm-no-media" name="options[no_media]" />
					<?php esc_html_e( 'Exclude media library (files)', 'all-in-one-wp-migration' ); ?>
				</label>
			</li>
			<li>
				<label for="ai1wm-no-themes">
					<input type="checkbox" id="ai1wm-no-themes" name="options[no_themes]" />
					<?php esc_html_e( 'Exclude themes (files)', 'all-in-one-wp-migration' ); ?>
				</label>
			</li>

			<?php do_action( 'ai1wm_export_inactive_themes' ); ?>

			<li>
				<label for="ai1wm-no-muplugins">
					<input type="checkbox" id="ai1wm-no-muplugins" name="options[no_muplugins]" />
					<?php esc_html_e( 'Exclude must-use plugins (files)', 'all-in-one-wp-migration' ); ?>
				</label>
			</li>

			<li>
				<label for="ai1wm-no-plugins">
					<input type="checkbox" id="ai1wm-no-plugins" name="options[no_plugins]" />
					<?php esc_html_e( 'Exclude plugins (files)', 'all-in-one-wp-migration' ); ?>
				</label>
			</li>

			<?php do_action( 'ai1wm_export_inactive_plugins' ); ?>

			<?php do_action( 'ai1wm_export_cache_files' ); ?>

			<li>
				<label for="ai1wm-no-database">
					<input type="checkbox" id="ai1wm-no-database" name="options[no_database]" />
					<?php esc_html_e( 'Exclude database (sql)', 'all-in-one-wp-migration' ); ?>
				</label>
			</li>
			<li>
				<label for="ai1wm-no-email-replace">
					<input type="checkbox" id="ai1wm-no-email-replace" name="options[no_email_replace]" />
					<?php
					echo wp_kses(
						__( 'Do <strong>not</strong> replace email domain (sql)', 'all-in-one-wp-migration' ),
						ai1wm_allowed_html_tags()
					);
					?>
				</label>
			</li>

			<?php do_action( 'ai1wm_export_advanced_settings' ); ?>

			<?php do_action( 'ai1wm_export_exclude_db_tables' ); ?>
		</ul>
	</div>
</div>
