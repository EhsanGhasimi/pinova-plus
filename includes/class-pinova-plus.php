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
        $this->init_dev_mode();
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
                    'default' => '1',
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
                    'default' => '1',
                ],
                [
                    'id'      => 'enable_sync_guest_orders',
                    'label'   => 'سینک خودکار سفارشات ثبت شده با شماره موبایل',
                    'desc'    => 'اگر کاربر قبلاً در حالت «ایجاد خودکار بدون احراز هویت» یا مهمان خریدی ثبت کرده باشد، هنگام اولین ورود با موبایل، تمام سفارشات قبلی‌اش خودکار به حساب کاربری او متصل و در داشبوردش نمایش داده می‌شوند.',
                    'type'    => 'checkbox',
                    'default' => '1',
                ],
                [
                    'id'      => 'enable_dev_mode',
                    'label'   => 'حالت توسعه و دیباگ (تست بدون پنل پیامک)',
                    'desc'    => 'ویژه زمان طراحی و دولوپ سایت! با فعال‌سازی این گزینه نیازی به اتصال پنل پیامک نیست؛ کد تایید به صورت خودکار در صفحه لاگین نمایش داده می‌شود و می‌توانید بدون پیامک لاگین را کامل تست کنید.',
                    'type'    => 'checkbox',
                    'default' => '0',
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
                'admin.php?page=pinova#pinova_plus'
            );
        }, 99 );

        // ثبت بخش تنظیمات در لیست مجاز options.php وردپرس با ذخیره‌سازی صحیح 1 و 0
        add_action( 'admin_init', function() {
            register_setting( 'pinova_plus', 'pinova_plus', [
                'type'              => 'array',
                'sanitize_callback' => function( $input ) {
                    if ( ! is_array( $input ) ) {
                        return [];
                    }
                    return [
                        'enable_custom_login_bypass' => ( isset( $input['enable_custom_login_bypass'] ) && (string) $input['enable_custom_login_bypass'] === '1' ) ? '1' : '0',
                        'custom_login_slugs'        => sanitize_text_field( $input['custom_login_slugs'] ?? 'w-develop, webinew-login' ),
                        'enable_auto_fake_email'     => ( isset( $input['enable_auto_fake_email'] ) && (string) $input['enable_auto_fake_email'] === '1' ) ? '1' : '0',
                        'enable_sync_guest_orders'   => ( isset( $input['enable_sync_guest_orders'] ) && (string) $input['enable_sync_guest_orders'] === '1' ) ? '1' : '0',
                        'enable_dev_mode'            => ( isset( $input['enable_dev_mode'] ) && (string) $input['enable_dev_mode'] === '1' ) ? '1' : '0',
                    ];
                },
            ] );
        } );

        // استایل کمکی برای حذف تداخل کلیک، حذف پرش، و هماهنگی کامل چک‌باکس‌ها
        add_action( 'admin_head', function() {
            $screen = get_current_screen();
            if ( $screen && str_contains( $screen->id, 'pinova' ) ) {
                echo '<style>
                    #pinova_plus .group, .group {
                        animation: none !important;
                    }
                    #pinova_plus fieldset label {
                        display: inline-flex !important;
                        align-items: center !important;
                        gap: 8px !important;
                        cursor: pointer !important;
                        user-select: none !important;
                    }
                    #pinova_plus input[type="checkbox"] {
                        appearance: checkbox !important;
                        -webkit-appearance: checkbox !important;
                        width: 18px !important;
                        height: 18px !important;
                        min-width: 18px !important;
                        margin: 0 !important;
                        display: inline-block !important;
                        position: relative !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                        cursor: pointer !important;
                        box-shadow: none !important;
                        border: 1px solid #c3c4c7 !important;
                        border-radius: 4px !important;
                        background: #fff !important;
                        transition: none !important;
                    }
                    #pinova_plus input[type="checkbox"]:checked {
                        background-color: #2271b1 !important;
                        border-color: #2271b1 !important;
                    }
                    #pinova_plus input[type="checkbox"]:checked::before {
                        content: url("data:image/svg+xml;utf8,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20viewBox%3D%270%200%2020%2020%27%3E%3Cpath%20d%3D%27M14.83%204.89l1.34.94-9.9%2014.13L1.5%2014.5l1.34-1.34%203.74%203.74%208.25-12.01z%27%20fill%3D%27%23fff%27%2F%3E%3C%2Fsvg%3E") !important;
                        display: block !important;
                        width: 14px !important;
                        height: 14px !important;
                        margin: 1px !important;
                    }
                </style>';
            }
        } );
    }

    /**
     * ۱. جلوگیری از ریدایرکت پینوا روی آدرس‌های ورود اختصاصی مدیریت
     */
    private function init_custom_login_bypass(): void {
        add_action( 'init', function() {
            $is_enabled = $this->get_setting( 'enable_custom_login_bypass', '1' );
            if ( $is_enabled !== '1' && $is_enabled !== 'on' ) {
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
        $is_enabled = $this->get_setting( 'enable_auto_fake_email', '1' );
        if ( $is_enabled !== '1' && $is_enabled !== 'on' ) {
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
            $is_enabled = $this->get_setting( 'enable_sync_guest_orders', '1' );
            if ( ( $is_enabled !== '1' && $is_enabled !== 'on' ) || ! function_exists( 'wc_get_orders' ) ) {
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

            // اجرای بررسی فقط یکبار برای هر کاربر جهت جلوگیری از هرگونه کندی سایت
            if ( get_user_meta( $user_id, '_pinova_plus_orders_synced', true ) ) {
                return;
            }

            // فرمت‌های ۱۰ رقمی شماره موبایل
            $short_mobile = substr( $clean_mobile, -10 );

            global $wpdb;
            // کوئری مستقیم، سبک و سریع فقط برای سفارشات با customer_id = 0
            $matched_order_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                INNER JOIN {$wpdb->postmeta} pm_customer ON p.ID = pm_customer.post_id
                WHERE p.post_type = 'shop_order'
                AND pm_customer.meta_key = '_customer_user' AND pm_customer.meta_value = '0'
                AND pm.meta_key = '_billing_phone' AND pm.meta_value LIKE %s
                LIMIT 20",
                '%' . $wpdb->esc_like( $short_mobile )
            ) );

            if ( ! empty( $matched_order_ids ) ) {
                foreach ( $matched_order_ids as $oid ) {
                    $order = wc_get_order( (int) $oid );
                    if ( $order ) {
                        $order->set_customer_id( $user_id );
                        $order->save();
                    }
                }
            }

            // علامت‌گذاری انجام موفق سینک برای این کاربر تا کوئری تکرار نشود
            update_user_meta( $user_id, '_pinova_plus_orders_synced', 1 );
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

    /**
     * ۴. حالت تست و دیباگ (توسعه و تست بدون نیاز به پنل پیامک):
     *    در صورتی که تیک فعال باشد، ارسال پیامک را کاملاً بای‌پاس کرده و یک کد تایید تستی
     *    (مثلاً 1234) را در جدول پینوا ثبت و معتبر می‌کند و در پنجره فرانت‌اند نمایش و خودکار پر می‌نماید.
     */
    private function init_dev_mode(): void {
        $is_enabled = $this->get_setting( 'enable_dev_mode', '0' );
        if ( $is_enabled !== '1' && $is_enabled !== 'on' ) {
            return;
        }

        // ۱. رهگیری و بای‌پاس کامل وب‌سرویس پینوا در حالت احراز هویت و ورود OTP
        add_action( 'rest_api_init', function() {
            // روت اختصاصی بای‌پاس احراز هویت
            register_rest_route( 'pinova/user', '/authenticate', [
                'methods'             => 'POST',
                'callback'            => [ $this, 'dev_authenticate_handler' ],
                'permission_callback' => '__return_true',
            ], true ); // آرگومان چهارم true بازنویسی قطعی روت اصلی پینوا است!

            // روت اختصاصی بای‌پاس تایید کد OTP
            register_rest_route( 'pinova/user', '/login/otp', [
                'methods'             => 'POST',
                'callback'            => [ $this, 'dev_login_otp_handler' ],
                'permission_callback' => '__return_true',
            ], true );
        }, 999 );

        // ۲. نمایش باکس شیک و خودکار کد تایید در فرانت‌اند
        add_action( 'wp_footer', [ $this, 'render_dev_mode_script' ] );
        add_action( 'admin_footer', [ $this, 'render_dev_mode_script' ] );
        add_action( 'login_footer', [ $this, 'render_dev_mode_script' ] );
    }

    /**
     * کنترل‌کننده اختصاصی ارسال کد در حالت تست و دیباگ
     */
    public function dev_authenticate_handler( \WP_REST_Request $request ) {
        $identifier = $request->get_param( 'identifier' );
        if ( empty( $identifier ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => 'لطفاً شماره تلفن همراه یا ایمیل را وارد کنید.',
                'data'    => [],
            ], 200 );
        }

        $clean_id = is_object( $identifier ) && method_exists( $identifier, 'get_value' ) ? $identifier->get_value() : (string) $identifier;
        $clean_id = sanitize_text_field( $clean_id );

        // یافتن یا ایجاد کاربر در صورت نیاز
        $user_id = email_exists( $clean_id );
        if ( ! $user_id ) {
            $user_id = username_exists( $clean_id );
        }
        if ( ! $user_id && class_exists( '\Pinova\Services\UserService' ) && class_exists( '\Pinova\Objects\Identifier' ) ) {
            try {
                $user_id = \Pinova\Services\UserService::match( new \Pinova\Objects\Identifier( $clean_id ) );
            } catch ( \Throwable $e ) {}
        }

        $code_len = 4;
        if ( class_exists( '\Pinova\Services\OTPService' ) ) {
            $code_len = \Pinova\Services\OTPService::code_length();
        }
        $test_code = $code_len == 5 ? '12345' : ( $code_len == 6 ? '123456' : '1234' );

        // ثبت کد در جدول pinova_otp برای سازگاری کامل
        global $wpdb;
        $otp_table = $wpdb->prefix . 'pinova_otp';
        $now = current_time( 'mysql' );
        $expires = date( 'Y-m-d H:i:s', strtotime( '+5 minutes' ) );

        $wpdb->insert( $otp_table, [
            'user_id'     => $user_id ?: null,
            'identifier'  => $clean_id,
            'code'        => wp_hash_password( $test_code ),
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'attempts'    => 0,
            'type'        => $user_id ? 'login' : 'register',
            'channels'    => json_encode( [ 'sms' => true ] ),
            'expires_at'  => $expires,
            'verified_at' => null,
        ] );
        $otp_id = $wpdb->insert_id;

        $jwt = '';
        if ( class_exists( '\Pinova\Helpers\JWT' ) ) {
            $jwt = \Pinova\Helpers\JWT::encode( [ 'otp_id' => $otp_id ], 300 );
        }

        return new \WP_REST_Response( [
            'success' => true,
            'message' => '⚡ حالت تست و توسعه وبینیو فعال است. کد تایید شبیه‌سازی شد: ' . $test_code,
            'data'    => [
                'jwt'          => $jwt,
                'has_account'  => (bool) $user_id,
                'has_password' => false,
                'login_method' => 'otp',
                'ttl'          => 120,
                'dev_code'     => $test_code,
            ],
        ], 200 );
    }

    /**
     * کنترل‌کننده اختصاصی تایید کد و ورود در حالت تست و دیباگ
     */
    public function dev_login_otp_handler( \WP_REST_Request $request ) {
        $jwt  = (string) $request->get_param( 'jwt' );
        $code = (string) $request->get_param( 'code' );

        global $wpdb;
        $otp_table = $wpdb->prefix . 'pinova_otp';

        $otp_row = null;
        if ( ! empty( $jwt ) && class_exists( '\Pinova\Helpers\JWT' ) ) {
            try {
                $payload = \Pinova\Helpers\JWT::decode( $jwt );
                if ( ! empty( $payload['otp_id'] ) ) {
                    $otp_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$otp_table} WHERE id = %d", $payload['otp_id'] ) );
                }
            } catch ( \Throwable $e ) {}
        }

        if ( ! $otp_row ) {
            $otp_row = $wpdb->get_row( "SELECT * FROM {$otp_table} ORDER BY id DESC LIMIT 1" );
        }

        if ( ! $otp_row ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => 'اطلاعات نشست معتبر نیست. لطفاً مجدداً شماره را وارد کنید.',
            ], 200 );
        }

        // اعتبارسنجی کد
        $is_valid = wp_check_password( $code, $otp_row->code ) || in_array( $code, [ '1234', '12345', '123456' ] );
        if ( ! $is_valid ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => 'کد تایید وارد شده اشتباه است.',
            ], 200 );
        }

        // علامت‌گذاری به عنوان تایید شده
        $wpdb->update( $otp_table, [ 'verified_at' => current_time( 'mysql' ) ], [ 'id' => $otp_row->id ] );

        // یافتن یا ساخت کاربر
        $identifier = $otp_row->identifier;
        $user = get_user_by( 'login', $identifier );
        if ( ! $user && is_email( $identifier ) ) {
            $user = get_user_by( 'email', $identifier );
        }

        if ( ! $user ) {
            $random_password = wp_generate_password( 12, false );
            $user_id = wp_create_user( $identifier, $random_password, '' );
            if ( is_wp_error( $user_id ) ) {
                return new \WP_REST_Response( [
                    'success' => false,
                    'message' => $user_id->get_error_message(),
                ], 200 );
            }
            $user = get_user_by( 'id', $user_id );
            do_action( 'pinova/user_registered', $user_id );
        }

        // لاگین کردن کاربر در وردپرس
        wp_clear_auth_cookie();
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );
        do_action( 'wp_login', $user->user_login, $user );
        do_action( 'pinova/user_logged_in', $user->ID );

        $redirect = wp_get_referer() ?: home_url();

        return new \WP_REST_Response( [
            'success' => true,
            'message' => 'ورود با موفقیت انجام شد (حالت تست وبینیو).',
            'data'    => [
                'redirect' => $redirect,
            ],
        ], 200 );
    }

    /**
     * اسکریپت فرانت‌اند جهت نمایش کادر کد تایید دولوپ در صفحه
     */
    public function render_dev_mode_script(): void {
        $is_enabled = $this->get_setting( 'enable_dev_mode', '0' );
        if ( $is_enabled !== '1' && $is_enabled !== 'on' ) {
            return;
        }

        $code_len = 4;
        if ( class_exists( '\Pinova\Services\OTPService' ) ) {
            $code_len = \Pinova\Services\OTPService::code_length();
        }
        $test_code = $code_len == 5 ? '12345' : ( $code_len == 6 ? '123456' : '1234' );
        ?>
        <script type="text/javascript">
            (function() {
                const defaultTestCode = "<?php echo esc_js( $test_code ); ?>";

                const origFetch = window.fetch;
                if (origFetch) {
                    window.fetch = async function(...args) {
                        const response = await origFetch.apply(this, args);
                        const url = args[0] || '';
                        if (typeof url === 'string' && url.indexOf('pinova/user/authenticate') !== -1) {
                            const clone = response.clone();
                            clone.json().then(data => {
                                if (data && data.success) {
                                    const code = data.data && data.data.dev_code ? data.data.dev_code : defaultTestCode;
                                    showDevOtpNotification(code);
                                }
                            }).catch(() => {});
                        }
                        return response;
                    };
                }

                function showDevOtpNotification(code) {
                    let container = document.getElementById('pinova-plus-dev-badge');
                    if (!container) {
                        container = document.createElement('div');
                        container.id = 'pinova-plus-dev-badge';
                        container.style.cssText = 'position:fixed;bottom:25px;right:25px;z-index:9999999;background:#1e1b4b;color:#fff;padding:16px 20px;border-radius:12px;border:2px solid #818cf8;box-shadow:0 10px 30px rgba(0,0,0,0.4);font-family:tahoma,sans-serif;direction:rtl;max-width:320px;animation:pinovaSlideIn 0.3s ease;';
                        document.body.appendChild(container);
                    }
                    container.innerHTML = `
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;">
                            <strong style="color:#a5b4fc;font-size:13px;">⚡ حالت تست و دیباگ (وبینیو)</strong>
                            <button onclick="this.parentElement.parentElement.remove()" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:18px;line-height:1;">&times;</button>
                        </div>
                        <p style="margin:0 0 8px 0;font-size:12px;color:#cbd5e1;line-height:1.6;">
                            پیامک شبیه‌سازی شد! برای ورود از کد تایید زیر استفاده کنید:
                        </p>
                        <div style="background:#0f172a;padding:8px 12px;border-radius:8px;text-align:center;font-size:22px;letter-spacing:6px;font-weight:bold;color:#38bdf8;user-select:all;cursor:pointer;" title="کلیک برای کپی" onclick="navigator.clipboard && navigator.clipboard.writeText(this.innerText.trim());">
                            ${code}
                        </div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:6px;text-align:center;">کد معتبر تست: <b>${code}</b> (کلیک کنید کپی شود)</div>
                    `;

                    // پر کردن خودکار فیلد کد تایید پینوا در صفحه
                    setTimeout(() => {
                        const codeInput = document.querySelector('input[autocomplete="one-time-code"], input[pinova-model*="code"]');
                        if (codeInput) {
                            codeInput.value = code;
                            codeInput.dispatchEvent(new Event('input', { bubbles: true }));
                            codeInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }, 400);
                }
            })();
        </script>
        <?php
    }
}


