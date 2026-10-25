<?php
/**
 * Plugin Name: پینوا پلاس (Pinova Plus)
 * Plugin URI: https://webinew.com
 * Description: افزودنی قدرتمند و تکمیلی برای افزونه ورود و ثبت‌نام پیامکی پینوا (تولید خودکار ایمیل سیستمی استاندارد برای ووکامرس، استثنا کردن صفحه ورود مدیریت و لاگین سفارشی، سینک خودکار سفارشات ثبت شده با شماره موبایل و خدمات پشتیبانی تخصصی وبینیو).
 * Version: 1.0.4
 * Author: احسان قسیمی (Ehsan Ghasimi) - وبینیو (Webinew)
 * Author URI: https://webinew.com
 * License: GPLv2 or later
 * Requires PHP: 8.0
 */

defined( 'ABSPATH' ) || exit;

define( 'PINOVA_PLUS_VERSION', '1.0.4' );
define( 'PINOVA_PLUS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PINOVA_PLUS_URL', plugin_dir_url( __FILE__ ) );
define( 'PINOVA_PLUS_FILE', __FILE__ );

// بارگذاری فایل اصلی افزونه
require_once PINOVA_PLUS_DIR . 'includes/class-pinova-plus.php';

/**
 * افزودن دکمه «تنظیمات» در لیست افزونه‌های وردپرس (زیر نام پینوا پلاس)
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( array $actions ) {
    $settings_url = admin_url( 'admin.php?page=pinova#pinova_plus' );
    $custom_links = [
        'settings' => sprintf(
            '<a href="%s" style="font-weight: bold; color: #7c3aed;">%s</a>',
            esc_url( $settings_url ),
            __( 'تنظیمات پینوا پلاس', 'pinova-plus' )
        ),
        'webinew'  => sprintf(
            '<a href="%s" target="_blank" style="color: #0284c7; font-weight: 500;">%s</a>',
            esc_url( 'https://webinew.com' ),
            __( 'وب‌سایت وبینیو', 'pinova-plus' )
        ),
    ];
    return array_merge( $custom_links, $actions );
} );

/**
 * افزودن پیوندهای پشتیبانی و خدمات وبینیو در بخش اطلاعات افزونه در صفحه افزونه‌ها
 */
add_filter( 'plugin_row_meta', function( array $links, string $file ) {
    if ( $file === plugin_basename( __FILE__ ) ) {
        $links[] = '<a href="https://webinew.com/webdesign-in-mashhad/" target="_blank" style="color:#7c3aed;">' . __( 'طراحی سایت وبینیو', 'pinova-plus' ) . '</a>';
        $links[] = '<a href="https://webinew.com/seo-in-mashhad/" target="_blank" style="color:#7c3aed;">' . __( 'خدمات سئو وبینیو', 'pinova-plus' ) . '</a>';
        $links[] = '<a href="https://github.com/EhsanGhasimi/pinova-plus" target="_blank" style="color:#2563eb;">' . __( 'گیت‌هاب پینوا پلاس', 'pinova-plus' ) . '</a>';
    }
    return $links;
}, 10, 2 );

/**
 * راه‌اندازی پس از بارگذاری افزونه‌ها
 */
add_action( 'plugins_loaded', function() {
    \PinovaPlus\Core::instance();
} );
