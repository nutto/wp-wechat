<style>
    .ec-wechat-wrap { background-color: #fff; border-radius: 5px; padding: 32px; }
    .ec-wechat-wrap h2 { font-weight: bold; margin-bottom: 24px; border-bottom: 1px #999 solid; }
    .ec-wechat-wrap tr { line-height: 32px; }
</style>
<div class="wrap ec-wechat-wrap">
    <h2>Easecloud Wechat Plugin Option</h2>
    <form method="post" action="options.php">
        <?php settings_fields( 'ec-wechat-plugin-settings-group' ); ?>
        <?php do_settings_sections( 'ec-wechat-plugin-settings-group' ); ?>
        <table>
            <tr valign="top">
                <th scope="row">APP ID</th>
                <td><input type="text" name="wx_app_id" value="<?php echo esc_attr( get_option('wx_app_id') ); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">APP Secret</th>
                <td><input type="text" name="wx_app_secret" value="<?php echo esc_attr( get_option('wx_app_secret') ); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Wechat ID</th>
                <td><input type="text" name="wx_self_id" value="<?php echo esc_attr( get_option('wx_self_id') ); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Token</th>
                <td><input type="text" name="wx_token" value="<?php echo esc_attr( get_option('wx_token') ); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Encoding AES Key</th>
                <td><input type="text" name="wx_encoding_AES_key" value="<?php echo esc_attr( get_option('wx_encoding_AES_key') ); ?>" /></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>