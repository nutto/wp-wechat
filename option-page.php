<?php
$wx = new WP_Wechat();

if($_POST) {
    switch($_POST['wechat_action']) {
        case 'update_menu':
            $menu_json = sanitize_text_field(wp_unslash($_POST['menu_json']));
            $create_menu_msg = $wx->createMenu(json_decode($menu_json));
            break;
        case 'update_access_token';
            $wx->getAccessToken();
            break;
        default:
            break;
    }
}

?>

<div class="wrap">
    <h2><?php _e('Wechat Settings', 'wp_wechat'); ?></h2>
    <form method="post" action="options.php">
        <?php settings_fields(WP_Wechat::$OPTION_GROUP); ?>
        <?php do_settings_sections('options-wechat'); ?>
        <?php submit_button(); ?>
    </form>

    <!-- TODO: 临时菜单配置 -->
    <h2>微信基本操作</h2>
    <h3>强制刷新access token</h3>
    <form method="post" action="">
        <input type="hidden" name="wechat_action" value="update_access_token"/>
        <input type="submit" class="button button-primary" value="刷新access token" />
    </form>
    <h3>微信菜单设置</h3>
    <div id="menu-field">
        <a href="javascript:;" class="plus-main-menu">+主菜单</a>
        <ul id="menu-block" >
            <?php
            $menus = $wx->getMenu();
            if(sizeof($menus) > 0) { ?>
                <?php foreach($menus as $item) { ?>
                    <li class="main-menu">
                        <a href="javascript:;" class="menu-item main-item"
                            <?php
                            $menu_type = !empty($item->sub_button) ? 'main_menu' : $item->type;
                            echo "data-type='$menu_type' data-name='$item->name' data-key='$item->key'
                            data-media-id='$item->media_id' data-url='$item->url' " ?> ><?php echo $item->name; ?>
                        </a>
                        <a href="javascript:;" class="plus-sub-menu">+子菜单</a>
                        <a href="javascript:;" class="min-menu min-main-menu">-</a>
                        <ul class="sub-menu-block">
                            <?php if(sizeof($item->sub_button) > 0) { // 子菜单?>
                                <?php foreach($item->sub_button as $sub_item) { ?>
                                    <li class="sub-menu">
                                        <span>*</span>
                                        <a href="javascript:;" class="menu-item sub-item"
                                            <?php
                                            $menu_type = !empty($sub_item->sub_button) ? 'main_menu' : $sub_item->type;
                                            echo "data-type='$menu_type' data-name='$sub_item->name' data-key='$sub_item->key'
                                            data-media-id='$sub_item->media_id' data-url='$sub_item->url' " ?> ><?php echo $sub_item->name; ?>
                                        </a>
                                        <a href="javascript:;" class="min-menu min-sub-menu">-</a>
                                    </li>
                                <?php } ?>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>
    </div>
    <ul id="menu-setting">
        <li>
            <label id="menu-type">
                <span class="hint">菜单类型</span>
                <select id="set-type" >
                    <option value="main_menu">母菜单</option>
                    <option value="click">点击按钮</option>
                    <option value="view">跳转按钮</option>
                    <option value="scancode_push">扫码按钮</option>
                    <option value="scancode_waitmsg">扫码弹框按钮</option>
                    <option value="pic_sysphoto">拍照按钮</option>
                    <option value="pic_photo_or_album">拍照或相册按钮</option>
                    <option value="pic_weixin">微信相册按钮</option>
                    <option value="location_select">地理位置按钮</option>
                    <option value="media_id">发送消息按钮</option>
                    <option value="view_limited">图文消息按钮</option>
                </select>
            </label>
        </li>
        <li>
            <label id="menu-name">
                <span class="hint">菜单名</span>
                <input type="text" id="set-name" />
            </label>
        </li>
        <li>
            <label id="menu-key">
                <span class="hint">菜单标识</span>
                <input type="text" id="set-key" />
            </label>
        </li>
        <li>
            <label id="menu-media-id">
                <span class="hint">菜单图文返回(media id)</span>
                <input type="text" id="set-media-id" />
            </label>
        </li>
        <li>
            <label id="menu-url">
                <span class="hint">菜单链接URL</span>
                <input type="text" id="set-url" />
            </label>
        </li>
    </ul>
    <p class="return-msg"><?php echo isset($create_menu_msg) ? $create_menu_msg->errmsg : '';?></p>
    <button id="menu-submit" class="button button-primary">保存菜单更改</button>
</div>

<script>
    jQuery(function($) {
        var $menu_item = $('.menu-item');
        var $set_type = $('#set-type');
        var $set_key = $('#set-key');
        var $set_name = $('#set-name');
        var $set_media_id = $('#set-media-id');
        var $set_url = $('#set-url');

        var $all_menu_label = $('#menu-setting').find('label');
        var $menu_name = $('#menu-name');
        var $menu_type = $('#menu-type');
        var $menu_key = $('#menu-key');
        var $menu_media_id = $('#menu-media-id');
        var $menu_url = $('#menu-url');

        var get_item_data = function($item) {
            var re = {};
            re['name'] = $item.data('name');
            re['type'] = $item.data('type');
            re['media_id'] = $item.data('media_id');
            re['key'] = $item.data('key');
            re['url'] = $item.data('url');
            return re;
        };

        var get_return_obj = function() {
            var $main_item = $('.main-item');
            var re = [];
            $main_item.each(function(index,element) {
                var item_data = get_item_data($(element));
                var $sub_items = $(element).closest('li').find('.sub-item');
                if($sub_items.length > 0) {
                    item_data['sub_button'] = [];
                    $sub_items.each(function(index, element) {
                        item_data['sub_button'].push(get_item_data($(element)));
                    });
                }
                re.push(item_data);
            });
            return re;
        };

        $(document).on('click', '#menu-submit', function() {
            save_active_item_data();
            simulate_form_post('', {menu_json: JSON.stringify(get_return_obj())});
        });

        var simulate_form_post = function(url, fields) {
            var $form = $('<form>', {
                action: url,
                method: 'post'
            });
            $.each(fields, function(key, val) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: key,
                    value: val
                }));
            });
            $form.append($('<input>', {
                type: 'hidden',
                name: 'wechat_action',
                value: 'update_menu'
            }));
            $form.submit();
        };

        var clean_data = function () {
            $all_menu_label.hide();
            $all_menu_label.find('input').val('');
        };

        var save_active_item_data = function() {
            var $old_active_item = $('.menu-item.active');
            $old_active_item.removeClass('active');
            // 保存修改信息
            $old_active_item.data('name', $set_name.val());
            $old_active_item.data('type', $set_type.val());
            $old_active_item.data('media_id', $set_media_id.val());
            $old_active_item.data('url', $set_url.val());
            $old_active_item.data('key', $set_key.val());

            $old_active_item.html( $old_active_item.data('name'));
        };

        var $refresh_label_block = function ($type) {
            switch($type) {
                case 'main_menu':
                    clean_data();
                    $menu_name.show();
                    $menu_type.show();
                    break;
                case 'view':
                    clean_data();
                    $menu_name.show();
                    $menu_type.show();
                    $menu_url.show();
                    break;
                case 'view_limited':
                case 'media_id':
                    clean_data();
                    $menu_name.show();
                    $menu_type.show();
                    $menu_media_id.show();
                    break;
                default :
                    clean_data();
                    $menu_name.show();
                    $menu_type.show();
                    $menu_key.show();
                    break
            }
        };
        $refresh_label_block($set_type.val());
        $(document).on('change', '#set-type', function() {
            $refresh_label_block($(this).val());
        });

        $(document).on('click', '#menu-block a', function() {
            save_active_item_data();
        });

        $(document).on('click', '.menu-item', function() {
            save_active_item_data();

            $refresh_label_block($(this).data('type'));
            $set_type.val($(this).data('type'));
            $set_key.val($(this).data('key'));
            $set_name.val($(this).data('name'));
            $set_url.val($(this).data('url'));
            $set_media_id.val($(this).data('media_id'));
            $(this).addClass('active');
        });

        $(document).on('click', '.plus-main-menu', function() {
            if($('.main-menu').length >= 3 ) {
                alert('主菜单不能超过三个');
                return;
            }
            var $li_container = $('<li>', {
                'class': 'main-menu'
            });
            var $main_item = $('<a>', {
                'class':  'menu-item main-item',
                'href': 'javascript:;',
                'data-type': 'click',
                'data-name': '新建菜单',
                'data-key': 'new',
                'data-media-id': '',
                'data-url': ''
            });
            $main_item.html($main_item.data('name'));
            var $plus_sub_menu = $('<a>', {
                'href': 'javascript:;',
                'class': 'plus-sub-menu'
            });

            var $min_menu = $('<a>', {
                'class':  'min-menu min-main-menu',
                'href': 'javascript:;'
            });
            $min_menu.html('-');

            $plus_sub_menu.html('+子菜单');
            var $sub_menu_block = $('<ul>', {
                'class': 'sub-menu-block'
            });
            $li_container.append($main_item);
            $li_container.append($plus_sub_menu);
            $li_container.append($min_menu);
            $li_container.append($sub_menu_block);
            $('#menu-block').append($li_container);
        });



        $(document).on('click', '.min-menu', function() {
            $(this).closest('li').remove();
        });


        $(document).on('click', '.plus-sub-menu', function() {
            if($(this).closest('.main-menu').find('.sub-menu').length >= 5 ) {
                alert('子菜单不能超过五个');
                return;
            }
            var $li_container = $('<li>', {
                'class': 'sub-menu'
            });
            var $prefix = $('<span>');
            $prefix.html('*');

            var $min_menu = $('<a>', {
                'class':  'min-menu min-sub-menu',
                'href': 'javascript:;'
            });
            $min_menu.html('-');

            var $main_item = $('<a>', {
                'class':  'menu-item sub-item',
                'href': 'javascript:;',
                'data-type': 'click',
                'data-name': '新建子菜单',
                'data-key': 'new',
                'data-media-id': '',
                'data-url': ''
            });
            $main_item.html($main_item.data('name'));
            $li_container.append($prefix);
            $li_container.append($main_item);
            $li_container.append($min_menu);
            $(this).closest('.main-menu').find('.sub-menu-block').append($li_container);
        })
    });
</script>