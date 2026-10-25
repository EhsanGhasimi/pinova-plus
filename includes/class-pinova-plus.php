<?php
namespace PinovaPlus;

defined( 'ABSPATH' ) || exit;

class Core {

    private static ?Core $instance = null;
    private array $options = [];

    public static function instance(): Core {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // خواندن تنظیمات ذخیره شده با اطمینان از خروجی آرایه
        $raw_options = get_option( 'pinova_plus', [] );
        if ( is_array( $raw_options ) ) {
            $this->options = $raw_options;
        } elseif ( is_string( $raw_options ) && ! empty( $raw_options ) ) {
            $unserialized = maybe_unserialize( $raw_options );
            $this->options = is_array( $unserialized ) ? $unserialized : [];
        } else {
            $this->options = [];
        }

        // اتصال تب و فیلدهای تنظیمات به پنل تنظیمات پینوا
        $this->init_settings_tab();

        // اجرای فیچرها بر اساس وضعیت فعال/غیرفعال بودن در تنظیمات
        $this->init_custom_login_bypass();
        $this->init_auto_fake_email();
        $this->init_sync_guest_orders_on_login();
    }

    /**
     * دریافت مقدار یک تنظیم خاص
     */
    public function get_setting( string $key, $default = '' ) {
        return $this->options[ $key ] ?? $default;
    }

    /**
     * افزودن تب «پینوا پلاس» به صفحه تنظیمات پینوا
     */
    private function init_settings_tab(): void {
        // ۱. اضافه کردن تب در هدر تنظیمات پینوا (در کنار همگانی، پیامک و...)
        add_filter( 'pinova/settings_sections', function( array $sections ) {
            $sections[] = [
                'id'    => 'pinova_plus',
                'title' => '⚡ پینوا پلاس (Webinew)',
            ];
            return $sections;
        } );

        // ۲. اضافه کردن فیلدهای تنظیمات برای تب پینوا پلاس
        add_filter( 'pinova/settings_fields', function( array $fields ) {
            $fields['pinova_plus'] = [
                [
                    'id'      => 'enable_custom_login_bypass',
                    'label'   => 'استثنا کردن صفحه ورود مدیریت (وبینیو)',
                    'desc'    => 'با فعال‌سازی این گزینه، در آدرس سفارشی لاگین سایت، فرم نام‌کاربری/رمز پیش‌فرض وردپرس باز می‌شود و به پینوا هدایت نمی‌گردد.',
                    'type'    => 'checkbox',
                    'default' => 'on',
                ],
                [
                    'id'          => 'custom_login_slugs',
                    'label'       => 'کلمات کلیدی یا اسلاگ آدرس اختصاصی',
                    'desc'        => 'کلمات یا مسیرهای لاگین اختصاصی را با کاما جدا کنید (پیش‌فرض: w-develop, webinew-login)',
                    'type'        => 'text',
                    'placeholder' => 'w-develop, webinew-login',
                    'default'     => 'w-develop, webinew-login',
                ],
                [
                    'id'      => 'enable_auto_fake_email',
                    'label'   => 'تولید ایمیل سیستمی خودکار برای ووکامرس',
                    'desc'    => 'برای کاربرانی که فقط با شماره موبایل ثبت‌نام می‌کنند، یک ایمیل استاندارد با ساختار شماره@دامنه ساخته می‌شود تا ووکامرس به آنها دسترسی کامل بدهد.',
                    'type'    => 'checkbox',
                    'default' => 'on',
                ],
                [
                    'id'      => 'enable_sync_guest_orders',
                    'label'   => 'سینک خودکار سفارشات ثبت شده با شماره موبایل',
                    'desc'    => 'اگر کاربر قبلاً در حالت «ایجاد خودکار بدون احراز هویت» یا مهمان خریدی ثبت کرده باشد، هنگام اولین ورود با موبایل، تمام سفارشات قبلی‌اش خودکار به حساب کاربری او متصل و در داشبوردش نمایش داده می‌شوند.',
                    'type'    => 'checkbox',
                    'default' => 'on',
                ],
                [
                    'id'      => 'webinew_services_banner',
                    'label'   => 'توسعه‌یافته توسط تیم وبینیو (Webinew)',
                    'type'    => 'html',
                    'desc'    => '<div style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border: 1px solid #c4b5fd; border-radius: 12px; padding: 20px; margin-top: 10px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 12px;">
                            <div>
                                <h3 style="margin: 0 0 6px 0; color: #5b21b6; font-size: 16px; font-weight: bold;">آژانس دیجیتال مارکتینگ و طراحی وب وبینیو (Webinew)</h3>
                                <p style="margin: 0; color: #4c1d95; font-size: 13px;">طراحی و توسعه تخصصی توسط <strong>احسان قسیمی (Ehsan Ghasimi)</strong></p>
                            </div>
                            <div>
                                <a href="https://webinew.com/" target="_blank" style="background: #7c3aed; color: #fff; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block;">مشاهده وب‌سایت وبینیو &larr;</a>
                            </div>
                        </div>
                        <p style="margin: 0 0 12px 0; color: #374151; font-size: 13px; line-height: 1.8;">
                            آژانس دیجیتال مارکتینگ <strong>وبینیو</strong> مجری تخصصی راهکارهای رشد آنلاین، خدمات زیر را با بالاترین کیفیت و ضمانت عملکرد ارائه می‌دهد:
                        </p>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 15px;">
                            <a href="https://webinew.com/webdesign-in-mashhad/" target="_blank" style="background: #fff; border: 1px solid #ddd6fe; border-radius: 8px; padding: 10px 14px; text-decoration: none; color: #4c1d95; font-size: 13px; display: block;">
                                🌐 <strong>طراحی سایت در مشهد (اختصاصی)</strong><br><small style="color: #6b7280;">فروشگاهی، شرکتی و پرسرعت</small>
                            </a>
                            <a href="https://webinew.com/seo-in-mashhad/" target="_blank" style="background: #fff; border: 1px solid #ddd6fe; border-radius: 8px; padding: 10px 14px; text-decoration: none; color: #4c1d95; font-size: 13px; display: block;">
                                🚀 <strong>خدمات سئو حرفه‌ای (SEO)</strong><br><small style="color: #6b7280;">رسیدن به صفحه اول گوگل</small>
                            </a>
                            <a href="https://webinew.com/google-ads/" target="_blank" style="background: #fff; border: 1px solid #ddd6fe; border-radius: 8px; padding: 10px 14px; text-decoration: none; color: #4c1d95; font-size: 13px; display: block;">
                                🎯 <strong>تبلیغات گوگل ادز (Google Ads)</strong><br><small style="color: #6b7280;">جذب مشتریان آماده خرید</small>
                            </a>
                            <a href="https://webinew.com/wp-support/" target="_blank" style="background: #fff; border: 1px solid #ddd6fe; border-radius: 8px; padding: 10px 14px; text-decoration: none; color: #4c1d95; font-size: 13px; display: block;">
                                🛠️ <strong>پشتیبانی سایت وردپرسی</strong><br><small style="color: #6b7280;">امنیت، سرعت و نگهداری مداوم</small>
                            </a>
                        </div>
                        <div style="border-top: 1px dashed #c4b5fd; padding-top: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: 12px; color: #6b7280;">
                            <span>📞 مشاوره و سفارش پروژه: <a href="tel:09159677791" style="color: #7c3aed; font-weight: bold; text-decoration: none;">09159677791</a></span>
                            <span>صفحات مهم: <a href="https://webinew.com/webdesign-in-mashhad/" target="_blank" style="color: #7c3aed; text-decoration: none;">طراحی سایت در مشهد</a> | <a href="https://webinew.com/website-design-in-tehran/" target="_blank" style="color: #7c3aed; text-decoration: none;">طراحی سایت در تهران</a> | <a href="https://webinew.com/wp-melli-plugin/" target="_blank" style="color: #7c3aed; text-decoration: none;">افزونه WP-Melli</a></span>
                        </div>
                    </div>',
                ],
            ];
            return $fields;
        } );

        // ایجاد منوی فرعی مجزا در پیشخوان زیر منوی «پینوا» برای دسترسی فوق‌العاده سریع
        add_action( 'admin_menu', function() {
            add_submenu_page(
                'pinova',
                'پینوا پلاس',
                '⚡ پینوا پلاس',
                'manage_options',
                'admin.php?page=pinova&tab=pinova_plus'
            );
        }, 99 );
    }

    /**
     * ۱. جلوگیری از ریدایرکت پینوا روی آدرس‌های ورود اختصاصی مدیریت
     */
    private function init_custom_login_bypass(): void {
        add_action( 'init', function() {
            $is_enabled = $this->get_setting( 'enable_custom_login_bypass', 'on' );
            if ( $is_enabled !== 'on' ) {
                return;
            }

            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            $slugs_str = $this->get_setting( 'custom_login_slugs', 'w-develop, webinew-login' );
            $slugs = array_map( 'trim', explode( ',', $slugs_str ) );
            $slugs[] = 'w-develop';

            $is_admin_custom_login = false;
            foreach ( $slugs as $slug ) {
                if ( ! empty( $slug ) && str_contains( $request_uri, $slug ) ) {
                    $is_admin_custom_login = true;
                    break;
                }
            }

            if ( isset( $_GET['admin_mode'] ) ) {
                $is_admin_custom_login = true;
            }

            if ( $is_admin_custom_login ) {
                if ( class_exists( '\Pinova\Integrations\Wordpress\Load' ) ) {
                    $pinova_wp = \Pinova\Integrations\Wordpress\Load::instance();
                    remove_action( 'login_form_login', [ $pinova_wp, 'wp_login_to_pinova_login' ] );
                    remove_action( 'login_form_register', [ $pinova_wp, 'wp_login_to_pinova_login' ] );
                    remove_action( 'login_form_lostpassword', [ $pinova_wp, 'wp_login_to_pinova_login' ] );
                    remove_action( 'login_form_retrievepassword', [ $pinova_wp, 'wp_login_to_pinova_login' ] );
                }
            }
        }, 5 );
    }

    /**
     * ۲. تولید خودکار ایمیل سیستمی استاندارد برای کاربرانی که بدون ایمیل و فقط با موبایل ثبت‌نام می‌کنند.
     */
    private function init_auto_fake_email(): void {
        $is_enabled = $this->get_setting( 'enable_auto_fake_email', 'on' );
        if ( $is_enabled !== 'on' ) {
            return;
        }

        // هوک پیش از ذخیره کاربر در دیتابیس وردپرس
        add_filter( 'insert_user_meta', function( array $meta, \WP_User $user, bool $update, array $userdata = [] ) {
            if ( ! $update ) {
                if ( empty( $user->user_email ) ) {
                    $mobile = $user->user_login;

                    if ( ! empty( $mobile ) && ( preg_match( '/^0?9[0-9]{9}$/', $mobile ) || preg_match( '/^[0-9]{10,12}$/', $mobile ) ) ) {
                        $host = wp_parse_url( home_url(), PHP_URL_HOST );
                        $host = preg_replace( '/^www\./i', '', $host );

                        $clean_mobile = ltrim( $mobile, '+' );
                        $generated_email = sanitize_email( $clean_mobile . '@' . $host );

                        if ( is_email( $generated_email ) && ! email_exists( $generated_email ) ) {
                            $user->user_email = $generated_email;
                        }
                    }
                }
            }
            return $meta;
        }, 10, 4 );

        // اصلاح user_email در جدول wp_users
        add_filter( 'pre_user_email', function( $user_email ) {
            if ( empty( $user_email ) ) {
                $login = $_POST['mobile'] ?? $_POST['username'] ?? '';
                if ( ! empty( $login ) ) {
                    $clean_mobile = preg_replace( '/\D/', '', $login );
                    if ( strlen( $clean_mobile ) >= 10 ) {
                        $host = wp_parse_url( home_url(), PHP_URL_HOST );
                        $host = preg_replace( '/^www\./i', '', $host );
                        $candidate_email = sanitize_email( $clean_mobile . '@' . $host );
                        if ( is_email( $candidate_email ) && ! email_exists( $candidate_email ) ) {
                            return $candidate_email;
                        }
                    }
                }
            }
            return $user_email;
        } );

        // پس از ثبت‌نام کاربر در پینوا جهت اطمینان صددرصدی
        add_action( 'pinova/user_registered', function( int $user_id ) {
            $user = get_userdata( $user_id );
            if ( $user && empty( $user->user_email ) ) {
                $mobile = $user->user_login;
                $host = wp_parse_url( home_url(), PHP_URL_HOST );
                $host = preg_replace( '/^www\./i', '', $host );
                $fake_email = sanitize_email( $mobile . '@' . $host );

                if ( ! email_exists( $fake_email ) ) {
                    wp_update_user( [
                        'ID'         => $user_id,
                        'user_email' => $fake_email,
                    ] );
                }
            }
        }, 10, 1 );
    }

    /**
     * ۳. سینک خودکار سفارشات ووکامرس بر اساس شماره موبایل
     *    هنگامی که کاربر لاگین می‌کند، بررسی می‌شود آیا سفارشاتی در ووکامرس با این شماره موبایل
     *    به عنوان مهمان یا بدون انتساب به یوزر وجود دارد؟ اگر بله، فوراً به حساب کاربر متصل می‌شوند.
     */
    private function init_sync_guest_orders_on_login(): void {
        $sync_handler = function( int $user_id ) {
            $is_enabled = $this->get_setting( 'enable_sync_guest_orders', 'on' );
            if ( $is_enabled !== 'on' || ! function_exists( 'wc_get_orders' ) ) {
                return;
            }

            $user = get_userdata( $user_id );
            if ( ! $user ) {
                return;
            }

            // استخراج شماره موبایل کاربر
            $raw_mobile = get_user_meta( $user_id, 'billing_phone', true );
            if ( empty( $raw_mobile ) && class_exists( '\Pinova\Services\UserService' ) ) {
                $raw_mobile = \Pinova\Services\UserService::get_mobile( $user_id );
            }
            if ( empty( $raw_mobile ) ) {
                $raw_mobile = $user->user_login;
            }

            $clean_mobile = preg_replace( '/\D/', '', $raw_mobile );
            if ( strlen( $clean_mobile ) < 10 ) {
                return;
            }

            // فرمت‌های رایج شماره موبایل در ایران جهت جستجو (09..., 989..., 9...)
            $short_mobile = substr( $clean_mobile, -10 ); // 9123456789
            $possible_numbers = [
                '0' . $short_mobile,
                $short_mobile,
                '98' . $short_mobile,
                '+98' . $short_mobile,
            ];

            // همچنین اگر ایمیل کاربر در ووکامرس ثبت شده باشد
            $user_email = $user->user_email;

            // جستجوی سفارشات ثبت‌شده توسط مهمان (customer_id = 0) با شماره موبایل کاربر
            $order_ids = wc_get_orders( [
                'customer_id' => 0,
                'limit'       => 50,
                'return'      => 'ids',
            ] );

            if ( empty( $order_ids ) ) {
                return;
            }

            foreach ( $order_ids as $order_id ) {
                $order = wc_get_order( $order_id );
                if ( ! $order ) {
                    continue;
                }

                $order_phone = preg_replace( '/\D/', '', $order->get_billing_phone() );
                $order_email = $order->get_billing_email();

                $match = false;
                if ( ! empty( $order_phone ) ) {
                    $order_short = substr( $order_phone, -10 );
                    if ( $order_short === $short_mobile ) {
                        $match = true;
                    }
                }

                // تطبیق کمکی با ایمیل (اگر ایمیل غیر فیک بود)
                if ( ! $match && ! empty( $user_email ) && ! empty( $order_email ) && strtolower( $user_email ) === strtolower( $order_email ) ) {
                    $match = true;
                }

                if ( $match ) {
                    // متصل کردن سفارش به کاربر
                    $order->set_customer_id( $user_id );
                    $order->save();
                }
            }
        };

        // اتصال به رویداد لاگین و عضویت پینوا و ورود استاندارد وردپرس
        add_action( 'pinova/user_logged_in', $sync_handler, 30, 1 );
        add_action( 'pinova/user_registered', $sync_handler, 30, 1 );
        add_action( 'wp_login', function( $user_login, $user ) use ( $sync_handler ) {
            if ( isset( $user->ID ) ) {
                $sync_handler( $user->ID );
            }
        }, 30, 2 );
    }
}
